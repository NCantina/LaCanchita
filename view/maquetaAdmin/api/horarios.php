<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';

require_perfil(2);

$action = $_GET['action'] ?? $_POST['action'] ?? '';
function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }
function e($link,$v){ return mysqli_real_escape_string($link, trim($v??'')); }

// Modo solo-lectura por mora: bloquear escritura del panel.
if (in_array($action, ['crear','editar','toggle','eliminar'], true)) assert_tenant_activo($link);

switch($action) {

// ── Complejos para el selector ──────────────────────────────────────────
case 'complejos':
    $rows=[];
    $scope = tenant_where(tenant_complejo_ids($link), 'COMPLEJO_ID');
    $q=mysqli_query($link,"SELECT COMPLEJO_ID,COMPLEJO_NOMBRE FROM complejo WHERE ACTIVO=1 AND $scope ORDER BY COMPLEJO_NOMBRE");
    while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

// ── Canchas de un complejo ──────────────────────────────────────────────
case 'canchas_por_complejo':
    $cid=(int)($_GET['complejo_id']??0);
    if(!$cid) resp(false,'ID de complejo requerido.');
    assert_complejo($link, $cid);
    $sql="
        SELECT ca.CANCHA_ID, ca.CANCHA_NOMBRE, ca.ACTIVO,
               tc.TIPO_CANCHA_NOMBRE, tc.TIPO_CANCHA_ICONO,
               (SELECT COUNT(*) FROM franja_horaria fh WHERE fh.CANCHA_ID=ca.CANCHA_ID AND fh.ACTIVO=1) AS TOTAL_FRANJAS
        FROM cancha ca
        JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID=ca.TIPO_CANCHA_ID
        WHERE ca.COMPLEJO_ID=$cid
        ORDER BY ca.ACTIVO DESC, ca.CANCHA_NOMBRE";
    $q=mysqli_query($link,$sql);
    $rows=[];
    while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

// ── Franjas de una cancha (con sus días) ───────────────────────────────
case 'franjas':
    $cid=(int)($_GET['cancha_id']??0);
    if(!$cid) resp(false,'ID de cancha requerido.');
    assert_cancha($link, $cid);
    $sql="
        SELECT fh.FRANJA_ID, fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN,
               fh.FRANJA_PRECIO, fh.FRANJA_SENA, fh.ACTIVO,
               GROUP_CONCAT(fd.DIA_ID ORDER BY fd.DIA_ID) AS DIAS
        FROM franja_horaria fh
        LEFT JOIN franja_dia fd ON fd.FRANJA_ID=fh.FRANJA_ID
        WHERE fh.CANCHA_ID=$cid
        GROUP BY fh.FRANJA_ID
        ORDER BY fh.FRANJA_HORA_INICIO, fh.ACTIVO DESC";
    $q=mysqli_query($link,$sql);
    $rows=[];
    while($r=mysqli_fetch_assoc($q)){
        $r['DIAS'] = $r['DIAS'] ? array_map('intval', explode(',',$r['DIAS'])) : [];
        $rows[]=$r;
    }
    resp(true,'',$rows);

// ── Crear franja ────────────────────────────────────────────────────────
case 'crear':
    $cancha  = (int)($_POST['cancha_id']??0);
    $inicio  = e($link,$_POST['hora_inicio']??'');
    $fin     = e($link,$_POST['hora_fin']??'');
    $precio  = (float)str_replace(',','.',($_POST['precio']??0));
    $sena    = (float)str_replace(',','.',($_POST['sena']??0));
    $dias    = json_decode($_POST['dias']??'[]',true);

    if(!$cancha)  resp(false,'Cancha requerida.');
    if(!$inicio)  resp(false,'Hora de inicio requerida.');
    if(!$fin)     resp(false,'Hora de fin requerida.');
    if($fin<=$inicio) resp(false,'La hora de fin debe ser posterior al inicio.');
    if($precio<=0) resp(false,'El precio debe ser mayor a 0.');
    if(empty($dias)) resp(false,'Seleccioná al menos un día.');
    assert_cancha($link, $cancha);

    $diasList = implode(',', array_map('intval', $dias));

    mysqli_begin_transaction($link);
    try {
        // Verificar solapamiento dentro de la transacción para evitar race conditions
        $solape = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN
             FROM franja_horaria fh
             JOIN franja_dia fd ON fd.FRANJA_ID = fh.FRANJA_ID
             WHERE fh.CANCHA_ID = $cancha
               AND fh.ACTIVO = 1
               AND fd.DIA_ID IN ($diasList)
               AND fh.FRANJA_HORA_INICIO < '$fin'
               AND fh.FRANJA_HORA_FIN > '$inicio'
             LIMIT 1 FOR UPDATE"
        ));
        if ($solape) {
            mysqli_rollback($link);
            $ini = substr($solape['FRANJA_HORA_INICIO'],0,5);
            $fi  = substr($solape['FRANJA_HORA_FIN'],0,5);
            resp(false,"El horario se superpone con una franja existente ($ini–$fi). Modificá el horario o eliminá la franja anterior.");
        }
        mysqli_query($link,
            "INSERT INTO franja_horaria (CANCHA_ID,FRANJA_HORA_INICIO,FRANJA_HORA_FIN,FRANJA_PRECIO,FRANJA_SENA,ACTIVO)
             VALUES ($cancha,'$inicio','$fin',$precio,$sena,1)"
        );
        $fid = mysqli_insert_id($link);
        foreach($dias as $d){
            $d=(int)$d;
            if($d>=1 && $d<=7) mysqli_query($link,
                "INSERT IGNORE INTO franja_dia (FRANJA_ID,DIA_ID) VALUES ($fid,$d)"
            );
        }
        mysqli_commit($link);
        resp(true,'Franja creada correctamente.',['id'=>$fid]);
    } catch(Exception $ex){ mysqli_rollback($link); resp(false,'Error: '.$ex->getMessage()); }

// ── Editar franja ───────────────────────────────────────────────────────
case 'editar':
    $fid    = (int)($_POST['franja_id']??0);
    $inicio = e($link,$_POST['hora_inicio']??'');
    $fin    = e($link,$_POST['hora_fin']??'');
    $precio = (float)str_replace(',','.',($_POST['precio']??0));
    $sena   = (float)str_replace(',','.',($_POST['sena']??0));
    $dias   = json_decode($_POST['dias']??'[]',true);

    if(!$fid)     resp(false,'ID de franja requerido.');
    if(!$inicio)  resp(false,'Hora de inicio requerida.');
    if(!$fin)     resp(false,'Hora de fin requerida.');
    if($fin<=$inicio) resp(false,'La hora de fin debe ser posterior al inicio.');
    if($precio<=0) resp(false,'El precio debe ser mayor a 0.');
    if(empty($dias)) resp(false,'Seleccioná al menos un día.');
    assert_franja($link, $fid);

    $fRow = mysqli_fetch_assoc(mysqli_query($link,"SELECT CANCHA_ID FROM franja_horaria WHERE FRANJA_ID=$fid"));
    $fCancha  = (int)($fRow['CANCHA_ID'] ?? 0);
    $diasList = implode(',', array_map('intval', $dias));

    mysqli_begin_transaction($link);
    try {
        // Chequeo de solapamiento dentro de la transacción
        $solape = mysqli_fetch_assoc(mysqli_query($link,
            "SELECT fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN
             FROM franja_horaria fh
             JOIN franja_dia fd ON fd.FRANJA_ID = fh.FRANJA_ID
             WHERE fh.CANCHA_ID = $fCancha
               AND fh.ACTIVO = 1
               AND fh.FRANJA_ID != $fid
               AND fd.DIA_ID IN ($diasList)
               AND fh.FRANJA_HORA_INICIO < '$fin'
               AND fh.FRANJA_HORA_FIN > '$inicio'
             LIMIT 1 FOR UPDATE"
        ));
        if ($solape) {
            mysqli_rollback($link);
            $ini = substr($solape['FRANJA_HORA_INICIO'],0,5);
            $fi  = substr($solape['FRANJA_HORA_FIN'],0,5);
            resp(false,"El horario se superpone con una franja existente ($ini–$fi). Modificá el horario o eliminá la franja anterior.");
        }
        mysqli_query($link,
            "UPDATE franja_horaria SET FRANJA_HORA_INICIO='$inicio',FRANJA_HORA_FIN='$fin',
             FRANJA_PRECIO=$precio,FRANJA_SENA=$sena WHERE FRANJA_ID=$fid"
        );
        mysqli_query($link,"DELETE FROM franja_dia WHERE FRANJA_ID=$fid");
        foreach($dias as $d){
            $d=(int)$d;
            if($d>=1 && $d<=7) mysqli_query($link,
                "INSERT IGNORE INTO franja_dia (FRANJA_ID,DIA_ID) VALUES ($fid,$d)"
            );
        }
        mysqli_commit($link);
        resp(true,'Franja actualizada correctamente.');
    } catch(Exception $ex){ mysqli_rollback($link); resp(false,'Error: '.$ex->getMessage()); }

// ── Toggle activo ───────────────────────────────────────────────────────
case 'toggle':
    $fid=(int)($_POST['franja_id']??0);
    if(!$fid) resp(false,'ID requerido.');
    assert_franja($link, $fid);
    $cur=mysqli_fetch_assoc(mysqli_query($link,"SELECT ACTIVO FROM franja_horaria WHERE FRANJA_ID=$fid"));
    if(!$cur) resp(false,'No encontrada.');
    $nuevo=$cur['ACTIVO']?0:1;
    mysqli_query($link,"UPDATE franja_horaria SET ACTIVO=$nuevo WHERE FRANJA_ID=$fid");
    resp(true,$nuevo?'Franja activada.':'Franja desactivada.',['activo'=>$nuevo]);

// ── Eliminar (soft delete) ──────────────────────────────────────────────
case 'eliminar':
    $fid=(int)($_POST['franja_id']??0);
    if(!$fid) resp(false,'ID requerido.');
    assert_franja($link, $fid);
    mysqli_query($link,"UPDATE franja_horaria SET ACTIVO=0 WHERE FRANJA_ID=$fid");
    mysqli_query($link,"DELETE FROM franja_dia WHERE FRANJA_ID=$fid");
    resp(true,'Franja eliminada.');

default:
    resp(false,'Acción no reconocida.');
}
