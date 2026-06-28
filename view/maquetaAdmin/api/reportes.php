<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';
require_perfil(2);

$action = $_GET['action'] ?? '';

function resp($ok, $msg, $data = null) {
    echo json_encode(['ok' => $ok, 'msg' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

function e($link, $v) {
    return mysqli_real_escape_string($link, trim($v ?? ''));
}

function get_rango($link) {
    $periodo = $_GET['periodo'] ?? 'mes';
    switch ($periodo) {
        case 'hoy':    return [date('Y-m-d'), date('Y-m-d')];
        case 'semana': return [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))];
        case 'mes':    return [date('Y-m-01'), date('Y-m-t')];
        case 'año':    return [date('Y-01-01'), date('Y-12-31')];
        case 'custom':
            $desde = $_GET['desde'] ?? date('Y-m-01');
            $hasta = $_GET['hasta'] ?? date('Y-m-d');
            return [$desde, $hasta];
        default:       return [date('Y-m-01'), date('Y-m-t')];
    }
}

function get_scope($link) {
    $ids = tenant_complejo_ids($link);
    return tenant_where($ids, 'co.COMPLEJO_ID');
}

// ──────────────────────────────────────────────────────────────────
//  Nombres de días (DAYOFWEEK: 1=Dom, 2=Lun ... 7=Sáb)
// ──────────────────────────────────────────────────────────────────
$dias_dow = [1=>'Domingo', 2=>'Lunes', 3=>'Martes', 4=>'Miércoles', 5=>'Jueves', 6=>'Viernes', 7=>'Sábado'];
$dias_abrev = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb','Sun'=>'Dom'];

// ══════════════════════════════════════════════════════════════════
//  ACTION: resumen
// ══════════════════════════════════════════════════════════════════
if ($action === 'resumen') {
    [$desde, $hasta] = get_rango($link);
    $scope = get_scope($link);
    $desde = e($link, $desde);
    $hasta = e($link, $hasta);

    // Reservas en el período
    $q = mysqli_query($link,
        "SELECT
            COUNT(*) AS reservas_total,
            SUM(r.RESERVA_ESTADO='confirmada') AS reservas_confirmadas,
            SUM(r.RESERVA_ESTADO='cancelada')  AS reservas_canceladas,
            SUM(r.RESERVA_ESTADO='pendiente')  AS reservas_pendientes,
            SUM(r.RESERVA_PRECIO) AS ingresos_total,
            AVG(r.RESERVA_PRECIO) AS ticket_promedio
         FROM reserva r
         JOIN cancha ca ON ca.CANCHA_ID = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE r.RESERVA_FECHA BETWEEN '$desde' AND '$hasta'
           AND r.ACTIVO = 1 AND $scope"
    );
    $row = mysqli_fetch_assoc($q);

    // Cobrado en el período (por fecha del pago)
    $qc = mysqli_query($link,
        "SELECT COALESCE(SUM(p.PAGO_MONTO), 0) AS cobrado
         FROM pago p
         JOIN reserva r  ON r.RESERVA_ID  = p.RESERVA_ID
         JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE DATE(p.PAGO_FECHA) BETWEEN '$desde' AND '$hasta'
           AND p.ACTIVO = 1 AND $scope"
    );
    $cobrado = (float) mysqli_fetch_assoc($qc)['cobrado'];

    // Cancha más reservada
    $qcan = mysqli_query($link,
        "SELECT ca.CANCHA_NOMBRE, COUNT(*) AS cnt
         FROM reserva r
         JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE r.RESERVA_FECHA BETWEEN '$desde' AND '$hasta'
           AND r.ACTIVO = 1 AND r.RESERVA_ESTADO = 'confirmada' AND $scope
         GROUP BY ca.CANCHA_ID
         ORDER BY cnt DESC LIMIT 1"
    );
    $cancha_top = '';
    if ($rcan = mysqli_fetch_assoc($qcan)) {
        $cancha_top = $rcan['CANCHA_NOMBRE'];
    }

    // Día más activo
    $qdow = mysqli_query($link,
        "SELECT DAYOFWEEK(r.RESERVA_FECHA) AS dow, COUNT(*) AS cnt
         FROM reserva r
         JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE r.RESERVA_FECHA BETWEEN '$desde' AND '$hasta'
           AND r.ACTIVO = 1 AND r.RESERVA_ESTADO = 'confirmada' AND $scope
         GROUP BY dow
         ORDER BY cnt DESC LIMIT 1"
    );
    $dia_top = '';
    if ($rdow = mysqli_fetch_assoc($qdow)) {
        $dia_top = $dias_dow[(int)$rdow['dow']] ?? '';
    }

    $ingresos_total  = (float) ($row['ingresos_total'] ?? 0);
    $saldo_pendiente = max(0, $ingresos_total - $cobrado);

    resp(true, '', [
        'reservas_total'       => (int) $row['reservas_total'],
        'reservas_confirmadas' => (int) $row['reservas_confirmadas'],
        'reservas_canceladas'  => (int) $row['reservas_canceladas'],
        'reservas_pendientes'  => (int) $row['reservas_pendientes'],
        'ingresos_total'       => round($ingresos_total, 2),
        'ingresos_cobrados'    => round($cobrado, 2),
        'saldo_pendiente'      => round($saldo_pendiente, 2),
        'ticket_promedio'      => round((float)($row['ticket_promedio'] ?? 0), 2),
        'cancha_top'           => $cancha_top,
        'dia_top'              => $dia_top,
    ]);
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: por_dia
// ══════════════════════════════════════════════════════════════════
if ($action === 'por_dia') {
    [$desde, $hasta] = get_rango($link);
    $scope = get_scope($link);
    $desde = e($link, $desde);
    $hasta = e($link, $hasta);

    // Reservas por día
    $qr = mysqli_query($link,
        "SELECT r.RESERVA_FECHA AS fecha,
                COUNT(*) AS reservas,
                SUM(r.RESERVA_PRECIO) AS ingresos
         FROM reserva r
         JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE r.RESERVA_FECHA BETWEEN '$desde' AND '$hasta'
           AND r.ACTIVO = 1 AND r.RESERVA_ESTADO IN ('confirmada','pendiente') AND $scope
         GROUP BY r.RESERVA_FECHA
         ORDER BY r.RESERVA_FECHA ASC"
    );
    $res_por_dia = [];
    while ($r = mysqli_fetch_assoc($qr)) {
        $res_por_dia[$r['fecha']] = ['reservas' => (int)$r['reservas'], 'ingresos' => (float)$r['ingresos']];
    }

    // Cobrado por día
    $qp = mysqli_query($link,
        "SELECT DATE(p.PAGO_FECHA) AS fecha, SUM(p.PAGO_MONTO) AS cobrado
         FROM pago p
         JOIN reserva r  ON r.RESERVA_ID  = p.RESERVA_ID
         JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE DATE(p.PAGO_FECHA) BETWEEN '$desde' AND '$hasta'
           AND p.ACTIVO = 1 AND $scope
         GROUP BY DATE(p.PAGO_FECHA)"
    );
    $cob_por_dia = [];
    while ($r = mysqli_fetch_assoc($qp)) {
        $cob_por_dia[$r['fecha']] = (float)$r['cobrado'];
    }

    // Rellenar todos los días del rango
    $result   = [];
    $cur      = strtotime($desde);
    $fin      = strtotime($hasta);
    while ($cur <= $fin) {
        $f    = date('Y-m-d', $cur);
        $eng  = date('D', $cur);  // Mon, Tue...
        $abrev = $dias_abrev[$eng] ?? $eng;
        $result[] = [
            'fecha'      => $f,
            'dia_nombre' => $abrev,
            'reservas'   => $res_por_dia[$f]['reservas'] ?? 0,
            'ingresos'   => $res_por_dia[$f]['ingresos'] ?? 0.0,
            'cobrado'    => $cob_por_dia[$f] ?? 0.0,
        ];
        $cur = strtotime('+1 day', $cur);
    }

    resp(true, '', $result);
}

// ══════════════════════════════════════════════════════════════════
//  ACTION: por_cancha
// ══════════════════════════════════════════════════════════════════
if ($action === 'por_cancha') {
    [$desde, $hasta] = get_rango($link);
    $scope = get_scope($link);
    $desde = e($link, $desde);
    $hasta = e($link, $hasta);

    // Capacidad real por cancha = franjas activas × cuántas veces cae su día
    // de semana dentro del período (en vez de la constante mágica "9 franjas/día").
    $dowCount = array_fill(1, 7, 0);            // 1=Lun .. 7=Dom (igual que DIA_ID)
    for ($t = strtotime($desde), $tEnd = strtotime($hasta); $t <= $tEnd; $t += 86400) {
        $dowCount[(int) date('N', $t)]++;
    }

    $cap = [];  // [CANCHA_ID => cantidad de turnos disponibles en el período]
    $qcap = mysqli_query($link,
        "SELECT fh.CANCHA_ID, fd.DIA_ID, COUNT(*) AS franjas
         FROM franja_horaria fh
         JOIN franja_dia fd ON fd.FRANJA_ID = fh.FRANJA_ID
         JOIN cancha ca     ON ca.CANCHA_ID = fh.CANCHA_ID
         JOIN complejo co   ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE fh.ACTIVO = 1 AND ca.ACTIVO = 1 AND $scope
         GROUP BY fh.CANCHA_ID, fd.DIA_ID"
    );
    if ($qcap) {
        while ($f = mysqli_fetch_assoc($qcap)) {
            $cid = (int) $f['CANCHA_ID'];
            $dia = (int) $f['DIA_ID'];
            $cap[$cid] = ($cap[$cid] ?? 0) + ((int) $f['franjas']) * ($dowCount[$dia] ?? 0);
        }
    }

    // Reservas e ingresos por cancha
    $qr = mysqli_query($link,
        "SELECT ca.CANCHA_ID, ca.CANCHA_NOMBRE, co.COMPLEJO_NOMBRE,
                COALESCE(tc.TIPO_CANCHA_ICONO, 'fa-futbol') AS TIPO_CANCHA_ICONO,
                COUNT(r.RESERVA_ID) AS reservas,
                COALESCE(SUM(r.RESERVA_PRECIO), 0) AS ingresos
         FROM cancha ca
         JOIN complejo co  ON co.COMPLEJO_ID   = ca.COMPLEJO_ID
         JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = ca.TIPO_CANCHA_ID
         LEFT JOIN reserva r ON r.CANCHA_ID = ca.CANCHA_ID
             AND r.RESERVA_FECHA BETWEEN '$desde' AND '$hasta'
             AND r.ACTIVO = 1 AND r.RESERVA_ESTADO IN ('confirmada','pendiente')
         WHERE ca.ACTIVO = 1 AND $scope
         GROUP BY ca.CANCHA_ID
         ORDER BY reservas DESC, ingresos DESC"
    );

    // Cobrado por cancha
    $qp = mysqli_query($link,
        "SELECT ca.CANCHA_ID, COALESCE(SUM(p.PAGO_MONTO), 0) AS cobrado
         FROM pago p
         JOIN reserva r  ON r.RESERVA_ID  = p.RESERVA_ID
         JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
         WHERE DATE(p.PAGO_FECHA) BETWEEN '$desde' AND '$hasta'
           AND p.ACTIVO = 1 AND $scope
         GROUP BY ca.CANCHA_ID"
    );
    $cob = [];
    while ($r = mysqli_fetch_assoc($qp)) {
        $cob[(int)$r['CANCHA_ID']] = (float)$r['cobrado'];
    }

    $result = [];
    while ($r = mysqli_fetch_assoc($qr)) {
        $cid  = (int)$r['CANCHA_ID'];
        $res  = (int)$r['reservas'];
        $capCancha = $cap[$cid] ?? 0;
        $ocup = $capCancha > 0 ? min(100, (int) round($res / $capCancha * 100)) : 0;
        $result[] = [
            'CANCHA_ID'         => $cid,
            'CANCHA_NOMBRE'     => $r['CANCHA_NOMBRE'],
            'COMPLEJO_NOMBRE'   => $r['COMPLEJO_NOMBRE'],
            'TIPO_CANCHA_ICONO' => $r['TIPO_CANCHA_ICONO'],
            'reservas'          => $res,
            'ingresos'          => round((float)$r['ingresos'], 2),
            'cobrado'           => round($cob[$cid] ?? 0.0, 2),
            'ocupacion_pct'     => $ocup,
        ];
    }

    resp(true, '', $result);
}

resp(false, 'Acción no reconocida.');
