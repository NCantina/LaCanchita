<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';

if (!isset($_SESSION['usuario_perfil'])) {
    http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'Sin sesión.']); exit;
}
session_write_close(); // liberar lock de sesión antes de queries

$action = $_GET['action'] ?? '';
function resp($ok,$msg,$data=null){ echo json_encode(['ok'=>$ok,'msg'=>$msg,'data'=>$data], JSON_UNESCAPED_UNICODE); exit; }

switch($action) {

case 'listar':
    $sql = "
        SELECT
            c.COMPLEJO_ID,
            c.COMPLEJO_NOMBRE,
            c.COMPLEJO_DIRECCION,
            c.COMPLEJO_TELEFONO,
            c.COMPLEJO_EMAIL,
            c.COMPLEJO_DESCRIPCION,
            tc.TIPO_COMPLEJO_NOMBRE,
            tc.TIPO_COMPLEJO_ICONO,
            l.LOCALIDAD_NOMBRE,
            pa.PARTIDO_NOMBRE,
            pr.PROVINCIA_NOMBRE,
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
        WHERE c.ACTIVO = 1
        ORDER BY c.COMPLEJO_NOMBRE ASC
    ";
    $q = mysqli_query($link, $sql);
    $rows = [];
    while($r = mysqli_fetch_assoc($q)) $rows[] = $r;
    resp(true, '', $rows);

case 'canchas':
    $cid = (int)($_GET['complejo_id'] ?? 0);
    if (!$cid) resp(false, 'ID de predio requerido.');

    $q = mysqli_query($link,
        "SELECT ca.CANCHA_ID, ca.CANCHA_NOMBRE, ca.CANCHA_DESCRIPCION,
                tc.TIPO_CANCHA_NOMBRE, tc.TIPO_CANCHA_ICONO
         FROM cancha ca
         JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = ca.TIPO_CANCHA_ID
         WHERE ca.COMPLEJO_ID = $cid AND ca.ACTIVO = 1
         ORDER BY ca.CANCHA_NOMBRE"
    );
    $canchas = [];
    while($r = mysqli_fetch_assoc($q)) $canchas[] = $r;

    // Franjas por cancha
    foreach($canchas as &$can) {
        $caid = (int)$can['CANCHA_ID'];
        $qf = mysqli_query($link,
            "SELECT f.FRANJA_ID, f.FRANJA_HORA_INICIO, f.FRANJA_HORA_FIN,
                    f.FRANJA_PRECIO, f.FRANJA_SENA,
                    GROUP_CONCAT(fd.DIA_ID ORDER BY fd.DIA_ID) AS DIAS
             FROM franja_horaria f
             LEFT JOIN franja_dia fd ON fd.FRANJA_ID = f.FRANJA_ID
             WHERE f.CANCHA_ID = $caid AND f.ACTIVO = 1
             GROUP BY f.FRANJA_ID
             ORDER BY f.FRANJA_HORA_INICIO"
        );
        $can['franjas'] = [];
        while($fr = mysqli_fetch_assoc($qf)) $can['franjas'][] = $fr;
    }
    unset($can);

    resp(true, '', $canchas);

default:
    resp(false, 'Acción no reconocida.');
}
