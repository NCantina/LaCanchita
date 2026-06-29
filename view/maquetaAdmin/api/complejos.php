<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

// Solo SuperAdmin (1) y Dueño (2) gestionan predios.
require_perfil(2);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function e($link,$v){ return mysqli_real_escape_string($link, trim($v??'')); }

// Agregar columna Instagram si no existe
mysqli_query($link, "ALTER TABLE complejo ADD COLUMN IF NOT EXISTS COMPLEJO_INSTAGRAM VARCHAR(150) NULL DEFAULT NULL AFTER COMPLEJO_EMAIL");

// Modo solo-lectura por mora: bloquear escritura del panel.
if (in_array($action, ['crear','editar','toggle','eliminar'], true)) assert_tenant_activo($link);

switch($action) {

// ── LISTAR ──────────────────────────────────────────────────────────────
case 'listar':
    $scope = tenant_where(tenant_complejo_ids($link), 'c.COMPLEJO_ID');
    $sql = "
        SELECT
            c.COMPLEJO_ID,
            c.COMPLEJO_NOMBRE,
            c.COMPLEJO_DIRECCION,
            c.COMPLEJO_TELEFONO,
            c.COMPLEJO_EMAIL,
            c.COMPLEJO_INSTAGRAM,
            c.COMPLEJO_DESCRIPCION,
            c.ACTIVO,
            tc.TIPO_COMPLEJO_NOMBRE,
            tc.TIPO_COMPLEJO_ICONO,
            l.LOCALIDAD_NOMBRE,
            pa.PARTIDO_NOMBRE,
            pr.PROVINCIA_NOMBRE,
            c.USUARIOS_ID,
            du.USUARIOS_NOMBRE   AS DUENO_NOMBRE,
            du.USUARIOS_APELLIDO AS DUENO_APELLIDO,
            c.TIPO_COMPLEJO_ID,
            c.LOCALIDAD_ID,
            (SELECT COUNT(*) FROM cancha ca WHERE ca.COMPLEJO_ID=c.COMPLEJO_ID AND ca.ACTIVO=1) AS TOTAL_CANCHAS,
            (SELECT GROUP_CONCAT(tc2.TIPO_CANCHA_NOMBRE ORDER BY tc2.TIPO_CANCHA_NOMBRE SEPARATOR '||')
             FROM complejo_actividad ca2
             JOIN tipo_cancha tc2 ON tc2.TIPO_CANCHA_ID=ca2.TIPO_CANCHA_ID
             WHERE ca2.COMPLEJO_ID=c.COMPLEJO_ID AND ca2.ACTIVO=1) AS ACTIVIDADES
        FROM complejo c
        LEFT JOIN tipo_complejo tc ON tc.TIPO_COMPLEJO_ID = c.TIPO_COMPLEJO_ID
        LEFT JOIN localidad l  ON l.LOCALIDAD_ID   = c.LOCALIDAD_ID
        LEFT JOIN partido pa   ON pa.PARTIDO_ID    = l.PARTIDO_ID
        LEFT JOIN provincia pr ON pr.PROVINCIA_ID  = pa.PROVINCIA_ID
        LEFT JOIN usuarios du  ON du.USUARIOS_ID   = c.USUARIOS_ID
        WHERE $scope
        ORDER BY c.ACTIVO DESC, c.COMPLEJO_NOMBRE ASC
    ";
    $q = mysqli_query($link,$sql);
    $rows=[];
    while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

// ── OBTENER UNO (para editar) ───────────────────────────────────────────
case 'get':
    $id = (int)($_GET['id']??0);
    if(!$id) resp(false,'ID inválido.');
    assert_complejo($link, $id);

    $c = mysqli_fetch_assoc(mysqli_query($link,"SELECT * FROM complejo WHERE COMPLEJO_ID=$id"));
    if(!$c) resp(false,'No encontrado.');

    // Actividades
    $acts=[];
    $qa=mysqli_query($link,"SELECT TIPO_CANCHA_ID,ACTIVIDAD_DESTACADA FROM complejo_actividad WHERE COMPLEJO_ID=$id AND ACTIVO=1");
    while($r=mysqli_fetch_assoc($qa)) $acts[]=$r;
    $c['actividades']=$acts;

    // Horarios
    $hors=[];
    $qh=mysqli_query($link,"SELECT DIA_ID,ATENCION_HORA_APERTURA,ATENCION_HORA_CIERRE FROM complejo_horario_atencion WHERE COMPLEJO_ID=$id AND ACTIVO=1");
    while($r=mysqli_fetch_assoc($qh)) $hors[$r['DIA_ID']]=$r;
    $c['horarios']=$hors;

    resp(true,'',$c);

// ── CREAR ───────────────────────────────────────────────────────────────
case 'crear':
    $nombre   = e($link,$_POST['nombre']??'');
    $dir      = e($link,$_POST['direccion']??'');
    $tel      = e($link,$_POST['telefono']??'');
    $email    = e($link,$_POST['email']??'');
    $ig       = e($link, ltrim($_POST['instagram']??'', '@'));
    $desc     = e($link,$_POST['descripcion']??'');
    $loc      = (int)($_POST['localidad_id']??0);
    $tipo     = ($_POST['tipo_complejo_id']??'') !== '' ? (int)$_POST['tipo_complejo_id'] : null;
    $acts     = json_decode($_POST['actividades']??'[]', true);
    $horarios = json_decode($_POST['horarios']??'[]', true);

    if(!$nombre) resp(false,'El nombre es obligatorio.');
    if(!$dir)    resp(false,'La dirección es obligatoria.');
    if(!$loc)    resp(false,'Seleccioná una localidad.');

    // Dueño propietario del predio:
    //  - Dueño (perfil 2): siempre él mismo (no puede crear para otro).
    //  - SuperAdmin (perfil 1): el dueño indicado en el form, o NULL si no eligió.
    if (is_dueno()) {
        $owner = current_uid();
    } else {
        $owner = ($_POST['usuarios_id'] ?? '') !== '' ? (int)$_POST['usuarios_id'] : null;
        if ($owner) {
            $ownerCheck = mysqli_fetch_assoc(mysqli_query($link,
                "SELECT USUARIOS_ID FROM usuarios WHERE USUARIOS_ID=$owner AND PERFIL_ID=2"));
            if (!$ownerCheck) resp(false,'El usuario indicado no existe o no es dueño.');
        }
    }
    $ownerSql = $owner ? $owner : 'NULL';

    mysqli_begin_transaction($link);
    try {
        $tipoSql = $tipo ? $tipo : 'NULL';
        $igSql   = $ig ? "'$ig'" : 'NULL';
        mysqli_query($link,
            "INSERT INTO complejo (COMPLEJO_NOMBRE,COMPLEJO_DIRECCION,COMPLEJO_TELEFONO,COMPLEJO_EMAIL,
             COMPLEJO_INSTAGRAM,COMPLEJO_DESCRIPCION,LOCALIDAD_ID,TIPO_COMPLEJO_ID,USUARIOS_ID,ACTIVO)
             VALUES ('$nombre','$dir','$tel','$email',$igSql,'$desc',$loc,$tipoSql,$ownerSql,1)"
        );
        $cid = mysqli_insert_id($link);

        // Actividades
        foreach($acts as $act){
            $tcid = (int)($act['tipo_cancha_id']??0);
            $dest = (int)($act['destacada']??0);
            if($tcid) mysqli_query($link,
                "INSERT IGNORE INTO complejo_actividad (COMPLEJO_ID,TIPO_CANCHA_ID,ACTIVIDAD_DESTACADA,ACTIVO)
                 VALUES ($cid,$tcid,$dest,1)"
            );
        }

        // Horarios
        foreach($horarios as $h){
            $dia  = (int)($h['dia_id']??0);
            $ap   = e($link,$h['apertura']??'');
            $ci   = e($link,$h['cierre']??'');
            if($dia && $ap && $ci) mysqli_query($link,
                "INSERT INTO complejo_horario_atencion
                 (COMPLEJO_ID,DIA_ID,ATENCION_HORA_APERTURA,ATENCION_HORA_CIERRE,ACTIVO)
                 VALUES ($cid,$dia,'$ap','$ci',1)"
            );
        }

        mysqli_commit($link);
        resp(true,'Complejo creado correctamente.',['id'=>$cid]);
    } catch(Exception $ex){
        mysqli_rollback($link);
        resp(false,'Error al crear: '.$ex->getMessage());
    }

// ── EDITAR ───────────────────────────────────────────────────────────────
case 'editar':
    $id       = (int)($_POST['id']??0);
    $nombre   = e($link,$_POST['nombre']??'');
    $dir      = e($link,$_POST['direccion']??'');
    $tel      = e($link,$_POST['telefono']??'');
    $email    = e($link,$_POST['email']??'');
    $ig       = e($link, ltrim($_POST['instagram']??'', '@'));
    $desc     = e($link,$_POST['descripcion']??'');
    $loc      = (int)($_POST['localidad_id']??0);
    $tipo     = ($_POST['tipo_complejo_id']??'') !== '' ? (int)$_POST['tipo_complejo_id'] : null;
    $acts     = json_decode($_POST['actividades']??'[]', true);
    $horarios = json_decode($_POST['horarios']??'[]', true);

    if(!$id)     resp(false,'ID inválido.');
    if(!$nombre) resp(false,'El nombre es obligatorio.');
    if(!$dir)    resp(false,'La dirección es obligatoria.');
    if(!$loc)    resp(false,'Seleccioná una localidad.');
    assert_complejo($link, $id);

    mysqli_begin_transaction($link);
    try {
        $tipoSql = $tipo ? $tipo : 'NULL';
        $igSql   = $ig ? "'$ig'" : 'NULL';
        mysqli_query($link,
            "UPDATE complejo SET
             COMPLEJO_NOMBRE='$nombre', COMPLEJO_DIRECCION='$dir',
             COMPLEJO_TELEFONO='$tel', COMPLEJO_EMAIL='$email',
             COMPLEJO_INSTAGRAM=$igSql,
             COMPLEJO_DESCRIPCION='$desc', LOCALIDAD_ID=$loc,
             TIPO_COMPLEJO_ID=$tipoSql
             WHERE COMPLEJO_ID=$id"
        );

        // Reemplazar actividades
        mysqli_query($link,"DELETE FROM complejo_actividad WHERE COMPLEJO_ID=$id");
        foreach($acts as $act){
            $tcid = (int)($act['tipo_cancha_id']??0);
            $dest = (int)($act['destacada']??0);
            if($tcid) mysqli_query($link,
                "INSERT IGNORE INTO complejo_actividad (COMPLEJO_ID,TIPO_CANCHA_ID,ACTIVIDAD_DESTACADA,ACTIVO)
                 VALUES ($id,$tcid,$dest,1)"
            );
        }

        // Reemplazar horarios
        mysqli_query($link,"DELETE FROM complejo_horario_atencion WHERE COMPLEJO_ID=$id");
        foreach($horarios as $h){
            $dia = (int)($h['dia_id']??0);
            $ap  = e($link,$h['apertura']??'');
            $ci  = e($link,$h['cierre']??'');
            if($dia && $ap && $ci) mysqli_query($link,
                "INSERT INTO complejo_horario_atencion
                 (COMPLEJO_ID,DIA_ID,ATENCION_HORA_APERTURA,ATENCION_HORA_CIERRE,ACTIVO)
                 VALUES ($id,$dia,'$ap','$ci',1)"
            );
        }

        mysqli_commit($link);
        resp(true,'Complejo actualizado correctamente.');
    } catch(Exception $ex){
        mysqli_rollback($link);
        resp(false,'Error al actualizar: '.$ex->getMessage());
    }

// ── TOGGLE ───────────────────────────────────────────────────────────────
case 'toggle':
    $id = (int)($_POST['id']??0);
    if(!$id) resp(false,'ID inválido.');
    assert_complejo($link, $id);
    $cur = mysqli_fetch_assoc(mysqli_query($link,"SELECT ACTIVO FROM complejo WHERE COMPLEJO_ID=$id"));
    if(!$cur) resp(false,'No encontrado.');
    $nuevo = $cur['ACTIVO'] ? 0 : 1;
    mysqli_query($link,"UPDATE complejo SET ACTIVO=$nuevo WHERE COMPLEJO_ID=$id");
    resp(true,$nuevo?'Complejo activado.':'Complejo desactivado.',['activo'=>$nuevo]);

// ── SELECTS (para el modal) ───────────────────────────────────────────────
case 'selects':
    $localidades  = [];
    $tipos_comp   = [];
    $tipos_cancha = [];
    $ql=mysqli_query($link,"SELECT LOCALIDAD_ID,LOCALIDAD_NOMBRE FROM localidad WHERE ACTIVO=1 ORDER BY LOCALIDAD_NOMBRE");
    while($r=mysqli_fetch_assoc($ql)) $localidades[]=$r;
    $qt=mysqli_query($link,"SELECT TIPO_COMPLEJO_ID,TIPO_COMPLEJO_NOMBRE,TIPO_COMPLEJO_ICONO FROM tipo_complejo WHERE ACTIVO=1 ORDER BY TIPO_COMPLEJO_NOMBRE");
    while($r=mysqli_fetch_assoc($qt)) $tipos_comp[]=$r;
    $qtc=mysqli_query($link,"SELECT TIPO_CANCHA_ID,TIPO_CANCHA_NOMBRE,TIPO_CANCHA_ICONO FROM tipo_cancha WHERE ACTIVO=1 ORDER BY TIPO_CANCHA_NOMBRE");
    while($r=mysqli_fetch_assoc($qtc)) $tipos_cancha[]=$r;

    // Solo el SuperAdmin elige a qué dueño pertenece el predio.
    $duenos = [];
    if (is_superadmin()) {
        $qd=mysqli_query($link,
            "SELECT USUARIOS_ID, USUARIOS_NOMBRE, USUARIOS_APELLIDO, USUARIOS_EMAIL
             FROM usuarios WHERE PERFIL_ID=2 AND ACTIVO=1
             ORDER BY USUARIOS_NOMBRE, USUARIOS_APELLIDO");
        while($r=mysqli_fetch_assoc($qd)) $duenos[]=$r;
    }
    resp(true,'',compact('localidades','tipos_comp','tipos_cancha','duenos'));

default:
    resp(false,'Acción no reconocida.');
}
