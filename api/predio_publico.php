<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/dist/script/php/conn.php';

if (!isset($link)) { echo json_encode(['ok'=>false,'msg'=>'Sin conexión']); exit; }

$id    = (int)($_GET['complejo_id'] ?? 0);
$fecha = trim($_GET['fecha'] ?? date('Y-m-d'));

if (!$id) { echo json_encode(['ok'=>false,'msg'=>'ID requerido']); exit; }
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $fecha = date('Y-m-d');

// Predio info
$stmt = mysqli_prepare($link, "
    SELECT co.COMPLEJO_ID, co.COMPLEJO_NOMBRE, co.COMPLEJO_DIRECCION,
           co.COMPLEJO_TELEFONO, co.COMPLEJO_EMAIL, co.COMPLEJO_DESCRIPCION,
           l.LOCALIDAD_NOMBRE, par.PARTIDO_NOMBRE, prov.PROVINCIA_NOMBRE,
           tc.TIPO_COMPLEJO_NOMBRE,
           GROUP_CONCAT(DISTINCT tip.TIPO_CANCHA_NOMBRE ORDER BY tip.TIPO_CANCHA_NOMBRE SEPARATOR ', ') AS ACTIVIDADES,
           COUNT(DISTINCT c.CANCHA_ID) AS TOTAL_CANCHAS
    FROM complejo co
    LEFT JOIN localidad l      ON l.LOCALIDAD_ID     = co.LOCALIDAD_ID
    LEFT JOIN partido par      ON par.PARTIDO_ID      = l.PARTIDO_ID
    LEFT JOIN provincia prov   ON prov.PROVINCIA_ID   = par.PROVINCIA_ID
    LEFT JOIN tipo_complejo tc ON tc.TIPO_COMPLEJO_ID = co.TIPO_COMPLEJO_ID
    LEFT JOIN cancha c         ON c.COMPLEJO_ID = co.COMPLEJO_ID AND c.ACTIVO = 1
    LEFT JOIN tipo_cancha tip  ON tip.TIPO_CANCHA_ID  = c.TIPO_CANCHA_ID
    WHERE co.COMPLEJO_ID = ? AND co.ACTIVO = 1
    GROUP BY co.COMPLEJO_ID
");
mysqli_stmt_bind_param($stmt, 'i', $id);
mysqli_stmt_execute($stmt);
$predio = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if (!$predio) { echo json_encode(['ok'=>false,'msg'=>'Predio no encontrado']); exit; }

// Canchas del predio
$stmt2 = mysqli_prepare($link, "
    SELECT c.CANCHA_ID, c.CANCHA_NOMBRE, c.CANCHA_DESCRIPCION,
           tc.TIPO_CANCHA_NOMBRE, tc.TIPO_CANCHA_ICONO
    FROM cancha c
    LEFT JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = c.TIPO_CANCHA_ID
    WHERE c.COMPLEJO_ID = ? AND c.ACTIVO = 1
    ORDER BY c.CANCHA_ID
");
mysqli_stmt_bind_param($stmt2, 'i', $id);
mysqli_stmt_execute($stmt2);
$res2   = mysqli_stmt_get_result($stmt2);
$canchas = [];
while ($row = mysqli_fetch_assoc($res2)) $canchas[] = $row;

// Slots por cancha para la fecha dada
$diaId = (int)date('N', strtotime($fecha));

foreach ($canchas as &$c) {
    $cid = (int)$c['CANCHA_ID'];
    $s3  = mysqli_prepare($link, "
        SELECT fh.FRANJA_ID, fh.FRANJA_HORA_INICIO, fh.FRANJA_HORA_FIN,
               fh.FRANJA_PRECIO, fh.FRANJA_SENA,
               (SELECT COUNT(*) FROM reserva r
                WHERE r.CANCHA_ID = fh.CANCHA_ID
                  AND r.RESERVA_FECHA = ?
                  AND r.RESERVA_HORA_INICIO = fh.FRANJA_HORA_INICIO
                  AND r.RESERVA_ESTADO IN ('pendiente','confirmada')
                  AND r.ACTIVO = 1) AS ocupado,
               (SELECT COUNT(*) FROM turno_fijo tf
                WHERE tf.CANCHA_ID = fh.CANCHA_ID
                  AND tf.FRANJA_ID = fh.FRANJA_ID
                  AND tf.DIA_ID = ?
                  AND tf.ACTIVO = 1) AS turno_fijo,
               (SELECT COUNT(*) FROM cierre_cancha cc
                WHERE cc.CANCHA_ID = fh.CANCHA_ID
                  AND ? BETWEEN cc.CIERRE_FECHA_INICIO AND cc.CIERRE_FECHA_FIN
                  AND cc.ACTIVO = 1) AS cerrada
        FROM franja_horaria fh
        INNER JOIN franja_dia fd ON fd.FRANJA_ID = fh.FRANJA_ID AND fd.DIA_ID = ?
        WHERE fh.CANCHA_ID = ? AND fh.ACTIVO = 1
        ORDER BY fh.FRANJA_HORA_INICIO
    ");
    mysqli_stmt_bind_param($s3, 'siisi', $fecha, $diaId, $fecha, $diaId, $cid);
    mysqli_stmt_execute($s3);
    $r3    = mysqli_stmt_get_result($s3);
    $slots = [];
    while ($sl = mysqli_fetch_assoc($r3)) {
        $disp   = ($sl['ocupado'] == 0 && $sl['turno_fijo'] == 0 && $sl['cerrada'] == 0);
        $motivo = '';
        if ($sl['cerrada'])        $motivo = 'cerrada';
        elseif ($sl['turno_fijo']) $motivo = 'turno fijo';
        elseif ($sl['ocupado'])    $motivo = 'reservada';
        $slots[] = [
            'FRANJA_ID'          => (int)$sl['FRANJA_ID'],
            'FRANJA_HORA_INICIO' => substr($sl['FRANJA_HORA_INICIO'], 0, 5),
            'FRANJA_HORA_FIN'    => substr($sl['FRANJA_HORA_FIN'],    0, 5),
            'FRANJA_PRECIO'      => (float)$sl['FRANJA_PRECIO'],
            'FRANJA_SENA'        => (float)$sl['FRANJA_SENA'],
            'disponible'         => $disp,
            'motivo'             => $motivo,
        ];
    }
    $c['slots'] = $slots;
}
unset($c);

echo json_encode(['ok' => true, 'predio' => $predio, 'canchas' => $canchas]);
