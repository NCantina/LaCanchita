<?php
session_start();
require_once '../../../config/dist/script/php/conn.php';
require_once '../../../config/dist/script/php/tenancy.php';
require_perfil(2);

$formato = $_GET['formato'] ?? 'excel'; // excel | pdf
$periodo = $_GET['periodo'] ?? 'mes';

// ── Rango de fechas ────────────────────────────────────────────────
function get_rango() {
    $p = $_GET['periodo'] ?? 'mes';
    switch ($p) {
        case 'hoy':    return [date('Y-m-d'), date('Y-m-d')];
        case 'semana': return [date('Y-m-d', strtotime('monday this week')), date('Y-m-d', strtotime('sunday this week'))];
        case 'mes':    return [date('Y-m-01'), date('Y-m-t')];
        case 'año':    return [date('Y-01-01'), date('Y-12-31')];
        case 'custom':
            return [$_GET['desde'] ?? date('Y-m-01'), $_GET['hasta'] ?? date('Y-m-d')];
        default:       return [date('Y-m-01'), date('Y-m-t')];
    }
}

function e_g($link, $v) { return mysqli_real_escape_string($link, trim($v ?? '')); }

[$desde, $hasta] = get_rango();
$scope = tenant_where(tenant_complejo_ids($link), 'co.COMPLEJO_ID');
$eDesde = e_g($link, $desde);
$eHasta = e_g($link, $hasta);

$dias_dow   = [1=>'Domingo', 2=>'Lunes', 3=>'Martes', 4=>'Miércoles', 5=>'Jueves', 6=>'Viernes', 7=>'Sábado'];
$dias_abrev = ['Mon'=>'Lun','Tue'=>'Mar','Wed'=>'Mié','Thu'=>'Jue','Fri'=>'Vie','Sat'=>'Sáb','Sun'=>'Dom'];

// ── Etiqueta del período para el título ───────────────────────────
$label_periodo = [
    'hoy'=>'Hoy', 'semana'=>'Esta semana', 'mes'=>'Este mes',
    'año'=>'Este año', 'custom'=> "$desde al $hasta",
][$periodo] ?? 'Período';

// ══════════════════════════════════════════════════════════════════
//  DATOS: Resumen
// ══════════════════════════════════════════════════════════════════
$q = mysqli_query($link,
    "SELECT
        COUNT(*) AS reservas_total,
        SUM(r.RESERVA_ESTADO='confirmada') AS res_confirmadas,
        SUM(r.RESERVA_ESTADO='cancelada')  AS res_canceladas,
        SUM(r.RESERVA_ESTADO='pendiente')  AS res_pendientes,
        COALESCE(SUM(r.RESERVA_PRECIO),0)  AS ingresos_total,
        COALESCE(AVG(r.RESERVA_PRECIO),0)  AS ticket_promedio
     FROM reserva r
     JOIN cancha ca  ON ca.CANCHA_ID   = r.CANCHA_ID
     JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
     WHERE r.RESERVA_FECHA BETWEEN '$eDesde' AND '$eHasta'
       AND r.ACTIVO=1 AND $scope");
$res = mysqli_fetch_assoc($q);

$qc = mysqli_query($link,
    "SELECT COALESCE(SUM(p.PAGO_MONTO),0) AS cobrado
     FROM pago p
     JOIN reserva r  ON r.RESERVA_ID  = p.RESERVA_ID
     JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
     JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
     WHERE DATE(p.PAGO_FECHA) BETWEEN '$eDesde' AND '$eHasta'
       AND p.ACTIVO=1 AND $scope");
$cobrado = (float)mysqli_fetch_assoc($qc)['cobrado'];

$ingresos_total  = (float)($res['ingresos_total'] ?? 0);
$saldo_pendiente = max(0, $ingresos_total - $cobrado);

$qcan = mysqli_query($link,
    "SELECT ca.CANCHA_NOMBRE, COUNT(*) AS cnt
     FROM reserva r
     JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
     JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
     WHERE r.RESERVA_FECHA BETWEEN '$eDesde' AND '$eHasta'
       AND r.ACTIVO=1 AND r.RESERVA_ESTADO='confirmada' AND $scope
     GROUP BY ca.CANCHA_ID ORDER BY cnt DESC LIMIT 1");
$cancha_top = ($rcan = mysqli_fetch_assoc($qcan)) ? $rcan['CANCHA_NOMBRE'] : '—';

$qdow = mysqli_query($link,
    "SELECT DAYOFWEEK(r.RESERVA_FECHA) AS dow, COUNT(*) AS cnt
     FROM reserva r
     JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
     JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
     WHERE r.RESERVA_FECHA BETWEEN '$eDesde' AND '$eHasta'
       AND r.ACTIVO=1 AND r.RESERVA_ESTADO='confirmada' AND $scope
     GROUP BY dow ORDER BY cnt DESC LIMIT 1");
$dia_top = ($rdow = mysqli_fetch_assoc($qdow)) ? ($dias_dow[(int)$rdow['dow']] ?? '—') : '—';

// ══════════════════════════════════════════════════════════════════
//  DATOS: Por día
// ══════════════════════════════════════════════════════════════════
$qr = mysqli_query($link,
    "SELECT r.RESERVA_FECHA AS fecha, COUNT(*) AS reservas, SUM(r.RESERVA_PRECIO) AS ingresos
     FROM reserva r
     JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
     JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
     WHERE r.RESERVA_FECHA BETWEEN '$eDesde' AND '$eHasta'
       AND r.ACTIVO=1 AND r.RESERVA_ESTADO IN ('confirmada','pendiente') AND $scope
     GROUP BY r.RESERVA_FECHA ORDER BY r.RESERVA_FECHA ASC");
$res_dia = [];
while ($r = mysqli_fetch_assoc($qr)) $res_dia[$r['fecha']] = $r;

$qp = mysqli_query($link,
    "SELECT DATE(p.PAGO_FECHA) AS fecha, SUM(p.PAGO_MONTO) AS cobrado
     FROM pago p
     JOIN reserva r  ON r.RESERVA_ID  = p.RESERVA_ID
     JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
     JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
     WHERE DATE(p.PAGO_FECHA) BETWEEN '$eDesde' AND '$eHasta'
       AND p.ACTIVO=1 AND $scope
     GROUP BY DATE(p.PAGO_FECHA)");
$cob_dia = [];
while ($r = mysqli_fetch_assoc($qp)) $cob_dia[$r['fecha']] = (float)$r['cobrado'];

$filas_dia = [];
$cur = strtotime($desde);
$fin = strtotime($hasta);
while ($cur <= $fin) {
    $f = date('Y-m-d', $cur);
    $eng = date('D', $cur);
    $filas_dia[] = [
        'fecha'    => $f,
        'dia'      => $dias_abrev[$eng] ?? $eng,
        'reservas' => (int)($res_dia[$f]['reservas'] ?? 0),
        'ingresos' => (float)($res_dia[$f]['ingresos'] ?? 0),
        'cobrado'  => $cob_dia[$f] ?? 0.0,
    ];
    $cur = strtotime('+1 day', $cur);
}

// ══════════════════════════════════════════════════════════════════
//  DATOS: Por cancha
// ══════════════════════════════════════════════════════════════════
$dias_periodo = max(1, (int)ceil((strtotime($hasta) - strtotime($desde)) / 86400) + 1);
$franjas_max  = $dias_periodo * 9;

$qrc = mysqli_query($link,
    "SELECT ca.CANCHA_ID, ca.CANCHA_NOMBRE, co.COMPLEJO_NOMBRE,
            COUNT(r.RESERVA_ID) AS reservas,
            COALESCE(SUM(r.RESERVA_PRECIO),0) AS ingresos
     FROM cancha ca
     JOIN complejo co  ON co.COMPLEJO_ID   = ca.COMPLEJO_ID
     LEFT JOIN reserva r ON r.CANCHA_ID = ca.CANCHA_ID
         AND r.RESERVA_FECHA BETWEEN '$eDesde' AND '$eHasta'
         AND r.ACTIVO=1 AND r.RESERVA_ESTADO IN ('confirmada','pendiente')
     WHERE ca.ACTIVO=1 AND $scope
     GROUP BY ca.CANCHA_ID ORDER BY reservas DESC, ingresos DESC");

$qpc = mysqli_query($link,
    "SELECT ca.CANCHA_ID, COALESCE(SUM(p.PAGO_MONTO),0) AS cobrado
     FROM pago p
     JOIN reserva r  ON r.RESERVA_ID  = p.RESERVA_ID
     JOIN cancha ca  ON ca.CANCHA_ID  = r.CANCHA_ID
     JOIN complejo co ON co.COMPLEJO_ID = ca.COMPLEJO_ID
     WHERE DATE(p.PAGO_FECHA) BETWEEN '$eDesde' AND '$eHasta'
       AND p.ACTIVO=1 AND $scope
     GROUP BY ca.CANCHA_ID");
$cob_cancha = [];
while ($r = mysqli_fetch_assoc($qpc)) $cob_cancha[(int)$r['CANCHA_ID']] = (float)$r['cobrado'];

$filas_cancha = [];
while ($r = mysqli_fetch_assoc($qrc)) {
    $cid  = (int)$r['CANCHA_ID'];
    $res2 = (int)$r['reservas'];
    $ocup = $franjas_max > 0 ? min(100, (int)round($res2 / $franjas_max * 100)) : 0;
    $filas_cancha[] = [
        'cancha'   => $r['CANCHA_NOMBRE'],
        'complejo' => $r['COMPLEJO_NOMBRE'],
        'reservas' => $res2,
        'ingresos' => round((float)$r['ingresos'], 2),
        'cobrado'  => round($cob_cancha[$cid] ?? 0.0, 2),
        'ocupacion'=> $ocup,
    ];
}

$fmt_ar = fn($n) => '$' . number_format((float)$n, 2, ',', '.');
$nombre_archivo = 'Reporte_LaCanchita_' . str_replace(['/', ' '], '-', $label_periodo);

// ══════════════════════════════════════════════════════════════════
//  EXPORTAR: EXCEL (XML Spreadsheet 2003)
// ══════════════════════════════════════════════════════════════════
if ($formato === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $nombre_archivo . '.xls"');
    header('Cache-Control: max-age=0');

    function xml_str($v) {
        return htmlspecialchars((string)$v, ENT_XML1, 'UTF-8');
    }
    function xls_cell_str($v) {
        return '<Cell><Data ss:Type="String">' . xml_str($v) . '</Data></Cell>';
    }
    function xls_cell_num($v) {
        return '<Cell><Data ss:Type="Number">' . (float)$v . '</Data></Cell>';
    }
    function xls_cell_head($v) {
        return '<Cell ss:StyleID="head"><Data ss:Type="String">' . xml_str($v) . '</Data></Cell>';
    }
    function xls_cell_title($v) {
        return '<Cell ss:StyleID="title"><Data ss:Type="String">' . xml_str($v) . '</Data></Cell>';
    }

    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
?>
<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet"
          xmlns:x="urn:schemas-microsoft-com:office:excel">
  <Styles>
    <Style ss:ID="head">
      <Font ss:Bold="1"/>
      <Interior ss:Color="#1a1a2e" ss:Pattern="Solid"/>
      <Font ss:Color="#4cd964" ss:Bold="1"/>
    </Style>
    <Style ss:ID="title">
      <Font ss:Bold="1" ss:Size="14" ss:Color="#000000"/>
    </Style>
    <Style ss:ID="sub">
      <Font ss:Italic="1" ss:Color="#555555"/>
    </Style>
    <Style ss:ID="money">
      <NumberFormat ss:Format="#,##0.00"/>
    </Style>
    <Style ss:ID="pct">
      <NumberFormat ss:Format="0\%"/>
    </Style>
  </Styles>

  <!-- ═══════ HOJA 1: RESUMEN ═══════ -->
  <Worksheet ss:Name="Resumen">
    <Table>
      <Row><?= xls_cell_title('Reporte LaCanchita — ' . xml_str($label_periodo)) ?></Row>
      <Row><?= xls_cell_str('Generado: ' . date('d/m/Y H:i')) ?></Row>
      <Row></Row>
      <Row>
        <?= xls_cell_head('Concepto') ?>
        <?= xls_cell_head('Valor') ?>
      </Row>
      <Row><?= xls_cell_str('Cobrado') ?><?= xls_cell_num($cobrado) ?></Row>
      <Row><?= xls_cell_str('Saldo pendiente') ?><?= xls_cell_num($saldo_pendiente) ?></Row>
      <Row><?= xls_cell_str('Ingresos esperados') ?><?= xls_cell_num($ingresos_total) ?></Row>
      <Row><?= xls_cell_str('Reservas totales') ?><?= xls_cell_num($res['reservas_total'] ?? 0) ?></Row>
      <Row><?= xls_cell_str('Confirmadas') ?><?= xls_cell_num($res['res_confirmadas'] ?? 0) ?></Row>
      <Row><?= xls_cell_str('Canceladas') ?><?= xls_cell_num($res['res_canceladas'] ?? 0) ?></Row>
      <Row><?= xls_cell_str('Pendientes') ?><?= xls_cell_num($res['res_pendientes'] ?? 0) ?></Row>
      <Row><?= xls_cell_str('Ticket promedio') ?><?= xls_cell_num(round((float)($res['ticket_promedio']??0),2)) ?></Row>
      <Row><?= xls_cell_str('Cancha top') ?><?= xls_cell_str($cancha_top) ?></Row>
      <Row><?= xls_cell_str('Día más activo') ?><?= xls_cell_str($dia_top) ?></Row>
    </Table>
  </Worksheet>

  <!-- ═══════ HOJA 2: POR DÍA ═══════ -->
  <Worksheet ss:Name="Por día">
    <Table>
      <Row>
        <?= xls_cell_head('Fecha') ?>
        <?= xls_cell_head('Día') ?>
        <?= xls_cell_head('Reservas') ?>
        <?= xls_cell_head('Ingresos esperados ($)') ?>
        <?= xls_cell_head('Cobrado ($)') ?>
      </Row>
      <?php foreach ($filas_dia as $fd): ?>
      <Row>
        <?= xls_cell_str($fd['fecha']) ?>
        <?= xls_cell_str($fd['dia']) ?>
        <?= xls_cell_num($fd['reservas']) ?>
        <?= xls_cell_num($fd['ingresos']) ?>
        <?= xls_cell_num($fd['cobrado']) ?>
      </Row>
      <?php endforeach; ?>
    </Table>
  </Worksheet>

  <!-- ═══════ HOJA 3: CANCHAS ═══════ -->
  <Worksheet ss:Name="Canchas">
    <Table>
      <Row>
        <?= xls_cell_head('Cancha') ?>
        <?= xls_cell_head('Complejo') ?>
        <?= xls_cell_head('Reservas') ?>
        <?= xls_cell_head('Ingresos esperados ($)') ?>
        <?= xls_cell_head('Cobrado ($)') ?>
        <?= xls_cell_head('Ocupación (%)') ?>
      </Row>
      <?php foreach ($filas_cancha as $fc): ?>
      <Row>
        <?= xls_cell_str($fc['cancha']) ?>
        <?= xls_cell_str($fc['complejo']) ?>
        <?= xls_cell_num($fc['reservas']) ?>
        <?= xls_cell_num($fc['ingresos']) ?>
        <?= xls_cell_num($fc['cobrado']) ?>
        <?= xls_cell_num($fc['ocupacion']) ?>
      </Row>
      <?php endforeach; ?>
    </Table>
  </Worksheet>

</Workbook>
<?php
    exit;
}

// ══════════════════════════════════════════════════════════════════
//  EXPORTAR: PDF (página HTML con auto-print)
// ══════════════════════════════════════════════════════════════════
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Reporte — <?= htmlspecialchars($label_periodo) ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; font-size:12px; color:#1a1a1a; background:#fff; padding:30px 40px; }

  .cover { text-align:center; margin-bottom:36px; padding-bottom:20px; border-bottom:3px solid #4cd964; }
  .cover .logo { font-size:28px; font-weight:800; letter-spacing:-0.5px; color:#0d0d0d; }
  .cover .logo span { color:#4cd964; }
  .cover .sub { color:#555; margin-top:4px; font-size:13px; }
  .cover .periodo { display:inline-block; margin-top:10px; background:#f0fdf4; color:#15803d; padding:4px 16px; border-radius:20px; font-weight:600; font-size:13px; }

  .kpi-grid { display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; margin-bottom:28px; }
  .kpi-box { border:1px solid #e0e0e0; border-radius:10px; padding:14px 16px; text-align:center; }
  .kpi-box .val { font-size:18px; font-weight:700; color:#0d0d0d; margin-bottom:2px; }
  .kpi-box .lbl { font-size:10px; text-transform:uppercase; letter-spacing:.05em; color:#666; }
  .kpi-box.green { border-color:#4cd964; background:#f0fdf4; }
  .kpi-box.green .val { color:#15803d; }
  .kpi-box.blue .val  { color:#1d4ed8; }
  .kpi-box.orange .val { color:#c2410c; }
  .kpi-box.purple .val { color:#7c3aed; }

  h2 { font-size:13px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#333; margin-bottom:10px; padding-bottom:5px; border-bottom:1px solid #e5e5e5; }

  table { width:100%; border-collapse:collapse; margin-bottom:28px; font-size:11.5px; }
  thead tr { background:#0d0d0d; color:#fff; }
  thead th { padding:8px 10px; text-align:left; font-weight:600; font-size:10.5px; letter-spacing:.04em; }
  thead th.num { text-align:right; }
  tbody tr:nth-child(even) { background:#f9f9f9; }
  tbody td { padding:7px 10px; border-bottom:1px solid #efefef; }
  tbody td.num { text-align:right; font-variant-numeric: tabular-nums; }
  tbody td.green { color:#15803d; font-weight:600; }
  tbody td.red   { color:#c0392b; }
  .bar-wrap { background:#e8e8e8; border-radius:4px; height:8px; width:100%; overflow:hidden; }
  .bar-fill  { background:#4cd964; height:8px; border-radius:4px; }

  .footer { margin-top:32px; padding-top:12px; border-top:1px solid #e0e0e0; text-align:center; color:#aaa; font-size:10px; }

  @media print {
    body { padding:15mm 20mm; }
    .no-print { display:none !important; }
    thead tr { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .kpi-box.green { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    .bar-fill { -webkit-print-color-adjust:exact; print-color-adjust:exact; }
    h2 { page-break-after:avoid; }
    table { page-break-inside:auto; }
    tr { page-break-inside:avoid; }
  }
</style>
</head>
<body>

<!-- Botones de acción (solo en pantalla) -->
<div class="no-print" style="text-align:right;margin-bottom:20px;display:flex;gap:10px;justify-content:flex-end">
  <button onclick="window.print()" style="background:#0d0d0d;color:#fff;border:none;border-radius:8px;padding:9px 20px;cursor:pointer;font-size:13px;font-weight:600">
    🖨 Imprimir / Guardar PDF
  </button>
  <button onclick="window.close()" style="background:#f0f0f0;color:#333;border:none;border-radius:8px;padding:9px 16px;cursor:pointer;font-size:13px">
    ✕ Cerrar
  </button>
</div>

<!-- Portada -->
<div class="cover">
  <div class="logo">La<span>Canchita</span></div>
  <div class="sub">Reporte de gestión</div>
  <div class="periodo"><?= htmlspecialchars($label_periodo) ?></div>
  <div style="color:#888;font-size:11px;margin-top:8px">Generado el <?= date('d/m/Y \a \l\a\s H:i') ?></div>
</div>

<!-- KPIs -->
<div class="kpi-grid">
  <div class="kpi-box green">
    <div class="val"><?= $fmt_ar($cobrado) ?></div>
    <div class="lbl">Cobrado</div>
  </div>
  <div class="kpi-box blue">
    <div class="val"><?= $fmt_ar($saldo_pendiente) ?></div>
    <div class="lbl">Saldo pendiente</div>
  </div>
  <div class="kpi-box">
    <div class="val"><?= (int)($res['reservas_total'] ?? 0) ?></div>
    <div class="lbl">Reservas totales</div>
  </div>
  <div class="kpi-box orange">
    <div class="val"><?= $fmt_ar($res['ticket_promedio'] ?? 0) ?></div>
    <div class="lbl">Ticket promedio</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:28px;font-size:12px">
  <div style="border:1px solid #e0e0e0;border-radius:8px;padding:12px">
    <div style="color:#888;font-size:10px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Estado de reservas</div>
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f0f0f0"><span>Confirmadas</span><strong style="color:#15803d"><?= (int)($res['res_confirmadas']??0) ?></strong></div>
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f0f0f0"><span>Pendientes</span><strong style="color:#c2410c"><?= (int)($res['res_pendientes']??0) ?></strong></div>
    <div style="display:flex;justify-content:space-between;padding:5px 0"><span>Canceladas</span><strong style="color:#aaa"><?= (int)($res['res_canceladas']??0) ?></strong></div>
  </div>
  <div style="border:1px solid #e0e0e0;border-radius:8px;padding:12px">
    <div style="color:#888;font-size:10px;text-transform:uppercase;letter-spacing:.05em;margin-bottom:6px">Destacados del período</div>
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f0f0f0"><span>🏆 Cancha top</span><strong><?= htmlspecialchars($cancha_top) ?></strong></div>
    <div style="display:flex;justify-content:space-between;padding:5px 0;border-bottom:1px solid #f0f0f0"><span>📅 Día más activo</span><strong><?= htmlspecialchars($dia_top) ?></strong></div>
    <div style="display:flex;justify-content:space-between;padding:5px 0"><span>💰 Ingresos esperados</span><strong><?= $fmt_ar($ingresos_total) ?></strong></div>
  </div>
</div>

<!-- Tabla por día -->
<h2>Detalle por día</h2>
<table>
  <thead>
    <tr>
      <th>Fecha</th>
      <th>Día</th>
      <th class="num">Reservas</th>
      <th class="num">Ingresos esperados</th>
      <th class="num">Cobrado</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($filas_dia as $fd):
      $tiene = $fd['reservas'] > 0;
    ?>
    <tr>
      <td><?= $fd['fecha'] ?></td>
      <td><?= $fd['dia'] ?></td>
      <td class="num"><?= $tiene ? $fd['reservas'] : '<span style="color:#ccc">—</span>' ?></td>
      <td class="num <?= $tiene ? '' : '' ?>"><?= $tiene ? $fmt_ar($fd['ingresos']) : '<span style="color:#ccc">—</span>' ?></td>
      <td class="num <?= $fd['cobrado'] > 0 ? 'green' : '' ?>"><?= $fd['cobrado'] > 0 ? $fmt_ar($fd['cobrado']) : '<span style="color:#ccc">—</span>' ?></td>
    </tr>
    <?php endforeach; ?>
    <tr style="font-weight:700;background:#f0fdf4">
      <td colspan="2">TOTAL</td>
      <td class="num"><?= array_sum(array_column($filas_dia,'reservas')) ?></td>
      <td class="num"><?= $fmt_ar(array_sum(array_column($filas_dia,'ingresos'))) ?></td>
      <td class="num green"><?= $fmt_ar(array_sum(array_column($filas_dia,'cobrado'))) ?></td>
    </tr>
  </tbody>
</table>

<!-- Tabla por cancha -->
<h2>Rendimiento por cancha</h2>
<table>
  <thead>
    <tr>
      <th>Cancha</th>
      <th>Complejo</th>
      <th class="num">Reservas</th>
      <th class="num">Ingresos esp.</th>
      <th class="num">Cobrado</th>
      <th style="width:120px">Ocupación</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($filas_cancha as $fc): ?>
    <tr>
      <td><strong><?= htmlspecialchars($fc['cancha']) ?></strong></td>
      <td style="color:#666"><?= htmlspecialchars($fc['complejo']) ?></td>
      <td class="num"><?= $fc['reservas'] ?></td>
      <td class="num"><?= $fmt_ar($fc['ingresos']) ?></td>
      <td class="num <?= $fc['cobrado'] > 0 ? 'green' : '' ?>"><?= $fmt_ar($fc['cobrado']) ?></td>
      <td>
        <div style="display:flex;align-items:center;gap:6px">
          <div class="bar-wrap" style="flex:1">
            <div class="bar-fill" style="width:<?= $fc['ocupacion'] ?>%"></div>
          </div>
          <span style="font-size:10px;color:#555;width:28px;text-align:right"><?= $fc['ocupacion'] ?>%</span>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<div class="footer">
  LaCanchita · Reporte generado el <?= date('d/m/Y') ?> · Solo para uso interno
</div>

<script>
  // Disparar impresión automática solo cuando se abre para PDF
  if (window.opener || window.name === 'rep_print') {
    window.addEventListener('load', () => setTimeout(() => window.print(), 400));
  }
</script>
</body>
</html>
<?php
