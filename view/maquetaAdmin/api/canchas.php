<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

// SuperAdmin (1) y Dueño (2) gestionan canchas. Staff opera reservas (otro API).
require_perfil(2);

$action = $_GET['action'] ?? $_POST['action'] ?? '';

function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function e($link,$v){ return mysqli_real_escape_string($link, trim($v??'')); }

// Modo solo-lectura por mora: bloquear escritura del panel.
if (in_array($action, ['crear','editar','toggle','eliminar'], true)) assert_tenant_activo($link);

switch($action) {

// ── LISTAR ──────────────────────────────────────────────────────────────
case 'listar':
    $sql = "
        SELECT
            ca.CANCHA_ID,
            ca.CANCHA_NOMBRE,
            ca.CANCHA_DESCRIPCION,
            ca.ACTIVO,
            ca.COMPLEJO_ID,
            ca.TIPO_CANCHA_ID,
            co.COMPLEJO_NOMBRE,
            tc.TIPO_CANCHA_NOMBRE,
            tc.TIPO_CANCHA_ICONO,
            (SELECT COUNT(*) FROM franja_horaria fh WHERE fh.CANCHA_ID=ca.CANCHA_ID AND fh.ACTIVO=1) AS TOTAL_FRANJAS,
            (SELECT COUNT(*) FROM cancha_encargado ce WHERE ce.CANCHA_ID=ca.CANCHA_ID AND ce.ACTIVO=1) AS TOTAL_ENCARGADOS,
            (SELECT COUNT(*) FROM reserva r WHERE r.CANCHA_ID=ca.CANCHA_ID AND r.RESERVA_FECHA=CURDATE() AND r.ACTIVO=1) AS RESERVAS_HOY
        FROM cancha ca
        JOIN complejo    co ON co.COMPLEJO_ID    = ca.COMPLEJO_ID
        JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = ca.TIPO_CANCHA_ID
        WHERE " . tenant_where(tenant_complejo_ids($link), 'ca.COMPLEJO_ID') . "
        ORDER BY ca.ACTIVO DESC, co.COMPLEJO_NOMBRE ASC, ca.CANCHA_NOMBRE ASC
    ";
    $q = mysqli_query($link,$sql);
    $rows=[];
    while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

// ── OBTENER UNO ────────────────────────────────────────────────────────
case 'get':
    $id = (int)($_GET['id']??0);
    if(!$id) resp(false,'ID inválido.');
    assert_cancha($link, $id);
    $c = mysqli_fetch_assoc(mysqli_query($link,"SELECT * FROM cancha WHERE CANCHA_ID=$id"));
    if(!$c) resp(false,'No encontrado.');
    // Encargados asignados
    $encs = [];
    $qe = mysqli_query($link,
        "SELECT ce.USUARIOS_ID FROM cancha_encargado ce
         WHERE ce.CANCHA_ID=$id AND ce.ACTIVO=1"
    );
    while($r=mysqli_fetch_assoc($qe)) $encs[]=(int)$r['USUARIOS_ID'];
    $c['encargados']=$encs;
    resp(true,'',$c);

// ── SELECTS ────────────────────────────────────────────────────────────
case 'selects':
    $complejos = [];
    $tipos     = [];
    $encargados= [];
    // Complejos: solo los del tenant.
    $scope = tenant_where(tenant_complejo_ids($link), 'COMPLEJO_ID');
    $qco=mysqli_query($link,"SELECT COMPLEJO_ID,COMPLEJO_NOMBRE FROM complejo WHERE ACTIVO=1 AND $scope ORDER BY COMPLEJO_NOMBRE");
    while($r=mysqli_fetch_assoc($qco)) $complejos[]=$r;

    $qtc=mysqli_query($link,"SELECT TIPO_CANCHA_ID,TIPO_CANCHA_NOMBRE,TIPO_CANCHA_ICONO FROM tipo_cancha WHERE ACTIVO=1 ORDER BY TIPO_CANCHA_NOMBRE");
    while($r=mysqli_fetch_assoc($qtc)) $tipos[]=$r;

    // Encargados/Empleados: solo el staff del dueño actual (SuperAdmin ve todos).
    $duenoId = current_dueno_id($link);
    $staffWhere = is_superadmin() ? '1=1' : ($duenoId ? "u.DUENO_ID=$duenoId" : '1=0');
    $qen=mysqli_query($link,
        "SELECT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO, p.PERFIL_NOMBRE
         FROM usuarios u JOIN perfil p ON u.PERFIL_ID=p.PERFIL_ID
         WHERE u.PERFIL_ID IN (3,4) AND u.ACTIVO=1 AND $staffWhere
         ORDER BY u.USUARIOS_NOMBRE"
    );
    while($r=mysqli_fetch_assoc($qen)) $encargados[]=$r;
    resp(true,'',compact('complejos','tipos','encargados'));

// ── CREAR ──────────────────────────────────────────────────────────────
case 'crear':
    $nombre   = e($link,$_POST['nombre']??'');
    $desc     = e($link,$_POST['descripcion']??'');
    $complejo = (int)($_POST['complejo_id']??0);
    $tipo     = (int)($_POST['tipo_cancha_id']??0);
    $encs     = json_decode($_POST['encargados']??'[]', true);

    if(!$nombre)   resp(false,'El nombre es obligatorio.');
    if(!$complejo) resp(false,'Seleccioná un complejo.');
    if(!$tipo)     resp(false,'Seleccioná un tipo de cancha.');
    assert_complejo($link, $complejo);

    mysqli_begin_transaction($link);
    try {
        mysqli_query($link,
            "INSERT INTO cancha (COMPLEJO_ID,TIPO_CANCHA_ID,CANCHA_NOMBRE,CANCHA_DESCRIPCION,ACTIVO)
             VALUES ($complejo,$tipo,'$nombre','$desc',1)"
        );
        $cid = mysqli_insert_id($link);
        foreach($encs as $uid){
            $uid=(int)$uid;
            if($uid) mysqli_query($link,
                "INSERT IGNORE INTO cancha_encargado (CANCHA_ID,USUARIOS_ID,ACTIVO) VALUES ($cid,$uid,1)"
            );
        }
        mysqli_commit($link);
        resp(true,'Cancha creada correctamente.',['id'=>$cid]);
    } catch(Exception $ex){ mysqli_rollback($link); resp(false,'Error: '.$ex->getMessage()); }

// ── EDITAR ─────────────────────────────────────────────────────────────
case 'editar':
    $id       = (int)($_POST['id']??0);
    $nombre   = e($link,$_POST['nombre']??'');
    $desc     = e($link,$_POST['descripcion']??'');
    $complejo = (int)($_POST['complejo_id']??0);
    $tipo     = (int)($_POST['tipo_cancha_id']??0);
    $encs     = json_decode($_POST['encargados']??'[]', true);

    if(!$id)       resp(false,'ID inválido.');
    if(!$nombre)   resp(false,'El nombre es obligatorio.');
    if(!$complejo) resp(false,'Seleccioná un complejo.');
    if(!$tipo)     resp(false,'Seleccioná un tipo de cancha.');
    assert_cancha($link, $id);          // la cancha actual es mía
    assert_complejo($link, $complejo);  // el complejo destino también

    mysqli_begin_transaction($link);
    try {
        mysqli_query($link,
            "UPDATE cancha SET COMPLEJO_ID=$complejo,TIPO_CANCHA_ID=$tipo,
             CANCHA_NOMBRE='$nombre',CANCHA_DESCRIPCION='$desc'
             WHERE CANCHA_ID=$id"
        );
        mysqli_query($link,"DELETE FROM cancha_encargado WHERE CANCHA_ID=$id");
        foreach($encs as $uid){
            $uid=(int)$uid;
            if($uid) mysqli_query($link,
                "INSERT IGNORE INTO cancha_encargado (CANCHA_ID,USUARIOS_ID,ACTIVO) VALUES ($id,$uid,1)"
            );
        }
        mysqli_commit($link);
        resp(true,'Cancha actualizada correctamente.');
    } catch(Exception $ex){ mysqli_rollback($link); resp(false,'Error: '.$ex->getMessage()); }

// ── TOGGLE ─────────────────────────────────────────────────────────────
case 'toggle':
    $id=(int)($_POST['id']??0);
    if(!$id) resp(false,'ID inválido.');
    assert_cancha($link, $id);
    $cur=mysqli_fetch_assoc(mysqli_query($link,"SELECT ACTIVO FROM cancha WHERE CANCHA_ID=$id"));
    if(!$cur) resp(false,'No encontrado.');
    $nuevo=$cur['ACTIVO']?0:1;
    mysqli_query($link,"UPDATE cancha SET ACTIVO=$nuevo WHERE CANCHA_ID=$id");
    resp(true,$nuevo?'Cancha activada.':'Cancha desactivada.',['activo'=>$nuevo]);

default:
    resp(false,'Acción no reconocida.');
}
