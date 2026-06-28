<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';

if (!isset($_SESSION['usuario_perfil'])) {
    http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Sin sesión.']); exit;
}

mysqli_query($link, "SET NAMES 'utf8mb4'");

$action = $_GET['action'] ?? '';
function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }

switch($action) {

case 'provincias':
    $rows=[];
    $q=mysqli_query($link,"SELECT PROVINCIA_ID,PROVINCIA_NOMBRE FROM provincia WHERE ACTIVO=1 ORDER BY PROVINCIA_NOMBRE");
    while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

case 'partidos':
    $pid=(int)($_GET['provincia_id']??0);
    if(!$pid) resp(false,'Provincia requerida.');
    $rows=[];
    $q=mysqli_query($link,"SELECT PARTIDO_ID,PARTIDO_NOMBRE FROM partido WHERE PROVINCIA_ID=$pid AND ACTIVO=1 ORDER BY PARTIDO_NOMBRE");
    while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

case 'localidades':
    $pid=(int)($_GET['partido_id']??0);
    if(!$pid) resp(false,'Partido requerido.');
    $rows=[];
    $q=mysqli_query($link,"SELECT LOCALIDAD_ID,LOCALIDAD_NOMBRE FROM localidad WHERE PARTIDO_ID=$pid AND ACTIVO=1 ORDER BY LOCALIDAD_NOMBRE");
    while($r=mysqli_fetch_assoc($q)) $rows[]=$r;
    resp(true,'',$rows);

case 'info_localidad':
    $lid=(int)($_GET['localidad_id']??0);
    if(!$lid) resp(false,'Localidad requerida.');
    $r=mysqli_fetch_assoc(mysqli_query($link,
        "SELECT l.LOCALIDAD_ID, l.LOCALIDAD_NOMBRE,
                pa.PARTIDO_ID, pa.PARTIDO_NOMBRE,
                pr.PROVINCIA_ID, pr.PROVINCIA_NOMBRE
         FROM localidad l
         JOIN partido pa ON pa.PARTIDO_ID = l.PARTIDO_ID
         JOIN provincia pr ON pr.PROVINCIA_ID = pa.PROVINCIA_ID
         WHERE l.LOCALIDAD_ID = $lid"
    ));
    if(!$r) resp(false,'No encontrada.');
    resp(true,'',$r);

case 'crear_provincia':
    $nombre = trim($_POST['nombre'] ?? '');
    if (!$nombre) resp(false,'El nombre es obligatorio.');
    $n = mysqli_real_escape_string($link, $nombre);
    if (mysqli_fetch_assoc(mysqli_query($link,"SELECT PROVINCIA_ID FROM provincia WHERE PROVINCIA_NOMBRE='$n'")))
        resp(false,'Ya existe esa provincia.');
    mysqli_query($link,"INSERT INTO provincia (PROVINCIA_NOMBRE,ACTIVO) VALUES ('$n',1)");
    $id = mysqli_insert_id($link);
    resp(true,'Provincia creada.',['id'=>$id,'nombre'=>$nombre]);

case 'crear_partido':
    $nombre  = trim($_POST['nombre']      ?? '');
    $prov_id = (int)($_POST['provincia_id'] ?? 0);
    if (!$nombre)  resp(false,'El nombre es obligatorio.');
    if (!$prov_id) resp(false,'Provincia requerida.');
    $n = mysqli_real_escape_string($link, $nombre);
    if (mysqli_fetch_assoc(mysqli_query($link,"SELECT PARTIDO_ID FROM partido WHERE PARTIDO_NOMBRE='$n' AND PROVINCIA_ID=$prov_id")))
        resp(false,'Ya existe ese partido en la provincia.');
    mysqli_query($link,"INSERT INTO partido (PROVINCIA_ID,PARTIDO_NOMBRE,ACTIVO) VALUES ($prov_id,'$n',1)");
    $id = mysqli_insert_id($link);
    resp(true,'Partido creado.',['id'=>$id,'nombre'=>$nombre,'provincia_id'=>$prov_id]);

case 'crear_localidad':
    $nombre     = trim($_POST['nombre']      ?? '');
    $partido_id = (int)($_POST['partido_id']   ?? 0);
    $prov_id    = (int)($_POST['provincia_id'] ?? 0);
    if (!$nombre)     resp(false,'El nombre es obligatorio.');
    if (!$partido_id) resp(false,'Partido requerido.');
    if (!$prov_id)    resp(false,'Provincia requerida.');
    $n = mysqli_real_escape_string($link, $nombre);
    if (mysqli_fetch_assoc(mysqli_query($link,"SELECT LOCALIDAD_ID FROM localidad WHERE LOCALIDAD_NOMBRE='$n' AND PARTIDO_ID=$partido_id")))
        resp(false,'Ya existe esa localidad en el partido.');
    mysqli_query($link,"INSERT INTO localidad (LOCALIDAD_NOMBRE,PROVINCIA_ID,PARTIDO_ID,ACTIVO) VALUES ('$n',$prov_id,$partido_id,1)");
    $id = mysqli_insert_id($link);
    resp(true,'Localidad creada.',['id'=>$id,'nombre'=>$nombre,'partido_id'=>$partido_id,'provincia_id'=>$prov_id]);

default:
    resp(false,'Acción no reconocida.');
}
