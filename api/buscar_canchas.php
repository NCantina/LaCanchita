<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/dist/script/php/conn.php';

if (!isset($link)) { echo json_encode(['ok'=>false,'msg'=>'Sin conexión']); exit; }

$provinciaId = (int)($_GET['provincia'] ?? 0);
$partidoId   = (int)($_GET['partido']   ?? 0);
$localidadId = (int)($_GET['localidad'] ?? 0);
$deporte     = trim($_GET['deporte']    ?? '');
$fecha       = trim($_GET['fecha']      ?? date('Y-m-d'));
$horario     = trim($_GET['horario']    ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) $fecha = date('Y-m-d');

$where = ['c.ACTIVO=1', 'co.ACTIVO=1'];
$params = [];
$types  = '';

if ($localidadId) {
    $where[] = 'co.LOCALIDAD_ID=?';
    $params[] = $localidadId; $types .= 'i';
} elseif ($partidoId) {
    $where[] = 'l.PARTIDO_ID=?';
    $params[] = $partidoId; $types .= 'i';
} elseif ($provinciaId) {
    $where[] = 'par.PROVINCIA_ID=?';
    $params[] = $provinciaId; $types .= 'i';
}

if ($deporte) {
    $where[] = 'tc.TIPO_CANCHA_NOMBRE LIKE ?';
    $params[] = '%' . $deporte . '%'; $types .= 's';
}

$whereStr = implode(' AND ', $where);

$sql = "
    SELECT c.CANCHA_ID, c.CANCHA_NOMBRE,
           co.COMPLEJO_NOMBRE, co.COMPLEJO_ID,
           l.LOCALIDAD_NOMBRE,
           tc.TIPO_CANCHA_NOMBRE,
           MIN(fh.FRANJA_PRECIO) AS PRECIO_DESDE
    FROM cancha c
    INNER JOIN complejo co ON co.COMPLEJO_ID = c.COMPLEJO_ID
    LEFT JOIN localidad l    ON l.LOCALIDAD_ID  = co.LOCALIDAD_ID
    LEFT JOIN partido par    ON par.PARTIDO_ID  = l.PARTIDO_ID
    LEFT JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = c.TIPO_CANCHA_ID
    LEFT JOIN franja_horaria fh ON fh.CANCHA_ID = c.CANCHA_ID AND fh.ACTIVO = 1
    WHERE $whereStr
    GROUP BY c.CANCHA_ID
    ORDER BY c.CANCHA_ID DESC
    LIMIT 12
";

$stmt = mysqli_prepare($link, $sql);
if ($params) mysqli_stmt_bind_param($stmt, $types, ...$params);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
$canchas = [];
while ($row = mysqli_fetch_assoc($res)) $canchas[] = $row;

// DIA_ID: 1=Lun..7=Dom (PHP date('N') gives 1=Mon..7=Sun — matches)
$diaId = (int)date('N', strtotime($fecha));

$horarioFilter = '';
if (strpos($horario, 'añana') !== false)     $horarioFilter = " AND TIME(fh2.FRANJA_HORA_INICIO) >= '08:00' AND TIME(fh2.FRANJA_HORA_INICIO) < '12:00'";
elseif (strpos($horario, 'arde') !== false)  $horarioFilter = " AND TIME(fh2.FRANJA_HORA_INICIO) >= '12:00' AND TIME(fh2.FRANJA_HORA_INICIO) < '18:00'";
elseif (strpos($horario, 'oche') !== false)  $horarioFilter = " AND TIME(fh2.FRANJA_HORA_INICIO) >= '18:00'";

foreach ($canchas as &$c) {
    $cid = (int)$c['CANCHA_ID'];
    $slotSql = "
        SELECT fh2.FRANJA_HORA_INICIO AS hora,
               (SELECT COUNT(*) FROM reserva r
                WHERE r.CANCHA_ID = fh2.CANCHA_ID
                  AND r.RESERVA_FECHA = ?
                  AND r.RESERVA_HORA_INICIO = fh2.FRANJA_HORA_INICIO
                  AND r.ACTIVO = 1) AS ocupado
        FROM franja_horaria fh2
        INNER JOIN franja_dia fd ON fd.FRANJA_ID = fh2.FRANJA_ID AND fd.DIA_ID = ?
        WHERE fh2.CANCHA_ID = ? AND fh2.ACTIVO = 1
        $horarioFilter
        ORDER BY fh2.FRANJA_HORA_INICIO
        LIMIT 8
    ";
    $s2 = mysqli_prepare($link, $slotSql);
    mysqli_stmt_bind_param($s2, 'sii', $fecha, $diaId, $cid);
    mysqli_stmt_execute($s2);
    $r2 = mysqli_stmt_get_result($s2);
    $slots = [];
    while ($sl = mysqli_fetch_assoc($r2)) {
        $slots[] = ['hora' => substr($sl['hora'], 0, 5), 'libre' => ($sl['ocupado'] == 0)];
    }
    $c['slots'] = $slots;
}
unset($c);

echo json_encode(['ok' => true, 'canchas' => $canchas]);
