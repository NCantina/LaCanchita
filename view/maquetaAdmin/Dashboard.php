<?php
session_start();
require_once '../../config/dist/script/php/conn.php';
require_once '../../config/dist/script/php/tenancy.php';

// Perfiles 1-4 pueden entrar. Clientes (5+) redireccionan al panel cliente.
if (!isset($_SESSION['usuario_perfil']) || (int)$_SESSION['usuario_perfil'] === 0) {
    header('Location: ../../login.php'); exit;
}
if ((int)$_SESSION['usuario_perfil'] >= 5) {
    header('Location: ../maquetaCliente/LaCanchitaCliente.php'); exit;
}

$nombre = $_SESSION['usuario_nombre'] ?? 'Admin';
$perfil = (int)($_SESSION['usuario_perfil'] ?? 1);
$uid    = (int)($_SESSION['usuario_id']    ?? 0);

// Onboarding: dueño nuevo sin complejos → wizard de configuración inicial
if ($perfil === 2 && empty($_SESSION['onboarding_skip']) && !isset($_GET['skip_onboarding'])) {
    $nComp = (int)(mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) AS n FROM complejo WHERE USUARIOS_ID=$uid AND ACTIVO=1"
    ))['n'] ?? 0);
    if ($nComp === 0) { header('Location: Onboarding.php'); exit; }
}
if (isset($_GET['skip_onboarding'])) $_SESSION['onboarding_skip'] = true;
$hora   = (int)date('H');
$saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

// ── KPIs con scoping por tenant ───────────────────────────────────────────
// Construir filtro de complejos según perfil
$kpi = [];
if ($perfil === 1) {
    // SuperAdmin: todo el sistema
    $cmpWhere = '1=1';
    $canWhere = '1=1';
    $resWhere = '1=1';
    $pagoWhere = '1=1';
} elseif ($perfil === 2) {
    // Dueño: solo sus complejos
    $cmpWhere  = "co.USUARIOS_ID = $uid";
    $canWhere  = "co.USUARIOS_ID = $uid";
    $resWhere  = "co.USUARIOS_ID = $uid";
    $pagoWhere = "co.USUARIOS_ID = $uid";
} else {
    // Staff (3/4): solo canchas asignadas
    $cmpWhere  = "ce.USUARIOS_ID = $uid";
    $canWhere  = "ce.USUARIOS_ID = $uid";
    $resWhere  = "ce.USUARIOS_ID = $uid";
    $pagoWhere = "ce.USUARIOS_ID = $uid";
}

if ($perfil <= 2) {
    $kpi['complejos']    = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM complejo co WHERE co.ACTIVO=1 AND $cmpWhere"))['c'];
    $kpi['canchas']      = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM cancha ca JOIN complejo co ON co.COMPLEJO_ID=ca.COMPLEJO_ID WHERE ca.ACTIVO=1 AND $canWhere"))['c'];
    $kpi['reservas_hoy'] = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM reserva r
         JOIN cancha ca ON ca.CANCHA_ID=r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID=ca.COMPLEJO_ID
         WHERE r.RESERVA_FECHA=CURDATE() AND r.ACTIVO=1 AND $resWhere"))['c'];
    $kpi['ingresos_hoy'] = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COALESCE(SUM(p.PAGO_MONTO),0) c FROM pago p
         JOIN reserva r ON r.RESERVA_ID=p.RESERVA_ID
         JOIN cancha ca ON ca.CANCHA_ID=r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID=ca.COMPLEJO_ID
         WHERE DATE(p.PAGO_FECHA)=CURDATE() AND p.ACTIVO=1 AND $pagoWhere"))['c'];
    $kpi['reservas_mes'] = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM reserva r
         JOIN cancha ca ON ca.CANCHA_ID=r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID=ca.COMPLEJO_ID
         WHERE MONTH(r.RESERVA_FECHA)=MONTH(NOW()) AND YEAR(r.RESERVA_FECHA)=YEAR(NOW()) AND r.ACTIVO=1 AND $resWhere"))['c'];
    $kpi['ingresos_mes'] = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COALESCE(SUM(p.PAGO_MONTO),0) c FROM pago p
         JOIN reserva r ON r.RESERVA_ID=p.RESERVA_ID
         JOIN cancha ca ON ca.CANCHA_ID=r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID=ca.COMPLEJO_ID
         WHERE MONTH(p.PAGO_FECHA)=MONTH(NOW()) AND YEAR(p.PAGO_FECHA)=YEAR(NOW()) AND p.ACTIVO=1 AND $pagoWhere"))['c'];
} else {
    // Staff: KPIs simplificados (solo reservas de sus canchas)
    $kpi['complejos']    = 0;
    $kpi['canchas']      = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(DISTINCT ce.CANCHA_ID) c FROM cancha_encargado ce WHERE ce.USUARIOS_ID=$uid AND ce.ACTIVO=1"))['c'];
    $kpi['reservas_hoy'] = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM reserva r
         JOIN cancha_encargado ce ON ce.CANCHA_ID=r.CANCHA_ID AND ce.USUARIOS_ID=$uid AND ce.ACTIVO=1
         WHERE r.RESERVA_FECHA=CURDATE() AND r.ACTIVO=1"))['c'];
    $kpi['ingresos_hoy'] = 0;
    $kpi['reservas_mes'] = 0;
    $kpi['ingresos_mes'] = 0;
}

// Reservas pendientes de hoy (para badge en topbar)
$kpi['reservas_pend_hoy'] = 0;
if ($perfil <= 2) {
    $kpi['reservas_pend_hoy'] = (int)(mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM reserva r
         JOIN cancha ca ON ca.CANCHA_ID=r.CANCHA_ID
         JOIN complejo co ON co.COMPLEJO_ID=ca.COMPLEJO_ID
         WHERE r.RESERVA_FECHA=CURDATE() AND r.RESERVA_ESTADO='pendiente' AND r.ACTIVO=1 AND $resWhere"))['c'] ?? 0);
} elseif ($perfil <= 4) {
    $kpi['reservas_pend_hoy'] = (int)(mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM reserva r
         JOIN cancha_encargado ce ON ce.CANCHA_ID=r.CANCHA_ID AND ce.USUARIOS_ID=$uid AND ce.ACTIVO=1
         WHERE r.RESERVA_FECHA=CURDATE() AND r.RESERVA_ESTADO='pendiente' AND r.ACTIVO=1"))['c'] ?? 0);
}

// Solo superadmin ve usuarios pendientes
$kpi['usuarios_total'] = $perfil === 1
    ? mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) c FROM usuarios WHERE ACTIVO=1"))['c'] : 0;
$kpi['usuarios_pend']  = $perfil === 1
    ? mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) c FROM usuarios WHERE ACTIVO=0"))['c'] : 0;

// Modo soporte: SuperAdmin gestionando en nombre de un dueño
$adminAsDueno       = ($perfil === 1) ? ((int)($_SESSION['admin_as_dueno'] ?? 0) ?: null) : null;
$adminAsDuenoNombre = ($perfil === 1) ? ($_SESSION['admin_as_dueno_nombre'] ?? '') : '';

// KPIs plataforma (solo SuperAdmin en modo plataforma)
if ($perfil === 1 && !$adminAsDueno) {
    $kpi['plataforma_duenos']    = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM usuarios WHERE PERFIL_ID=2 AND ACTIVO=1"))['c'];
    $kpi['plataforma_complejos'] = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM complejo WHERE ACTIVO=1"))['c'];
    $kpi['plataforma_res_hoy']   = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM reserva WHERE RESERVA_FECHA=CURDATE() AND ACTIVO=1"))['c'];
    $kpi['plataforma_ingresos_mes'] = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COALESCE(SUM(PAGO_MONTO),0) c FROM pago
         WHERE MONTH(PAGO_FECHA)=MONTH(NOW()) AND YEAR(PAGO_FECHA)=YEAR(NOW()) AND ACTIVO=1"))['c'];
    $kpi['plataforma_clientes_activos'] = mysqli_fetch_assoc(mysqli_query($link,
        "SELECT COUNT(*) c FROM usuarios WHERE PERFIL_ID=5 AND ACTIVO=1"))['c'];
    // Top 5 dueños más activos hoy
    $kpi['top_duenos'] = [];
    $qt = mysqli_query($link,
        "SELECT u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO,
                COUNT(r.RESERVA_ID) AS reservas_hoy,
                COALESCE(SUM(p.PAGO_MONTO),0) AS cobrado_hoy
         FROM usuarios u
         LEFT JOIN complejo co ON co.USUARIOS_ID=u.USUARIOS_ID
         LEFT JOIN cancha ca ON ca.COMPLEJO_ID=co.COMPLEJO_ID
         LEFT JOIN reserva r ON r.CANCHA_ID=ca.CANCHA_ID AND r.RESERVA_FECHA=CURDATE() AND r.ACTIVO=1
         LEFT JOIN pago p ON p.RESERVA_ID=r.RESERVA_ID AND DATE(p.PAGO_FECHA)=CURDATE() AND p.ACTIVO=1
         WHERE u.PERFIL_ID=2 AND u.ACTIVO=1
         GROUP BY u.USUARIOS_ID
         ORDER BY reservas_hoy DESC, cobrado_hoy DESC
         LIMIT 5");
    while ($r = mysqli_fetch_assoc($qt)) $kpi['top_duenos'][] = $r;
}

// Usuarios pendientes de aprobación (solo superadmin)
$pendientes = [];
if ($perfil === 1) {
    $pend_query = mysqli_query($link,
        "SELECT u.USUARIOS_ID, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO, u.USUARIOS_EMAIL,
                u.USUARIOS_DNI, u.USUARIOS_TELEFONO, p.PERFIL_NOMBRE
         FROM usuarios u JOIN perfil p ON u.PERFIL_ID = p.PERFIL_ID
         WHERE u.ACTIVO = 0 ORDER BY u.USUARIOS_ID DESC"
    );
    while ($r = mysqli_fetch_assoc($pend_query)) $pendientes[] = $r;
}

// Reservas de hoy para el dashboard (dueño, staff, y SA en modo soporte)
$dash_reservas_hoy = [];
if ($perfil >= 2) {
    $ids_hoy = tenant_complejo_ids($link);
    $scope_hoy = tenant_where($ids_hoy, 'co.COMPLEJO_ID');
    $join_staff_hoy = '';
    if ($perfil >= 3) {
        $join_staff_hoy = "JOIN cancha_encargado ce ON ce.CANCHA_ID=ca.CANCHA_ID AND ce.USUARIOS_ID=$uid AND ce.ACTIVO=1";
    }
    $q_hoy = mysqli_query($link,
        "SELECT r.RESERVA_ID, r.RESERVA_HORA_INICIO, r.RESERVA_HORA_FIN,
                r.RESERVA_PRECIO, r.RESERVA_ESTADO,
                ca.CANCHA_NOMBRE,
                COALESCE(tc.TIPO_CANCHA_ICONO,'fa-futbol') AS TIPO_CANCHA_ICONO,
                u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO, u.USUARIOS_TELEFONO,
                COALESCE(SUM(p.PAGO_MONTO),0) AS PAGADO,
                (r.RESERVA_PRECIO - COALESCE(SUM(p.PAGO_MONTO),0)) AS SALDO
         FROM reserva r
         JOIN cancha ca   ON ca.CANCHA_ID    = r.CANCHA_ID
         $join_staff_hoy
         JOIN complejo co ON co.COMPLEJO_ID  = ca.COMPLEJO_ID
         JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = ca.TIPO_CANCHA_ID
         JOIN usuarios u  ON u.USUARIOS_ID   = r.USUARIOS_ID
         LEFT JOIN pago p ON p.RESERVA_ID    = r.RESERVA_ID AND p.ACTIVO=1
         WHERE r.RESERVA_FECHA=CURDATE() AND r.ACTIVO=1 AND $scope_hoy
         GROUP BY r.RESERVA_ID
         ORDER BY r.RESERVA_HORA_INICIO ASC"
    );
    while ($r = mysqli_fetch_assoc($q_hoy)) $dash_reservas_hoy[] = $r;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Panel Admin · La Canchita</title>
    <?php $PWA_BASE = '../../'; require_once '../../config/dist/script/php/pwa_head.php'; ?>
    <link rel="shortcut icon" href="../../config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
    <link rel="stylesheet" href="../../config/pluggins/vendor/fontawesome-free/css/all.min.css">
    <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    :root {
        --green:      #4cd964;
        --green-d:    #34c759;
        --blue:       #3498db;
        --orange:     #ff9500;
        --red:        #e74c3c;
        --purple:     #9b59b6;
        --yellow:     #ffd60a;
        --bg:         #0a0a0a;
        --s1:         rgba(255,255,255,0.05);
        --s2:         rgba(255,255,255,0.09);
        --s3:         rgba(255,255,255,0.13);
        --border:     rgba(255,255,255,0.09);
        --text:       #f0f0f0;
        --muted:      rgba(255,255,255,0.42);
        --sidebar-w:  250px;
        --header-h:   58px;
        --radius:     12px;
        --radius-sm:  8px;
    }

    html, body { height: 100%; overflow: hidden; }

    body {
        font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        background: var(--bg);
        color: var(--text);
        display: flex;
        font-size: 13px;
        line-height: 1.5;
    }

    /* ── BG ── */
    .bg-layer {
        position: fixed; inset: 0; z-index: 0;
        background: radial-gradient(ellipse at 10% 20%, rgba(76,217,100,0.04) 0%, transparent 60%),
                    radial-gradient(ellipse at 90% 80%, rgba(52,152,219,0.03) 0%, transparent 60%);
        pointer-events: none;
    }

    /* ═══════════════ SIDEBAR ═══════════════ */
    .sidebar {
        position: fixed; left: 0; top: 0; bottom: 0;
        width: var(--sidebar-w);
        background: rgba(8,8,8,0.95);
        backdrop-filter: blur(24px);
        border-right: 1px solid var(--border);
        display: flex; flex-direction: column;
        z-index: 300;
        transition: transform .3s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }
    .sidebar.hidden { transform: translateX(calc(-1 * var(--sidebar-w))); }

    .sb-logo {
        padding: 18px 20px 14px;
        display: flex; align-items: center; gap: 11px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .sb-logo img { width: 36px; border-radius: 8px; }
    .sb-logo-text strong { display: block; font-size: 15px; font-weight: 800; letter-spacing: -.3px; }
    .sb-logo-text span   { font-size: 10px; color: var(--muted); letter-spacing: 1px; text-transform: uppercase; }
    .sb-badge-admin {
        margin-left: auto;
        padding: 3px 7px; border-radius: 5px;
        background: rgba(76,217,100,0.15);
        color: var(--green);
        font-size: 9px; font-weight: 800;
        letter-spacing: .5px;
        text-transform: uppercase;
    }

    .sb-user {
        padding: 13px 20px;
        display: flex; align-items: center; gap: 10px;
        border-bottom: 1px solid var(--border);
        flex-shrink: 0;
    }
    .sb-avatar {
        width: 35px; height: 35px; border-radius: 10px;
        background: linear-gradient(135deg, var(--green), var(--green-d));
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 14px; flex-shrink: 0;
        color: #fff;
    }
    .sb-user-info strong { display: block; font-size: 12px; font-weight: 700; }
    .sb-user-info span   { font-size: 11px; color: var(--muted); }

    .sb-nav { flex: 1; overflow-y: auto; padding: 10px 0; }
    .sb-nav::-webkit-scrollbar { width: 3px; }
    .sb-nav::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

    .sb-section {
        padding: 10px 20px 4px;
        font-size: 9px; font-weight: 700; letter-spacing: 1.5px;
        color: var(--muted); text-transform: uppercase;
    }

    .sb-item {
        display: flex; align-items: center; gap: 10px;
        padding: 9px 20px;
        color: var(--muted);
        text-decoration: none;
        font-size: 13px; font-weight: 500;
        border-left: 2px solid transparent;
        cursor: pointer;
        transition: all .18s;
        user-select: none;
        position: relative;
    }
    .sb-item:hover  { color: var(--text); background: var(--s1); }
    .sb-item.active { color: var(--green); border-left-color: var(--green); background: rgba(76,217,100,0.07); }
    .sb-item i      { width: 17px; text-align: center; font-size: 13px; flex-shrink: 0; }
    .sb-pill {
        margin-left: auto; min-width: 18px; height: 18px;
        padding: 0 5px;
        border-radius: 9px;
        background: var(--red);
        color: #fff; font-size: 9px; font-weight: 800;
        display: flex; align-items: center; justify-content: center;
    }
    .sb-pill.green { background: var(--green); color: #000; }

    .sb-footer {
        padding: 12px 20px;
        border-top: 1px solid var(--border);
        flex-shrink: 0;
        display: flex; flex-direction: column; gap: 6px;
    }
    .sb-footer-link {
        display: flex; align-items: center; gap: 8px;
        padding: 8px 12px; border-radius: 9px;
        font-size: 12px; font-weight: 500;
        text-decoration: none; cursor: pointer;
        transition: all .18s;
        border: 1px solid transparent;
    }
    .sb-footer-link.client { color: var(--muted); border-color: var(--border); background: var(--s1); }
    .sb-footer-link.client:hover { color: var(--text); background: var(--s2); }
    .sb-footer-link.logout { color: var(--red); border-color: rgba(231,76,60,.2); background: rgba(231,76,60,.06); }
    .sb-footer-link.logout:hover { background: rgba(231,76,60,.14); border-color: var(--red); }

    /* ═══════════════ MAIN ═══════════════ */
    .main {
        flex: 1;
        margin-left: var(--sidebar-w);
        display: flex; flex-direction: column;
        min-height: 100vh;
        position: relative; z-index: 1;
        transition: margin-left .3s cubic-bezier(.4,0,.2,1);
    }
    .main.expanded { margin-left: 0; }

    /* ── TOPBAR ── */
    .topbar {
        height: var(--header-h);
        background: rgba(8,8,8,0.88);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--border);
        display: flex; align-items: center; gap: 12px;
        padding: 0 20px;
        position: sticky; top: 0; z-index: 200;
        flex-shrink: 0;
    }
    .tb-toggle {
        width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center;
        background: var(--s1); border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        color: var(--muted); cursor: pointer;
        transition: all .18s; flex-shrink: 0;
    }
    .tb-toggle:hover { color: var(--text); background: var(--s2); }

    .tb-breadcrumb {
        display: flex; align-items: center; gap: 7px;
        font-size: 13px;
    }
    .tb-breadcrumb .root { color: var(--muted); }
    .tb-breadcrumb .sep  { color: rgba(255,255,255,.2); font-size: 11px; }
    .tb-breadcrumb .cur  { color: var(--text); font-weight: 600; }

    .tb-right { margin-left: auto; display: flex; align-items: center; gap: 8px; }

    .tb-btn {
        width: 34px; height: 34px;
        display: flex; align-items: center; justify-content: center;
        background: var(--s1); border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        color: var(--muted); text-decoration: none;
        cursor: pointer; transition: all .18s;
        position: relative;
    }
    .tb-btn:hover { color: var(--text); background: var(--s2); }
    .tb-dot {
        position: absolute; top: 7px; right: 7px;
        width: 6px; height: 6px;
        background: var(--red); border-radius: 50%;
        border: 1.5px solid var(--bg);
    }

    .tb-date {
        font-size: 11px; color: var(--muted);
        padding: 5px 10px;
        background: var(--s1); border: 1px solid var(--border);
        border-radius: var(--radius-sm);
    }

    /* ── CONTENT ── */
    .content {
        flex: 1; overflow-y: auto;
        padding: 22px;
        max-height: calc(100vh - var(--header-h));
    }
    .content::-webkit-scrollbar { width: 4px; }
    .content::-webkit-scrollbar-thumb { background: var(--border); border-radius: 2px; }

    /* ── VIEW ── */
    .view { display: none; animation: fadeUp .35s ease both; }
    .view.active { display: block; }
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(8px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    /* ── PAGE HEADER ── */
    .page-header {
        display: flex; align-items: flex-start; justify-content: space-between;
        margin-bottom: 22px; gap: 16px; flex-wrap: wrap;
    }
    .page-header-left h1 { font-size: 20px; font-weight: 800; letter-spacing: -.3px; margin-bottom: 3px; }
    .page-header-left p  { font-size: 12px; color: var(--muted); }
    .page-header-right { display: flex; gap: 8px; flex-shrink: 0; }

    /* ── KPI GRID ── */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
        gap: 12px; margin-bottom: 24px;
    }
    .kpi {
        background: var(--s1); border: 1px solid var(--border);
        border-radius: var(--radius); padding: 15px;
        display: flex; flex-direction: column; gap: 10px;
        transition: transform .2s, box-shadow .2s;
    }
    .kpi:hover { transform: translateY(-2px); box-shadow: 0 8px 28px rgba(0,0,0,.35); }
    .kpi-top { display: flex; align-items: center; justify-content: space-between; }
    .kpi-icon {
        width: 34px; height: 34px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center; font-size: 14px;
    }
    .kpi-icon.g  { background: rgba(76,217,100,.12); color: var(--green); }
    .kpi-icon.b  { background: rgba(52,152,219,.12); color: var(--blue); }
    .kpi-icon.o  { background: rgba(255,149,0,.12);  color: var(--orange); }
    .kpi-icon.r  { background: rgba(231,76,60,.12);  color: var(--red); }
    .kpi-icon.p  { background: rgba(155,89,182,.12); color: var(--purple); }
    .kpi-icon.y  { background: rgba(255,214,10,.12); color: var(--yellow); }
    .kpi-change  { font-size: 10px; }
    .kpi-change.up   { color: var(--green); }
    .kpi-change.warn { color: var(--orange); }
    .kpi-val  { font-size: 28px; font-weight: 800; line-height: 1; letter-spacing: -1px; }
    .kpi-lbl  { font-size: 11px; color: var(--muted); font-weight: 500; }

    /* ── SECTION CARD ── */
    .card {
        background: var(--s1); border: 1px solid var(--border);
        border-radius: var(--radius);
        overflow: hidden;
        margin-bottom: 18px;
    }
    .card-header {
        display: flex; align-items: center; gap: 12px;
        padding: 14px 18px;
        border-bottom: 1px solid var(--border);
        background: rgba(255,255,255,.02);
    }
    .card-header-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
    }
    .card-header h3 { font-size: 14px; font-weight: 700; }
    .card-header p  { font-size: 11px; color: var(--muted); margin-top: 1px; }
    .card-actions   { margin-left: auto; display: flex; gap: 8px; }

    .card-body { padding: 0; }
    .card-body-pad { padding: 18px; }

    /* ── TABS SECONDARY ── */
    .stabs { display: flex; border-bottom: 1px solid var(--border); }
    .stab {
        padding: 11px 18px;
        font-size: 12px; font-weight: 600;
        color: var(--muted);
        cursor: pointer; border-bottom: 2px solid transparent;
        transition: all .18s; user-select: none;
    }
    .stab:hover { color: var(--text); }
    .stab.active { color: var(--green); border-bottom-color: var(--green); }
    .stab-count {
        display: inline-flex; align-items: center; justify-content: center;
        min-width: 16px; height: 16px; padding: 0 4px;
        border-radius: 8px; background: var(--s2);
        font-size: 9px; font-weight: 800; margin-left: 5px;
        color: var(--muted);
    }
    .stab.active .stab-count { background: rgba(76,217,100,.15); color: var(--green); }

    .stab-content { display: none; }
    .stab-content.active { display: block; }

    /* ── DATA TABLE ── */
    .dt-toolbar {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px;
        border-bottom: 1px solid var(--border);
        flex-wrap: wrap;
    }
    .dt-search {
        display: flex; align-items: center; gap: 8px;
        background: var(--s2); border: 1px solid var(--border);
        border-radius: var(--radius-sm); padding: 7px 12px;
        flex: 1; min-width: 180px;
    }
    .dt-search i { color: var(--muted); font-size: 12px; flex-shrink: 0; }
    .dt-search input {
        background: none; border: none; outline: none;
        color: var(--text); font-size: 13px; width: 100%;
    }
    .dt-search input::placeholder { color: var(--muted); }

    .dt-filter {
        display: flex; gap: 5px;
    }
    .filter-btn {
        padding: 6px 12px; border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        background: var(--s1);
        color: var(--muted); font-size: 11px; font-weight: 600;
        cursor: pointer; transition: all .15s;
    }
    .filter-btn:hover { color: var(--text); background: var(--s2); }
    .filter-btn.active { background: rgba(76,217,100,.12); border-color: rgba(76,217,100,.3); color: var(--green); }

    table { width: 100%; border-collapse: collapse; }
    thead tr { border-bottom: 1px solid var(--border); }
    th {
        padding: 10px 16px;
        text-align: left; font-size: 10px; font-weight: 700;
        letter-spacing: .8px; text-transform: uppercase; color: var(--muted);
        white-space: nowrap;
    }
    td {
        padding: 13px 16px;
        border-bottom: 1px solid rgba(255,255,255,.04);
        font-size: 13px; vertical-align: middle;
    }
    tr:last-child td { border-bottom: none; }
    tbody tr { transition: background .15s; }
    tbody tr:hover td { background: rgba(255,255,255,.03); }

    .td-icon {
        width: 32px; height: 32px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px;
        background: var(--s2); color: var(--muted);
    }

    .badge {
        display: inline-flex; align-items: center; gap: 4px;
        padding: 3px 9px; border-radius: 20px;
        font-size: 10px; font-weight: 700;
    }
    .badge::before { content: ''; width: 5px; height: 5px; border-radius: 50%; background: currentColor; }
    .badge.active   { background: rgba(76,217,100,.12); color: var(--green);  border: 1px solid rgba(76,217,100,.2); }
    .badge.inactive { background: rgba(255,255,255,.06); color: var(--muted); border: 1px solid var(--border); }
    .badge.pending  { background: rgba(255,149,0,.12);  color: var(--orange); border: 1px solid rgba(255,149,0,.2); }

    .row-actions { display: flex; align-items: center; gap: 6px; }
    .act-btn {
        width: 30px; height: 30px;
        display: flex; align-items: center; justify-content: center;
        border-radius: var(--radius-sm);
        border: 1px solid var(--border);
        background: var(--s1); color: var(--muted);
        cursor: pointer; font-size: 12px;
        transition: all .15s;
    }
    .act-btn:hover       { background: var(--s2); color: var(--text); }
    .act-btn.edit:hover  { border-color: rgba(52,152,219,.4);  background: rgba(52,152,219,.1);  color: var(--blue); }
    .act-btn.toggle:hover.on { border-color: rgba(231,76,60,.4); background: rgba(231,76,60,.1); color: var(--red); }
    .act-btn.toggle:hover { border-color: rgba(76,217,100,.4); background: rgba(76,217,100,.1); color: var(--green); }

    .empty-state {
        text-align: center; padding: 48px 20px;
    }
    .empty-state .es-icon {
        width: 52px; height: 52px; border-radius: 14px;
        background: var(--s2); display: flex; align-items: center; justify-content: center;
        margin: 0 auto 14px; font-size: 22px; color: var(--muted);
    }
    .empty-state h4 { font-size: 14px; font-weight: 700; margin-bottom: 5px; }
    .empty-state p  { font-size: 12px; color: var(--muted); }

    /* ── USUARIOS PENDIENTES ── */
    .pend-row td { padding: 12px 16px; }
    .user-cell { display: flex; align-items: center; gap: 10px; }
    .user-av {
        width: 32px; height: 32px; border-radius: 9px;
        background: linear-gradient(135deg, var(--blue), #2980b9);
        display: flex; align-items: center; justify-content: center;
        font-size: 12px; font-weight: 800; color: #fff; flex-shrink: 0;
    }
    .user-name  { font-size: 13px; font-weight: 600; }
    .user-email { font-size: 11px; color: var(--muted); }

    /* ── BOTONES ── */
    .btn {
        display: inline-flex; align-items: center; gap: 7px;
        padding: 8px 16px; border-radius: var(--radius-sm);
        font-size: 12px; font-weight: 700;
        cursor: pointer; transition: all .18s;
        border: none; text-decoration: none; white-space: nowrap;
    }
    .btn-primary {
        background: linear-gradient(135deg, var(--green), var(--green-d));
        color: #fff;
    }
    .btn-primary:hover { opacity: .88; transform: translateY(-1px); box-shadow: 0 5px 18px rgba(76,217,100,.3); }

    .btn-ghost {
        background: var(--s1); border: 1px solid var(--border); color: var(--muted);
    }
    .btn-ghost:hover { background: var(--s2); color: var(--text); }

    .btn-danger {
        background: rgba(231,76,60,.1); border: 1px solid rgba(231,76,60,.25); color: var(--red);
    }
    .btn-danger:hover { background: rgba(231,76,60,.2); }

    .btn-sm { padding: 6px 12px; font-size: 11px; }
    .btn-approve { background: rgba(76,217,100,.12); border: 1px solid rgba(76,217,100,.25); color: var(--green); }
    .btn-approve:hover { background: rgba(76,217,100,.22); }

    /* ── MODAL ── */
    .modal-overlay {
        display: none; position: fixed; inset: 0; z-index: 500;
        background: rgba(0,0,0,.75);
        backdrop-filter: blur(6px);
        align-items: center; justify-content: center;
        padding: 16px;
    }
    .modal-overlay.show { display: flex; }

    .modal {
        background: #111;
        border: 1px solid var(--border);
        border-radius: 16px;
        width: 100%; max-width: 440px;
        animation: modalIn .25s cubic-bezier(.4,0,.2,1);
        overflow: hidden;
    }
    .modal-lg { max-width: 560px; }
    @keyframes modalIn {
        from { opacity:0; transform:translateY(16px) scale(.98); }
        to   { opacity:1; transform:translateY(0)   scale(1); }
    }
    .modal-head {
        display: flex; align-items: center; gap: 12px;
        padding: 18px 20px; border-bottom: 1px solid var(--border);
        background: rgba(255,255,255,.02);
    }
    .modal-head-icon {
        width: 36px; height: 36px; border-radius: 9px;
        display: flex; align-items: center; justify-content: center;
        font-size: 15px; flex-shrink: 0;
    }
    .modal-head-icon.g { background: rgba(76,217,100,.15); color: var(--green); }
    .modal-head-icon.b { background: rgba(52,152,219,.15); color: var(--blue); }
    .modal-head-icon.r { background: rgba(231,76,60,.15);  color: var(--red); }
    .modal-head h3 { font-size: 15px; font-weight: 700; }
    .modal-head p  { font-size: 11px; color: var(--muted); margin-top: 1px; }
    .modal-close {
        margin-left: auto; width: 30px; height: 30px;
        display: flex; align-items: center; justify-content: center;
        background: none; border: none; color: var(--muted);
        cursor: pointer; border-radius: 6px; font-size: 15px;
        transition: all .15s;
    }
    .modal-close:hover { color: var(--text); background: var(--s2); }

    .modal-body  { padding: 20px; }
    .modal-footer {
        padding: 14px 20px; border-top: 1px solid var(--border);
        display: flex; gap: 8px; justify-content: flex-end;
        background: rgba(255,255,255,.02);
    }

    /* ── WIZARD ONBOARDING ── */
    .modal-wiz { max-width: 660px; }
    .wiz-stepper {
        display: flex; align-items: center;
        padding: 16px 24px 12px;
        border-bottom: 1px solid var(--border);
        background: rgba(255,255,255,.015);
        gap: 0;
    }
    .wiz-step {
        display: flex; flex-direction: column; align-items: center; gap: 5px;
        flex-shrink: 0; min-width: 64px;
    }
    .wiz-step-dot {
        width: 32px; height: 32px; border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; font-weight: 700;
        background: var(--s2); color: var(--muted);
        border: 2px solid var(--border);
        transition: all .3s;
    }
    .wiz-step.active .wiz-step-dot {
        background: var(--green); color: #000; border-color: var(--green);
        box-shadow: 0 0 16px rgba(76,217,100,.35);
    }
    .wiz-step.done .wiz-step-dot {
        background: rgba(76,217,100,.15); color: var(--green);
        border-color: rgba(76,217,100,.4);
    }
    .wiz-step.done .wiz-step-dot::before { content: '✓'; }
    .wiz-step.done .wiz-step-dot span { display: none; }
    .wiz-step-lbl {
        font-size: 10px; text-transform: uppercase; letter-spacing: .05em;
        color: var(--muted); font-weight: 600; white-space: nowrap;
    }
    .wiz-step.active .wiz-step-lbl { color: var(--green); }
    .wiz-step.done  .wiz-step-lbl { color: rgba(76,217,100,.6); }
    .wiz-step-line {
        flex: 1; height: 2px; background: var(--border);
        margin-bottom: 20px; transition: background .3s; min-width: 16px;
    }
    .wiz-step-line.done { background: rgba(76,217,100,.4); }

    .wiz-body { padding: 22px 24px; max-height: 55vh; overflow-y: auto; }
    .wiz-panel { animation: fadeUp .2s ease; }

    .wiz-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
    @media(max-width:520px){ .wiz-2col { grid-template-columns: 1fr; } }

    .wiz-cancha-row {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px; border-radius: 10px;
        background: rgba(255,255,255,.04); border: 1px solid var(--border);
        margin-bottom: 8px;
    }
    .wiz-cancha-row .cancha-ico {
        width: 34px; height: 34px; border-radius: 8px;
        background: rgba(52,152,219,.15); color: var(--blue);
        display: flex; align-items: center; justify-content: center; font-size: 14px;
        flex-shrink: 0;
    }
    .wiz-cancha-row .cancha-info { flex: 1; }
    .wiz-cancha-row .cancha-name { font-weight: 700; font-size: 13px; }
    .wiz-cancha-row .cancha-tipo { font-size: 11px; color: var(--muted); }
    .wiz-cancha-row .cancha-rm {
        background: rgba(231,76,60,.1); border: 1px solid rgba(231,76,60,.25);
        color: var(--red); border-radius: 6px; padding: 4px 8px;
        cursor: pointer; font-size: 11px; flex-shrink: 0;
    }

    .wiz-tabs { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 14px; }
    .wiz-tab {
        padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 600;
        border: 1px solid var(--border); background: var(--s1); color: var(--muted);
        cursor: pointer; transition: all .15s;
    }
    .wiz-tab.active {
        background: rgba(52,152,219,.15); border-color: var(--blue); color: var(--blue);
    }

    .wiz-franja-row {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
        padding: 8px 12px; border-radius: 8px;
        background: rgba(255,255,255,.03); border: 1px solid var(--border);
        margin-bottom: 6px; font-size: 12px;
    }
    .wiz-franja-row .fr-time { font-weight: 700; color: var(--text); }
    .wiz-franja-row .fr-price { color: var(--green); font-weight: 700; }
    .wiz-franja-row .fr-dias  { color: var(--muted); flex: 1; }

    .gen-preset-btn {
        padding: 4px 10px; border-radius: 6px; border: 1px solid var(--border);
        background: var(--s1); color: var(--muted); font-size: 10px; font-weight: 700;
        cursor: pointer; transition: all .15s;
    }
    .gen-preset-btn:hover { color: var(--text); border-color: rgba(255,255,255,.25); }

    .wiz-done-icon {
        width: 80px; height: 80px; border-radius: 50%;
        background: rgba(76,217,100,.15); color: var(--green);
        display: flex; align-items: center; justify-content: center;
        font-size: 36px; margin: 0 auto 20px;
        box-shadow: 0 0 40px rgba(76,217,100,.25);
        animation: popIn .4s cubic-bezier(.34,1.56,.64,1);
    }
    @keyframes popIn {
        from { transform: scale(0); opacity: 0; }
        to   { transform: scale(1); opacity: 1; }
    }
    .wiz-summary-box {
        background: rgba(255,255,255,.04); border: 1px solid var(--border);
        border-radius: 12px; padding: 16px 20px; margin-top: 16px;
    }
    .wiz-summary-row {
        display: flex; justify-content: space-between; align-items: center;
        padding: 6px 0; border-bottom: 1px solid rgba(255,255,255,.05);
        font-size: 13px;
    }
    .wiz-summary-row:last-child { border: none; }
    .wiz-summary-row .lbl { color: var(--muted); }
    .wiz-summary-row .val { font-weight: 700; }

    .wiz-dias-grid {
        display: grid; grid-template-columns: repeat(7,1fr); gap: 4px; margin-top: 6px;
    }
    .wiz-dia-btn {
        padding: 5px 2px; text-align: center; border-radius: 6px; cursor: pointer;
        border: 1px solid var(--border); background: var(--s1);
        color: var(--muted); font-size: 10px; font-weight: 700;
        transition: all .15s; user-select: none;
    }
    .wiz-dia-btn.sel {
        background: rgba(76,217,100,.15); border-color: var(--green); color: var(--green);
    }
    /* ── /WIZARD ── */

    /* ── AGENDA GRILLA ── */
    .agenda-toggle {
        display: flex; background: var(--s1); border: 1px solid var(--border);
        border-radius: 10px; overflow: hidden; flex-shrink: 0;
    }
    .agenda-toggle-btn {
        padding: 7px 16px; border: none; background: none; color: var(--muted);
        font-size: 12px; font-weight: 700; cursor: pointer; transition: all .15s;
        display: flex; align-items: center; gap: 6px;
    }
    .agenda-toggle-btn.active {
        background: rgba(76,217,100,.15); color: var(--green);
    }

    .grid-wrap { overflow-x: auto; border-radius: 12px; border: 1px solid var(--border); }
    .agenda-grid { border-collapse: collapse; width: 100%; min-width: 600px; }

    /* Cabecera */
    .agenda-grid thead th {
        background: #111; padding: 12px 10px;
        font-size: 11px; font-weight: 700; text-transform: uppercase;
        letter-spacing: .05em; color: var(--muted);
        border-bottom: 1px solid var(--border);
        white-space: nowrap; text-align: left;
        position: sticky; top: 0; z-index: 10;
    }
    .agenda-grid thead th:first-child {
        width: 82px; min-width: 82px;
        position: sticky; left: 0; z-index: 20; background: #111;
        border-right: 1px solid var(--border);
    }
    .agenda-grid thead th .cancha-head-name { font-size: 12px; color: var(--text); }
    .agenda-grid thead th .cancha-head-sub  { font-size: 10px; color: var(--muted); font-weight: 400; }

    /* Celdas */
    .agenda-grid tbody tr { border-bottom: 1px solid rgba(255,255,255,.05); }
    .agenda-grid tbody td {
        padding: 0; vertical-align: top; min-width: 168px;
        border-right: 1px solid rgba(255,255,255,.05);
    }
    .agenda-grid tbody td:first-child {
        position: sticky; left: 0; background: #0d0d0d; z-index: 5;
        border-right: 1px solid var(--border); min-width: 82px; width: 82px;
    }
    .grid-time {
        padding: 10px 8px; font-size: 11px; font-weight: 700;
        color: var(--muted); text-align: center; white-space: nowrap;
        line-height: 1.4;
    }

    /* Slots */
    .grid-slot {
        margin: 5px; border-radius: 8px; padding: 8px 10px;
        min-height: 66px; font-size: 11px; position: relative;
        transition: transform .12s, box-shadow .12s;
    }
    .grid-slot.libre {
        background: rgba(76,217,100,.05); border: 1px dashed rgba(76,217,100,.22);
        cursor: pointer; color: var(--muted);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        gap: 3px;
    }
    .grid-slot.libre:hover {
        background: rgba(76,217,100,.13); border-color: rgba(76,217,100,.55); border-style: solid;
        transform: translateY(-1px); box-shadow: 0 4px 14px rgba(76,217,100,.15);
    }
    .grid-slot.libre .slot-libre-plus {
        font-size: 26px; line-height: 1; color: rgba(76,217,100,.45);
        font-weight: 300; transition: color .15s, transform .15s;
    }
    .grid-slot.libre:hover .slot-libre-plus {
        color: rgba(76,217,100,.9); transform: scale(1.2);
    }
    .grid-slot.libre .slot-precio {
        color: rgba(76,217,100,.55); font-weight: 700; font-size: 11px;
    }
    .grid-slot.confirmada {
        background: rgba(76,217,100,.1); border: 1px solid rgba(76,217,100,.3);
        cursor: pointer;
    }
    .grid-slot.pendiente {
        background: rgba(255,149,0,.08); border: 1px solid rgba(255,149,0,.3);
        cursor: pointer;
    }
    .grid-slot.cierre {
        background: rgba(231,76,60,.06); border: 1px dashed rgba(231,76,60,.2);
        opacity: .7;
    }
    .grid-slot.sin-franja {
        background: transparent; border: none; min-height: 66px;
    }

    .slot-nombre { font-weight: 800; font-size: 12px; color: var(--text); margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .slot-tel    { font-size: 10px; color: var(--muted); margin-bottom: 4px; }
    .slot-badges { display: flex; gap: 4px; flex-wrap: wrap; margin-top: 4px; }
    .slot-badge  { font-size: 9px; font-weight: 700; padding: 2px 6px; border-radius: 10px; }
    .slot-badge.sena     { background: rgba(76,217,100,.15); color: var(--green); }
    .slot-badge.pendiente{ background: rgba(255,149,0,.15);  color: var(--orange); }
    .slot-badge.conf     { background: rgba(76,217,100,.15); color: var(--green); }
    .slot-badge.cierre   { background: rgba(231,76,60,.15);  color: var(--red); }
    .slot-badge.saldo    { background: rgba(231,76,60,.12);  color: var(--red); }
    .slot-libre-lbl { font-size: 10px; margin-bottom: 4px; }
    /* ── /AGENDA GRILLA ── */

    .form-row { margin-bottom: 16px; }
    .form-row:last-child { margin-bottom: 0; }
    .form-label {
        display: block; font-size: 11px; font-weight: 700;
        color: var(--muted); text-transform: uppercase; letter-spacing: .5px;
        margin-bottom: 6px;
    }
    .form-label span { color: var(--red); margin-left: 3px; }
    .form-input, .form-select {
        width: 100%; padding: 10px 14px;
        background: var(--s2); border: 1px solid var(--border);
        border-radius: var(--radius-sm);
        color: var(--text); font-size: 13px;
        outline: none; transition: border-color .2s, box-shadow .2s;
        -webkit-appearance: none;
    }
    .form-input:focus, .form-select:focus {
        border-color: var(--green);
        box-shadow: 0 0 0 3px rgba(76,217,100,.1);
    }
    .form-select option { background: #1a1a1a; }
    .form-hint { font-size: 11px; color: var(--muted); margin-top: 5px; }
    .form-error { font-size: 11px; color: var(--red); margin-top: 5px; display: none; }

    /* Icono preview */
    .icon-row { display: flex; align-items: center; gap: 10px; margin-top: 8px; flex-wrap: wrap; }
    .icon-opt {
        width: 36px; height: 36px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        background: var(--s2); border: 1px solid var(--border);
        color: var(--muted); cursor: pointer; font-size: 14px;
        transition: all .15s;
    }
    .icon-opt:hover   { border-color: rgba(76,217,100,.3); color: var(--green); }
    .icon-opt.sel { background: rgba(76,217,100,.12); border-color: var(--green); color: var(--green); }
    .icon-preview {
        width: 38px; height: 38px; border-radius: 9px;
        background: var(--s2); border: 1px solid var(--border);
        display: flex; align-items: center; justify-content: center;
        font-size: 16px; color: var(--green);
    }

    /* ── TOAST ── */
    .toast-container {
        position: fixed; top: 14px; right: 14px; z-index: 9000;
        display: flex; flex-direction: column; gap: 8px;
        pointer-events: none;
    }
    .toast {
        display: flex; align-items: center; gap: 10px;
        padding: 12px 16px; border-radius: 10px;
        background: #1a1a1a; border: 1px solid var(--border);
        box-shadow: 0 8px 28px rgba(0,0,0,.5);
        min-width: 240px; max-width: 320px;
        animation: toastIn .3s ease;
        pointer-events: all;
        font-size: 13px;
    }
    .toast.removing { animation: toastOut .25s ease forwards; }
    @keyframes toastIn  { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:translateX(0); } }
    @keyframes toastOut { from { opacity:1; transform:translateX(0); }    to { opacity:0; transform:translateX(20px); } }
    .toast-icon { width: 28px; height: 28px; border-radius: 7px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 13px; }
    .toast-icon.ok  { background: rgba(76,217,100,.15); color: var(--green); }
    .toast-icon.err { background: rgba(231,76,60,.15);  color: var(--red); }
    .toast-icon.inf { background: rgba(52,152,219,.15); color: var(--blue); }
    .toast-msg { flex: 1; font-size: 13px; }

    /* ── OVERLAY MOBILE ── */
    .sb-overlay {
        display: none; position: fixed; inset: 0; z-index: 299;
        background: rgba(0,0,0,.6);
    }
    .sb-overlay.show { display: block; }

    /* ── LOADING ROW ── */
    .skeleton {
        background: linear-gradient(90deg, var(--s1), var(--s2), var(--s1));
        background-size: 200% 100%;
        animation: shimmer 1.5s infinite;
        border-radius: 6px; height: 14px;
    }
    @keyframes shimmer { 0% { background-position:200% 0; } 100% { background-position:-200% 0; } }

    /* ── AGENDA ── */
    .reserva-row {
        background: var(--s1);
        border: 1px solid var(--border);
        border-left: 3px solid var(--border);
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: border-color 0.2s;
    }
    .reserva-row.estado-pendiente  { border-left-color: var(--orange); }
    .reserva-row.estado-confirmada { border-left-color: var(--green); }
    .reserva-row.estado-cancelada  { border-left-color: rgba(255,255,255,0.2); opacity: 0.6; }
    .reserva-hora {
        font-size: 1.1rem; font-weight: 800; min-width: 110px;
        color: var(--green);
    }
    .reserva-info { flex: 1; }
    .reserva-info .res-cancha { font-weight: 700; font-size: 0.9rem; }
    .reserva-info .res-cliente { font-size: 0.8rem; color: var(--muted); margin-top: 2px; }
    .reserva-finanzas { text-align: right; min-width: 120px; }
    .reserva-finanzas .res-precio { font-weight: 800; font-size: 0.95rem; }
    .reserva-finanzas .res-saldo { font-size: 0.75rem; color: var(--orange); }
    .reserva-finanzas .res-saldo.pagado { color: var(--green); }
    .reserva-actions { display: flex; gap: 8px; }
    .btn-icon-sm {
        width: 32px; height: 32px; border-radius: 8px;
        border: 1px solid var(--border); background: transparent;
        color: var(--muted); cursor: pointer; font-size: 13px;
        display: flex; align-items: center; justify-content: center;
        transition: all 0.15s;
    }
    .btn-icon-sm:hover { background: rgba(255,255,255,0.08); color: #fff; }
    .btn-icon-sm.green:hover { border-color: var(--green); color: var(--green); }
    .btn-icon-sm.red:hover   { border-color: var(--red); color: var(--red); }
    .btn-icon-sm.blue:hover  { border-color: var(--blue); color: var(--blue); }
    .agenda-kpi-card {
        background: var(--s1); border: 1px solid var(--border);
        border-radius: 12px; padding: 14px 16px;
    }
    .agenda-kpi-card .kpi-val { font-size: 1.6rem; font-weight: 800; }
    .agenda-kpi-card .kpi-label { font-size: 0.75rem; color: var(--muted); margin-top: 2px; }
    .estado-badge {
        display: inline-block; padding: 2px 8px; border-radius: 20px;
        font-size: 10px; font-weight: 700;
        background: rgba(255,255,255,0.07); color: var(--muted);
        margin-right: 4px;
    }
    .estado-badge.estado-pendiente  { background: rgba(255,149,0,.15);   color: var(--orange); }
    .estado-badge.estado-confirmada { background: rgba(76,217,100,.12);  color: var(--green); }
    .estado-badge.estado-cancelada  { background: rgba(255,255,255,.06); color: var(--muted); }

    /* ── CIERRES DE CANCHA ── */
    .cierre-card {
        background: var(--s1);
        border: 1px solid var(--border);
        border-left: 3px solid var(--orange);
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 16px;
        opacity: 1;
        transition: opacity 0.2s;
    }
    .cierre-card.inactivo { opacity: 0.45; border-left-color: rgba(255,255,255,0.15); }
    .cierre-icono {
        width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
        background: rgba(255,149,0,0.12); border: 1px solid rgba(255,149,0,0.25);
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; color: var(--orange);
    }
    .cierre-icono.azul { background: rgba(52,152,219,0.12); border-color: rgba(52,152,219,0.25); color: var(--blue); }
    .cierre-info { flex: 1; }
    .cierre-titulo { font-weight: 700; font-size: 0.9rem; margin-bottom: 3px; }
    .cierre-sub { font-size: 0.78rem; color: var(--muted); }
    .cierre-fechas {
        text-align: right; font-size: 0.83rem; min-width: 160px;
        color: var(--muted);
    }
    .cierre-fechas strong { display: block; color: var(--text); font-size: 0.88rem; margin-bottom: 2px; }
    .cierre-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-top: 6px; }
    .cierre-badge {
        padding: 2px 8px; border-radius: 20px; font-size: 0.7rem; font-weight: 700;
    }
    .badge-complejo { background: rgba(52,152,219,0.15); color: var(--blue); }
    .badge-cancha   { background: rgba(155,89,182,0.15); color: #9b59b6; }
    .badge-total    { background: rgba(231,76,60,0.15);  color: var(--red); }
    .badge-parcial  { background: rgba(255,149,0,0.15);  color: var(--orange); }

    /* ── TURNOS FIJOS ── */
    .turno-card {
        background: var(--s1);
        border: 1px solid var(--border);
        border-left: 3px solid var(--green);
        border-radius: 12px;
        padding: 16px 18px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 16px;
    }
    .turno-card.inactivo { opacity: 0.45; border-left-color: rgba(255,255,255,0.15); }
    .turno-dia-badge {
        width: 52px; height: 52px; border-radius: 14px; flex-shrink: 0;
        background: rgba(76,217,100,0.12); border: 1px solid rgba(76,217,100,0.25);
        display: flex; flex-direction: column; align-items: center; justify-content: center;
        color: var(--green); font-weight: 800;
    }
    .turno-dia-badge .dia-abrev { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px; }
    .turno-dia-badge .dia-hora  { font-size: 0.65rem; color: var(--muted); margin-top: 2px; }
    .turno-info { flex: 1; }
    .turno-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 3px; }
    .turno-cliente { font-size: 0.78rem; color: var(--muted); display: flex; align-items: center; gap: 6px; }
    .turno-meta { display: flex; gap: 14px; margin-top: 6px; font-size: 0.78rem; color: var(--muted); }
    .turno-precio { font-weight: 800; font-size: 1rem; color: var(--green); text-align: right; min-width: 80px; }

    /* ── REPORTES ── */
    .rep-kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 14px;
        margin-bottom: 28px;
    }
    .rep-kpi {
        background: var(--s1);
        border: 1px solid var(--border);
        border-radius: 14px;
        padding: 18px 20px;
    }
    .rep-kpi .kpi-icon {
        width: 40px; height: 40px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 17px; margin-bottom: 12px;
    }
    .rep-kpi .kpi-val  { font-size: 1.55rem; font-weight: 800; line-height: 1; }
    .rep-kpi .kpi-lbl  { font-size: 0.72rem; color: var(--muted); margin-top: 5px; text-transform: uppercase; letter-spacing: 0.06em; }
    .rep-kpi .kpi-sub  { font-size: 0.75rem; color: var(--muted); margin-top: 4px; }

    .rep-section { margin-bottom: 32px; }
    .rep-section-title {
        font-size: 0.8rem; font-weight: 700; letter-spacing: 0.08em;
        text-transform: uppercase; color: var(--muted); margin-bottom: 14px;
    }

    .rep-chart-wrap {
        background: var(--s1); border: 1px solid var(--border);
        border-radius: 14px; padding: 20px; overflow-x: auto;
    }
    .rep-chart-wrap svg { display: block; }

    .rep-cancha-row {
        display: flex; align-items: center; gap: 14px;
        padding: 12px 16px; border-radius: 10px;
        background: var(--s1); border: 1px solid var(--border);
        margin-bottom: 8px;
    }
    .rep-cancha-row .rc-rank {
        width: 28px; height: 28px; border-radius: 8px;
        background: rgba(76,217,100,0.1); border: 1px solid rgba(76,217,100,0.2);
        color: var(--green); font-weight: 800; font-size: 0.85rem;
        display: flex; align-items: center; justify-content: center; flex-shrink: 0;
    }
    .rep-cancha-row .rc-info { flex: 1; }
    .rep-cancha-row .rc-nombre { font-weight: 600; font-size: 0.88rem; }
    .rep-cancha-row .rc-predio { font-size: 0.75rem; color: var(--muted); }
    .rep-cancha-row .rc-bar-wrap {
        flex: 2; height: 6px; background: rgba(255,255,255,0.07);
        border-radius: 3px; overflow: hidden;
    }
    .rep-cancha-row .rc-bar {
        height: 100%; border-radius: 3px;
        background: linear-gradient(90deg, var(--green), var(--green-d));
        transition: width 0.6s ease;
    }
    .rep-cancha-row .rc-vals { text-align: right; min-width: 110px; }
    .rep-cancha-row .rc-ingresos { font-weight: 700; font-size: 0.9rem; }
    .rep-cancha-row .rc-res { font-size: 0.72rem; color: var(--muted); }

    .rep-periodo-btn {
        padding: 7px 14px; border: none; background: transparent;
        color: var(--muted); font-size: 0.82rem; cursor: pointer;
        transition: all 0.15s; font-weight: 500;
    }
    .rep-periodo-btn.active {
        background: rgba(76,217,100,0.15);
        color: var(--green); font-weight: 700;
    }
    .rep-periodo-btn:hover:not(.active) { color: #fff; }

    /* ── BANNER MODO SOPORTE ── */
    .banner-soporte {
        background: rgba(52,152,219,0.1);
        border: 1px solid rgba(52,152,219,0.25);
        border-radius: 10px;
        padding: 10px 16px;
        display: flex; align-items: center; gap: 10px;
        font-size: 0.83rem; color: rgba(255,255,255,0.7);
        margin-bottom: 18px; flex-wrap: wrap;
    }
    .banner-soporte i { color: #3498db; }
    .banner-soporte strong { color: #3498db; font-weight: 700; }
    .banner-exit-btn {
        margin-left: auto; padding: 5px 12px;
        background: rgba(52,152,219,0.15); color: #3498db;
        border: 1px solid rgba(52,152,219,0.25); border-radius: 8px;
        font-size: 0.75rem; font-weight: 700; cursor: pointer;
        transition: background 0.15s;
    }
    .banner-exit-btn:hover { background: rgba(52,152,219,0.28); }

    /* ── SIDEBAR: context banner ── */
    .sb-context-banner {
        display: flex; align-items: center; gap: 8px;
        background: rgba(52,152,219,0.1);
        border: 1px solid rgba(52,152,219,0.2);
        border-radius: 10px; padding: 10px 10px;
        margin: 0 0 10px;
    }
    .scb-icon { font-size: 14px; }
    .scb-info { flex: 1; min-width: 0; }
    .scb-label { font-size: 9px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: rgba(52,152,219,0.7); }
    .scb-name  { font-size: 11px; font-weight: 700; color: #3498db; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .scb-exit  {
        background: rgba(52,152,219,0.15); border: 1px solid rgba(52,152,219,0.2);
        color: #3498db; border-radius: 6px; padding: 3px 7px;
        font-size: 11px; cursor: pointer; flex-shrink: 0;
    }
    .scb-exit:hover { background: rgba(52,152,219,0.28); }

    /* ── CLIENTES / RAVIOLES ── */
    .clientes-search {
        width: 100%; padding: 10px 14px;
        background: var(--s1); border: 1px solid var(--b1);
        border-radius: 10px; color: #fff; font-size: 0.85rem;
        margin-bottom: 20px; outline: none; transition: border-color 0.15s;
    }
    .clientes-search:focus { border-color: rgba(76,217,100,0.4); }
    .clientes-search::placeholder { color: var(--muted); }
    .clientes-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
        gap: 14px;
    }
    .raviol-card {
        background: var(--s1); border: 1px solid var(--b1);
        border-radius: 14px; padding: 16px;
        transition: border-color 0.15s, transform 0.15s;
    }
    .raviol-card:hover { border-color: rgba(76,217,100,0.3); transform: translateY(-2px); }
    .raviol-card.inactivo { opacity: 0.5; }
    .raviol-top  { display: flex; align-items: center; gap: 10px; margin-bottom: 12px; }
    .raviol-avatar {
        width: 40px; height: 40px; border-radius: 11px;
        display: flex; align-items: center; justify-content: center;
        font-weight: 800; font-size: 14px; flex-shrink: 0; letter-spacing: -0.5px;
    }
    .raviol-info  { flex: 1; min-width: 0; }
    .raviol-nombre { font-weight: 700; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .raviol-email  { font-size: 0.72rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .raviol-status-badge {
        font-size: 0.68rem; font-weight: 700; padding: 2px 7px;
        border-radius: 6px; flex-shrink: 0; white-space: nowrap;
    }
    .rs-activo   { background: rgba(76,217,100,0.12); color: var(--green); }
    .rs-inactivo { background: rgba(255,255,255,0.07); color: var(--muted); }
    .raviol-stats {
        display: flex; gap: 4px; margin-bottom: 12px;
        padding: 10px 0; border-top: 1px solid var(--b1); border-bottom: 1px solid var(--b1);
    }
    .raviol-stat { display: flex; flex-direction: column; align-items: center; flex: 1; }
    .raviol-stat strong { color: #fff; font-size: 1.05rem; font-weight: 800; line-height: 1; }
    .raviol-stat span { font-size: 0.65rem; color: var(--muted); margin-top: 3px; }
    .raviol-sep  { color: var(--b1); width: 1px; background: var(--b1); margin: 4px 0; flex-shrink: 0; }
    .raviol-actions { display: flex; gap: 8px; margin-top: 12px; }
    .btn-gestionar {
        flex: 1; padding: 7px 0; border-radius: 8px;
        background: rgba(76,217,100,0.12); color: var(--green);
        border: 1px solid rgba(76,217,100,0.2); font-size: 0.8rem;
        font-weight: 700; cursor: pointer; transition: background 0.15s;
    }
    .btn-gestionar:hover:not(:disabled) { background: rgba(76,217,100,0.22); }
    .btn-gestionar:disabled { opacity: 0.35; cursor: default; }
    .btn-raviol-more {
        padding: 7px 12px; border-radius: 8px;
        background: rgba(255,255,255,0.05); color: var(--muted);
        border: 1px solid var(--b1); font-size: 13px; cursor: pointer;
        transition: all 0.15s;
    }
    .btn-raviol-more:hover { color: #fff; background: rgba(255,255,255,0.1); }

    /* ── RESPONSIVE ── */
    @media (max-width: 768px) {
        .rep-kpi-grid { grid-template-columns: repeat(2,1fr); }
        :root { --sidebar-w: 260px; }
        .sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
        .sidebar.mobile-open { transform: translateX(0); }
        .main { margin-left: 0 !important; }
        .tb-date { display: none; }
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
        .page-header { flex-direction: column; align-items: flex-start; }
        th:nth-child(3), td:nth-child(3) { display: none; }
        /* Grids con ID que usan inline styles */
        #agendaKpis    { grid-template-columns: repeat(2,1fr) !important; }
        #predioCanchasGrid { grid-template-columns: 1fr !important; }
        #horLayoutGrid { grid-template-columns: 1fr !important; }
        #pagosKpisGrid { grid-template-columns: repeat(3,1fr) !important; }
        /* Agenda header con filtros */
        .page-header-right { flex-wrap: wrap; gap: 8px !important; }
        .page-header-right > * { flex: 1 1 140px; }
        /* Cards */
        .card { border-radius: 12px; }
        /* Topbar */
        .topbar { padding: 0 12px; gap: 8px; }
    }
    @media (max-width: 480px) {
        .content { padding: 12px; }
        .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
        .kpi-val  { font-size: 1.5rem; }
        .rep-kpi-grid { grid-template-columns: repeat(2,1fr); gap: 10px; }
        .rep-kpi .kpi-val { font-size: 1.3rem; }
        #agendaKpis    { grid-template-columns: repeat(2,1fr) !important; }
        #pagosKpisGrid { grid-template-columns: 1fr !important; }
        .page-sub { font-size: 12px; }
        .tb-title { font-size: 13px; }
        /* Ocultar columnas extra en tablas */
        th:nth-child(4), td:nth-child(4) { display: none; }
        /* Modales/form grids */
        .wiz-2col { grid-template-columns: 1fr !important; }
    }
    </style>
</head>
<body>

<div class="bg-layer"></div>

<div class="sb-overlay" id="sbOverlay" onclick="closeSidebar()"></div>

<!-- ═══════════ SIDEBAR ═══════════ -->
<aside class="sidebar" id="sidebar">

    <div class="sb-logo">
        <img src="../../config/dist/img/loguito_lacanchita.webp" alt="Logo">
        <div class="sb-logo-text">
            <strong>La Canchita</strong>
            <span>Sistema de gestión</span>
        </div>
        <span class="sb-badge-admin"><?= match($perfil) { 1=>'Admin', 2=>'Dueño', 3=>'Encargado', 4=>'Empleado', default=>'Usuario' } ?></span>
    </div>

    <div class="sb-user">
        <div class="sb-avatar"><?= strtoupper(substr($nombre,0,1)) ?></div>
        <div class="sb-user-info">
            <strong><?= htmlspecialchars($nombre) ?></strong>
            <span><?= match($perfil) { 1=>'Administrador', 2=>'Dueño de complejo', 3=>'Encargado', 4=>'Empleado', default=>'Usuario' } ?></span>
        </div>
    </div>

    <nav class="sb-nav">
        <div class="sb-section">Principal</div>
        <div class="sb-item active" data-view="dashboard" onclick="showView(this)">
            <i class="fas fa-th-large"></i> Dashboard
        </div>

        <?php if($perfil === 1 && !$adminAsDueno): ?>
        <!-- ── SuperAdmin: modo plataforma (sin contexto de soporte) ── -->
        <div class="sb-section">Plataforma</div>
        <div class="sb-item" data-view="clientes" onclick="showView(this)">
            <i class="fas fa-users-cog"></i> Clientes
        </div>
        <div class="sb-item" data-view="catalogos" onclick="showView(this)">
            <i class="fas fa-tags"></i> Tipos y categorías
        </div>
        <div class="sb-section">Operaciones</div>
        <div class="sb-item" data-view="reportes" onclick="showView(this)">
            <i class="fas fa-chart-bar"></i> Reportes
        </div>
        <div class="sb-section">Personas</div>
        <div class="sb-item" data-view="staff" onclick="showView(this)">
            <i class="fas fa-id-badge"></i> Usuarios
            <?php if($kpi['usuarios_pend'] > 0): ?>
            <span class="sb-pill"><?= $kpi['usuarios_pend'] ?></span>
            <?php endif; ?>
        </div>
        <div class="sb-section">Mi cuenta</div>
        <div class="sb-item" data-view="perfil" onclick="showView(this)">
            <i class="fas fa-user-circle"></i> Mi perfil
        </div>
        <?php endif; ?>

        <?php if($perfil === 1 && $adminAsDueno): ?>
        <!-- ── SuperAdmin: modo soporte (gestionando como un dueño) ── -->
        <div class="sb-context-banner">
            <div class="scb-icon">👁</div>
            <div class="scb-info">
                <div class="scb-label">Modo soporte</div>
                <div class="scb-name"><?= htmlspecialchars($adminAsDuenoNombre) ?></div>
            </div>
            <button class="scb-exit" onclick="salirContextoAdmin()">✕</button>
        </div>
        <div class="sb-section">Mi negocio</div>
        <div class="sb-item" data-view="complejos" onclick="showView(this)">
            <i class="fas fa-building"></i> Mis Predios
        </div>
        <div class="sb-item" data-view="canchas" onclick="showView(this)">
            <i class="fas fa-futbol"></i> Canchas
        </div>
        <div class="sb-item" data-view="horarios" onclick="showView(this)">
            <i class="fas fa-clock"></i> Horarios y precios
        </div>
        <div class="sb-item" data-view="cierres" onclick="showView(this)">
            <i class="fas fa-ban"></i> Cierres
        </div>
        <div class="sb-item" data-view="turnos" onclick="showView(this)">
            <i class="fas fa-redo-alt"></i> Turnos fijos
        </div>
        <div class="sb-section">Operaciones</div>
        <div class="sb-item" data-view="reportes" onclick="showView(this)">
            <i class="fas fa-chart-bar"></i> Reportes
        </div>
        <div class="sb-item" data-view="agenda" onclick="showView(this)">
            <i class="fas fa-calendar-alt"></i> Agenda
        </div>
        <div class="sb-item" data-view="reservas" onclick="showView(this)">
            <i class="fas fa-calendar-check"></i> Reservas
        </div>
        <div class="sb-item" data-view="pagos" onclick="showView(this)">
            <i class="fas fa-dollar-sign"></i> Cobros
        </div>
        <div class="sb-section">Personas</div>
        <div class="sb-item" data-view="staff" onclick="showView(this)">
            <i class="fas fa-id-badge"></i> Staff del cliente
        </div>
        <div class="sb-section">Mi cuenta</div>
        <div class="sb-item" data-view="perfil" onclick="showView(this)">
            <i class="fas fa-user-circle"></i> Mi perfil
        </div>
        <?php endif; ?>

        <?php if($perfil === 2): ?>
        <!-- ── Dueño: gestiona su propio negocio ── -->
        <div class="sb-section">Mi negocio</div>
        <div class="sb-item" data-view="complejos" onclick="showView(this)">
            <i class="fas fa-building"></i> Mis Predios
        </div>
        <div class="sb-item" data-view="canchas" onclick="showView(this)">
            <i class="fas fa-futbol"></i> Canchas
        </div>
        <div class="sb-item" data-view="horarios" onclick="showView(this)">
            <i class="fas fa-clock"></i> Horarios y precios
        </div>
        <div class="sb-item" data-view="cierres" onclick="showView(this)">
            <i class="fas fa-ban"></i> Cierres
        </div>
        <div class="sb-item" data-view="turnos" onclick="showView(this)">
            <i class="fas fa-redo-alt"></i> Turnos fijos
        </div>
        <div class="sb-section">Operaciones</div>
        <div class="sb-item" data-view="reportes" onclick="showView(this)">
            <i class="fas fa-chart-bar"></i> Reportes
        </div>
        <div class="sb-item" data-view="agenda" onclick="showView(this)">
            <i class="fas fa-calendar-alt"></i> Agenda
        </div>
        <div class="sb-item" data-view="reservas" onclick="showView(this)">
            <i class="fas fa-calendar-check"></i> Reservas
        </div>
        <div class="sb-item" data-view="pagos" onclick="showView(this)">
            <i class="fas fa-dollar-sign"></i> Cobros
        </div>
        <div class="sb-section">Personas</div>
        <div class="sb-item" data-view="staff" onclick="showView(this)">
            <i class="fas fa-id-badge"></i> Mi Staff
        </div>
        <div class="sb-section">Mi cuenta</div>
        <div class="sb-item" data-view="perfil" onclick="showView(this)">
            <i class="fas fa-user-circle"></i> Mi perfil
        </div>
        <?php endif; ?>

        <?php if($perfil === 3 || $perfil === 4): ?>
        <!-- ── Staff: solo operaciones ── -->
        <div class="sb-section">Operaciones</div>
        <div class="sb-item" data-view="reportes" onclick="showView(this)">
            <i class="fas fa-chart-bar"></i> Reportes
        </div>
        <div class="sb-item" data-view="agenda" onclick="showView(this)">
            <i class="fas fa-calendar-alt"></i> Agenda
        </div>
        <div class="sb-item" data-view="reservas" onclick="showView(this)">
            <i class="fas fa-calendar-check"></i> Reservas
        </div>
        <div class="sb-item" data-view="pagos" onclick="showView(this)">
            <i class="fas fa-dollar-sign"></i> Cobros
        </div>
        <div class="sb-section">Mi cuenta</div>
        <div class="sb-item" data-view="perfil" onclick="showView(this)">
            <i class="fas fa-user-circle"></i> Mi perfil
        </div>
        <?php endif; ?>
    </nav>

    <div class="sb-footer">
        <a href="../maquetaCliente/LaCanchitaCliente.php" class="sb-footer-link client">
            <i class="fas fa-arrow-left"></i> Ver panel cliente
        </a>
        <?php if ($perfil === 1): ?>
        <a href="../maquetaSuperAdmin/PanelDesarrollador.php" class="sb-footer-link" style="color:#9b59b6;border-color:rgba(155,89,182,.2);background:rgba(155,89,182,.06)">
            <i class="fas fa-code"></i> Panel desarrollador
        </a>
        <?php endif; ?>
        <a href="../../logout.php" class="sb-footer-link logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </div>
</aside>

<!-- ═══════════ MAIN ═══════════ -->
<div class="main" id="main">

    <!-- TOPBAR -->
    <div class="topbar">
        <button class="tb-toggle" onclick="toggleSidebar()" aria-label="Menú">
            <i class="fas fa-bars"></i>
        </button>
        <div class="tb-breadcrumb">
            <span class="root">La Canchita</span>
            <span class="sep">›</span>
            <span class="cur" id="breadcrumb">Dashboard</span>
        </div>
        <div class="tb-right">
            <span class="tb-date"><?= date('D d/m/Y') ?></span>
            <?php if($kpi['reservas_pend_hoy'] > 0): ?>
            <div class="tb-btn" id="badge-res-pend" onclick="irAReservasPendientes()" title="<?= $kpi['reservas_pend_hoy'] ?> reserva(s) pendiente(s) hoy" style="position:relative">
                <i class="fas fa-calendar-check" style="color:var(--orange)"></i>
                <span class="tb-dot" style="background:var(--orange)"></span>
                <span style="position:absolute;top:-4px;right:-4px;background:var(--orange);color:#000;
                      font-size:9px;font-weight:800;border-radius:50%;width:16px;height:16px;
                      display:flex;align-items:center;justify-content:center;line-height:1"
                      id="badge-res-pend-num"><?= $kpi['reservas_pend_hoy'] ?></span>
            </div>
            <?php endif; ?>
            <?php if($kpi['usuarios_pend'] > 0): ?>
            <div class="tb-btn" onclick="showView(document.querySelector('[data-view=usuarios]'))" title="Usuarios pendientes">
                <i class="fas fa-user-clock"></i>
                <span class="tb-dot"></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- CONTENT -->
    <div class="content">
        <?php if ($adminAsDueno): ?>
        <div class="banner-soporte">
            <i class="fas fa-eye"></i>
            Gestionando como: <strong><?= htmlspecialchars($adminAsDuenoNombre) ?></strong>
            &mdash; los datos, APIs y acciones están scopeados a este cliente.
            <button onclick="salirContextoAdmin()" class="banner-exit-btn">✕ Salir del modo soporte</button>
        </div>
        <?php endif; ?>

        <!-- ══════════════════════════════
             VIEW: DASHBOARD
        ══════════════════════════════ -->
        <div class="view active" id="view-dashboard">

            <div class="page-header">
                <div class="page-header-left">
                    <h1><?= $saludo ?>, <?= htmlspecialchars(explode(' ',$nombre)[0]) ?></h1>
                    <p><?= date('l j \d\e F \d\e Y') ?> — vista general del sistema</p>
                </div>
            </div>

            <?php if ($perfil === 1 && !$adminAsDueno): ?>
            <!-- KPIs PLATAFORMA -->
            <div style="margin-bottom:28px">
                <div style="font-size:0.75rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-bottom:14px">
                    Plataforma · <?= date('F Y') ?>
                </div>
                <div class="kpi-grid" style="grid-template-columns:repeat(5,1fr)">
                    <div class="kpi">
                        <div class="kpi-top"><div class="kpi-icon g"><i class="fas fa-users-cog"></i></div></div>
                        <div class="kpi-val"><?= $kpi['plataforma_duenos'] ?></div>
                        <div class="kpi-lbl">Clientes activos</div>
                    </div>
                    <div class="kpi">
                        <div class="kpi-top"><div class="kpi-icon b"><i class="fas fa-building"></i></div></div>
                        <div class="kpi-val"><?= $kpi['plataforma_complejos'] ?></div>
                        <div class="kpi-lbl">Predios en plataforma</div>
                    </div>
                    <div class="kpi">
                        <div class="kpi-top"><div class="kpi-icon o"><i class="fas fa-calendar-check"></i></div></div>
                        <div class="kpi-val"><?= $kpi['plataforma_res_hoy'] ?></div>
                        <div class="kpi-lbl">Reservas hoy</div>
                    </div>
                    <div class="kpi">
                        <div class="kpi-top"><div class="kpi-icon g"><i class="fas fa-dollar-sign"></i></div></div>
                        <div class="kpi-val">$<?= number_format($kpi['plataforma_ingresos_mes'],0,',','.') ?></div>
                        <div class="kpi-lbl">Cobrado este mes</div>
                    </div>
                    <div class="kpi">
                        <div class="kpi-top"><div class="kpi-icon b"><i class="fas fa-user-friends"></i></div></div>
                        <div class="kpi-val"><?= $kpi['plataforma_clientes_activos'] ?></div>
                        <div class="kpi-lbl">Clientes registrados</div>
                    </div>
                </div>
            </div>

            <!-- TOP DUEÑOS HOY -->
            <?php if (!empty($kpi['top_duenos'])): ?>
            <div style="margin-bottom:28px">
                <div style="font-size:0.75rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted);margin-bottom:14px">
                    Actividad de clientes hoy
                </div>
                <div class="card" style="padding:0">
                    <table style="margin:0">
                        <thead>
                            <tr>
                                <th>#</th><th>Cliente</th><th>Reservas hoy</th><th>Cobrado hoy</th><th>Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach($kpi['top_duenos'] as $i => $td): ?>
                        <tr>
                            <td style="color:var(--muted);font-size:0.8rem"><?= $i+1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($td['USUARIOS_NOMBRE'].' '.$td['USUARIOS_APELLIDO']) ?></strong>
                            </td>
                            <td>
                                <span style="font-weight:700;color:<?= $td['reservas_hoy']>0?'#4cd964':'rgba(255,255,255,0.35)' ?>"><?= $td['reservas_hoy'] ?></span>
                            </td>
                            <td>
                                <span style="font-weight:700">$<?= number_format($td['cobrado_hoy'],0,',','.') ?></span>
                            </td>
                            <td>
                                <button class="btn btn-ghost btn-sm" style="font-size:0.72rem;padding:4px 10px"
                                        onclick="showView(document.querySelector('[data-view=clientes]'))">
                                    Ver clientes →
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; // fin perfil 1 plataforma ?>

            <div class="kpi-grid">
                <div class="kpi">
                    <div class="kpi-top">
                        <div class="kpi-icon g"><i class="fas fa-calendar-check"></i></div>
                        <span class="kpi-change up"><i class="fas fa-arrow-up"></i></span>
                    </div>
                    <div class="kpi-val"><?= $kpi['reservas_hoy'] ?></div>
                    <div class="kpi-lbl">Reservas hoy</div>
                </div>
                <div class="kpi">
                    <div class="kpi-top">
                        <div class="kpi-icon g"><i class="fas fa-dollar-sign"></i></div>
                    </div>
                    <div class="kpi-val">$<?= number_format($kpi['ingresos_hoy'],0,',','.') ?></div>
                    <div class="kpi-lbl">Ingresos hoy</div>
                </div>
                <div class="kpi">
                    <div class="kpi-top">
                        <div class="kpi-icon b"><i class="fas fa-calendar-alt"></i></div>
                    </div>
                    <div class="kpi-val"><?= $kpi['reservas_mes'] ?></div>
                    <div class="kpi-lbl">Reservas del mes</div>
                </div>
                <div class="kpi">
                    <div class="kpi-top">
                        <div class="kpi-icon b"><i class="fas fa-chart-line"></i></div>
                    </div>
                    <div class="kpi-val">$<?= number_format($kpi['ingresos_mes'],0,',','.') ?></div>
                    <div class="kpi-lbl">Ingresos del mes</div>
                </div>
                <div class="kpi">
                    <div class="kpi-top">
                        <div class="kpi-icon p"><i class="fas fa-building"></i></div>
                    </div>
                    <div class="kpi-val"><?= $kpi['complejos'] ?></div>
                    <div class="kpi-lbl">Complejos activos</div>
                </div>
                <div class="kpi">
                    <div class="kpi-top">
                        <div class="kpi-icon o"><i class="fas fa-futbol"></i></div>
                    </div>
                    <div class="kpi-val"><?= $kpi['canchas'] ?></div>
                    <div class="kpi-lbl">Canchas activas</div>
                </div>
                <div class="kpi">
                    <div class="kpi-top">
                        <div class="kpi-icon g"><i class="fas fa-users"></i></div>
                    </div>
                    <div class="kpi-val"><?= $kpi['usuarios_total'] ?></div>
                    <div class="kpi-lbl">Usuarios activos</div>
                </div>
                <div class="kpi">
                    <div class="kpi-top">
                        <div class="kpi-icon r"><i class="fas fa-user-clock"></i></div>
                        <?php if($kpi['usuarios_pend']>0): ?><span class="kpi-change warn"><i class="fas fa-circle"></i></span><?php endif; ?>
                    </div>
                    <div class="kpi-val"><?= $kpi['usuarios_pend'] ?></div>
                    <div class="kpi-lbl">Pendientes aprobación</div>
                </div>
            </div>

            <?php if (!empty($dash_reservas_hoy)): ?>
            <!-- ── RESERVAS DE HOY ── -->
            <div style="margin-top:28px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
                    <div style="font-size:0.75rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;color:var(--muted)">
                        Agenda de hoy — <?= count($dash_reservas_hoy) ?> reserva<?= count($dash_reservas_hoy)!==1?'s':'' ?>
                    </div>
                    <button class="btn btn-ghost btn-sm" onclick="showView(document.querySelector('[data-view=reservas]'))">
                        Ver todas <i class="fas fa-arrow-right"></i>
                    </button>
                </div>
                <div style="display:grid;gap:8px">
                <?php
                $est_col = ['pendiente'=>'var(--orange)','confirmada'=>'var(--green)','cancelada'=>'rgba(255,255,255,0.3)'];
                $est_ico = ['pendiente'=>'fa-clock','confirmada'=>'fa-check-circle','cancelada'=>'fa-times-circle'];
                foreach($dash_reservas_hoy as $rv):
                    $col  = $est_col[$rv['RESERVA_ESTADO']] ?? 'var(--muted)';
                    $ico  = $est_ico[$rv['RESERVA_ESTADO']] ?? 'fa-circle';
                    $saldo = (float)$rv['SALDO'];
                    $fmtP  = fn($n) => '$'.number_format((float)$n,0,',','.');
                ?>
                <div style="background:var(--s1);border:1px solid var(--b1);border-radius:11px;padding:12px 16px;display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                    <div style="min-width:55px;text-align:center">
                        <div style="font-size:1rem;font-weight:800;line-height:1"><?= substr($rv['RESERVA_HORA_INICIO'],0,5) ?></div>
                        <div style="font-size:0.68rem;color:var(--muted)"><?= substr($rv['RESERVA_HORA_FIN'],0,5) ?></div>
                    </div>
                    <div style="flex:1;min-width:120px">
                        <div style="font-weight:700;font-size:0.85rem">
                            <i class="fas <?= htmlspecialchars($rv['TIPO_CANCHA_ICONO']) ?>" style="color:var(--green);margin-right:5px;font-size:11px"></i>
                            <?= htmlspecialchars($rv['CANCHA_NOMBRE']) ?>
                        </div>
                        <div style="font-size:0.75rem;color:var(--muted)">
                            <?= htmlspecialchars($rv['USUARIOS_NOMBRE'].' '.$rv['USUARIOS_APELLIDO']) ?>
                            <?php if($rv['USUARIOS_TELEFONO']): ?> · <?= htmlspecialchars($rv['USUARIOS_TELEFONO']) ?><?php endif; ?>
                        </div>
                    </div>
                    <span style="display:inline-flex;align-items:center;gap:4px;padding:3px 9px;border-radius:20px;background:<?= $col ?>18;color:<?= $col ?>;font-size:0.7rem;font-weight:700">
                        <i class="fas <?= $ico ?>" style="font-size:9px"></i>
                        <?= $rv['RESERVA_ESTADO'] ?>
                    </span>
                    <div style="text-align:right;min-width:80px">
                        <div style="font-weight:800;font-size:0.9rem"><?= $fmtP($rv['RESERVA_PRECIO']) ?></div>
                        <?php if($saldo > 0 && $rv['RESERVA_ESTADO']!=='cancelada'): ?>
                        <div style="font-size:0.7rem;color:var(--orange)">Debe <?= $fmtP($saldo) ?></div>
                        <?php elseif((float)$rv['PAGADO'] > 0): ?>
                        <div style="font-size:0.7rem;color:var(--green)">Saldado</div>
                        <?php endif; ?>
                    </div>
                    <?php if($rv['RESERVA_ESTADO'] === 'pendiente'): ?>
                    <button class="btn btn-ghost btn-sm"
                            style="color:var(--green);border-color:rgba(76,217,100,.25);font-size:0.75rem"
                            onclick="dashConfirmar(<?= $rv['RESERVA_ID'] ?>, this)">
                        <i class="fas fa-check"></i> Confirmar
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                </div>
            </div>
            <?php elseif($perfil >= 2): ?>
            <div style="margin-top:28px;padding:28px;background:var(--s1);border:1px solid var(--b1);border-radius:12px;text-align:center;color:var(--muted)">
                <i class="fas fa-calendar-day" style="font-size:28px;display:block;margin-bottom:10px;opacity:0.4"></i>
                <div style="font-size:0.85rem">Sin reservas para hoy</div>
                <button class="btn btn-ghost btn-sm" style="margin-top:12px"
                        onclick="showView(document.querySelector('[data-view=agenda]'))">
                    <i class="fas fa-calendar-alt"></i> Ver agenda
                </button>
            </div>
            <?php endif; ?>

            <?php if($kpi['usuarios_pend'] > 0): ?>
            <div class="card" style="border-color:rgba(255,149,0,.25)">
                <div class="card-header" style="background:rgba(255,149,0,.05)">
                    <div class="card-header-icon" style="background:rgba(255,149,0,.12);color:var(--orange)">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div>
                        <h3>Usuarios pendientes de aprobación</h3>
                        <p><?= $kpi['usuarios_pend'] ?> cuenta<?= $kpi['usuarios_pend']>1?'s':'' ?> esperando revisión</p>
                    </div>
                    <div class="card-actions">
                        <button class="btn btn-ghost btn-sm" onclick="showView(document.querySelector('[data-view=usuarios]'))">
                            Ver todos <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <table>
                        <thead><tr>
                            <th>Usuario</th>
                            <th>DNI</th>
                            <th>Perfil</th>
                            <th>Acciones</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach(array_slice($pendientes,0,5) as $u): ?>
                        <tr class="pend-row">
                            <td>
                                <div class="user-cell">
                                    <div class="user-av"><?= strtoupper(substr($u['USUARIOS_NOMBRE'],0,1)) ?></div>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($u['USUARIOS_NOMBRE'].' '.$u['USUARIOS_APELLIDO']) ?></div>
                                        <div class="user-email"><?= htmlspecialchars($u['USUARIOS_EMAIL']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($u['USUARIOS_DNI']) ?></td>
                            <td><span class="badge pending"><?= htmlspecialchars($u['PERFIL_NOMBRE']) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="btn btn-approve btn-sm" onclick="aprobarUsuario(<?= $u['USUARIOS_ID'] ?>, this)">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rechazarUsuario(<?= $u['USUARIOS_ID'] ?>, this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        </div><!-- /view-dashboard -->


        <!-- ══════════════════════════════
             VIEW: CATÁLOGOS
        ══════════════════════════════ -->
        <div class="view" id="view-catalogos">

            <div class="page-header">
                <div class="page-header-left">
                    <h1>Tipos y categorías</h1>
                    <p>Gestioná los catálogos autoadministrables del sistema</p>
                </div>
            </div>

            <div class="card">
                <div class="stabs" id="catTabs">
                    <div class="stab active" data-cat="tipo_cancha" onclick="switchCat(this)">
                        Tipos de cancha <span class="stab-count" id="cnt-tipo_cancha">—</span>
                    </div>
                    <div class="stab" data-cat="tipo_complejo" onclick="switchCat(this)">
                        Tipos de complejo <span class="stab-count" id="cnt-tipo_complejo">—</span>
                    </div>
                    <div class="stab" data-cat="medio_pago" onclick="switchCat(this)">
                        Medios de pago <span class="stab-count" id="cnt-medio_pago">—</span>
                    </div>
                </div>

                <!-- Toolbar dinámico -->
                <div class="dt-toolbar">
                    <div class="dt-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="catSearch" placeholder="Buscar…" oninput="filterTable()">
                    </div>
                    <div class="dt-filter">
                        <button class="filter-btn active" data-filter="all" onclick="setFilter(this,'all')">Todos</button>
                        <button class="filter-btn" data-filter="1" onclick="setFilter(this,'1')">Activos</button>
                        <button class="filter-btn" data-filter="0" onclick="setFilter(this,'0')">Inactivos</button>
                    </div>
                    <button class="btn btn-primary btn-sm" onclick="openModalCrear()">
                        <i class="fas fa-plus"></i> Nuevo
                    </button>
                </div>

                <!-- Tabla -->
                <div id="catTableWrap">
                    <table id="catTable">
                        <thead>
                            <tr>
                                <th style="width:44px"></th>
                                <th>Nombre</th>
                                <th>Ícono</th>
                                <th>Estado</th>
                                <th style="width:90px">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="catTbody">
                            <tr><td colspan="5"><div class="empty-state">
                                <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
                                <p>Cargando…</p>
                            </div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

        </div><!-- /view-catalogos -->


        <!-- ══════════════════════════════
             VIEW: USUARIOS
        ══════════════════════════════ -->
        <div class="view" id="view-usuarios">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Usuarios</h1>
                    <p>Gestioná cuentas y aprobaciones</p>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon kpi-icon r"><i class="fas fa-users"></i></div>
                    <div><h3>Pendientes de aprobación</h3><p>Usuarios registrados esperando activación</p></div>
                </div>
                <div class="card-body">
                <?php if(empty($pendientes)): ?>
                    <div class="empty-state">
                        <div class="es-icon"><i class="fas fa-user-check"></i></div>
                        <h4>Todo al día</h4>
                        <p>No hay usuarios pendientes de aprobación.</p>
                    </div>
                <?php else: ?>
                    <table>
                        <thead><tr>
                            <th>Usuario</th>
                            <th>DNI</th>
                            <th>Teléfono</th>
                            <th>Perfil</th>
                            <th>Acciones</th>
                        </tr></thead>
                        <tbody>
                        <?php foreach($pendientes as $u): ?>
                        <tr id="pend-<?= $u['USUARIOS_ID'] ?>">
                            <td>
                                <div class="user-cell">
                                    <div class="user-av"><?= strtoupper(substr($u['USUARIOS_NOMBRE'],0,1)) ?></div>
                                    <div>
                                        <div class="user-name"><?= htmlspecialchars($u['USUARIOS_NOMBRE'].' '.$u['USUARIOS_APELLIDO']) ?></div>
                                        <div class="user-email"><?= htmlspecialchars($u['USUARIOS_EMAIL']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="color:var(--muted); font-family:monospace"><?= htmlspecialchars($u['USUARIOS_DNI']) ?></td>
                            <td style="color:var(--muted)"><?= htmlspecialchars($u['USUARIOS_TELEFONO'] ?? '—') ?></td>
                            <td><span class="badge pending"><?= htmlspecialchars($u['PERFIL_NOMBRE']) ?></span></td>
                            <td>
                                <div class="row-actions">
                                    <button class="btn btn-approve btn-sm" onclick="aprobarUsuario(<?= $u['USUARIOS_ID'] ?>, this)">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="rechazarUsuario(<?= $u['USUARIOS_ID'] ?>, this)">
                                        <i class="fas fa-ban"></i> Rechazar
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
                </div>
            </div>
        </div><!-- /view-usuarios -->


        <!-- ══════════════════════════════
             VIEW: DUEÑOS (solo superadmin)
        ══════════════════════════════ -->
        <div class="view" id="view-duenos">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Clientes</h1>
                    <p>Dueños de predios registrados en el sistema</p>
                </div>
                <div class="page-header-right">
                    <button class="btn btn-primary" onclick="duenoAbrirCrear()">
                        <i class="fas fa-plus"></i> Nuevo dueño
                    </button>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon kpi-icon g"><i class="fas fa-users-cog"></i></div>
                    <div><h3>Dueños registrados</h3><p>Clientes del sistema con sus predios y actividad</p></div>
                </div>
                <div class="card-body" id="duenos-body">
                    <div class="empty-state">
                        <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
                        <h4>Cargando…</h4>
                    </div>
                </div>
            </div>
        </div><!-- /view-duenos -->

        <!-- ══════════════════════════════
             VIEW: CLIENTES (ravioles)
        ══════════════════════════════ -->
        <div class="view" id="view-clientes">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Clientes</h1>
                    <p class="page-sub">Dueños de predios registrados en la plataforma</p>
                </div>
                <button class="btn btn-primary" onclick="abrirWizard()">
                    <i class="fas fa-magic"></i> Nuevo cliente
                </button>
            </div>
            <input type="text" class="clientes-search" id="clientesSearch"
                   placeholder="Buscar por nombre, email o teléfono..."
                   oninput="filtrarClientes(this.value)">
            <div class="clientes-grid" id="clientesGrid">
                <div style="text-align:center;padding:60px;color:var(--muted);grid-column:1/-1">
                    <i class="fas fa-spinner fa-spin" style="font-size:24px"></i>
                </div>
            </div>
        </div><!-- /view-clientes -->

        <!-- ══════════════════════════════
             VIEW: STAFF
        ══════════════════════════════ -->
        <div class="view" id="view-staff">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Mi Staff</h1>
                    <p>Encargados y empleados asignados a tus canchas</p>
                </div>
                <div class="page-header-right">
                    <button class="btn btn-primary" onclick="staffAbrirCrear()">
                        <i class="fas fa-plus"></i> Agregar encargado/empleado
                    </button>
                </div>
            </div>
            <div class="card">
                <div class="card-header">
                    <div class="card-header-icon kpi-icon b"><i class="fas fa-id-badge"></i></div>
                    <div><h3>Equipo</h3><p>Personal con acceso al sistema</p></div>
                </div>
                <div class="card-body" id="staff-body">
                    <div class="empty-state">
                        <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
                        <h4>Cargando…</h4>
                    </div>
                </div>
            </div>
        </div><!-- /view-staff -->


        <!-- ══════════════════════════════
             VIEW: COMPLEJOS
        ══════════════════════════════ -->
        <div class="view" id="view-complejos">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Complejos</h1>
                    <p>Predios deportivos registrados en el sistema</p>
                </div>
                <div class="page-header-right">
                    <button class="btn btn-primary" onclick="complejosAbrirCrear()">
                        <i class="fas fa-plus"></i> Nuevo complejo
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="dt-toolbar">
                    <div class="dt-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="cmpSearch" placeholder="Buscar complejo…" oninput="cmpFilter()">
                    </div>
                    <div class="dt-filter">
                        <button class="filter-btn active" data-cmpf="all" onclick="cmpSetFilter(this,'all')">Todos</button>
                        <button class="filter-btn" data-cmpf="1"   onclick="cmpSetFilter(this,'1')">Activos</button>
                        <button class="filter-btn" data-cmpf="0"   onclick="cmpSetFilter(this,'0')">Inactivos</button>
                    </div>
                </div>

                <div id="cmpTableWrap">
                    <table>
                        <thead><tr>
                            <th>Complejo</th>
                            <th>Tipo</th>
                            <th>Localidad</th>
                            <th>Actividades</th>
                            <th style="text-align:center">Canchas</th>
                            <th>Estado</th>
                            <th style="width:100px">Acciones</th>
                        </tr></thead>
                        <tbody id="cmpTbody">
                            <tr><td colspan="7"><div class="empty-state">
                                <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
                                <p>Cargando…</p>
                            </div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /view-complejos -->

        <!-- ══ VISTA DETALLE PREDIO ════════════════════════════════════ -->
        <div class="view" id="view-predio" style="display:none">
            <!-- breadcrumb -->
            <div class="page-header">
                <div class="page-header-left">
                    <button onclick="volverAComplejos()" style="background:none;border:none;
                        color:var(--muted);cursor:pointer;font-size:12px;display:flex;align-items:center;gap:6px;
                        margin-bottom:6px;padding:0">
                        <i class="fas fa-arrow-left"></i> Volver a Predios
                    </button>
                    <h1 id="predioNombreHeader">Predio</h1>
                    <p>Canchas, horarios y precios configurados en este predio</p>
                </div>
                <div class="page-header-right" style="gap:8px">
                    <button class="btn btn-primary" id="btnNuevaCancha" onclick="predioCanchaCrear()">
                        <i class="fas fa-plus"></i> Nueva cancha
                    </button>
                </div>
            </div>

            <!-- Grid de canchas del predio -->
            <div id="predioCanchasGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px">
                <div class="card" style="padding:40px;text-align:center;color:var(--muted)">
                    <i class="fas fa-spinner fa-spin" style="font-size:24px"></i>
                </div>
            </div>
        </div><!-- /view-predio -->

        <!-- ══════════════════════════════
             VIEW: CANCHAS
        ══════════════════════════════ -->
        <div class="view" id="view-canchas">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Canchas</h1>
                    <p>Canchas disponibles en todos los complejos</p>
                </div>
                <div class="page-header-right">
                    <button class="btn btn-primary" onclick="canchasAbrirCrear()">
                        <i class="fas fa-plus"></i> Nueva cancha
                    </button>
                </div>
            </div>

            <!-- Filtro rápido por complejo -->
            <div id="cmpFilterBar" style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px"></div>

            <div class="card">
                <div class="dt-toolbar">
                    <div class="dt-search">
                        <i class="fas fa-search"></i>
                        <input type="text" id="canSearch" placeholder="Buscar cancha…" oninput="canFilter()">
                    </div>
                    <div class="dt-filter">
                        <button class="filter-btn active" data-canf="all" onclick="canSetFilter(this,'all')">Todas</button>
                        <button class="filter-btn" data-canf="1"   onclick="canSetFilter(this,'1')">Activas</button>
                        <button class="filter-btn" data-canf="0"   onclick="canSetFilter(this,'0')">Inactivas</button>
                    </div>
                </div>
                <div id="canTableWrap">
                    <table>
                        <thead><tr>
                            <th>Cancha</th>
                            <th>Tipo</th>
                            <th>Complejo</th>
                            <th style="text-align:center">Franjas</th>
                            <th style="text-align:center">Hoy</th>
                            <th>Estado</th>
                            <th style="width:100px">Acciones</th>
                        </tr></thead>
                        <tbody id="canTbody">
                            <tr><td colspan="7"><div class="empty-state">
                                <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
                                <p>Cargando…</p>
                            </div></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div><!-- /view-canchas -->


        <!-- ══════════════════════════════
             VIEW: HORARIOS
        ══════════════════════════════ -->
        <div class="view" id="view-horarios">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Horarios y precios</h1>
                    <p>Configurá las franjas horarias y precios por cancha</p>
                </div>
            </div>

            <!-- Layout 2 paneles -->
            <div id="horLayoutGrid" style="display:grid;grid-template-columns:260px 1fr;gap:16px;align-items:start">

                <!-- Panel izquierdo: selector complejo + lista canchas -->
                <div>
                    <div class="card" style="margin-bottom:12px">
                        <div style="padding:12px 14px;border-bottom:1px solid var(--border)">
                            <label class="form-label" style="margin-bottom:6px">Complejo</label>
                            <select class="form-select" id="horComplejoSel" onchange="horLoadCanchas()"
                                style="font-size:13px;padding:9px 12px">
                                <option value="">Seleccioná…</option>
                            </select>
                        </div>
                    </div>

                    <div class="card">
                        <div style="padding:10px 14px;border-bottom:1px solid var(--border);
                            font-size:10px;font-weight:700;letter-spacing:1px;color:var(--muted);text-transform:uppercase">
                            Canchas
                        </div>
                        <div id="horCanchasList" style="padding:8px">
                            <div style="text-align:center;padding:24px 12px;color:var(--muted);font-size:12px">
                                <i class="fas fa-arrow-up" style="display:block;margin-bottom:6px;opacity:.3"></i>
                                Seleccioná un complejo
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel derecho: franjas de la cancha seleccionada -->
                <div id="horFranjasPanel">
                    <div class="card">
                        <div class="empty-state" style="padding:60px 20px">
                            <div class="es-icon" style="width:52px;height:52px;font-size:22px">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h4>Seleccioná una cancha</h4>
                            <p>Elegí un complejo y una cancha para ver y configurar sus horarios</p>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /view-horarios -->


        <!-- ══════════════════════════════
             VIEW: CIERRES DE CANCHA
        ══════════════════════════════ -->
        <div class="view" id="view-cierres">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Cierres de cancha</h1>
                    <p class="page-sub">Feriados, mantenimiento y bloqueos de horario</p>
                </div>
                <button class="btn btn-primary" onclick="cierreAbrirCrear()">
                    <i class="fas fa-plus"></i> Nuevo cierre
                </button>
            </div>

            <!-- Filtros -->
            <div class="filter-bar" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap">
                <select id="cierreFiltroComplejo" onchange="loadCierres()"
                    style="background:var(--s1);border:1px solid var(--border);border-radius:8px;color:#fff;padding:8px 14px;font-size:0.85rem">
                    <option value="">Todos los predios</option>
                </select>
                <label style="display:flex;align-items:center;gap:6px;font-size:0.83rem;color:var(--muted);cursor:pointer">
                    <input type="checkbox" id="cierreSoloVigentes" checked onchange="loadCierres()">
                    Solo vigentes y futuros
                </label>
            </div>

            <div id="cierresLista"></div>
        </div><!-- /view-cierres -->


        <!-- ══════════════════════════════
             VIEW: TURNOS FIJOS
        ══════════════════════════════ -->
        <div class="view" id="view-turnos">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Turnos Fijos</h1>
                    <p class="page-sub">Clientes recurrentes que ocupan la misma cancha cada semana</p>
                </div>
                <button class="btn btn-primary" onclick="turnoAbrirCrear()">
                    <i class="fas fa-plus"></i> Nuevo turno fijo
                </button>
            </div>

            <!-- Filtros -->
            <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
                <select id="turnoFiltroComplejo" onchange="turnoFiltrarCanchas();loadTurnos()"
                    style="background:var(--s1);border:1px solid var(--border);border-radius:8px;color:#fff;padding:8px 14px;font-size:0.85rem">
                    <option value="">Todos los predios</option>
                </select>
                <select id="turnoFiltroCancha" onchange="loadTurnos()"
                    style="background:var(--s1);border:1px solid var(--border);border-radius:8px;color:#fff;padding:8px 14px;font-size:0.85rem">
                    <option value="">Todas las canchas</option>
                </select>
                <select id="turnoFiltroDia" onchange="loadTurnos()"
                    style="background:var(--s1);border:1px solid var(--border);border-radius:8px;color:#fff;padding:8px 14px;font-size:0.85rem">
                    <option value="">Todos los días</option>
                    <option value="1">Lunes</option><option value="2">Martes</option>
                    <option value="3">Miércoles</option><option value="4">Jueves</option>
                    <option value="5">Viernes</option><option value="6">Sábado</option>
                    <option value="7">Domingo</option>
                </select>
                <label style="display:flex;align-items:center;gap:6px;font-size:0.83rem;color:var(--muted);cursor:pointer">
                    <input type="checkbox" id="turnoSoloActivos" checked onchange="loadTurnos()">
                    Solo vigentes
                </label>
            </div>

            <div id="turnosLista"></div>
        </div><!-- /view-turnos -->


        <!-- ══════════════════════════════
             VIEW: AGENDA
        ══════════════════════════════ -->
        <div class="view" id="view-agenda">
            <div class="page-header">
                <div class="page-header-left">
                    <h1 class="page-title">Agenda</h1>
                    <p class="page-sub" id="agendaSubtitulo">Reservas del día</p>
                </div>
                <div class="page-header-right" style="display:flex;gap:8px;align-items:center">
                    <!-- Toggle Lista / Grilla -->
                    <div class="agenda-toggle">
                        <button class="agenda-toggle-btn active" id="agendaBtnLista" onclick="agendaSetModo('lista')">
                            <i class="fas fa-list"></i> Lista
                        </button>
                        <button class="agenda-toggle-btn" id="agendaBtnGrilla" onclick="agendaSetModo('grilla')">
                            <i class="fas fa-th"></i> Grilla
                        </button>
                    </div>
                    <button class="btn btn-primary" onclick="resAbrirNueva()">
                        <i class="fas fa-plus"></i> Nueva
                    </button>
                    <button class="btn btn-ghost" onclick="agendaFecha=new Date();agendaRecargar()">
                        <i class="fas fa-sync-alt"></i> Hoy
                    </button>
                </div>
            </div>

            <!-- Navegación de fecha + filtros -->
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:16px;flex-wrap:wrap">
                <button class="btn btn-ghost" onclick="agendaIrDia(-1)"><i class="fas fa-chevron-left"></i></button>
                <input type="date" id="agendaFechaInput"
                       style="background:var(--s1);border:1px solid var(--border);border-radius:8px;
                       color:#fff;padding:8px 14px;font-size:0.9rem;cursor:pointer;outline:none"
                       onchange="agendaFecha=new Date(this.value+'T12:00:00');agendaRecargar()">
                <button class="btn btn-ghost" onclick="agendaIrDia(1)"><i class="fas fa-chevron-right"></i></button>

                <!-- Filtro complejo (para grilla) -->
                <select id="agendaFiltroComplejo" onchange="agendaRecargar()"
                        style="background:var(--s1);border:1px solid var(--border);border-radius:8px;
                        color:#fff;padding:8px 12px;font-size:0.83rem;outline:none">
                    <option value="">Todos los predios</option>
                </select>

                <!-- Filtro estado (solo lista) -->
                <select id="agendaFiltroEstado" onchange="agendaRecargar()"
                        style="background:var(--s1);border:1px solid var(--border);border-radius:8px;
                        color:#fff;padding:8px 12px;font-size:0.83rem;margin-left:auto;outline:none"
                        id="agendaFiltroEstado">
                    <option value="">Todos los estados</option>
                    <option value="pendiente">Pendientes</option>
                    <option value="confirmada">Confirmadas</option>
                    <option value="cancelada">Canceladas</option>
                </select>
            </div>

            <!-- KPIs del día (solo modo lista) -->
            <div id="agendaKpis" style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px;margin-bottom:24px"></div>

            <!-- MODO LISTA -->
            <div id="agendaLista"></div>

            <!-- MODO GRILLA -->
            <div id="agendaGrillaWrap" style="display:none">
                <div class="grid-wrap">
                    <table class="agenda-grid" id="agendaGridTable">
                        <thead><tr><th><div class="grid-time">Hora</div></th></tr></thead>
                        <tbody id="agendaGridBody"></tbody>
                    </table>
                </div>
            </div>

        </div><!-- /view-agenda -->

        <!-- ══════════════════════════════
             VIEW: RESERVAS
        ══════════════════════════════ -->
        <div class="view" id="view-reservas">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Reservas</h1>
                    <p class="page-sub" id="resFechaLabel">Hoy</p>
                </div>
                <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                    <input type="date" id="resFecha" class="form-input"
                           style="padding:8px 12px;width:160px"
                           value="<?= date('Y-m-d') ?>"
                           onchange="loadReservas()">
                    <button class="btn btn-primary" onclick="resAbrirNueva()">
                        <i class="fas fa-plus"></i> Nueva reserva
                    </button>
                </div>
            </div>

            <!-- Filtros -->
            <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;align-items:center">
                <div class="dt-filter">
                    <button class="filter-btn active" data-resf="all"   onclick="resSetEstado(this,'')">Todas</button>
                    <button class="filter-btn"        data-resf="pend"  onclick="resSetEstado(this,'pendiente')">Pendientes</button>
                    <button class="filter-btn"        data-resf="conf"  onclick="resSetEstado(this,'confirmada')">Confirmadas</button>
                    <button class="filter-btn"        data-resf="canc"  onclick="resSetEstado(this,'cancelada')">Canceladas</button>
                </div>
                <select id="resFiltroCancha" onchange="loadReservas()"
                        style="background:var(--s1);border:1px solid var(--border);border-radius:8px;color:#fff;padding:8px 12px;font-size:0.83rem">
                    <option value="">Todas las canchas</option>
                </select>
                <span id="resContador" style="font-size:0.8rem;color:var(--muted);margin-left:4px"></span>
            </div>

            <div id="resLista"></div>
        </div><!-- /view-reservas -->

        <!-- ══════════════════════════════
             VIEW: PAGOS
        ══════════════════════════════ -->
        <div class="view" id="view-pagos">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Cobros</h1>
                    <p class="page-sub" id="pagosFechaLabel">Hoy</p>
                </div>
                <input type="date" id="pagosFecha" class="form-input"
                       style="padding:8px 12px;width:160px"
                       value="<?= date('Y-m-d') ?>"
                       onchange="loadPagosView()">
            </div>

            <!-- Resumen rápido -->
            <div id="pagosKpisGrid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:24px">
                <div class="kpi-card">
                    <div class="kpi-icon g"><i class="fas fa-check-circle"></i></div>
                    <div class="kpi-val" id="pvCobrado">—</div>
                    <div class="kpi-lbl">Cobrado hoy</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon o"><i class="fas fa-clock"></i></div>
                    <div class="kpi-val" id="pvPendiente">—</div>
                    <div class="kpi-lbl">Saldo pendiente</div>
                </div>
                <div class="kpi-card">
                    <div class="kpi-icon b"><i class="fas fa-receipt"></i></div>
                    <div class="kpi-val" id="pvTransacciones">—</div>
                    <div class="kpi-lbl">Transacciones</div>
                </div>
            </div>

            <div id="pagosLista"></div>
        </div><!-- /view-pagos -->

        <!-- ══════════════════════════════
             VIEW: REPORTES
        ══════════════════════════════ -->
        <div class="view" id="view-reportes">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Reportes</h1>
                    <p class="page-sub" id="repSubtitulo">Este mes</p>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                    <div style="display:flex;background:var(--s1);border:1px solid var(--border);border-radius:10px;overflow:hidden">
                        <button class="rep-periodo-btn" data-periodo="hoy">Hoy</button>
                        <button class="rep-periodo-btn" data-periodo="semana">Semana</button>
                        <button class="rep-periodo-btn active" data-periodo="mes">Mes</button>
                        <button class="rep-periodo-btn" data-periodo="año">Año</button>
                    </div>
                    <div id="repCustomWrap" style="display:none;gap:6px;align-items:center">
                        <input type="date" id="repDesde" class="form-input" style="width:140px;padding:7px 10px">
                        <span style="color:var(--muted)">→</span>
                        <input type="date" id="repHasta" class="form-input" style="width:140px;padding:7px 10px">
                        <button class="btn btn-primary btn-sm" onclick="loadReportes()">Buscar</button>
                    </div>
                    <div style="display:flex;gap:6px">
                        <button class="btn btn-sm" onclick="repExportar('excel')"
                                style="background:rgba(33,115,70,0.15);color:#4cd964;border:1px solid rgba(76,217,100,0.3);display:flex;align-items:center;gap:6px">
                            <i class="fas fa-file-excel"></i> Excel
                        </button>
                        <button class="btn btn-sm" onclick="repExportar('pdf')"
                                style="background:rgba(231,76,60,0.12);color:#e74c3c;border:1px solid rgba(231,76,60,0.3);display:flex;align-items:center;gap:6px">
                            <i class="fas fa-file-pdf"></i> PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- KPIs -->
            <div class="rep-kpi-grid" id="repKpis">
                <div class="rep-kpi" style="animation:fadeUp 0.3s ease both">
                    <div class="kpi-icon" style="background:rgba(76,217,100,0.1);color:var(--green)"><i class="fas fa-dollar-sign"></i></div>
                    <div class="kpi-val" id="kRepCobrado">—</div>
                    <div class="kpi-lbl">Cobrado</div>
                    <div class="kpi-sub" id="kRepSaldo"></div>
                </div>
                <div class="rep-kpi" style="animation:fadeUp 0.3s ease both;animation-delay:0.05s">
                    <div class="kpi-icon" style="background:rgba(52,152,219,0.1);color:var(--blue)"><i class="fas fa-calendar-check"></i></div>
                    <div class="kpi-val" id="kRepReservas">—</div>
                    <div class="kpi-lbl">Reservas</div>
                    <div class="kpi-sub" id="kRepConfirmadas"></div>
                </div>
                <div class="rep-kpi" style="animation:fadeUp 0.3s ease both;animation-delay:0.1s">
                    <div class="kpi-icon" style="background:rgba(255,149,0,0.1);color:var(--orange)"><i class="fas fa-receipt"></i></div>
                    <div class="kpi-val" id="kRepTicket">—</div>
                    <div class="kpi-lbl">Ticket promedio</div>
                    <div class="kpi-sub" id="kRepTop"></div>
                </div>
                <div class="rep-kpi" style="animation:fadeUp 0.3s ease both;animation-delay:0.15s">
                    <div class="kpi-icon" style="background:rgba(155,89,182,0.1);color:#9b59b6"><i class="fas fa-star"></i></div>
                    <div class="kpi-val" id="kRepCanchaTop">—</div>
                    <div class="kpi-lbl">Cancha top</div>
                    <div class="kpi-sub" id="kRepDiaTop"></div>
                </div>
            </div>

            <!-- Gráfico de ingresos por día -->
            <div class="rep-section">
                <div class="rep-section-title">Ingresos por día</div>
                <div class="rep-chart-wrap" id="repChart">
                    <div style="text-align:center;padding:40px;color:var(--muted)"><i class="fas fa-spinner fa-spin"></i></div>
                </div>
            </div>

            <!-- Ranking de canchas -->
            <div class="rep-section">
                <div class="rep-section-title">Canchas</div>
                <div id="repCanchas"></div>
            </div>
        </div><!-- /view-reportes -->

        <!-- ══════════════════════════════
             VIEW: MI PERFIL
        ══════════════════════════════ -->
        <div class="view" id="view-perfil">
            <div class="page-header">
                <div class="page-header-left">
                    <h1>Mi perfil</h1>
                    <p class="page-sub">Editá tus datos personales y contraseña</p>
                </div>
            </div>

            <div style="max-width:560px">
                <div class="card">
                    <div class="card-header">
                        <div class="card-header-icon kpi-icon b"><i class="fas fa-user"></i></div>
                        <div><h3>Datos personales</h3><p>Tu información de cuenta</p></div>
                    </div>
                    <div class="card-body" style="padding:20px">
                        <form id="frmPerfil" onsubmit="submitPerfil(event)">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:14px">
                                <div>
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" id="perfilNombre" name="nombre" class="form-input" required>
                                </div>
                                <div>
                                    <label class="form-label">Apellido *</label>
                                    <input type="text" id="perfilApellido" name="apellido" class="form-input" required>
                                </div>
                            </div>
                            <div style="margin-bottom:14px">
                                <label class="form-label">Email *</label>
                                <input type="email" id="perfilEmail" name="email" class="form-input" required>
                            </div>
                            <div style="margin-bottom:20px">
                                <label class="form-label">Teléfono</label>
                                <input type="text" id="perfilTel" name="telefono" class="form-input" placeholder="Ej: 221 555-1234">
                            </div>

                            <div style="border-top:1px solid var(--b1);padding-top:18px;margin-bottom:14px">
                                <div style="font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:var(--muted);margin-bottom:12px">
                                    Cambiar contraseña <span style="font-weight:400">(dejá en blanco para no cambiar)</span>
                                </div>
                                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                                    <div>
                                        <label class="form-label">Nueva contraseña</label>
                                        <input type="password" id="perfilPass" name="password" class="form-input" placeholder="Mínimo 6 caracteres" minlength="6">
                                    </div>
                                    <div>
                                        <label class="form-label">Confirmar contraseña</label>
                                        <input type="password" id="perfilPass2" name="password2" class="form-input" placeholder="Repetí la contraseña">
                                    </div>
                                </div>
                            </div>

                            <div style="display:flex;justify-content:flex-end">
                                <button type="submit" class="btn btn-primary" id="btnPerfilSubmit">
                                    <i class="fas fa-save"></i> Guardar cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!-- /view-perfil -->

    </div><!-- /content -->
</div><!-- /main -->


<!-- ═══════════ MODAL FRANJA ═══════════ -->
<div class="modal-overlay" id="modalFranja">
    <div class="modal" style="max-width:480px">
        <div class="modal-head">
            <div class="modal-head-icon" style="background:rgba(52,152,219,.15);color:var(--blue)">
                <i class="fas fa-clock"></i>
            </div>
            <div>
                <h3 id="mFrTitle">Nueva franja horaria</h3>
                <p id="mFrSub">Definí horario, días y precio</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalFranja')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mFrId">
            <input type="hidden" id="mFrCanchaId">

            <!-- Selector de días visual -->
            <div class="form-row">
                <label class="form-label">Días de la semana <span>*</span></label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:2px" id="mFrDias">
                    <!-- generado por JS -->
                </div>
                <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
                    <button type="button" onclick="frSetDias([1,2,3,4,5])"
                        style="padding:4px 10px;border-radius:6px;border:1px solid var(--border);
                        background:var(--s1);color:var(--muted);font-size:10px;font-weight:700;
                        cursor:pointer;transition:all .15s"
                        onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
                        Lun-Vie
                    </button>
                    <button type="button" onclick="frSetDias([6,7])"
                        style="padding:4px 10px;border-radius:6px;border:1px solid var(--border);
                        background:var(--s1);color:var(--muted);font-size:10px;font-weight:700;
                        cursor:pointer;transition:all .15s"
                        onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
                        Sáb-Dom
                    </button>
                    <button type="button" onclick="frSetDias([1,2,3,4,5,6,7])"
                        style="padding:4px 10px;border-radius:6px;border:1px solid var(--border);
                        background:var(--s1);color:var(--muted);font-size:10px;font-weight:700;
                        cursor:pointer;transition:all .15s"
                        onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
                        Todos
                    </button>
                    <button type="button" onclick="frSetDias([])"
                        style="padding:4px 10px;border-radius:6px;border:1px solid var(--border);
                        background:var(--s1);color:var(--muted);font-size:10px;font-weight:700;
                        cursor:pointer;transition:all .15s"
                        onmouseover="this.style.color='var(--text)'" onmouseout="this.style.color='var(--muted)'">
                        Limpiar
                    </button>
                </div>
                <div class="form-error" id="mFrDiasErr"></div>
            </div>

            <!-- Horario -->
            <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:8px;align-items:end;margin-bottom:16px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Hora inicio <span>*</span></label>
                    <input type="time" class="form-input" id="mFrInicio" style="color-scheme:dark"
                        onchange="frActualizarResumen()">
                    <div class="form-error" id="mFrInicioErr"></div>
                </div>
                <div style="text-align:center;color:var(--muted);font-size:18px;font-weight:300;
                    padding-bottom:10px;align-self:center">→</div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Hora fin <span>*</span></label>
                    <input type="time" class="form-input" id="mFrFin" style="color-scheme:dark"
                        onchange="frActualizarResumen()">
                    <div class="form-error" id="mFrFinErr"></div>
                </div>
            </div>

            <!-- Precios -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Precio <span>*</span></label>
                    <div style="position:relative">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                            color:var(--muted);font-weight:700;font-size:13px">$</span>
                        <input type="number" class="form-input" id="mFrPrecio"
                            placeholder="0" min="0" step="100"
                            style="padding-left:26px" onchange="frActualizarResumen()">
                    </div>
                    <div class="form-error" id="mFrPrecioErr"></div>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Seña mínima</label>
                    <div style="position:relative">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                            color:var(--muted);font-weight:700;font-size:13px">$</span>
                        <input type="number" class="form-input" id="mFrSena"
                            placeholder="0" min="0" step="100"
                            style="padding-left:26px">
                    </div>
                    <div class="form-hint">Opcional. 0 = sin seña.</div>
                </div>
            </div>

            <!-- Resumen preview -->
            <div id="mFrResumen" style="display:none;border-radius:10px;
                border:1px solid rgba(52,152,219,.25);background:rgba(52,152,219,.06);
                padding:12px 14px">
                <div style="font-size:10px;color:var(--muted);text-transform:uppercase;
                    letter-spacing:.5px;font-weight:700;margin-bottom:8px">Resumen</div>
                <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px">
                    <div id="mFrResHoras" style="font-size:18px;font-weight:800;color:var(--blue)">—</div>
                    <div style="text-align:right">
                        <div id="mFrResPrecio" style="font-size:15px;font-weight:800;color:var(--green)">—</div>
                        <div id="mFrResSena" style="font-size:11px;color:var(--muted)"></div>
                    </div>
                </div>
                <div id="mFrResDias" style="margin-top:8px;display:flex;gap:4px;flex-wrap:wrap"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalFranja')">Cancelar</button>
            <button class="btn btn-primary" id="mFrSubmit" onclick="submitFranja()"
                style="background:linear-gradient(135deg,var(--blue),#2980b9)">
                <i class="fas fa-check"></i> <span id="mFrSubmitTxt">Crear franja</span>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════ MODAL GENERAR HORARIOS ═══════════ -->
<div class="modal-overlay" id="modalGenerar">
    <div class="modal" style="max-width:500px">
        <div class="modal-head">
            <div class="modal-head-icon" style="background:rgba(76,217,100,.12);color:var(--green)">
                <i class="fas fa-magic"></i>
            </div>
            <div>
                <h3>Generador de horarios</h3>
                <p id="mGenSub">Creá todos los turnos de la semana de una vez</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalGenerar')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <!-- Días -->
            <div class="form-row">
                <label class="form-label">Días <span>*</span></label>
                <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:2px" id="mGenDias"></div>
                <div style="display:flex;gap:6px;margin-top:8px;flex-wrap:wrap">
                    <button type="button" onclick="genSetDias([1,2,3,4,5])" class="gen-preset-btn">Lun-Vie</button>
                    <button type="button" onclick="genSetDias([6,7])" class="gen-preset-btn">Sáb-Dom</button>
                    <button type="button" onclick="genSetDias([1,2,3,4,5,6,7])" class="gen-preset-btn">Todos</button>
                    <button type="button" onclick="genSetDias([])" class="gen-preset-btn">Limpiar</button>
                </div>
                <div class="form-error" id="mGenDiasErr"></div>
            </div>

            <!-- Apertura / Cierre -->
            <div style="display:grid;grid-template-columns:1fr auto 1fr;gap:8px;align-items:end;margin-bottom:16px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Apertura <span>*</span></label>
                    <input type="time" class="form-input" id="mGenApertura" style="color-scheme:dark"
                        onchange="genActualizarPreview()">
                    <div class="form-error" id="mGenAperturaErr"></div>
                </div>
                <div style="text-align:center;color:var(--muted);font-size:18px;font-weight:300;
                    padding-bottom:10px;align-self:center">→</div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Cierre <span>*</span></label>
                    <input type="time" class="form-input" id="mGenCierre" style="color-scheme:dark"
                        onchange="genActualizarPreview()">
                    <div class="form-error" id="mGenCierreErr"></div>
                </div>
            </div>

            <!-- Duración y precio -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Duración del turno <span>*</span></label>
                    <select class="form-input" id="mGenDuracion" onchange="genActualizarPreview()">
                        <option value="30">30 minutos</option>
                        <option value="60" selected>1 hora</option>
                        <option value="90">1 hora 30 min</option>
                        <option value="120">2 horas</option>
                    </select>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Precio por turno <span>*</span></label>
                    <div style="position:relative">
                        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                            color:var(--muted);font-weight:700;font-size:13px">$</span>
                        <input type="number" class="form-input" id="mGenPrecio"
                            placeholder="0" min="0" step="100"
                            style="padding-left:26px" oninput="genActualizarPreview()">
                    </div>
                    <div class="form-error" id="mGenPrecioErr"></div>
                </div>
            </div>

            <div class="form-row" style="margin-bottom:16px">
                <label class="form-label">Seña mínima (opcional)</label>
                <div style="position:relative">
                    <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);
                        color:var(--muted);font-weight:700;font-size:13px">$</span>
                    <input type="number" class="form-input" id="mGenSena"
                        placeholder="0" min="0" step="100" style="padding-left:26px">
                </div>
                <div class="form-hint">0 = sin seña</div>
            </div>

            <!-- Preview -->
            <div id="mGenPreview" style="display:none">
                <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;
                    letter-spacing:.5px;margin-bottom:8px">
                    Vista previa — <span id="mGenCount">0</span> franjas a crear
                </div>
                <div id="mGenSlots" style="display:flex;flex-wrap:wrap;gap:5px;max-height:130px;
                    overflow-y:auto;padding:10px;border-radius:10px;
                    background:var(--s1);border:1px solid var(--border)">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalGenerar')">Cancelar</button>
            <button class="btn btn-primary" id="mGenSubmit" onclick="submitGenerar()"
                style="background:linear-gradient(135deg,var(--green),#34c759);color:#000">
                <i class="fas fa-magic"></i> <span id="mGenSubmitTxt">Generar horarios</span>
            </button>
        </div>
    </div>
</div>

<!-- ═══════════ MODAL CANCHAS ═══════════ -->
<div class="modal-overlay" id="modalCancha">
    <div class="modal modal-lg" style="max-width:580px">

        <div class="modal-head">
            <div class="modal-head-icon" id="mCanIcon"
                style="background:rgba(255,149,0,.15);color:var(--orange)">
                <i class="fas fa-plus"></i>
            </div>
            <div>
                <h3 id="mCanTitle">Nueva cancha</h3>
                <p id="mCanSub">Asignada a un complejo y tipo de deporte</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalCancha')"><i class="fas fa-times"></i></button>
        </div>

        <!-- Steps -->
        <div style="display:flex;border-bottom:1px solid var(--border)">
            <div class="mstep-can active" id="mstepCan-1" onclick="goCanStep(1)"
                style="flex:1;padding:11px 16px;text-align:center;cursor:pointer;border-bottom:2px solid var(--orange);transition:all .2s">
                <div style="font-size:10px;font-weight:700;color:var(--orange);text-transform:uppercase;letter-spacing:.5px">Paso 1</div>
                <div style="font-size:12px;font-weight:600;color:var(--orange);margin-top:2px">Datos de la cancha</div>
            </div>
            <div class="mstep-can" id="mstepCan-2" onclick="goCanStep(2)"
                style="flex:1;padding:11px 16px;text-align:center;cursor:pointer;border-bottom:2px solid transparent;transition:all .2s">
                <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">Paso 2</div>
                <div style="font-size:12px;font-weight:600;color:var(--muted);margin-top:2px">Encargados</div>
            </div>
        </div>

        <div class="modal-body" style="max-height:58vh;overflow-y:auto;padding:20px">
            <input type="hidden" id="mCanId">

            <!-- PASO 1 -->
            <div id="mCanStep1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-row" style="margin:0;grid-column:1/-1">
                        <label class="form-label">Nombre de la cancha <span>*</span></label>
                        <input type="text" class="form-input" id="mCanNombre"
                            placeholder="Ej: Cancha 1 · Fútbol 5" maxlength="100">
                        <div class="form-error" id="mCanNombreErr"></div>
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Complejo <span>*</span></label>
                        <select class="form-select" id="mCanComplejo" onchange="canUpdateTipoHint()">
                            <option value="">Seleccioná…</option>
                        </select>
                        <div class="form-error" id="mCanComplejoErr"></div>
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Tipo de cancha <span>*</span></label>
                        <select class="form-select" id="mCanTipo" onchange="canUpdateIcon()">
                            <option value="">Seleccioná…</option>
                        </select>
                        <div class="form-error" id="mCanTipoErr"></div>
                    </div>
                    <div class="form-row" style="margin:0;grid-column:1/-1">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-input" id="mCanDesc"
                            placeholder="Medidas, superficie, iluminación, vestuarios…"
                            rows="3" maxlength="500"
                            style="resize:vertical;min-height:70px;font-family:inherit"></textarea>
                    </div>
                </div>

                <!-- Preview card -->
                <div id="canPreview" style="margin-top:16px;display:none;
                    border-radius:10px;border:1px solid rgba(255,149,0,.2);
                    background:rgba(255,149,0,.05);padding:12px 14px">
                    <div style="font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;font-weight:700">
                        Vista previa
                    </div>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div id="canPrevIcon" style="width:40px;height:40px;border-radius:10px;
                            background:rgba(255,149,0,.15);color:var(--orange);
                            display:flex;align-items:center;justify-content:center;font-size:18px">
                            <i class="fas fa-futbol"></i>
                        </div>
                        <div>
                            <div id="canPrevNombre" style="font-weight:700;font-size:14px">—</div>
                            <div id="canPrevSub" style="font-size:11px;color:var(--muted)">—</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PASO 2 -->
            <div id="mCanStep2" style="display:none">
                <p style="font-size:12px;color:var(--muted);margin-bottom:14px">
                    Asigná encargados o empleados responsables de esta cancha. Es opcional — podés dejarlo vacío.
                </p>
                <div id="encargadosGrid" style="display:flex;flex-direction:column;gap:6px">
                    <div style="text-align:center;color:var(--muted);padding:16px;font-size:12px">
                        <i class="fas fa-spinner fa-spin"></i> Cargando…
                    </div>
                </div>
            </div>
        </div>

        <div class="modal-footer" style="justify-content:space-between">
            <button class="btn btn-ghost" id="mCanBtnPrev" onclick="goCanStep(canCurrentStep-1)"
                style="display:none">
                <i class="fas fa-arrow-left"></i> Anterior
            </button>
            <div style="display:flex;gap:8px;margin-left:auto">
                <button class="btn btn-ghost" onclick="closeModal('modalCancha')">Cancelar</button>
                <button class="btn btn-primary" id="mCanBtnNext" onclick="canNextOrSubmit()"
                    style="background:linear-gradient(135deg,var(--orange),#e67e22)">
                    Siguiente <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════ MODAL CATÁLOGO ═══════════ -->
<div class="modal-overlay" id="modalCat">
    <div class="modal">
        <div class="modal-head">
            <div class="modal-head-icon g" id="mCatIcon"><i class="fas fa-plus"></i></div>
            <div>
                <h3 id="mCatTitle">Nuevo tipo</h3>
                <p id="mCatSub">Completá los datos</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalCat')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mCatId">
            <div class="form-row">
                <label class="form-label">Nombre <span>*</span></label>
                <input type="text" class="form-input" id="mCatNombre" placeholder="Ej: Fútbol 5" maxlength="100">
                <div class="form-error" id="mCatNombreErr"></div>
            </div>
            <div class="form-row">
                <label class="form-label">Ícono <span style="color:var(--muted);font-weight:400;text-transform:none">(opcional)</span></label>
                <input type="text" class="form-input" id="mCatIconoInput" placeholder="fa-futbol" oninput="previewIcon()">
                <div class="form-hint">Clase FontAwesome 6 sin el prefijo "fas". <a href="https://fontawesome.com/icons" target="_blank" style="color:var(--green)">Ver íconos →</a></div>
                <div class="icon-row" id="iconPresetRow">
                    <!-- populado por JS -->
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalCat')">Cancelar</button>
            <button class="btn btn-primary" id="mCatSubmit" onclick="submitCatalogo()">
                <i class="fas fa-check"></i> <span id="mCatSubmitText">Crear</span>
            </button>
        </div>
    </div>
</div>


<!-- ═══════════ MODAL COMPLEJOS ═══════════ -->
<div class="modal-overlay" id="modalComplejo">
    <div class="modal modal-lg" style="max-width:620px">

        <div class="modal-head">
            <div class="modal-head-icon b" id="mCmpIcon"><i class="fas fa-plus"></i></div>
            <div>
                <h3 id="mCmpTitle">Nuevo complejo</h3>
                <p id="mCmpSub">Completá los datos en los tres pasos</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalComplejo')"><i class="fas fa-times"></i></button>
        </div>

        <!-- Steps indicator -->
        <div style="display:flex;border-bottom:1px solid var(--border);padding:0">
            <div class="mstep active" id="mstep-1" onclick="goStep(1)" style="flex:1;padding:12px 16px;text-align:center;cursor:pointer;border-bottom:2px solid var(--green);transition:all .2s">
                <div style="font-size:10px;font-weight:700;color:var(--green);text-transform:uppercase;letter-spacing:.5px">Paso 1</div>
                <div style="font-size:12px;font-weight:600;color:var(--green);margin-top:2px">Datos generales</div>
            </div>
            <div class="mstep" id="mstep-2" onclick="goStep(2)" style="flex:1;padding:12px 16px;text-align:center;cursor:pointer;border-bottom:2px solid transparent;transition:all .2s">
                <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">Paso 2</div>
                <div style="font-size:12px;font-weight:600;color:var(--muted);margin-top:2px">Actividades</div>
            </div>
            <div class="mstep" id="mstep-3" onclick="goStep(3)" style="flex:1;padding:12px 16px;text-align:center;cursor:pointer;border-bottom:2px solid transparent;transition:all .2s">
                <div style="font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px">Paso 3</div>
                <div style="font-size:12px;font-weight:600;color:var(--muted);margin-top:2px">Horarios</div>
            </div>
        </div>

        <div class="modal-body" style="max-height:60vh;overflow-y:auto;padding:20px">
            <input type="hidden" id="mCmpId">

            <!-- PASO 1: Datos generales -->
            <div id="mCmpStep1">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-row" style="margin:0;grid-column:1/-1">
                        <label class="form-label">Nombre del complejo <span>*</span></label>
                        <input type="text" class="form-input" id="mCmpNombre" placeholder="Ej: Complejo Los Pinos" maxlength="150">
                        <div class="form-error" id="mCmpNombreErr"></div>
                    </div>
                    <div class="form-row" style="margin:0;grid-column:1/-1">
                        <label class="form-label">Dirección <span>*</span></label>
                        <input type="text" class="form-input" id="mCmpDir" placeholder="Ej: Av. Siempreviva 742" maxlength="200">
                        <div class="form-error" id="mCmpDirErr"></div>
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Teléfono</label>
                        <input type="text" class="form-input" id="mCmpTel" placeholder="Ej: 011-4444-5555" maxlength="30">
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-input" id="mCmpEmail" placeholder="contacto@complejo.com" maxlength="150">
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Provincia <span>*</span></label>
                        <select class="form-select" id="mCmpProv" onchange="geoOnChange('provincia','mCmpProv','mCmpPartido','mCmpLoc')">
                            <option value="">Seleccioná provincia…</option>
                        </select>
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Partido / Departamento <span>*</span></label>
                        <select class="form-select" id="mCmpPartido" onchange="geoOnChange('partido','mCmpProv','mCmpPartido','mCmpLoc')" disabled>
                            <option value="">Primero elegí provincia…</option>
                        </select>
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Localidad <span>*</span></label>
                        <select class="form-select" id="mCmpLoc" onchange="geoOnChange('localidad','mCmpProv','mCmpPartido','mCmpLoc')" disabled>
                            <option value="">Primero elegí partido…</option>
                        </select>
                        <div class="form-error" id="mCmpLocErr"></div>
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Tipo de complejo</label>
                        <select class="form-select" id="mCmpTipo">
                            <option value="">Sin clasificar</option>
                        </select>
                    </div>
                    <div class="form-row" style="margin:0;grid-column:1/-1">
                        <label class="form-label">Descripción</label>
                        <textarea class="form-input" id="mCmpDesc" placeholder="Descripción breve del complejo…"
                            rows="2" maxlength="500"
                            style="resize:vertical;min-height:60px;font-family:inherit"></textarea>
                    </div>
                </div>
            </div>

            <!-- PASO 2: Actividades -->
            <div id="mCmpStep2" style="display:none">
                <p style="font-size:12px;color:var(--muted);margin-bottom:14px">
                    Seleccioná qué deportes o actividades ofrece este complejo. Podés marcar una como destacada para que aparezca primero.
                </p>
                <div id="actividadesGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:10px">
                    <div style="color:var(--muted);font-size:12px;grid-column:1/-1;text-align:center;padding:20px">
                        <i class="fas fa-spinner fa-spin"></i> Cargando…
                    </div>
                </div>
            </div>

            <!-- PASO 3: Horarios -->
            <div id="mCmpStep3" style="display:none">
                <p style="font-size:12px;color:var(--muted);margin-bottom:14px">
                    Configurá el horario de atención para cada día. Dejá sin marcar los días que el complejo no abre.
                </p>
                <div style="display:flex;flex-direction:column;gap:8px" id="horariosTable">
                    <!-- Generado por JS -->
                </div>
            </div>
        </div>

        <div class="modal-footer" style="justify-content:space-between">
            <div style="display:flex;gap:8px">
                <button class="btn btn-ghost" id="mCmpBtnPrev" onclick="goStep(cmpCurrentStep-1)" style="display:none">
                    <i class="fas fa-arrow-left"></i> Anterior
                </button>
            </div>
            <div style="display:flex;gap:8px">
                <button class="btn btn-ghost" onclick="closeModal('modalComplejo')">Cancelar</button>
                <button class="btn btn-primary" id="mCmpBtnNext" onclick="cmpNextOrSubmit()">
                    Siguiente <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════ TOASTS ═══════════ -->
<div class="toast-container" id="toastContainer"></div>


<script>
// ═══════════════════════════════════════════════
// ESTADO GLOBAL
// ═══════════════════════════════════════════════
let currentCat   = 'tipo_cancha';
let catData      = {};
let filterActive = 'all';
let searchText   = '';

const API = 'api/catalogo.php';

const CAT_LABELS = {
    tipo_cancha:   { label: 'Tipo de cancha',   sub: 'Tipos de canchas disponibles en el sistema' },
    tipo_complejo: { label: 'Tipo de complejo',  sub: 'Clasificación de complejos deportivos' },
    medio_pago:    { label: 'Medio de pago',     sub: 'Métodos de pago disponibles para reservas' },
};

const ICON_PRESETS = {
    tipo_cancha:   ['fa-futbol','fa-table-tennis-paddle-ball','fa-volleyball','fa-baseball','fa-basketball','fa-hockey-puck','fa-bowling-ball'],
    tipo_complejo: ['fa-shield-halved','fa-building','fa-person-running','fa-dumbbell','fa-tree','fa-house-chimney','fa-water'],
    medio_pago:    ['fa-money-bill','fa-building-columns','fa-mobile-screen','fa-credit-card','fa-wallet','fa-qrcode'],
};

// ═══════════════════════════════════════════════
// NAVEGACIÓN
// ═══════════════════════════════════════════════
const VIEW_LABELS = {
    dashboard:'Dashboard', catalogos:'Tipos y categorías', complejos:'Complejos',
    canchas:'Canchas', horarios:'Horarios y precios', cierres:'Cierres de cancha',
    turnos:'Turnos Fijos',
    reportes:'Reportes',
    agenda:'Agenda', reservas:'Reservas', pagos:'Pagos', usuarios:'Usuarios',
    staff:'Mi Staff', duenos:'Clientes / Dueños', clientes:'Clientes',
    perfil:'Mi perfil'
};
const PERFIL = <?= $perfil ?>;

function showView(el) {
    const name = el.dataset.view;
    document.querySelectorAll('.view').forEach(v => v.classList.remove('active'));
    document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));
    document.getElementById('view-'+name).classList.add('active');
    el.classList.add('active');
    document.getElementById('breadcrumb').textContent = VIEW_LABELS[name] || name;

    // Auto-reload al cambiar de sección
    if (name === 'agenda')    loadAgenda();
    if (name === 'reservas')  loadReservas();
    if (name === 'pagos')     loadPagosView();
    if (name === 'catalogos') loadCat(currentCat);
    if (window.innerWidth < 768) closeSidebar();
}

async function actualizarBadgePendientes() {
    try {
        const r = await fetch('api/reservas.php?action=pendientes_count');
        const j = await r.json();
        if (!j.ok) return;
        const { count } = j.data;
        const badge    = document.getElementById('badge-res-pend');
        const badgeNum = document.getElementById('badge-res-pend-num');
        if (count === 0) {
            // Sin pendientes: ocultar badge y notificación
            if (badge) badge.style.display = 'none';
            cerrarNotifPendientes();
        } else {
            // Actualizar número
            if (badge) badge.style.display = '';
            if (badgeNum) badgeNum.textContent = count;
        }
    } catch(e) {}
}

// ═══════════════════════════════════════════════
// SIDEBAR
// ═══════════════════════════════════════════════
let sidebarOpen = window.innerWidth >= 768;

function toggleSidebar() {
    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.toggle('mobile-open');
        document.getElementById('sbOverlay').classList.toggle('show');
    } else {
        document.getElementById('sidebar').classList.toggle('hidden');
        document.getElementById('main').classList.toggle('expanded');
    }
}
function closeSidebar() {
    document.getElementById('sidebar').classList.remove('mobile-open');
    document.getElementById('sbOverlay').classList.remove('show');
}

// ═══════════════════════════════════════════════
// CATÁLOGOS
// ═══════════════════════════════════════════════
function switchCat(tab) {
    document.querySelectorAll('.stab').forEach(t => t.classList.remove('active'));
    tab.classList.add('active');
    currentCat = tab.dataset.cat;
    document.getElementById('catSearch').value = '';
    searchText = ''; filterActive = 'all';
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    document.querySelector('.filter-btn[data-filter="all"]').classList.add('active');
    loadCat(currentCat);
}

async function loadCat(tabla) {
    document.getElementById('catTbody').innerHTML =
        `<tr><td colspan="5"><div class="empty-state">
            <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
            <p style="color:var(--muted)">Cargando…</p>
        </div></td></tr>`;

    const res  = await fetch(`${API}?action=listar&tabla=${tabla}`);
    const json = await res.json();
    if (!json.ok) { toast(json.msg, 'err'); return; }
    catData[tabla] = json.data;
    updateCount(tabla, json.data.length);
    renderTable(tabla, json.data);
}

function updateCount(tabla, n) {
    const el = document.getElementById('cnt-'+tabla);
    if (el) el.textContent = n;
}

function renderTable(tabla, rows) {
    filterActive = filterActive ?? 'all';
    const search = searchText.toLowerCase();
    const cfg    = { tipo_cancha: 'TIPO_CANCHA', tipo_complejo: 'TIPO_COMPLEJO', medio_pago: 'MEDIO_PAGO' }[tabla];
    const idKey  = cfg + '_ID';
    const nmKey  = cfg + '_NOMBRE';
    const icKey  = cfg + '_ICONO';

    let filtered = rows.filter(r => {
        const matchFilter = filterActive === 'all' || String(r.ACTIVO) === filterActive;
        const matchSearch = !search || r[nmKey].toLowerCase().includes(search);
        return matchFilter && matchSearch;
    });

    if (!filtered.length) {
        document.getElementById('catTbody').innerHTML =
            `<tr><td colspan="5"><div class="empty-state">
                <div class="es-icon"><i class="fas fa-box-open"></i></div>
                <h4>Sin resultados</h4>
                <p>No se encontraron registros con los filtros actuales.</p>
            </div></td></tr>`;
        return;
    }

    const html = filtered.map((r,i) => {
        const activo = parseInt(r.ACTIVO);
        const ico    = r[icKey] || '';
        return `<tr data-id="${r[idKey]}" style="animation:fadeUp .25s ease ${i*.03}s both">
            <td>
                <div class="td-icon" style="${ico ? 'background:rgba(76,217,100,.1);color:var(--green)' : ''}">
                    ${ico ? `<i class="fas ${ico}"></i>` : '<i class="fas fa-tag"></i>'}
                </div>
            </td>
            <td style="font-weight:600">${esc(r[nmKey])}</td>
            <td style="font-family:monospace;color:var(--muted);font-size:11px">${ico || '—'}</td>
            <td><span class="badge ${activo?'active':'inactive'}">${activo?'Activo':'Inactivo'}</span></td>
            <td>
                <div class="row-actions">
                    <button class="act-btn edit" title="Editar" onclick="openModalEditar(${r[idKey]},'${esc(r[nmKey])}','${esc(ico)}')">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="act-btn toggle ${activo?'on':''}" title="${activo?'Desactivar':'Activar'}"
                        onclick="toggleItem(${r[idKey]},this)">
                        <i class="fas ${activo?'fa-toggle-on':'fa-toggle-off'}"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    document.getElementById('catTbody').innerHTML = html;
}

function filterTable() {
    searchText = document.getElementById('catSearch').value;
    if (catData[currentCat]) renderTable(currentCat, catData[currentCat]);
}

function setFilter(btn, val) {
    document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    filterActive = val;
    filterTable();
}

function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ═══════════════════════════════════════════════
// MODAL CATÁLOGO
// ═══════════════════════════════════════════════
function openModalCrear() {
    const lbl = CAT_LABELS[currentCat];
    document.getElementById('mCatTitle').textContent = 'Nuevo ' + lbl.label.toLowerCase();
    document.getElementById('mCatSub').textContent   = lbl.sub;
    document.getElementById('mCatId').value          = '';
    document.getElementById('mCatNombre').value      = '';
    document.getElementById('mCatIconoInput').value  = '';
    document.getElementById('mCatSubmitText').textContent = 'Crear';
    document.getElementById('mCatNombreErr').style.display = 'none';
    renderIconPresets(currentCat, '');
    document.getElementById('modalCat').classList.add('show');
    setTimeout(() => document.getElementById('mCatNombre').focus(), 150);
}

function openModalEditar(id, nombre, icono) {
    const lbl = CAT_LABELS[currentCat];
    document.getElementById('mCatTitle').textContent = 'Editar ' + lbl.label.toLowerCase();
    document.getElementById('mCatSub').textContent   = lbl.sub;
    document.getElementById('mCatId').value          = id;
    document.getElementById('mCatNombre').value      = nombre;
    document.getElementById('mCatIconoInput').value  = icono;
    document.getElementById('mCatSubmitText').textContent = 'Guardar';
    document.getElementById('mCatNombreErr').style.display = 'none';
    document.getElementById('mCatIcon').innerHTML    = '<i class="fas fa-pen"></i>';
    renderIconPresets(currentCat, icono);
    document.getElementById('modalCat').classList.add('show');
    setTimeout(() => document.getElementById('mCatNombre').focus(), 150);
}

function renderIconPresets(tabla, selected) {
    const presets = ICON_PRESETS[tabla] || [];
    const row = document.getElementById('iconPresetRow');
    row.innerHTML = presets.map(ic =>
        `<div class="icon-opt ${ic===selected?'sel':''}" title="${ic}" onclick="selectIconPreset('${ic}',this)">
            <i class="fas ${ic}"></i>
         </div>`
    ).join('');
}

function selectIconPreset(ic, el) {
    document.getElementById('mCatIconoInput').value = ic;
    document.querySelectorAll('.icon-opt').forEach(o => o.classList.remove('sel'));
    el.classList.add('sel');
}

function previewIcon() {
    const val = document.getElementById('mCatIconoInput').value.trim();
    document.querySelectorAll('.icon-opt').forEach(o => {
        o.classList.toggle('sel', o.title === val);
    });
}

async function submitCatalogo() {
    const id     = document.getElementById('mCatId').value;
    const nombre = document.getElementById('mCatNombre').value.trim();
    const icono  = document.getElementById('mCatIconoInput').value.trim();
    const errEl  = document.getElementById('mCatNombreErr');

    errEl.style.display = 'none';
    if (!nombre) {
        errEl.textContent    = 'El nombre es obligatorio.';
        errEl.style.display  = 'block';
        document.getElementById('mCatNombre').focus();
        return;
    }

    const btn = document.getElementById('mCatSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

    const fd = new FormData();
    fd.append('tabla', currentCat);
    fd.append('action', id ? 'editar' : 'crear');
    fd.append('nombre', nombre);
    fd.append('icono',  icono);
    if (id) fd.append('id', id);

    try {
        const res  = await fetch(API, { method:'POST', body:fd });
        const json = await res.json();
        if (json.ok) {
            toast(json.msg, 'ok');
            closeModal('modalCat');
            loadCat(currentCat);
        } else {
            errEl.textContent   = json.msg;
            errEl.style.display = 'block';
        }
    } catch(e) {
        toast('Error de red.', 'err');
    } finally {
        btn.disabled = false;
        btn.innerHTML = `<i class="fas fa-check"></i> <span id="mCatSubmitText">${id?'Guardar':'Crear'}</span>`;
    }
}

async function toggleItem(id, btn) {
    const fd = new FormData();
    fd.append('tabla', currentCat);
    fd.append('action', 'toggle');
    fd.append('id', id);
    btn.disabled = true;
    try {
        const res  = await fetch(API, { method:'POST', body:fd });
        const json = await res.json();
        if (json.ok) { toast(json.msg, 'ok'); loadCat(currentCat); }
        else           toast(json.msg, 'err');
    } catch(e) { toast('Error de red.','err'); }
    finally { btn.disabled = false; }
}

// ═══════════════════════════════════════════════
// USUARIOS
// ═══════════════════════════════════════════════
async function aprobarUsuario(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    const fd = new FormData();
    fd.append('id', id);
    const res  = await fetch('api/usuarios.php?action=aprobar', { method:'POST', body:fd });
    const json = await res.json();
    if (json.ok) {
        toast('Usuario aprobado correctamente.', 'ok');
        const row = document.getElementById('pend-'+id);
        if (row) { row.style.opacity='0'; row.style.transform='translateX(20px)'; setTimeout(()=>row.remove(),300); }
        document.querySelectorAll('#pend-'+id).forEach(r=>r.remove());
    } else {
        toast(json.msg, 'err');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Aprobar';
    }
}

async function rechazarUsuario(id, btn) {
    if (!confirm('¿Seguro que querés rechazar esta cuenta? El usuario deberá registrarse nuevamente.')) return;
    btn.disabled = true;
    const fd = new FormData();
    fd.append('id', id);
    const res  = await fetch('api/usuarios.php?action=rechazar', { method:'POST', body:fd });
    const json = await res.json();
    if (json.ok) {
        toast('Cuenta rechazada.', 'inf');
        const row = document.getElementById('pend-'+id);
        if (row) { row.style.opacity='0'; setTimeout(()=>row.remove(),300); }
    } else {
        toast(json.msg, 'err');
        btn.disabled = false;
    }
}

// ═══════════════════════════════════════════════
// MODAL GENÉRICO
// ═══════════════════════════════════════════════
function closeModal(id) {
    document.getElementById(id).classList.remove('show');
}
document.querySelectorAll('.modal-overlay').forEach(m => {
    m.addEventListener('click', e => { if (e.target === m) m.classList.remove('show'); });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') document.querySelectorAll('.modal-overlay.show').forEach(m => m.classList.remove('show'));
});

// ═══════════════════════════════════════════════
// TOASTS
// ═══════════════════════════════════════════════
function toast(msg, type = 'ok') {
    const icons = { ok:'fa-check', err:'fa-exclamation', inf:'fa-info' };
    const t = document.createElement('div');
    t.className = 'toast';
    t.innerHTML = `<div class="toast-icon ${type}"><i class="fas ${icons[type]||'fa-info'}"></i></div>
                   <span class="toast-msg">${msg}</span>`;
    document.getElementById('toastContainer').appendChild(t);
    setTimeout(() => { t.classList.add('removing'); setTimeout(()=>t.remove(), 280); }, 3500);
}

// ═══════════════════════════════════════════════
// HORARIOS
// ═══════════════════════════════════════════════
const HOR_API     = 'api/horarios.php';
let horCanchaId   = null;
let horCanchaNom  = '';
let frDiasActivos = new Set();

const DIAS_CORTO = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
const DIAS_COLOR  = { 1:'#3498db',2:'#3498db',3:'#3498db',4:'#3498db',5:'#3498db',6:'#9b59b6',7:'#9b59b6' };

// ─── Inicializar vista horarios ────────────────
async function horInit() {
    const res  = await fetch(`${HOR_API}?action=complejos`);
    const json = await res.json();
    if (!json.ok) return;
    const sel = document.getElementById('horComplejoSel');
    sel.innerHTML = '<option value="">Seleccioná complejo…</option>';
    json.data.forEach(c =>
        sel.innerHTML += `<option value="${c.COMPLEJO_ID}">${escHtml(c.COMPLEJO_NOMBRE)}</option>`
    );
}

// ─── Cargar canchas del complejo ──────────────
async function horLoadCanchas() {
    const cid = document.getElementById('horComplejoSel').value;
    const list = document.getElementById('horCanchasList');

    if (!cid) {
        list.innerHTML = `<div style="text-align:center;padding:24px 12px;color:var(--muted);font-size:12px">
            <i class="fas fa-arrow-up" style="display:block;margin-bottom:6px;opacity:.3"></i>
            Seleccioná un complejo
        </div>`;
        document.getElementById('horFranjasPanel').innerHTML = `<div class="card"><div class="empty-state" style="padding:60px 20px">
            <div class="es-icon" style="width:52px;height:52px;font-size:22px"><i class="fas fa-clock"></i></div>
            <h4>Seleccioná una cancha</h4><p>Elegí un complejo y una cancha para ver sus horarios</p>
        </div></div>`;
        horCanchaId = null; return;
    }

    list.innerHTML = `<div style="text-align:center;padding:16px;color:var(--muted);font-size:12px">
        <i class="fas fa-spinner fa-spin"></i></div>`;

    const res  = await fetch(`${HOR_API}?action=canchas_por_complejo&complejo_id=${cid}`);
    const json = await res.json();
    if (!json.ok) { toast(json.msg,'err'); return; }

    if (!json.data.length) {
        list.innerHTML = `<div style="text-align:center;padding:20px 12px;color:var(--muted);font-size:12px">
            <i class="fas fa-futbol" style="display:block;margin-bottom:6px;opacity:.3;font-size:20px"></i>
            Sin canchas en este complejo.
        </div>`; return;
    }

    list.innerHTML = json.data.map(c => `
        <div id="hc-${c.CANCHA_ID}" onclick="horSelectCancha(${c.CANCHA_ID},'${escHtml(c.CANCHA_NOMBRE)}')"
            style="display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:9px;
            cursor:pointer;transition:all .15s;margin-bottom:4px;
            border:1px solid transparent;opacity:${c.ACTIVO?1:.5}">
            <div style="width:28px;height:28px;border-radius:7px;flex-shrink:0;
                background:rgba(255,149,0,.12);color:var(--orange);
                display:flex;align-items:center;justify-content:center;font-size:12px">
                <i class="fas ${escHtml(c.TIPO_CANCHA_ICONO||'fa-futbol')}"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-size:12px;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                    ${escHtml(c.CANCHA_NOMBRE)}
                </div>
                <div style="font-size:10px;color:var(--muted)">${c.TOTAL_FRANJAS} franja${c.TOTAL_FRANJAS!=1?'s':''} configurada${c.TOTAL_FRANJAS!=1?'s':''}</div>
            </div>
            <div style="width:20px;height:20px;border-radius:5px;flex-shrink:0;
                background:${c.TOTAL_FRANJAS>0?'rgba(76,217,100,.15)':'rgba(255,255,255,.06)'};
                color:${c.TOTAL_FRANJAS>0?'var(--green)':'var(--muted)'};
                display:flex;align-items:center;justify-content:center;font-size:10px;font-weight:800">
                ${c.TOTAL_FRANJAS}
            </div>
        </div>`).join('');

    // Si había una cancha seleccionada del mismo complejo, mantener selección
    if (horCanchaId) {
        const el = document.getElementById('hc-'+horCanchaId);
        if (el) { horHighlight(horCanchaId); horLoadFranjas(horCanchaId, horCanchaNom); }
        else horCanchaId = null;
    }
}

function horHighlight(cid) {
    document.querySelectorAll('[id^="hc-"]').forEach(el => {
        el.style.background    = 'transparent';
        el.style.borderColor   = 'transparent';
        el.style.color         = '';
    });
    const el = document.getElementById('hc-'+cid);
    if (el) {
        el.style.background  = 'rgba(76,217,100,.08)';
        el.style.borderColor = 'rgba(76,217,100,.25)';
    }
}

async function horSelectCancha(cid, nombre) {
    horCanchaId  = cid;
    horCanchaNom = nombre;
    horHighlight(cid);
    await horLoadFranjas(cid, nombre);
}

// ─── Franjas de una cancha ─────────────────────
async function horLoadFranjas(cid, nombre) {
    document.getElementById('horFranjasPanel').innerHTML = `<div class="card"><div class="empty-state" style="padding:40px">
        <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div><p>Cargando…</p>
    </div></div>`;

    const res  = await fetch(`${HOR_API}?action=franjas&cancha_id=${cid}`);
    const json = await res.json();
    if (!json.ok) { toast(json.msg,'err'); return; }

    renderFranjas(cid, nombre, json.data);
}

function renderFranjas(cid, nombre, franjas) {
    const activas   = franjas.filter(f=>parseInt(f.ACTIVO));
    const inactivas = franjas.filter(f=>!parseInt(f.ACTIVO));

    const frCard = (f) => {
        const activo = parseInt(f.ACTIVO);
        const dias   = (f.DIAS||[]).map(d =>
            `<span style="display:inline-flex;align-items:center;justify-content:center;
                width:28px;height:28px;border-radius:7px;font-size:10px;font-weight:800;
                background:${activo?`rgba(${hexToRgb(DIAS_COLOR[d]||'#3498db')},.15)`:'rgba(255,255,255,.04)'};
                color:${activo?(DIAS_COLOR[d]||'var(--blue)'):'var(--muted)'}"
            >${DIAS_CORTO[d]||'?'}</span>`
        ).join('');
        const durMin = duracion(f.FRANJA_HORA_INICIO, f.FRANJA_HORA_FIN);

        return `<div style="border-radius:12px;border:1px solid ${activo?'var(--border)':'rgba(255,255,255,.04)'};
            background:${activo?'var(--s1)':'rgba(255,255,255,.02)'};padding:14px 16px;
            opacity:${activo?1:.55};transition:all .2s;margin-bottom:10px">
            <div style="display:flex;align-items:flex-start;gap:12px">
                <!-- Horario -->
                <div style="flex-shrink:0;text-align:center;min-width:90px">
                    <div style="font-size:20px;font-weight:800;letter-spacing:-1px;
                        color:${activo?'var(--text)':'var(--muted)'}; line-height:1">
                        ${f.FRANJA_HORA_INICIO.slice(0,5)}
                    </div>
                    <div style="font-size:10px;color:var(--muted);margin:2px 0">↓ ${durMin} min</div>
                    <div style="font-size:18px;font-weight:700;color:${activo?'var(--muted)':'var(--muted)'}; line-height:1">
                        ${f.FRANJA_HORA_FIN.slice(0,5)}
                    </div>
                </div>
                <!-- Días + precio -->
                <div style="flex:1;min-width:0">
                    <div style="display:flex;gap:4px;flex-wrap:wrap;margin-bottom:10px">
                        ${dias || '<span style="font-size:11px;color:var(--muted)">Sin días asignados</span>'}
                    </div>
                    <div style="display:flex;align-items:center;gap:14px;flex-wrap:wrap">
                        <div>
                            <div style="font-size:18px;font-weight:800;color:${activo?'var(--green)':'var(--muted)'}">
                                $${Number(f.FRANJA_PRECIO).toLocaleString('es-AR')}
                            </div>
                            <div style="font-size:10px;color:var(--muted)">precio / turno</div>
                        </div>
                        ${parseFloat(f.FRANJA_SENA)>0?`
                        <div style="padding:4px 10px;border-radius:7px;background:rgba(255,214,10,.08);
                            border:1px solid rgba(255,214,10,.15)">
                            <div style="font-size:13px;font-weight:700;color:var(--yellow)">
                                $${Number(f.FRANJA_SENA).toLocaleString('es-AR')}
                            </div>
                            <div style="font-size:9px;color:var(--muted)">seña mín.</div>
                        </div>`:''}
                    </div>
                </div>
                <!-- Acciones -->
                <div style="display:flex;flex-direction:column;gap:5px;flex-shrink:0">
                    <button class="act-btn edit" title="Editar"
                        onclick="horAbrirEditar(${f.FRANJA_ID})">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="act-btn toggle ${activo?'on':''}" title="${activo?'Desactivar':'Activar'}"
                        onclick="horToggle(${f.FRANJA_ID},this)">
                        <i class="fas ${activo?'fa-toggle-on':'fa-toggle-off'}"></i>
                    </button>
                </div>
            </div>
        </div>`;
    };

    const emptyPanel = `<div class="card">
        <div class="card-header" style="justify-content:space-between">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:8px;background:rgba(52,152,219,.12);
                    color:var(--blue);display:flex;align-items:center;justify-content:center">
                    <i class="fas fa-clock"></i>
                </div>
                <div><h3>${escHtml(nombre)}</h3><p>Sin franjas configuradas aún</p></div>
            </div>
            <div style="display:flex;gap:8px">
                <button class="btn btn-ghost btn-sm" onclick="horAbrirGenerar()">
                    <i class="fas fa-magic"></i> Generar semana
                </button>
                <button class="btn btn-primary btn-sm" onclick="horAbrirCrear()"
                    style="background:linear-gradient(135deg,var(--blue),#2980b9)">
                    <i class="fas fa-plus"></i> Nueva franja
                </button>
            </div>
        </div>
        <div class="empty-state" style="padding:52px 20px">
            <div class="es-icon" style="font-size:22px;width:52px;height:52px"><i class="fas fa-clock"></i></div>
            <h4>Sin franjas horarias</h4>
            <p>Esta cancha no tiene horarios configurados.</p>
            <div style="display:flex;gap:10px;justify-content:center;margin-top:14px;flex-wrap:wrap">
                <button class="btn btn-ghost btn-sm" onclick="horAbrirGenerar()">
                    <i class="fas fa-magic"></i> Generar semana
                </button>
                <button class="btn btn-primary btn-sm" style="background:linear-gradient(135deg,var(--blue),#2980b9)"
                    onclick="horAbrirCrear()">
                    <i class="fas fa-plus"></i> Crear primera franja
                </button>
            </div>
        </div>
    </div>`;

    if (!franjas.length) { document.getElementById('horFranjasPanel').innerHTML = emptyPanel; return; }

    document.getElementById('horFranjasPanel').innerHTML = `
        <div class="card" style="margin-bottom:0">
            <div class="card-header">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:32px;height:32px;border-radius:8px;background:rgba(52,152,219,.12);
                        color:var(--blue);display:flex;align-items:center;justify-content:center">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div>
                        <h3>${escHtml(nombre)}</h3>
                        <p>${activas.length} franja${activas.length!=1?'s':''} activa${activas.length!=1?'s':''}</p>
                    </div>
                </div>
                <div class="card-actions" style="display:flex;gap:8px">
                    <button class="btn btn-ghost btn-sm" onclick="horAbrirGenerar()">
                        <i class="fas fa-magic"></i> Generar semana
                    </button>
                    <button class="btn btn-primary btn-sm"
                        onclick="horAbrirCrear()"
                        style="background:linear-gradient(135deg,var(--blue),#2980b9)">
                        <i class="fas fa-plus"></i> Nueva franja
                    </button>
                </div>
            </div>
            <div style="padding:16px">
                ${activas.map(frCard).join('')}
                ${inactivas.length ? `
                <div style="font-size:10px;font-weight:700;color:var(--muted);letter-spacing:1px;
                    text-transform:uppercase;margin:14px 0 8px">
                    Inactivas (${inactivas.length})
                </div>
                ${inactivas.map(frCard).join('')}` : ''}
            </div>
        </div>`;
}

// ─── Helpers ──────────────────────────────────
function duracion(ini, fin) {
    const [h1,m1]=ini.split(':').map(Number);
    const [h2,m2]=fin.split(':').map(Number);
    return (h2*60+m2)-(h1*60+m1);
}
function hexToRgb(hex) {
    const m=hex.replace('#','').match(/.{2}/g);
    return m ? m.map(x=>parseInt(x,16)).join(',') : '255,255,255';
}

// ─── Modal franja ──────────────────────────────
function horAbrirCrear() {
    if (!horCanchaId) { toast('Seleccioná una cancha primero.','err'); return; }
    document.getElementById('mFrId').value = '';
    document.getElementById('mFrCanchaId').value = horCanchaId;
    document.getElementById('mFrTitle').textContent = 'Nueva franja horaria';
    document.getElementById('mFrSub').textContent   = escHtml(horCanchaNom);
    document.getElementById('mFrInicio').value = '';
    document.getElementById('mFrFin').value    = '';
    document.getElementById('mFrPrecio').value = '';
    document.getElementById('mFrSena').value   = '';
    document.getElementById('mFrSubmitTxt').textContent = 'Crear franja';
    document.getElementById('mFrResumen').style.display = 'none';
    ['mFrDiasErr','mFrInicioErr','mFrFinErr','mFrPrecioErr'].forEach(id=>{
        const el=document.getElementById(id); if(el) el.style.display='none';
    });
    frDiasActivos = new Set();
    frRenderDias();
    document.getElementById('modalFranja').classList.add('show');
    setTimeout(()=>document.getElementById('mFrInicio').focus(),150);
}

async function horAbrirEditar(fid) {
    // Buscar la franja en los datos ya cargados
    const res  = await fetch(`${HOR_API}?action=franjas&cancha_id=${horCanchaId}`);
    const json = await res.json();
    if (!json.ok) { toast(json.msg,'err'); return; }
    const f = json.data.find(x=>parseInt(x.FRANJA_ID)===fid);
    if (!f) { toast('Franja no encontrada.','err'); return; }

    document.getElementById('mFrId').value        = fid;
    document.getElementById('mFrCanchaId').value  = horCanchaId;
    document.getElementById('mFrTitle').textContent = 'Editar franja horaria';
    document.getElementById('mFrSub').textContent   = escHtml(horCanchaNom);
    document.getElementById('mFrInicio').value = f.FRANJA_HORA_INICIO.slice(0,5);
    document.getElementById('mFrFin').value    = f.FRANJA_HORA_FIN.slice(0,5);
    document.getElementById('mFrPrecio').value = f.FRANJA_PRECIO;
    document.getElementById('mFrSena').value   = f.FRANJA_SENA||'';
    document.getElementById('mFrSubmitTxt').textContent = 'Guardar cambios';
    ['mFrDiasErr','mFrInicioErr','mFrFinErr','mFrPrecioErr'].forEach(id=>{
        const el=document.getElementById(id); if(el) el.style.display='none';
    });
    frDiasActivos = new Set((f.DIAS||[]).map(Number));
    frRenderDias();
    frActualizarResumen();
    document.getElementById('modalFranja').classList.add('show');
}

// ─── Selector días ─────────────────────────────
function frRenderDias() {
    const wrap = document.getElementById('mFrDias');
    wrap.innerHTML = [1,2,3,4,5,6,7].map(d => {
        const sel = frDiasActivos.has(d);
        const color = DIAS_COLOR[d];
        return `<div onclick="frToggleDia(${d})" id="fdBtn-${d}"
            style="width:38px;height:38px;border-radius:9px;cursor:pointer;
            display:flex;flex-direction:column;align-items:center;justify-content:center;gap:1px;
            border:1px solid ${sel?color:'var(--border)'};
            background:${sel?`rgba(${hexToRgb(color)},.15)`:'var(--s1)'};
            transition:all .15s;user-select:none">
            <span style="font-size:10px;font-weight:800;
                color:${sel?color:'var(--muted)'}">${DIAS_CORTO[d]}</span>
        </div>`;
    }).join('');
}

function frToggleDia(d) {
    frDiasActivos.has(d) ? frDiasActivos.delete(d) : frDiasActivos.add(d);
    frRenderDias(); frActualizarResumen();
}

function frSetDias(arr) {
    frDiasActivos = new Set(arr);
    frRenderDias(); frActualizarResumen();
}

function frActualizarResumen() {
    const ini    = document.getElementById('mFrInicio').value;
    const fin    = document.getElementById('mFrFin').value;
    const precio = parseFloat(document.getElementById('mFrPrecio').value)||0;
    const sena   = parseFloat(document.getElementById('mFrSena').value)||0;
    const res    = document.getElementById('mFrResumen');

    if (!ini || !fin || !precio || !frDiasActivos.size) { res.style.display='none'; return; }
    res.style.display='block';

    document.getElementById('mFrResHoras').textContent  = `${ini} → ${fin}`;
    document.getElementById('mFrResPrecio').textContent = `$${precio.toLocaleString('es-AR')}`;
    document.getElementById('mFrResSena').textContent   = sena>0 ? `Seña: $${sena.toLocaleString('es-AR')}` : 'Sin seña';

    document.getElementById('mFrResDias').innerHTML = [...frDiasActivos].sort().map(d =>
        `<span style="padding:3px 8px;border-radius:6px;font-size:10px;font-weight:700;
            background:rgba(${hexToRgb(DIAS_COLOR[d]||'#3498db')},.15);
            color:${DIAS_COLOR[d]||'var(--blue)'}">
            ${DIAS_CORTO[d]}
        </span>`
    ).join('');
}

async function submitFranja() {
    // Validaciones
    let ok = true;
    const checks = [
        [()=>!document.getElementById('mFrInicio').value,  'mFrInicioErr','Hora de inicio requerida.'],
        [()=>!document.getElementById('mFrFin').value,     'mFrFinErr',   'Hora de fin requerida.'],
        [()=>!document.getElementById('mFrPrecio').value||parseFloat(document.getElementById('mFrPrecio').value)<=0,
            'mFrPrecioErr','El precio debe ser mayor a 0.'],
    ];
    checks.forEach(([cond,eId,msg])=>{
        const err=document.getElementById(eId);
        if(cond()){ err.textContent=msg; err.style.display='block'; ok=false; }
        else { err.style.display='none'; }
    });
    if (!frDiasActivos.size) {
        const err=document.getElementById('mFrDiasErr');
        err.textContent='Seleccioná al menos un día.'; err.style.display='block'; ok=false;
    } else { document.getElementById('mFrDiasErr').style.display='none'; }
    if (!ok) return;

    const btn = document.getElementById('mFrSubmit');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Guardando…';

    const fid = document.getElementById('mFrId').value;
    const fd  = new FormData();
    fd.append('action',     fid?'editar':'crear');
    fd.append('cancha_id',  document.getElementById('mFrCanchaId').value);
    fd.append('hora_inicio',document.getElementById('mFrInicio').value);
    fd.append('hora_fin',   document.getElementById('mFrFin').value);
    fd.append('precio',     document.getElementById('mFrPrecio').value);
    fd.append('sena',       document.getElementById('mFrSena').value||0);
    fd.append('dias',       JSON.stringify([...frDiasActivos]));
    if (fid) { fd.append('franja_id', fid); }

    try {
        const res  = await fetch(HOR_API,{method:'POST',body:fd});
        const json = await res.json();
        if(json.ok){
            toast(json.msg,'ok');
            closeModal('modalFranja');
            horLoadFranjas(horCanchaId, horCanchaNom);
            horLoadCanchas(); // actualiza el contador de franjas
        } else {
            toast(json.msg,'err');
        }
    } catch(e){ toast('Error de red.','err'); }
    finally {
        btn.disabled=false;
        btn.innerHTML=`<i class="fas fa-check"></i> <span id="mFrSubmitTxt">${fid?'Guardar cambios':'Crear franja'}</span>`;
    }
}

async function horToggle(fid, btn) {
    btn.disabled=true;
    const fd=new FormData();
    fd.append('action','toggle'); fd.append('franja_id',fid);
    try{
        const res=await fetch(HOR_API,{method:'POST',body:fd});
        const json=await res.json();
        if(json.ok){ toast(json.msg,'ok'); horLoadFranjas(horCanchaId,horCanchaNom); }
        else toast(json.msg,'err');
    }catch(e){toast('Error de red.','err');}
    finally{btn.disabled=false;}
}

// ─── Generador de horarios ──────────────────────
let genDiasActivos = new Set([1,2,3,4,5]);

function horAbrirGenerar() {
    if (!horCanchaId) { toast('Seleccioná una cancha primero.','err'); return; }
    document.getElementById('mGenSub').textContent = escHtml(horCanchaNom);
    document.getElementById('mGenApertura').value = '';
    document.getElementById('mGenCierre').value   = '';
    document.getElementById('mGenPrecio').value   = '';
    document.getElementById('mGenSena').value     = '';
    document.getElementById('mGenDuracion').value = '60';
    document.getElementById('mGenPreview').style.display = 'none';
    ['mGenDiasErr','mGenAperturaErr','mGenCierreErr','mGenPrecioErr'].forEach(id => {
        const el = document.getElementById(id); if(el) el.style.display='none';
    });
    genDiasActivos = new Set([1,2,3,4,5]);
    genRenderDias();
    document.getElementById('modalGenerar').classList.add('show');
    setTimeout(()=>document.getElementById('mGenApertura').focus(),150);
}

function genRenderDias() {
    const wrap = document.getElementById('mGenDias');
    wrap.innerHTML = [1,2,3,4,5,6,7].map(d => {
        const sel   = genDiasActivos.has(d);
        const color = DIAS_COLOR[d];
        return `<div onclick="genToggleDia(${d})"
            style="width:38px;height:38px;border-radius:9px;cursor:pointer;
            display:flex;align-items:center;justify-content:center;
            border:1px solid ${sel?color:'var(--border)'};
            background:${sel?`rgba(${hexToRgb(color)},.15)`:'var(--s1)'};
            transition:all .15s;user-select:none">
            <span style="font-size:10px;font-weight:800;color:${sel?color:'var(--muted)'}">${DIAS_CORTO[d]}</span>
        </div>`;
    }).join('');
    genActualizarPreview();
}

function genToggleDia(d) {
    genDiasActivos.has(d) ? genDiasActivos.delete(d) : genDiasActivos.add(d);
    genRenderDias();
}

function genSetDias(arr) {
    genDiasActivos = new Set(arr);
    genRenderDias();
}

function genCalcularSlots() {
    const apertura = document.getElementById('mGenApertura').value;
    const cierre   = document.getElementById('mGenCierre').value;
    const dur      = parseInt(document.getElementById('mGenDuracion').value) || 60;
    if (!apertura || !cierre) return [];
    const [h1,m1] = apertura.split(':').map(Number);
    const [h2,m2] = cierre.split(':').map(Number);
    const start = h1*60+m1, end = h2*60+m2;
    if (end <= start) return [];
    const slots = [], pad = n => String(Math.floor(n/60)).padStart(2,'0')+':'+String(n%60).padStart(2,'0');
    for (let t = start; t+dur <= end; t += dur) slots.push({ ini: pad(t), fin: pad(t+dur) });
    return slots;
}

function genActualizarPreview() {
    const slots   = genCalcularSlots();
    const precio  = parseFloat(document.getElementById('mGenPrecio').value)||0;
    const prevEl  = document.getElementById('mGenPreview');
    if (!slots.length || !genDiasActivos.size) { prevEl.style.display='none'; return; }
    prevEl.style.display = 'block';
    document.getElementById('mGenCount').textContent = slots.length;
    document.getElementById('mGenSlots').innerHTML = slots.map(s =>
        `<span style="padding:4px 10px;border-radius:7px;font-size:12px;font-weight:700;
            background:rgba(76,217,100,.08);border:1px solid rgba(76,217,100,.2);color:var(--green)">
            ${s.ini}–${s.fin}${precio?` <span style="color:var(--muted);font-weight:400;font-size:10px">$${precio.toLocaleString('es-AR')}</span>`:''}
        </span>`
    ).join('');
}

async function submitGenerar() {
    let ok = true;
    if (!genDiasActivos.size) {
        const e=document.getElementById('mGenDiasErr'); e.textContent='Seleccioná al menos un día.'; e.style.display='block'; ok=false;
    } else { document.getElementById('mGenDiasErr').style.display='none'; }
    const apertura = document.getElementById('mGenApertura').value;
    const cierre   = document.getElementById('mGenCierre').value;
    const precio   = parseFloat(document.getElementById('mGenPrecio').value)||0;
    if (!apertura) { const e=document.getElementById('mGenAperturaErr'); e.textContent='Requerida.'; e.style.display='block'; ok=false; } else document.getElementById('mGenAperturaErr').style.display='none';
    if (!cierre)   { const e=document.getElementById('mGenCierreErr');   e.textContent='Requerida.'; e.style.display='block'; ok=false; } else document.getElementById('mGenCierreErr').style.display='none';
    if (precio<=0) { const e=document.getElementById('mGenPrecioErr');  e.textContent='Ingresá un precio mayor a 0.'; e.style.display='block'; ok=false; } else document.getElementById('mGenPrecioErr').style.display='none';
    const slots = genCalcularSlots();
    if (!slots.length) { toast('El rango no genera franjas. Revisá apertura y cierre.','err'); return; }
    if (!ok) return;

    const btn = document.getElementById('mGenSubmit');
    btn.disabled=true; btn.innerHTML='<i class="fas fa-spinner fa-spin"></i> Creando…';

    const sena = parseFloat(document.getElementById('mGenSena').value)||0;
    const dias = JSON.stringify([...genDiasActivos]);
    let creadas=0, errores=[];

    for (const slot of slots) {
        const fd = new FormData();
        fd.append('action','crear'); fd.append('cancha_id',horCanchaId);
        fd.append('hora_inicio',slot.ini); fd.append('hora_fin',slot.fin);
        fd.append('precio',precio); fd.append('sena',sena); fd.append('dias',dias);
        try {
            const res  = await fetch(HOR_API,{method:'POST',body:fd});
            const json = await res.json();
            json.ok ? creadas++ : errores.push(`${slot.ini}-${slot.fin}: ${json.msg}`);
        } catch(e) { errores.push(`${slot.ini}-${slot.fin}: error de red`); }
    }

    btn.disabled=false; btn.innerHTML='<i class="fas fa-magic"></i> <span id="mGenSubmitTxt">Generar horarios</span>';

    if (creadas > 0) {
        closeModal('modalGenerar');
        horLoadFranjas(horCanchaId, horCanchaNom);
        horLoadCanchas();
        if (errores.length) toast(`${creadas} franja${creadas!=1?'s':''} creada${creadas!=1?'s':''}, ${errores.length} omitida${errores.length!=1?'s':''}  (superposición).`,'err');
        else toast(`✓ ${creadas} franja${creadas!=1?'s':''} creada${creadas!=1?'s':''} correctamente.`,'ok');
    } else {
        toast(errores[0] || 'No se pudieron crear las franjas.','err');
    }
}

// ═══════════════════════════════════════════════
// GEO (provincia → partido → localidad) — datos embebidos
// ═══════════════════════════════════════════════
<?php
$geoProvincias = [];
$geoPartidos   = [];
$geoLocalidades= [];
$qp = mysqli_query($link,"SELECT PROVINCIA_ID,PROVINCIA_NOMBRE FROM provincia WHERE ACTIVO=1 ORDER BY PROVINCIA_NOMBRE");
while($r=mysqli_fetch_assoc($qp)) $geoProvincias[]=$r;
$qpa= mysqli_query($link,"SELECT PARTIDO_ID,PROVINCIA_ID,PARTIDO_NOMBRE FROM partido WHERE ACTIVO=1 ORDER BY PARTIDO_NOMBRE");
while($r=mysqli_fetch_assoc($qpa)) $geoPartidos[]=$r;
$ql = mysqli_query($link,"SELECT LOCALIDAD_ID,PARTIDO_ID,PROVINCIA_ID,LOCALIDAD_NOMBRE FROM localidad WHERE ACTIVO=1 ORDER BY LOCALIDAD_NOMBRE");
while($r=mysqli_fetch_assoc($ql)) $geoLocalidades[]=$r;
?>
const GEO_PROVINCIAS  = <?= json_encode($geoProvincias,  JSON_UNESCAPED_UNICODE) ?>;
const GEO_PARTIDOS    = <?= json_encode($geoPartidos,    JSON_UNESCAPED_UNICODE) ?>;
const GEO_LOCALIDADES = <?= json_encode($geoLocalidades, JSON_UNESCAPED_UNICODE) ?>;

// ─── Poblar selects ───────────────────────────
function geoLoadProvincias(selId) {
    const sel = document.getElementById(selId);
    sel.innerHTML = '<option value="">Seleccioná provincia…</option>';
    GEO_PROVINCIAS.forEach(p =>
        sel.innerHTML += `<option value="${p.PROVINCIA_ID}">${escHtml(p.PROVINCIA_NOMBRE)}</option>`
    );
    sel.innerHTML += `<option value="__add__" style="color:var(--green);font-weight:700">+ Agregar provincia</option>`;
}

function geoLoadPartidos(provSelId, parSelId, locSelId) {
    const provId = parseInt(document.getElementById(provSelId).value)||0;
    const parSel = document.getElementById(parSelId);
    const locSel = document.getElementById(locSelId);

    locSel.innerHTML = '<option value="">Primero elegí partido…</option>';
    locSel.disabled  = true;

    if (!provId) {
        parSel.innerHTML = '<option value="">Primero elegí provincia…</option>';
        parSel.disabled  = true;
        return;
    }

    const provNom  = GEO_PROVINCIAS.find(p=>parseInt(p.PROVINCIA_ID)===provId)?.PROVINCIA_NOMBRE || 'provincia';
    const partidos = GEO_PARTIDOS.filter(p => parseInt(p.PROVINCIA_ID) === provId);
    parSel.innerHTML = '<option value="">Seleccioná partido…</option>';
    partidos.forEach(p =>
        parSel.innerHTML += `<option value="${p.PARTIDO_ID}">${escHtml(p.PARTIDO_NOMBRE)}</option>`
    );
    parSel.innerHTML += `<option value="__add__" style="color:var(--green);font-weight:700">+ Agregar partido en ${escHtml(provNom)}</option>`;
    parSel.disabled = false;
}

function geoLoadLocalidades(parSelId, locSelId) {
    const parId  = parseInt(document.getElementById(parSelId).value)||0;
    const locSel = document.getElementById(locSelId);

    if (!parId) {
        locSel.innerHTML = '<option value="">Primero elegí partido…</option>';
        locSel.disabled  = true;
        return;
    }

    const parNom = GEO_PARTIDOS.find(p=>parseInt(p.PARTIDO_ID)===parId)?.PARTIDO_NOMBRE || 'partido';
    const locs   = GEO_LOCALIDADES.filter(l => parseInt(l.PARTIDO_ID) === parId);
    locSel.innerHTML = '<option value="">Seleccioná localidad…</option>';
    locs.forEach(l =>
        locSel.innerHTML += `<option value="${l.LOCALIDAD_ID}">${escHtml(l.LOCALIDAD_NOMBRE)}</option>`
    );
    locSel.innerHTML += `<option value="__add__" style="color:var(--green);font-weight:700">+ Agregar localidad en ${escHtml(parNom)}</option>`;
    locSel.disabled = false;
}

// Pre-cargar jerarquía completa dado un LOCALIDAD_ID
function geoPrecargar(localidadId, provSelId, parSelId, locSelId) {
    if (!localidadId) return;
    const loc = GEO_LOCALIDADES.find(l => parseInt(l.LOCALIDAD_ID) === parseInt(localidadId));
    if (!loc) return;

    document.getElementById(provSelId).value = loc.PROVINCIA_ID;
    geoLoadPartidos(provSelId, parSelId, locSelId);
    document.getElementById(parSelId).value = loc.PARTIDO_ID;
    geoLoadLocalidades(parSelId, locSelId);
    document.getElementById(locSelId).value = localidadId;
}

// ─── Interceptar selección de "+ Agregar" ─────
// Contexto activo del panel
let _geoCtx = null; // { type, provSelId, parSelId, locSelId, anchorSel }

function geoOnChange(type, provSelId, parSelId, locSelId) {
    const selId = type==='provincia' ? provSelId : type==='partido' ? parSelId : locSelId;
    const sel   = document.getElementById(selId);

    if (sel.value !== '__add__') {
        // Cascada normal
        if (type==='provincia') geoLoadPartidos(provSelId, parSelId, locSelId);
        if (type==='partido')   geoLoadLocalidades(parSelId, locSelId);
        return;
    }

    // Volver al placeholder mientras el panel está abierto
    sel.value = '';

    const provNom = GEO_PROVINCIAS.find(p=>parseInt(p.PROVINCIA_ID)===parseInt(document.getElementById(provSelId).value))?.PROVINCIA_NOMBRE || '';
    const parNom  = GEO_PARTIDOS.find(p=>parseInt(p.PARTIDO_ID)===parseInt(document.getElementById(parSelId).value))?.PARTIDO_NOMBRE || '';

    const labels = { provincia:'provincia', partido:`partido en ${provNom}`, localidad:`localidad en ${parNom}` };

    _geoCtx = { type, provSelId, parSelId, locSelId, anchorSel: selId };

    const panel = document.getElementById('geoPanel');
    document.getElementById('geoPanelLabel').textContent = `Agregar ${labels[type]}`;
    document.getElementById('geoPanelInput').value = '';
    document.getElementById('geoPanelErr').style.display = 'none';

    // Posicionar cerca del select
    const rect = sel.getBoundingClientRect();
    panel.style.top  = (rect.bottom + window.scrollY + 6) + 'px';
    panel.style.left = Math.min(rect.left + window.scrollX, window.innerWidth - 280) + 'px';
    panel.style.display = 'block';
    setTimeout(()=>document.getElementById('geoPanelInput').focus(), 50);
}

function geoPanelCerrar() {
    document.getElementById('geoPanel').style.display = 'none';
    _geoCtx = null;
}

async function geoPanelGuardar() {
    if (!_geoCtx) return;
    const nombre = document.getElementById('geoPanelInput').value.trim();
    const errEl  = document.getElementById('geoPanelErr');
    if (!nombre) { errEl.textContent='Ingresá un nombre.'; errEl.style.display='block'; return; }

    const btn = document.querySelector('#geoPanel .btn-primary');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

    const { type, provSelId, parSelId, locSelId } = _geoCtx;
    const provId = parseInt(document.getElementById(provSelId).value)||0;
    const parId  = parseInt(document.getElementById(parSelId).value)||0;

    const fd = new FormData();
    fd.append('nombre', nombre);
    if (type==='partido'  ) fd.append('provincia_id', provId);
    if (type==='localidad') { fd.append('partido_id', parId); fd.append('provincia_id', provId); }

    try {
        const res  = await fetch(`api/geo.php?action=crear_${type}`, {method:'POST', body:fd});
        const json = await res.json();
        if (!json.ok) {
            errEl.textContent = json.msg; errEl.style.display = 'block';
            btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Guardar';
            return;
        }

        const d = json.data;
        // Agregar al array en memoria y re-renderizar select
        if (type==='provincia') {
            GEO_PROVINCIAS.push({PROVINCIA_ID:String(d.id), PROVINCIA_NOMBRE:d.nombre});
            GEO_PROVINCIAS.sort((a,b)=>a.PROVINCIA_NOMBRE.localeCompare(b.PROVINCIA_NOMBRE,'es'));
            geoLoadProvincias(provSelId);
            document.getElementById(provSelId).value = d.id;
            geoLoadPartidos(provSelId, parSelId, locSelId);
        } else if (type==='partido') {
            GEO_PARTIDOS.push({PARTIDO_ID:String(d.id), PROVINCIA_ID:String(provId), PARTIDO_NOMBRE:d.nombre});
            GEO_PARTIDOS.sort((a,b)=>a.PARTIDO_NOMBRE.localeCompare(b.PARTIDO_NOMBRE,'es'));
            geoLoadPartidos(provSelId, parSelId, locSelId);
            document.getElementById(parSelId).value = d.id;
            geoLoadLocalidades(parSelId, locSelId);
        } else {
            GEO_LOCALIDADES.push({LOCALIDAD_ID:String(d.id), PARTIDO_ID:String(parId), PROVINCIA_ID:String(provId), LOCALIDAD_NOMBRE:d.nombre});
            GEO_LOCALIDADES.sort((a,b)=>a.LOCALIDAD_NOMBRE.localeCompare(b.LOCALIDAD_NOMBRE,'es'));
            geoLoadLocalidades(parSelId, locSelId);
            document.getElementById(locSelId).value = d.id;
        }

        toast(`${d.nombre} agregado/a correctamente.`, 'ok');
        geoPanelCerrar();
    } catch(e) {
        errEl.textContent = 'Error de red.'; errEl.style.display='block';
    } finally {
        btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Guardar';
    }
}

// ═══════════════════════════════════════════════
// VISTA DETALLE PREDIO
// ═══════════════════════════════════════════════
let predioId   = null;
let predioNom  = '';

function abrirPredio(id, nombre) {
    predioId  = id;
    predioNom = nombre;

    // Ocultar todas las views, mostrar view-predio
    document.querySelectorAll('.view').forEach(v => v.style.display='none');
    document.getElementById('view-predio').style.display = 'block';

    // Quitar active del sidebar
    document.querySelectorAll('.sb-item').forEach(i => i.classList.remove('active'));

    document.getElementById('predioNombreHeader').textContent = nombre;
    document.getElementById('predioCanchasGrid').innerHTML =
        `<div class="card" style="padding:40px;text-align:center;color:var(--muted)">
            <i class="fas fa-spinner fa-spin" style="font-size:24px"></i>
         </div>`;

    cargarCanchasDelPredio(id);
}

function volverAComplejos() {
    predioId = null;
    document.querySelectorAll('.view').forEach(v => v.style.display='none');
    document.getElementById('view-complejos').style.display = 'block';
    const sb = document.querySelector('.sb-item[data-view="complejos"]');
    if (sb) { document.querySelectorAll('.sb-item').forEach(i=>i.classList.remove('active')); sb.classList.add('active'); }
}

async function cargarCanchasDelPredio(cid) {
    const [resC, resF] = await Promise.all([
        fetch(`api/canchas.php?action=listar`),
        fetch(`api/horarios.php?action=canchas_por_complejo&complejo_id=${cid}`)
    ]);
    const jC = await resC.json();
    const jF = await resF.json();

    const todasCanchas = (jC.data||[]).filter(c => parseInt(c.COMPLEJO_ID)===parseInt(cid));
    const canchasHor   = {};
    (jF.data||[]).forEach(c => { canchasHor[c.CANCHA_ID] = c; });

    if (!todasCanchas.length) {
        document.getElementById('predioCanchasGrid').innerHTML = `
            <div class="card" style="grid-column:1/-1;padding:60px 20px">
                <div class="empty-state">
                    <div class="es-icon"><i class="fas fa-futbol"></i></div>
                    <h4>Sin canchas</h4>
                    <p>Este predio no tiene canchas configuradas aún.</p>
                    <button class="btn btn-primary btn-sm" style="margin-top:14px" onclick="predioCanchaCrear()">
                        <i class="fas fa-plus"></i> Agregar primera cancha
                    </button>
                </div>
            </div>`;
        return;
    }

    document.getElementById('predioCanchasGrid').innerHTML =
        todasCanchas.map(c => renderCanchaCard(c, canchasHor[c.CANCHA_ID])).join('');

    // Cargar franjas de cada cancha
    todasCanchas.forEach(c => cargarFranjasMini(c.CANCHA_ID));
}

function renderCanchaCard(c, ch) {
    const activo    = parseInt(c.ACTIVO);
    const ico       = escHtml(c.TIPO_CANCHA_ICONO||'fa-futbol');
    const totalFr   = ch ? parseInt(ch.TOTAL_FRANJAS) : 0;

    return `<div id="pcard-${c.CANCHA_ID}" class="card" style="opacity:${activo?1:.6};transition:opacity .2s">
        <!-- Header cancha -->
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:14px">
            <div style="width:40px;height:40px;border-radius:10px;flex-shrink:0;
                background:rgba(255,149,0,.12);color:var(--orange);
                display:flex;align-items:center;justify-content:center;font-size:16px">
                <i class="fas ${ico}"></i>
            </div>
            <div style="flex:1;min-width:0">
                <div style="font-weight:800;font-size:14px">${escHtml(c.CANCHA_NOMBRE)}</div>
                <div style="font-size:11px;color:var(--muted)">${escHtml(c.TIPO_CANCHA_NOMBRE||'')}</div>
            </div>
            <div style="display:flex;gap:5px;flex-shrink:0">
                <button class="act-btn edit" title="Editar cancha" onclick="canchasAbrirEditar(${c.CANCHA_ID})">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="act-btn" title="Agregar franja" onclick="predioFranjaCrear(${c.CANCHA_ID},'${escHtml(c.CANCHA_NOMBRE)}')"
                    style="color:var(--blue);border-color:rgba(52,152,219,.2)">
                    <i class="fas fa-clock"></i>
                </button>
                <button class="act-btn toggle ${activo?'on':''}" title="${activo?'Desactivar':'Activar'}"
                    onclick="canTogglePredio(${c.CANCHA_ID},this)">
                    <i class="fas ${activo?'fa-toggle-on':'fa-toggle-off'}"></i>
                </button>
            </div>
        </div>

        <!-- Stats -->
        <div style="display:flex;gap:8px;margin-bottom:14px">
            <div style="flex:1;padding:8px;border-radius:8px;background:var(--s1);text-align:center">
                <div style="font-size:18px;font-weight:800;color:${totalFr>0?'var(--blue)':'var(--muted)'}">${totalFr}</div>
                <div style="font-size:10px;color:var(--muted)">franja${totalFr!=1?'s':''}</div>
            </div>
            <div style="flex:1;padding:8px;border-radius:8px;background:var(--s1);text-align:center">
                <div style="font-size:18px;font-weight:800;color:${c.RESERVAS_HOY>0?'var(--green)':'var(--muted)'}">${c.RESERVAS_HOY}</div>
                <div style="font-size:10px;color:var(--muted)">reservas hoy</div>
            </div>
            <div style="flex:1;padding:8px;border-radius:8px;background:var(--s1);text-align:center">
                <div style="font-size:18px;font-weight:800;color:${c.TOTAL_ENCARGADOS>0?'var(--green)':'var(--muted)'}">${c.TOTAL_ENCARGADOS}</div>
                <div style="font-size:10px;color:var(--muted)">encargado${c.TOTAL_ENCARGADOS!=1?'s':''}</div>
            </div>
        </div>

        <!-- Franjas (se cargan async) -->
        <div id="franjas-mini-${c.CANCHA_ID}" style="font-size:11px;color:var(--muted);text-align:center;padding:6px">
            <i class="fas fa-spinner fa-spin"></i>
        </div>
    </div>`;
}

async function cargarFranjasMini(cid) {
    const res  = await fetch(`api/horarios.php?action=franjas&cancha_id=${cid}`);
    const json = await res.json();
    const cont = document.getElementById(`franjas-mini-${cid}`);
    if (!cont) return;

    const franjas = (json.data||[]).filter(f=>parseInt(f.ACTIVO));
    if (!franjas.length) {
        cont.innerHTML = `<div style="display:flex;align-items:center;justify-content:space-between;
            padding:8px 10px;border-radius:8px;border:1px dashed var(--border);color:var(--muted)">
            <span>Sin horarios configurados</span>
            <button onclick="predioFranjaCrear(${cid},'')"
                style="background:none;border:none;color:var(--blue);cursor:pointer;font-size:11px;font-weight:700">
                + Agregar
            </button>
        </div>`;
        return;
    }

    const DIAS_C = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
    cont.innerHTML = franjas.map(f => {
        const dias = (f.DIAS||[]).map(d=>`<span style="padding:2px 5px;border-radius:4px;background:rgba(52,152,219,.15);color:var(--blue);font-weight:700">${DIAS_C[d]||'?'}</span>`).join(' ');
        return `<div style="display:flex;align-items:center;gap:8px;padding:7px 10px;
            border-radius:8px;background:var(--s1);margin-bottom:5px">
            <div style="font-weight:800;font-size:12px;min-width:90px;color:var(--text)">
                ${f.FRANJA_HORA_INICIO.slice(0,5)} – ${f.FRANJA_HORA_FIN.slice(0,5)}
            </div>
            <div style="flex:1;display:flex;gap:3px;flex-wrap:wrap">${dias}</div>
            <div style="font-weight:800;color:var(--green);font-size:12px;white-space:nowrap">
                $${Number(f.FRANJA_PRECIO).toLocaleString('es-AR')}
            </div>
            <button class="act-btn edit" style="width:24px;height:24px;font-size:10px"
                onclick="horAbrirEditar(${f.FRANJA_ID})">
                <i class="fas fa-pen"></i>
            </button>
        </div>`;
    }).join('') + `
    <button onclick="predioFranjaCrear(${cid},'')"
        style="width:100%;margin-top:4px;padding:6px;border-radius:8px;
        border:1px dashed rgba(52,152,219,.3);background:transparent;
        color:var(--blue);font-size:11px;font-weight:700;cursor:pointer">
        + Agregar horario
    </button>`;
}

function predioCanchaCrear() {
    // Abrir modal de nueva cancha con el complejo pre-seleccionado
    canchasAbrirCrear().then(() => {
        if (predioId) document.getElementById('mCanComplejo').value = predioId;
    });
}

function predioFranjaCrear(cid, nombre) {
    // Abrir modal de franja con la cancha activa ya seteada
    horCanchaId  = cid;
    horCanchaNom = nombre;
    horAbrirCrear();
}

function canTogglePredio(cid, btn) {
    // Toggle desde vista predio — reutiliza canToggle y recarga la card
    canToggle(cid, btn, () => cargarCanchasDelPredio(predioId));
}

// ═══════════════════════════════════════════════
// COMPLEJOS
// ═══════════════════════════════════════════════
const CMP_API    = 'api/complejos.php';
let cmpData      = [];
let cmpFilterVal = 'all';
let cmpSearchVal = '';
let cmpSelects   = null;
let cmpCurrentStep = 1;
let cmpActividades = {};   // { tipo_cancha_id: { destacada: 0|1 } }
let cmpHorarios    = {};   // { dia_id: { apertura, cierre, abre: bool } }

const DIAS = [
    {id:1,nombre:'Lunes'},   {id:2,nombre:'Martes'}, {id:3,nombre:'Miércoles'},
    {id:4,nombre:'Jueves'},  {id:5,nombre:'Viernes'},{id:6,nombre:'Sábado'},
    {id:7,nombre:'Domingo'}
];

async function loadComplejos() {
    document.getElementById('cmpTbody').innerHTML =
        `<tr><td colspan="7"><div class="empty-state">
            <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
            <p style="color:var(--muted)">Cargando…</p>
        </div></td></tr>`;
    const res  = await fetch(`${CMP_API}?action=listar`);
    const json = await res.json();
    if (!json.ok) { toast(json.msg,'err'); return; }
    cmpData = json.data;
    renderComplejos();
}

function renderComplejos() {
    const search = cmpSearchVal.toLowerCase();
    let filtered = cmpData.filter(r => {
        const mf = cmpFilterVal === 'all' || String(r.ACTIVO) === cmpFilterVal;
        const ms = !search || r.COMPLEJO_NOMBRE.toLowerCase().includes(search)
                           || (r.LOCALIDAD_NOMBRE||'').toLowerCase().includes(search)
                           || (r.PARTIDO_NOMBRE||'').toLowerCase().includes(search)
                           || (r.PROVINCIA_NOMBRE||'').toLowerCase().includes(search);
        return mf && ms;
    });

    if (!filtered.length) {
        document.getElementById('cmpTbody').innerHTML =
            `<tr><td colspan="7"><div class="empty-state">
                <div class="es-icon"><i class="fas fa-building"></i></div>
                <h4>Sin resultados</h4>
                <p>No hay complejos con los filtros actuales.</p>
                <button class="btn btn-primary btn-sm" style="margin-top:14px" onclick="complejosAbrirCrear()">
                    <i class="fas fa-plus"></i> Crear primer complejo
                </button>
            </div></td></tr>`;
        return;
    }

    const html = filtered.map((r,i) => {
        const acts = r.ACTIVIDADES
            ? r.ACTIVIDADES.split('||').map(a =>
                `<span style="display:inline-flex;align-items:center;padding:2px 8px;border-radius:20px;
                 font-size:10px;font-weight:700;background:rgba(76,217,100,.1);color:var(--green);
                 border:1px solid rgba(76,217,100,.2);white-space:nowrap">${escHtml(a)}</span>`)
              .join(' ')
            : '<span style="color:var(--muted);font-size:11px">—</span>';

        const tipoHtml = r.TIPO_COMPLEJO_NOMBRE
            ? `<div style="display:flex;align-items:center;gap:6px">
                   ${r.TIPO_COMPLEJO_ICONO ? `<i class="fas ${escHtml(r.TIPO_COMPLEJO_ICONO)}" style="color:var(--blue);font-size:12px"></i>` : ''}
                   <span style="font-size:12px">${escHtml(r.TIPO_COMPLEJO_NOMBRE)}</span>
               </div>`
            : '<span style="color:var(--muted);font-size:11px">—</span>';

        const activo = parseInt(r.ACTIVO);
        return `<tr style="animation:fadeUp .25s ease ${i*.04}s both">
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <div class="td-icon" style="background:rgba(52,152,219,.1);color:var(--blue);font-size:15px;width:36px;height:36px;border-radius:10px">
                        <i class="fas fa-building"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px">${escHtml(r.COMPLEJO_NOMBRE)}</div>
                        <div style="font-size:11px;color:var(--muted)">${escHtml(r.COMPLEJO_DIRECCION)}</div>
                    </div>
                </div>
            </td>
            <td>${tipoHtml}</td>
            <td style="font-size:12px">
                <div style="font-weight:600">${escHtml(r.LOCALIDAD_NOMBRE||'—')}</div>
                <div style="color:var(--muted);font-size:11px">${escHtml(r.PARTIDO_NOMBRE||'')}${r.PROVINCIA_NOMBRE?', '+escHtml(r.PROVINCIA_NOMBRE):''}</div>
            </td>
            <td>
                <div style="display:flex;flex-wrap:wrap;gap:4px">${acts}</div>
            </td>
            <td style="text-align:center">
                <button onclick="abrirPredio(${r.COMPLEJO_ID},'${escHtml(r.COMPLEJO_NOMBRE)}')"
                    style="display:inline-flex;align-items:center;gap:5px;padding:4px 10px;
                    border-radius:8px;border:1px solid ${r.TOTAL_CANCHAS>0?'rgba(76,217,100,.3)':'var(--border)'};
                    background:${r.TOTAL_CANCHAS>0?'rgba(76,217,100,.08)':'transparent'};
                    color:${r.TOTAL_CANCHAS>0?'var(--green)':'var(--muted)'};
                    font-size:12px;font-weight:700;cursor:pointer;transition:all .15s">
                    <i class="fas fa-futbol" style="font-size:10px"></i>
                    ${r.TOTAL_CANCHAS} cancha${r.TOTAL_CANCHAS!=1?'s':''}
                </button>
            </td>
            <td><span class="badge ${activo?'active':'inactive'}">${activo?'Activo':'Inactivo'}</span></td>
            <td>
                <div class="row-actions">
                    <button class="act-btn" title="Gestionar canchas y horarios"
                        onclick="abrirPredio(${r.COMPLEJO_ID},'${escHtml(r.COMPLEJO_NOMBRE)}')"
                        style="color:var(--green);border-color:rgba(76,217,100,.2)">
                        <i class="fas fa-layer-group"></i>
                    </button>
                    <button class="act-btn edit" title="Editar datos" onclick="complejosAbrirEditar(${r.COMPLEJO_ID})">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="act-btn toggle ${activo?'on':''}" title="${activo?'Desactivar':'Activar'}"
                        onclick="cmpToggle(${r.COMPLEJO_ID},this)">
                        <i class="fas ${activo?'fa-toggle-on':'fa-toggle-off'}"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');

    document.getElementById('cmpTbody').innerHTML = html;
}

function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function cmpFilter()          { cmpSearchVal = document.getElementById('cmpSearch').value; renderComplejos(); }
function cmpSetFilter(btn,v)  {
    document.querySelectorAll('[data-cmpf]').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active'); cmpFilterVal = v; renderComplejos();
}

// ─── SELECTS ──────────────────────────────────
async function loadSelects() {
    // Cargar provincias en el select (sincrónico, datos ya están en memoria)
    geoLoadProvincias('mCmpProv');

    if (!cmpSelects) {
        const res  = await fetch(`${CMP_API}?action=selects`);
        const json = await res.json();
        if (!json.ok) { toast('Error cargando datos.','err'); return null; }
        cmpSelects = json.data;
    }

    const tipEl = document.getElementById('mCmpTipo');
    tipEl.innerHTML = '<option value="">Sin clasificar</option>';
    cmpSelects.tipos_comp.forEach(t => tipEl.innerHTML += `<option value="${t.TIPO_COMPLEJO_ID}">${escHtml(t.TIPO_COMPLEJO_NOMBRE)}</option>`);

    // Si el usuario ya llegó al paso 2 mientras cargaban los datos, renderizar ahora
    if (cmpCurrentStep === 2) renderActividadesGrid();
    if (cmpCurrentStep === 3) renderHorariosTable();

    return cmpSelects;
}

// ─── MODAL CREAR ──────────────────────────────
async function complejosAbrirCrear() {
    resetCmpModal();
    document.getElementById('mCmpTitle').textContent = 'Nuevo complejo';
    document.getElementById('mCmpIcon').innerHTML    = '<i class="fas fa-plus"></i>';
    document.getElementById('mCmpBtnNext').innerHTML = 'Siguiente <i class="fas fa-arrow-right"></i>';
    goStep(1);
    document.getElementById('modalComplejo').classList.add('show');
    await loadSelects();
    setTimeout(() => document.getElementById('mCmpNombre').focus(), 150);
}

// ─── MODAL EDITAR ─────────────────────────────
async function complejosAbrirEditar(id) {
    resetCmpModal();
    document.getElementById('mCmpTitle').textContent = 'Editar complejo';
    document.getElementById('mCmpIcon').innerHTML    = '<i class="fas fa-pen"></i>';
    document.getElementById('modalComplejo').classList.add('show');
    goStep(1);

    const [sels, res] = await Promise.all([
        loadSelects(),
        fetch(`${CMP_API}?action=get&id=${id}`)
    ]);
    const json = await res.json();
    if (!json.ok) { toast(json.msg,'err'); closeModal('modalComplejo'); return; }
    const d = json.data;

    document.getElementById('mCmpId').value    = d.COMPLEJO_ID;
    document.getElementById('mCmpNombre').value = d.COMPLEJO_NOMBRE;
    document.getElementById('mCmpDir').value    = d.COMPLEJO_DIRECCION;
    document.getElementById('mCmpTel').value    = d.COMPLEJO_TELEFONO||'';
    document.getElementById('mCmpEmail').value  = d.COMPLEJO_EMAIL||'';
    document.getElementById('mCmpDesc').value   = d.COMPLEJO_DESCRIPCION||'';
    document.getElementById('mCmpTipo').value   = d.TIPO_COMPLEJO_ID||'';

    // Pre-cargar jerarquía provincia → partido → localidad
    if (d.LOCALIDAD_ID) geoPrecargar(d.LOCALIDAD_ID, 'mCmpProv', 'mCmpPartido', 'mCmpLoc');

    // Actividades pre-cargadas (keys siempre string)
    cmpActividades = {};
    (d.actividades||[]).forEach(a => {
        cmpActividades[String(a.TIPO_CANCHA_ID)] = { destacada: parseInt(a.ACTIVIDAD_DESTACADA)||0 };
    });

    // Horarios pre-cargados
    cmpHorarios = {};
    Object.entries(d.horarios||{}).forEach(([dia,h]) => {
        cmpHorarios[dia] = { abre:true, apertura:h.ATENCION_HORA_APERTURA.slice(0,5), cierre:h.ATENCION_HORA_CIERRE.slice(0,5) };
    });

    setTimeout(() => document.getElementById('mCmpNombre').focus(), 150);
}

function resetCmpModal() {
    document.getElementById('mCmpId').value     = '';
    document.getElementById('mCmpNombre').value = '';
    document.getElementById('mCmpDir').value    = '';
    document.getElementById('mCmpTel').value    = '';
    document.getElementById('mCmpEmail').value  = '';
    document.getElementById('mCmpDesc').value   = '';
    document.getElementById('mCmpTipo').value   = '';

    // Reset cascada geo
    const provSel = document.getElementById('mCmpProv');
    const parSel  = document.getElementById('mCmpPartido');
    const locSel  = document.getElementById('mCmpLoc');
    provSel.innerHTML = '<option value="">Cargando…</option>';
    parSel.innerHTML  = '<option value="">Primero elegí provincia…</option>';
    parSel.disabled   = true;
    locSel.innerHTML  = '<option value="">Primero elegí partido…</option>';
    locSel.disabled   = true;

    ['mCmpNombreErr','mCmpDirErr','mCmpLocErr'].forEach(id => {
        const el = document.getElementById(id);
        if(el) el.style.display='none';
    });
    cmpActividades = {};
    cmpHorarios    = {};
}

// ─── STEPS ────────────────────────────────────
function goStep(n) {
    cmpCurrentStep = n;
    [1,2,3].forEach(s => {
        document.getElementById('mCmpStep'+s).style.display = s===n ? 'block':'none';
        const tab = document.getElementById('mstep-'+s);
        const isActive = s===n;
        tab.style.borderBottomColor = isActive ? 'var(--green)' : 'transparent';
        tab.querySelector('div:first-child').style.color = isActive ? 'var(--green)':'var(--muted)';
        tab.querySelector('div:last-child').style.color  = isActive ? 'var(--green)':'var(--muted)';
    });
    document.getElementById('mCmpBtnPrev').style.display = n>1 ? 'inline-flex':'none';

    const nextBtn = document.getElementById('mCmpBtnNext');
    if (n === 3) {
        nextBtn.innerHTML = '<i class="fas fa-check"></i> Guardar complejo';
    } else {
        nextBtn.innerHTML = 'Siguiente <i class="fas fa-arrow-right"></i>';
    }

    if (n===2) renderActividadesGrid();
    if (n===3) renderHorariosTable();
}

function cmpNextOrSubmit() {
    if (cmpCurrentStep < 3) {
        if (!validateStep(cmpCurrentStep)) return;
        goStep(cmpCurrentStep + 1);
    } else {
        submitComplejo();
    }
}

function validateStep(n) {
    let ok = true;
    if (n === 1) {
        const fields = [
            ['mCmpNombre','mCmpNombreErr','El nombre es obligatorio.'],
            ['mCmpDir',   'mCmpDirErr',   'La dirección es obligatoria.'],
            ['mCmpLoc',   'mCmpLocErr',   'Seleccioná una localidad.'],
        ];
        fields.forEach(([fId,eId,msg]) => {
            const el  = document.getElementById(fId);
            const err = document.getElementById(eId);
            if (!el.value.trim()) {
                err.textContent = msg; err.style.display='block'; ok=false;
            } else { err.style.display='none'; }
        });
    }
    return ok;
}

// ─── GRID ACTIVIDADES ─────────────────────────
function renderActividadesGrid() {
    const grid = document.getElementById('actividadesGrid');
    if (!grid) return;

    if (!cmpSelects || !cmpSelects.tipos_cancha) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;color:var(--muted);padding:30px;font-size:12px">
            <i class="fas fa-spinner fa-spin" style="display:block;font-size:20px;margin-bottom:8px;opacity:.4"></i>
            Cargando actividades…
        </div>`;
        return;
    }

    const tipos = cmpSelects.tipos_cancha;

    if (!tipos.length) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;color:var(--muted);padding:20px;font-size:12px">
            No hay tipos de cancha. <a href="#" onclick="showView(document.querySelector('[data-view=catalogos]'))" style="color:var(--green)">Crear uno →</a>
        </div>`;
        return;
    }

    grid.innerHTML = tipos.map(t => {
        // Normalizar a string para comparar con keys de cmpActividades
        const tid    = String(t.TIPO_CANCHA_ID);
        const sel    = cmpActividades[tid];
        const active = !!sel;
        const dest   = sel ? sel.destacada : 0;
        const ico    = t.TIPO_CANCHA_ICONO || 'fa-tag';
        return `<div id="act-card-${tid}" onclick="toggleActividad('${tid}')"
            style="border-radius:10px;border:1px solid ${active?'rgba(76,217,100,.35)':'var(--border)'};
            padding:12px;cursor:pointer;transition:all .18s;
            background:${active?'rgba(76,217,100,.07)':'var(--s1)'};
            user-select:none">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:${active?'8':'0'}px">
                <div style="width:30px;height:30px;border-radius:8px;display:flex;align-items:center;justify-content:center;
                    font-size:13px;background:${active?'rgba(76,217,100,.15)':'var(--s2)'};
                    color:${active?'var(--green)':'var(--muted)'}">
                    <i class="fas ${escHtml(ico)}"></i>
                </div>
                <span style="font-size:12px;font-weight:700;color:${active?'var(--text)':'var(--muted)'};flex:1">${escHtml(t.TIPO_CANCHA_NOMBRE)}</span>
                <div style="width:16px;height:16px;border-radius:50%;border:1.5px solid ${active?'var(--green)':'var(--border)'};
                    background:${active?'var(--green)':'transparent'};flex-shrink:0;display:flex;align-items:center;justify-content:center">
                    ${active?'<i class="fas fa-check" style="font-size:8px;color:#000"></i>':''}
                </div>
            </div>
            ${active ? `
            <div onclick="event.stopPropagation()" style="display:flex;align-items:center;gap:6px;margin-top:8px;
                padding-top:8px;border-top:1px solid rgba(76,217,100,.15)">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:11px;color:var(--muted)">
                    <input type="checkbox" ${dest?'checked':''} onclick="toggleDestacada(event,'${tid}')"
                        style="accent-color:var(--green);width:13px;height:13px">
                    Destacada
                </label>
            </div>` : ''}
        </div>`;
    }).join('');
}

function toggleActividad(tid) {
    tid = String(tid);
    if (cmpActividades[tid]) { delete cmpActividades[tid]; }
    else                      { cmpActividades[tid] = { destacada: 0 }; }
    renderActividadesGrid();
}

function toggleDestacada(e, tid) {
    e.stopPropagation();
    tid = String(tid);
    if (cmpActividades[tid]) cmpActividades[tid].destacada = e.target.checked ? 1 : 0;
}

// ─── TABLA HORARIOS ───────────────────────────
function renderHorariosTable() {
    const wrap = document.getElementById('horariosTable');
    wrap.innerHTML = DIAS.map(d => {
        const h     = cmpHorarios[d.id] || { abre:false, apertura:'08:00', cierre:'22:00' };
        const abre  = h.abre;
        return `<div style="display:flex;align-items:center;gap:12px;padding:10px 12px;
            border-radius:9px;border:1px solid ${abre?'rgba(76,217,100,.2)':'var(--border)'};
            background:${abre?'rgba(76,217,100,.04)':'var(--s1)'};
            transition:all .18s" id="hor-row-${d.id}">
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;width:110px;flex-shrink:0">
                <input type="checkbox" id="hor-chk-${d.id}" ${abre?'checked':''}
                    onchange="toggleDia(${d.id})"
                    style="accent-color:var(--green);width:15px;height:15px">
                <span style="font-size:13px;font-weight:${abre?700:500};color:${abre?'var(--text)':'var(--muted)'}">${d.nombre}</span>
            </label>
            <div style="display:flex;align-items:center;gap:8px;flex:1;${abre?'':'opacity:.3;pointer-events:none'}">
                <div style="display:flex;align-items:center;gap:6px;flex:1">
                    <i class="fas fa-door-open" style="color:var(--green);font-size:11px"></i>
                    <input type="time" value="${h.apertura}" id="hor-ap-${d.id}"
                        onchange="updateHorario(${d.id})"
                        style="background:var(--s2);border:1px solid var(--border);border-radius:7px;
                        padding:6px 10px;color:var(--text);font-size:12px;outline:none;flex:1;
                        min-width:0;-webkit-appearance:none;color-scheme:dark">
                </div>
                <span style="color:var(--muted);font-size:11px">a</span>
                <div style="display:flex;align-items:center;gap:6px;flex:1">
                    <i class="fas fa-door-closed" style="color:var(--red);font-size:11px"></i>
                    <input type="time" value="${h.cierre}" id="hor-ci-${d.id}"
                        onchange="updateHorario(${d.id})"
                        style="background:var(--s2);border:1px solid var(--border);border-radius:7px;
                        padding:6px 10px;color:var(--text);font-size:12px;outline:none;flex:1;
                        min-width:0;-webkit-appearance:none;color-scheme:dark">
                </div>
            </div>
        </div>`;
    }).join('');
}

function toggleDia(diaId) {
    const chk = document.getElementById('hor-chk-'+diaId);
    const ap  = document.getElementById('hor-ap-'+diaId).value;
    const ci  = document.getElementById('hor-ci-'+diaId).value;
    if (chk.checked) { cmpHorarios[diaId] = { abre:true,  apertura: ap||'08:00', cierre: ci||'22:00' }; }
    else              { cmpHorarios[diaId] = { abre:false, apertura: ap||'08:00', cierre: ci||'22:00' }; }
    renderHorariosTable();
}

function updateHorario(diaId) {
    const ap = document.getElementById('hor-ap-'+diaId)?.value;
    const ci = document.getElementById('hor-ci-'+diaId)?.value;
    if (cmpHorarios[diaId]) { cmpHorarios[diaId].apertura=ap; cmpHorarios[diaId].cierre=ci; }
}

// ─── SUBMIT ───────────────────────────────────
async function submitComplejo() {
    const btn = document.getElementById('mCmpBtnNext');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

    const id = document.getElementById('mCmpId').value;

    // Preparar actividades
    const acts = Object.entries(cmpActividades).map(([tid,v]) => ({
        tipo_cancha_id: parseInt(tid), destacada: v.destacada
    }));

    // Preparar horarios
    const hors = Object.entries(cmpHorarios)
        .filter(([,h]) => h.abre)
        .map(([dia,h]) => ({ dia_id: parseInt(dia), apertura: h.apertura, cierre: h.cierre }));

    const fd = new FormData();
    fd.append('action',         id ? 'editar' : 'crear');
    fd.append('nombre',         document.getElementById('mCmpNombre').value.trim());
    fd.append('direccion',      document.getElementById('mCmpDir').value.trim());
    fd.append('telefono',       document.getElementById('mCmpTel').value.trim());
    fd.append('email',          document.getElementById('mCmpEmail').value.trim());
    fd.append('descripcion',    document.getElementById('mCmpDesc').value.trim());
    fd.append('localidad_id',   document.getElementById('mCmpLoc').value);
    fd.append('tipo_complejo_id', document.getElementById('mCmpTipo').value);
    fd.append('actividades',    JSON.stringify(acts));
    fd.append('horarios',       JSON.stringify(hors));
    if (id) fd.append('id', id);

    try {
        const res  = await fetch(CMP_API, { method:'POST', body:fd });
        const json = await res.json();
        if (json.ok) {
            toast(json.msg, 'ok');
            closeModal('modalComplejo');
            loadComplejos();
        } else {
            toast(json.msg, 'err');
            goStep(1);
        }
    } catch(e) {
        toast('Error de red.','err');
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Guardar complejo';
    }
}

function showConfirmDeactivate(mensaje, onConfirm) {
    const existing = document.getElementById('confirmDeactivateOverlay');
    if (existing) existing.remove();
    const overlay = document.createElement('div');
    overlay.id = 'confirmDeactivateOverlay';
    overlay.style.cssText = `
        position:fixed;inset:0;background:rgba(0,0,0,0.7);
        display:flex;align-items:center;justify-content:center;
        z-index:9999;backdrop-filter:blur(4px);
    `;
    overlay.innerHTML = `
        <div id="confirmDeactivateBox" style="background:rgba(20,20,20,0.95);border:1px solid rgba(255,255,255,0.15);
             border-radius:16px;padding:32px;max-width:420px;width:90%;text-align:center;">
            <div style="font-size:2.5rem;margin-bottom:16px;">⚠️</div>
            <p style="color:#fff;font-size:1rem;line-height:1.5;margin-bottom:24px;">${mensaje}</p>
            <div style="display:flex;gap:12px;justify-content:center;">
                <button onclick="document.getElementById('confirmDeactivateOverlay').remove()"
                    style="padding:10px 24px;border-radius:8px;border:1px solid rgba(255,255,255,0.2);
                    background:transparent;color:#fff;cursor:pointer;font-size:0.9rem;">
                    Cancelar
                </button>
                <button id="confirmDeactivateBtn"
                    style="padding:10px 24px;border-radius:8px;border:none;
                    background:#e74c3c;color:#fff;cursor:pointer;font-size:0.9rem;font-weight:600;">
                    Desactivar igual
                </button>
            </div>
        </div>
    `;
    overlay.addEventListener('click', e => {
        if (e.target === overlay) overlay.remove();
    });
    document.body.appendChild(overlay);
    document.getElementById('confirmDeactivateBtn').onclick = () => {
        overlay.remove();
        onConfirm();
    };
}

async function cmpToggleReal(id, btn) {
    btn.disabled = true;
    const fd = new FormData();
    fd.append('action','toggle'); fd.append('id',id);
    try {
        const res  = await fetch(CMP_API,{method:'POST',body:fd});
        const json = await res.json();
        if(json.ok) { toast(json.msg,'ok'); loadComplejos(); }
        else          toast(json.msg,'err');
    } catch(e) { toast('Error de red.','err'); }
    finally { btn.disabled=false; }
}

async function cmpToggle(id, btn) {
    const isActive = btn.classList.contains('on');
    if (!isActive) {
        // Activando → directo, sin confirmación
        cmpToggleReal(id, btn);
        return;
    }
    // Desactivando → verificar TOTAL_CANCHAS
    const complejo = cmpData.find(r => r.COMPLEJO_ID == id);
    const totalCanchas = complejo ? (parseInt(complejo.TOTAL_CANCHAS) || 0) : 0;
    if (totalCanchas > 0) {
        showConfirmDeactivate(
            `Este complejo tiene ${totalCanchas} cancha${totalCanchas !== 1 ? 's' : ''} activa${totalCanchas !== 1 ? 's' : ''}. Desactivarlo las ocultará a los clientes. ¿Confirmás?`,
            () => cmpToggleReal(id, btn)
        );
    } else {
        cmpToggleReal(id, btn);
    }
}

// ═══════════════════════════════════════════════
// CANCHAS
// ═══════════════════════════════════════════════
const CAN_API     = 'api/canchas.php';
let canData       = [];
let canFilterVal  = 'all';
let canSearchVal  = '';
let canComplejoF  = 'all';
let canSelects    = null;
let canCurrentStep= 1;
let canEncargados = new Set();

async function loadCanchas() {
    document.getElementById('canTbody').innerHTML =
        `<tr><td colspan="7"><div class="empty-state">
            <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
            <p style="color:var(--muted)">Cargando…</p>
        </div></td></tr>`;
    const res  = await fetch(`${CAN_API}?action=listar`);
    const json = await res.json();
    if (!json.ok) { toast(json.msg,'err'); return; }
    canData = json.data;
    buildCmpFilterBar();
    renderCanchas();
}

function buildCmpFilterBar() {
    const complejos = [...new Map(canData.map(r => [r.COMPLEJO_ID, r.COMPLEJO_NOMBRE])).entries()];
    const bar = document.getElementById('cmpFilterBar');
    if (complejos.length <= 1) { bar.style.display='none'; return; }
    bar.style.display='flex';
    bar.innerHTML = [['all','Todos los complejos'], ...complejos].map(([id,nom]) =>
        `<button class="filter-btn ${canComplejoF==id?'active':''}"
            onclick="canSetComplejo(this,'${id}')"
            style="font-size:11px">
            ${id==='all'?'<i class="fas fa-building" style="margin-right:4px"></i>':''}
            ${escHtml(String(nom))}
        </button>`
    ).join('');
}

function canSetComplejo(btn,val) {
    document.querySelectorAll('#cmpFilterBar .filter-btn').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active'); canComplejoF=val; renderCanchas();
}

function renderCanchas() {
    const search = canSearchVal.toLowerCase();
    let filtered = canData.filter(r => {
        const mf  = canFilterVal==='all' || String(r.ACTIVO)===canFilterVal;
        const mc  = canComplejoF==='all' || String(r.COMPLEJO_ID)===String(canComplejoF);
        const ms  = !search || r.CANCHA_NOMBRE.toLowerCase().includes(search)
                             || r.COMPLEJO_NOMBRE.toLowerCase().includes(search);
        return mf && mc && ms;
    });

    if (!filtered.length) {
        document.getElementById('canTbody').innerHTML =
            `<tr><td colspan="7"><div class="empty-state">
                <div class="es-icon"><i class="fas fa-futbol"></i></div>
                <h4>Sin resultados</h4>
                <p>No hay canchas con los filtros actuales.</p>
                <button class="btn btn-primary btn-sm" style="margin-top:14px;background:linear-gradient(135deg,var(--orange),#e67e22)"
                    onclick="canchasAbrirCrear()">
                    <i class="fas fa-plus"></i> Crear primera cancha
                </button>
            </div></td></tr>`;
        return;
    }

    const html = filtered.map((r,i) => {
        const activo = parseInt(r.ACTIVO);
        const ico    = r.TIPO_CANCHA_ICONO || 'fa-futbol';
        return `<tr style="animation:fadeUp .22s ease ${i*.04}s both">
            <td>
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;border-radius:10px;flex-shrink:0;
                        background:rgba(255,149,0,.12);color:var(--orange);
                        display:flex;align-items:center;justify-content:center;font-size:16px">
                        <i class="fas ${escHtml(ico)}"></i>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:13px">${escHtml(r.CANCHA_NOMBRE)}</div>
                        <div style="font-size:11px;color:var(--muted)">${r.CANCHA_DESCRIPCION?escHtml(r.CANCHA_DESCRIPCION.substring(0,40))+'…':''}</div>
                    </div>
                </div>
            </td>
            <td>
                <span style="display:inline-flex;align-items:center;gap:5px;
                    padding:3px 9px;border-radius:20px;font-size:11px;font-weight:700;
                    background:rgba(255,149,0,.1);color:var(--orange);
                    border:1px solid rgba(255,149,0,.2)">
                    <i class="fas ${escHtml(ico)}" style="font-size:10px"></i>
                    ${escHtml(r.TIPO_CANCHA_NOMBRE)}
                </span>
            </td>
            <td style="font-size:12px">
                <div style="font-weight:600">${escHtml(r.COMPLEJO_NOMBRE)}</div>
            </td>
            <td style="text-align:center">
                ${statChip(r.TOTAL_FRANJAS, 'fa-clock', r.TOTAL_FRANJAS>0?'var(--blue)':'var(--muted)', r.TOTAL_FRANJAS>0?'rgba(52,152,219,.12)':'rgba(255,255,255,.06)')}
            </td>
            <td style="text-align:center">
                ${statChip(r.RESERVAS_HOY, 'fa-calendar-check', r.RESERVAS_HOY>0?'var(--green)':'var(--muted)', r.RESERVAS_HOY>0?'rgba(76,217,100,.12)':'rgba(255,255,255,.06)')}
            </td>
            <td><span class="badge ${activo?'active':'inactive'}">${activo?'Activa':'Inactiva'}</span></td>
            <td>
                <div class="row-actions">
                    <button class="act-btn edit" title="Editar" onclick="canchasAbrirEditar(${r.CANCHA_ID})">
                        <i class="fas fa-pen"></i>
                    </button>
                    <button class="act-btn toggle ${activo?'on':''}" title="${activo?'Desactivar':'Activar'}"
                        onclick="canToggle(${r.CANCHA_ID},this)">
                        <i class="fas ${activo?'fa-toggle-on':'fa-toggle-off'}"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
    document.getElementById('canTbody').innerHTML = html;
}

function statChip(val, ico, color, bg) {
    return `<span style="display:inline-flex;align-items:center;gap:5px;
        padding:4px 10px;border-radius:8px;font-size:12px;font-weight:800;
        background:${bg};color:${color}">
        <i class="fas ${ico}" style="font-size:10px"></i>${val}
    </span>`;
}

function canFilter()         { canSearchVal=document.getElementById('canSearch').value; renderCanchas(); }
function canSetFilter(btn,v) {
    document.querySelectorAll('[data-canf]').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active'); canFilterVal=v; renderCanchas();
}

// ─── SELECTS ──────────────────────────────────
async function loadCanSelects() {
    if (!canSelects) {
        const res  = await fetch(`${CAN_API}?action=selects`);
        const json = await res.json();
        if (!json.ok) { toast('Error cargando datos.','err'); return null; }
        canSelects = json.data;
    }

    // Siempre repoblar el DOM (el reset limpia los selects en cada apertura)
    const cmpEl = document.getElementById('mCanComplejo');
    const tipEl = document.getElementById('mCanTipo');
    cmpEl.innerHTML = '<option value="">Seleccioná complejo…</option>';
    tipEl.innerHTML = '<option value="">Seleccioná tipo…</option>';
    canSelects.complejos.forEach(c => cmpEl.innerHTML += `<option value="${c.COMPLEJO_ID}">${escHtml(c.COMPLEJO_NOMBRE)}</option>`);
    canSelects.tipos.forEach(t    => tipEl.innerHTML += `<option value="${t.TIPO_CANCHA_ID}" data-ico="${escHtml(t.TIPO_CANCHA_ICONO||'fa-futbol')}">${escHtml(t.TIPO_CANCHA_NOMBRE)}</option>`);

    // Si el usuario ya llegó al paso 2 mientras cargaba, renderizar encargados
    if (canCurrentStep === 2) renderEncargadosGrid();

    return canSelects;
}

// ─── PREVIEW ──────────────────────────────────
function canUpdateIcon() {
    const tipEl  = document.getElementById('mCanTipo');
    const selOpt = tipEl.options[tipEl.selectedIndex];
    const ico    = selOpt?.dataset?.ico || 'fa-futbol';
    document.getElementById('canPrevIcon').innerHTML = `<i class="fas ${escHtml(ico)}"></i>`;
    canRefreshPreview();
}
function canUpdateTipoHint() { canRefreshPreview(); }
function canRefreshPreview() {
    const nombre  = document.getElementById('mCanNombre').value.trim();
    const cmpEl   = document.getElementById('mCanComplejo');
    const tipEl   = document.getElementById('mCanTipo');
    const cmpNom  = cmpEl.options[cmpEl.selectedIndex]?.text || '';
    const tipNom  = tipEl.options[tipEl.selectedIndex]?.text || '';
    const prev    = document.getElementById('canPreview');
    if (nombre || cmpNom !== 'Seleccioná complejo…') {
        prev.style.display='block';
        document.getElementById('canPrevNombre').textContent = nombre || '(sin nombre)';
        document.getElementById('canPrevSub').textContent    = [cmpNom,tipNom].filter(Boolean).join(' · ');
    } else {
        prev.style.display='none';
    }
}
// Live preview al escribir
['mCanNombre'].forEach(id => {
    document.getElementById(id)?.addEventListener('input', canRefreshPreview);
});

// ─── ENCARGADOS GRID ──────────────────────────
function renderEncargadosGrid() {
    const grid = document.getElementById('encargadosGrid');
    const encs = canSelects?.encargados || [];

    if (!encs.length) {
        grid.innerHTML = `<div style="text-align:center;padding:24px;color:var(--muted);font-size:12px">
            <i class="fas fa-user-slash" style="font-size:22px;display:block;margin-bottom:8px;opacity:.3"></i>
            No hay encargados o empleados activos para asignar.
        </div>`;
        return;
    }

    grid.innerHTML = encs.map(u => {
        const uid = u.USUARIOS_ID;
        const sel = canEncargados.has(parseInt(uid));
        return `<div onclick="toggleEncargado(${uid},this)" id="enc-${uid}"
            style="display:flex;align-items:center;gap:10px;padding:10px 12px;
            border-radius:9px;border:1px solid ${sel?'rgba(76,217,100,.3)':'var(--border)'};
            background:${sel?'rgba(76,217,100,.06)':'var(--s1)'};
            cursor:pointer;transition:all .15s;user-select:none">
            <div style="width:32px;height:32px;border-radius:8px;
                background:${sel?'rgba(76,217,100,.15)':'var(--s2)'};
                color:${sel?'var(--green)':'var(--muted)'};
                display:flex;align-items:center;justify-content:center;
                font-weight:800;font-size:13px;flex-shrink:0">
                ${escHtml(u.USUARIOS_NOMBRE.charAt(0).toUpperCase())}
            </div>
            <div style="flex:1">
                <div style="font-size:12px;font-weight:700;color:${sel?'var(--text)':'var(--muted)'}">
                    ${escHtml(u.USUARIOS_NOMBRE+' '+u.USUARIOS_APELLIDO)}
                </div>
                <div style="font-size:10px;color:var(--muted)">${escHtml(u.PERFIL_NOMBRE)}</div>
            </div>
            <div style="width:18px;height:18px;border-radius:50%;flex-shrink:0;
                border:1.5px solid ${sel?'var(--green)':'var(--border)'};
                background:${sel?'var(--green)':'transparent'};
                display:flex;align-items:center;justify-content:center">
                ${sel?'<i class="fas fa-check" style="font-size:9px;color:#000"></i>':''}
            </div>
        </div>`;
    }).join('');
}

function toggleEncargado(uid) {
    const id = parseInt(uid);
    if (canEncargados.has(id)) canEncargados.delete(id);
    else                        canEncargados.add(id);
    renderEncargadosGrid();
}

// ─── MODAL ABRIR ──────────────────────────────
async function canchasAbrirCrear() {
    resetCanModal();
    document.getElementById('mCanTitle').textContent = 'Nueva cancha';
    document.getElementById('mCanIcon').innerHTML    = '<i class="fas fa-plus"></i>';
    goCanStep(1);
    document.getElementById('modalCancha').classList.add('show');
    await loadCanSelects();
    setTimeout(()=>document.getElementById('mCanNombre').focus(),150);
}

async function canchasAbrirEditar(id) {
    resetCanModal();
    document.getElementById('mCanTitle').textContent = 'Editar cancha';
    document.getElementById('mCanIcon').innerHTML    = '<i class="fas fa-pen"></i>';
    document.getElementById('modalCancha').classList.add('show');
    goCanStep(1);

    const [sels, res] = await Promise.all([
        loadCanSelects(),
        fetch(`${CAN_API}?action=get&id=${id}`)
    ]);
    const json = await res.json();
    if (!json.ok) { toast(json.msg,'err'); closeModal('modalCancha'); return; }
    const d = json.data;

    document.getElementById('mCanId').value       = d.CANCHA_ID;
    document.getElementById('mCanNombre').value   = d.CANCHA_NOMBRE;
    document.getElementById('mCanDesc').value     = d.CANCHA_DESCRIPCION||'';
    document.getElementById('mCanComplejo').value = d.COMPLEJO_ID;
    document.getElementById('mCanTipo').value     = d.TIPO_CANCHA_ID;
    canEncargados = new Set((d.encargados||[]).map(Number));
    canUpdateIcon();
    canRefreshPreview();
}

function resetCanModal() {
    document.getElementById('mCanId').value     = '';
    document.getElementById('mCanNombre').value = '';
    document.getElementById('mCanDesc').value   = '';
    document.getElementById('canPreview').style.display = 'none';

    // Resetear selects a placeholder (se repoblan en loadCanSelects)
    document.getElementById('mCanComplejo').innerHTML = '<option value="">Cargando…</option>';
    document.getElementById('mCanTipo').innerHTML     = '<option value="">Cargando…</option>';

    ['mCanNombreErr','mCanComplejoErr','mCanTipoErr'].forEach(id=>{
        const el=document.getElementById(id); if(el) el.style.display='none';
    });
    canEncargados = new Set();
}

// ─── STEPS CANCHAS ────────────────────────────
function goCanStep(n) {
    canCurrentStep = n;
    [1,2].forEach(s=>{
        document.getElementById('mCanStep'+s).style.display = s===n?'block':'none';
        const tab = document.getElementById('mstepCan-'+s);
        const act = s===n;
        tab.style.borderBottomColor = act?'var(--orange)':'transparent';
        tab.querySelector('div:first-child').style.color = act?'var(--orange)':'var(--muted)';
        tab.querySelector('div:last-child').style.color  = act?'var(--orange)':'var(--muted)';
    });
    document.getElementById('mCanBtnPrev').style.display = n>1?'inline-flex':'none';
    const nextBtn = document.getElementById('mCanBtnNext');
    nextBtn.innerHTML = n===2
        ? '<i class="fas fa-check"></i> Guardar cancha'
        : 'Siguiente <i class="fas fa-arrow-right"></i>';
    if (n===2) renderEncargadosGrid();
}

function canNextOrSubmit() {
    if (canCurrentStep < 2) {
        if (!validateCanStep()) return;
        goCanStep(2);
    } else {
        submitCancha();
    }
}

function validateCanStep() {
    let ok = true;
    const checks = [
        ['mCanNombre', 'mCanNombreErr',   'El nombre es obligatorio.'],
        ['mCanComplejo','mCanComplejoErr', 'Seleccioná un complejo.'],
        ['mCanTipo',   'mCanTipoErr',     'Seleccioná el tipo de cancha.'],
    ];
    checks.forEach(([fId,eId,msg])=>{
        const el=document.getElementById(fId), err=document.getElementById(eId);
        if(!el.value.trim()){ err.textContent=msg; err.style.display='block'; ok=false; }
        else { err.style.display='none'; }
    });
    return ok;
}

// ─── SUBMIT ───────────────────────────────────
async function submitCancha() {
    const btn = document.getElementById('mCanBtnNext');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando…';

    const id = document.getElementById('mCanId').value;
    const fd = new FormData();
    fd.append('action',        id?'editar':'crear');
    fd.append('nombre',        document.getElementById('mCanNombre').value.trim());
    fd.append('descripcion',   document.getElementById('mCanDesc').value.trim());
    fd.append('complejo_id',   document.getElementById('mCanComplejo').value);
    fd.append('tipo_cancha_id',document.getElementById('mCanTipo').value);
    fd.append('encargados',    JSON.stringify([...canEncargados]));
    if(id) fd.append('id',id);

    try {
        const res  = await fetch(CAN_API,{method:'POST',body:fd});
        const json = await res.json();
        if(json.ok){
            toast(json.msg,'ok'); closeModal('modalCancha');
            if (predioId) cargarCanchasDelPredio(predioId);
            else loadCanchas();
        }
        else        { toast(json.msg,'err'); goCanStep(1); }
    } catch(e){ toast('Error de red.','err'); }
    finally {
        btn.disabled=false;
        btn.innerHTML = canCurrentStep===2
            ? '<i class="fas fa-check"></i> Guardar cancha'
            : 'Siguiente <i class="fas fa-arrow-right"></i>';
    }
}

async function canToggleReal(id, btn, afterCb) {
    btn.disabled=true;
    const fd=new FormData();
    fd.append('action','toggle'); fd.append('id',id);
    try{
        const res=await fetch(CAN_API,{method:'POST',body:fd});
        const json=await res.json();
        if(json.ok){
            toast(json.msg,'ok');
            if (typeof afterCb === 'function') afterCb();
            else loadCanchas();
        } else toast(json.msg,'err');
    }catch(e){toast('Error de red.','err');}
    finally{btn.disabled=false;}
}

async function canToggle(id, btn, afterCb) {
    const isActive = btn.classList.contains('on');
    if (!isActive) {
        // Activando → directo, sin confirmación
        canToggleReal(id, btn, afterCb);
        return;
    }
    // Desactivando → verificar RESERVAS_HOY
    const cancha = canData.find(r => r.CANCHA_ID == id);
    const reservasHoy = cancha ? (parseInt(cancha.RESERVAS_HOY) || 0) : 0;
    if (reservasHoy > 0) {
        showConfirmDeactivate(
            `Esta cancha tiene ${reservasHoy} reserva${reservasHoy !== 1 ? 's' : ''} activa${reservasHoy !== 1 ? 's' : ''} para hoy. ¿Confirmás que querés desactivarla?`,
            () => canToggleReal(id, btn, afterCb)
        );
    } else {
        canToggleReal(id, btn, afterCb);
    }
}

// ═══════════════════════════════════════════════
// INIT
// ═══════════════════════════════════════════════
// Cargar conteos del sidebar al iniciar
(async () => {
    for (const cat of ['tipo_cancha','tipo_complejo','medio_pago']) {
        const res = await fetch(`${API}?action=listar&tabla=${cat}`);
        const js  = await res.json();
        if (js.ok) { catData[cat] = js.data; updateCount(cat, js.data.length); }
    }
})();

// Interceptar navegación para cargar datos al entrar a vistas
const _origShowView = showView;
showView = function(el) {
    _origShowView(el);
    const name = el.dataset.view;
    if (name === 'complejos') loadComplejos();
    if (name === 'canchas')   loadCanchas();
    if (name === 'horarios')  horInit();
    if (name === 'cierres')   loadCierres();
    if (name === 'turnos')    loadTurnos();
    if (name === 'staff')     loadStaff();
    if (name === 'duenos')    loadDuenos();
    if (name === 'agenda') {
        document.getElementById('agendaFechaInput').value = agendaFechaStr(agendaFecha);
        agendaCargarComplejos();
        agendaRecargar();
    }
    if (name === 'reportes') loadReportes();
    if (name === 'clientes') loadClientes();
    if (name === 'perfil')   loadPerfil();
    if (name === 'reservas') { resCargarCanchas(); loadReservas(); }
    if (name === 'pagos')    loadPagosView();
};

// ══════════════════════════════════════════════════════
//  CIERRES DE CANCHA
// ══════════════════════════════════════════════════════
const CIERRE_API = 'api/cierres.php';
let cierreSelects = null;
let cierresData   = [];

// Cargar selects del modal
async function cierreCargarSelects() {
    if (cierreSelects) return;
    const r = await fetch(`${CIERRE_API}?action=selects`);
    const j = await r.json();
    if (!j.ok) { toast('Error cargando datos', 'err'); return; }
    cierreSelects = j.data;

    // Poblar filtro de complejo en la vista
    const filtro = document.getElementById('cierreFiltroComplejo');
    filtro.innerHTML = '<option value="">Todos los predios</option>';
    cierreSelects.complejos.forEach(c =>
        filtro.innerHTML += `<option value="${c.COMPLEJO_ID}">${escHtml(c.COMPLEJO_NOMBRE)}</option>`
    );
}

// Cargar lista de cierres
async function loadCierres() {
    await cierreCargarSelects();
    const cmpId    = document.getElementById('cierreFiltroComplejo').value;
    const vigentes = document.getElementById('cierreSoloVigentes').checked ? 1 : 0;
    const hoy      = new Date().toISOString().split('T')[0];

    let url = `${CIERRE_API}?action=listar&solo_activos=0`;
    if (cmpId)    url += `&complejo_id=${cmpId}`;
    if (vigentes) url += `&desde=${hoy}`;

    const lista = document.getElementById('cierresLista');
    lista.innerHTML = '<div class="empty-state"><div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div></div>';

    const r = await fetch(url);
    const j = await r.json();
    if (!j.ok) { lista.innerHTML = `<div class="empty-state"><p>${j.msg}</p></div>`; return; }

    cierresData = j.data || [];

    if (!cierresData.length) {
        lista.innerHTML = `<div class="empty-state">
            <div class="es-icon"><i class="fas fa-calendar-check"></i></div>
            <h4>Sin cierres registrados</h4>
            <p>No hay bloqueos para el período seleccionado.</p>
        </div>`;
        return;
    }

    lista.innerHTML = cierresData.map(c => {
        const esComplejo = c.ALCANCE === 'complejo';
        const esTotal    = c.TIPO_CIERRE === 'total';
        const titulo     = esComplejo
            ? escHtml(c.COMPLEJO_NOMBRE)
            : `${escHtml(c.CANCHA_NOMBRE)} · ${escHtml(c.COMPLEJO_NOMBRE)}`;
        const sub = c.CIERRE_MOTIVO ? escHtml(c.CIERRE_MOTIVO) : 'Sin motivo especificado';
        const fechas = c.CIERRE_FECHA_DESDE === c.CIERRE_FECHA_HASTA
            ? fmtFecha(c.CIERRE_FECHA_DESDE)
            : `${fmtFecha(c.CIERRE_FECHA_DESDE)} → ${fmtFecha(c.CIERRE_FECHA_HASTA)}`;
        const horas = !esTotal
            ? `<br><span style="font-size:0.75rem">${c.CIERRE_HORA_DESDE.substring(0,5)} - ${c.CIERRE_HORA_HASTA.substring(0,5)}</span>` : '';

        return `<div class="cierre-card ${c.ACTIVO==1?'':'inactivo'}" id="cierre-${c.CIERRE_ID}">
            <div class="cierre-icono ${esComplejo?'azul':''}">
                <i class="fas ${esComplejo ? 'fa-building' : 'fa-futbol'}"></i>
            </div>
            <div class="cierre-info">
                <div class="cierre-titulo">${titulo}</div>
                <div class="cierre-sub">${sub}</div>
                <div class="cierre-badges">
                    <span class="cierre-badge ${esComplejo?'badge-complejo':'badge-cancha'}">${esComplejo?'Todo el predio':'Cancha específica'}</span>
                    <span class="cierre-badge ${esTotal?'badge-total':'badge-parcial'}">${esTotal?'Día completo':'Horario parcial'}</span>
                    ${c.ACTIVO==0?'<span class="cierre-badge" style="background:rgba(255,255,255,0.06);color:var(--muted)">Inactivo</span>':''}
                </div>
            </div>
            <div class="cierre-fechas">
                <strong>${fechas}</strong>${horas}
            </div>
            <div class="row-actions" style="flex-shrink:0">
                <button class="btn btn-ghost btn-sm" title="Editar" onclick="cierreAbrirEditar(${c.CIERRE_ID})">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="btn btn-ghost btn-sm" title="${c.ACTIVO?'Desactivar':'Activar'}"
                    onclick="cierreToggle(${c.CIERRE_ID}, this)">
                    <i class="fas fa-${c.ACTIVO?'eye-slash':'eye'}"></i>
                </button>
                <button class="btn btn-ghost btn-sm" title="Eliminar" style="color:var(--red)"
                    onclick="cierreEliminar(${c.CIERRE_ID})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

// Helper: formatear fecha legible
function fmtFecha(str) {
    if (!str) return '—';
    const [y,m,d] = str.split('-');
    const meses = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
    return `${d} ${meses[parseInt(m)-1]} ${y}`;
}

// Abrir modal crear
async function cierreAbrirCrear() {
    await cierreCargarSelects();
    document.getElementById('mCierreId').value         = '';
    document.getElementById('mCierreFechaDesde').value = new Date().toISOString().split('T')[0];
    document.getElementById('mCierreFechaHasta').value = new Date().toISOString().split('T')[0];
    document.getElementById('mCierreMotivo').value     = '';
    document.getElementById('mCierreParcial').checked  = false;
    document.getElementById('mCierreHorasWrap').style.display = 'none';
    document.getElementById('mCierreHoraDesde').value = '';
    document.getElementById('mCierreHoraHasta').value = '';
    const titleEl = document.getElementById('mCierreTitle');
    if (titleEl) titleEl.textContent = 'Nuevo cierre';

    cierrePopularSelects();
    openModal('modalCierre');
}

// Abrir modal editar
async function cierreAbrirEditar(id) {
    await cierreCargarSelects();
    const c = cierresData.find(x => x.CIERRE_ID == id);
    if (!c) { toast('Cierre no encontrado.', 'err'); return; }

    document.getElementById('mCierreId').value         = c.CIERRE_ID;
    document.getElementById('mCierreFechaDesde').value = c.CIERRE_FECHA_DESDE;
    document.getElementById('mCierreFechaHasta').value = c.CIERRE_FECHA_HASTA;
    document.getElementById('mCierreMotivo').value     = c.CIERRE_MOTIVO || '';

    const parcial = c.TIPO_CIERRE === 'parcial';
    document.getElementById('mCierreParcial').checked  = parcial;
    document.getElementById('mCierreHorasWrap').style.display = parcial ? '' : 'none';
    document.getElementById('mCierreHoraDesde').value = c.CIERRE_HORA_DESDE ? c.CIERRE_HORA_DESDE.substring(0,5) : '';
    document.getElementById('mCierreHoraHasta').value = c.CIERRE_HORA_HASTA ? c.CIERRE_HORA_HASTA.substring(0,5) : '';

    cierrePopularSelects(c.COMPLEJO_ID, c.CANCHA_ID);
    openModal('modalCierre');
}

// Poblar selects del modal
function cierrePopularSelects(cmpId = '', canId = '') {
    const selCmp = document.getElementById('mCierreComplejo');

    selCmp.innerHTML = '<option value="">Seleccioná predio…</option>';
    cierreSelects.complejos.forEach(c =>
        selCmp.innerHTML += `<option value="${c.COMPLEJO_ID}" ${c.COMPLEJO_ID==cmpId?'selected':''}>${escHtml(c.COMPLEJO_NOMBRE)}</option>`
    );

    cierrePopularCanchas(cmpId, canId);
}

// Poblar canchas filtradas por complejo
function cierrePopularCanchas(cmpId, canId = '') {
    const selCan = document.getElementById('mCierreCancha');
    selCan.innerHTML = '<option value="">Todo el predio</option>';
    if (!cmpId) return;
    cierreSelects.canchas
        .filter(c => c.COMPLEJO_ID == cmpId)
        .forEach(c => selCan.innerHTML += `<option value="${c.CANCHA_ID}" ${c.CANCHA_ID==canId?'selected':''}>${escHtml(c.CANCHA_NOMBRE)}</option>`);
}

function cierreOnComplejoChange() {
    const cmpId = document.getElementById('mCierreComplejo').value;
    cierrePopularCanchas(cmpId);
}

function cierreToggleParcial() {
    const wrap = document.getElementById('mCierreHorasWrap');
    wrap.style.display = document.getElementById('mCierreParcial').checked ? '' : 'none';
}

// Enviar formulario (crear o editar)
async function submitCierre() {
    const id      = document.getElementById('mCierreId').value;
    const cmpId   = document.getElementById('mCierreComplejo').value;
    const canId   = document.getElementById('mCierreCancha').value;
    const fDesde  = document.getElementById('mCierreFechaDesde').value;
    const fHasta  = document.getElementById('mCierreFechaHasta').value;
    const parcial = document.getElementById('mCierreParcial').checked;
    const hDesde  = document.getElementById('mCierreHoraDesde').value;
    const hHasta  = document.getElementById('mCierreHoraHasta').value;
    const motivo  = document.getElementById('mCierreMotivo').value.trim();

    if (!cmpId)                                    { toast('Seleccioná un predio.', 'err'); return; }
    if (!fDesde || !fHasta)                        { toast('Las fechas son obligatorias.', 'err'); return; }
    if (fHasta < fDesde)                           { toast('La fecha hasta debe ser igual o posterior a desde.', 'err'); return; }
    if (parcial && (!hDesde || !hHasta))           { toast('Completá ambos horarios para el cierre parcial.', 'err'); return; }

    const fd = new FormData();
    fd.append('action',      id ? 'editar' : 'crear');
    if (id) fd.append('cierre_id', id);
    fd.append('complejo_id', cmpId);
    if (canId) fd.append('cancha_id', canId);
    fd.append('fecha_desde', fDesde);
    fd.append('fecha_hasta', fHasta);
    if (parcial) { fd.append('hora_desde', hDesde); fd.append('hora_hasta', hHasta); }
    if (motivo) fd.append('motivo', motivo);

    const r = await fetch(CIERRE_API, { method: 'POST', body: fd });
    const j = await r.json();
    toast(j.msg, j.ok ? 'ok' : 'err');
    if (j.ok) { closeModal('modalCierre'); loadCierres(); }
}

async function cierreToggle(id, btn) {
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('cierre_id', id);
    const j = await (await fetch(CIERRE_API, { method: 'POST', body: fd })).json();
    toast(j.msg, j.ok ? 'ok' : 'err');
    if (j.ok) loadCierres();
}

async function cierreEliminar(id) {
    if (!confirm('¿Eliminár este cierre definitivamente?')) return;
    const fd = new FormData();
    fd.append('action', 'eliminar');
    fd.append('cierre_id', id);
    const j = await (await fetch(CIERRE_API, { method: 'POST', body: fd })).json();
    toast(j.msg, j.ok ? 'ok' : 'err');
    if (j.ok) loadCierres();
}

// ═══════════════════════════════════════════════
// AGENDA DE RESERVAS
// ═══════════════════════════════════════════════
let agendaFecha = new Date();
let agendaData  = [];

function agendaFechaStr(d) {
    return d.toISOString().split('T')[0];
}

function agendaIrDia(delta) {
    agendaFecha.setDate(agendaFecha.getDate() + delta);
    document.getElementById('agendaFechaInput').value = agendaFechaStr(agendaFecha);
    agendaRecargar();
}

async function loadAgenda() {
    document.getElementById('agendaFechaInput').value = agendaFechaStr(agendaFecha);
    const sub = document.getElementById('agendaSubtitulo');
    if (sub) sub.textContent = agendaFecha.toLocaleDateString('es-AR',
        { weekday:'long', day:'numeric', month:'long', year:'numeric' });

    const estado = document.getElementById('agendaFiltroEstado').value;
    const url = `api/reservas.php?action=listar&fecha=${agendaFechaStr(agendaFecha)}${estado?'&estado='+estado:''}`;

    document.getElementById('agendaLista').innerHTML =
        '<div class="empty-state"><div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div><p>Cargando…</p></div>';
    document.getElementById('agendaKpis').innerHTML = '';

    try {
        const r = await fetch(url);
        const j = await r.json();
        if (!j.ok) {
            document.getElementById('agendaLista').innerHTML =
                `<div class="empty-state"><div class="es-icon"><i class="fas fa-exclamation-triangle"></i></div><p>${j.msg||'Error al cargar reservas'}</p></div>`;
            return;
        }
        agendaData = j.data || [];
        renderAgenda();
    } catch(e) {
        document.getElementById('agendaLista').innerHTML =
            `<div class="empty-state"><div class="es-icon"><i class="fas fa-exclamation-triangle"></i></div><p>Error de conexión</p></div>`;
    }
}

function renderAgenda() {
    const lista = document.getElementById('agendaLista');
    const kpis  = document.getElementById('agendaKpis');

    // KPIs
    const total       = agendaData.length;
    const pendientes  = agendaData.filter(r => r.RESERVA_ESTADO === 'pendiente').length;
    const confirmadas = agendaData.filter(r => r.RESERVA_ESTADO === 'confirmada').length;
    const cobrado     = agendaData.reduce((s,r) => s + parseFloat(r.PAGADO_TOTAL||0), 0);

    kpis.innerHTML = `
        <div class="agenda-kpi-card"><div class="kpi-val">${total}</div><div class="kpi-label">Total reservas</div></div>
        <div class="agenda-kpi-card"><div class="kpi-val" style="color:var(--orange)">${pendientes}</div><div class="kpi-label">Pendientes</div></div>
        <div class="agenda-kpi-card"><div class="kpi-val" style="color:var(--green)">${confirmadas}</div><div class="kpi-label">Confirmadas</div></div>
        <div class="agenda-kpi-card"><div class="kpi-val" style="color:var(--blue)">$${cobrado.toLocaleString('es-AR')}</div><div class="kpi-label">Cobrado hoy</div></div>
    `;

    if (!agendaData.length) {
        lista.innerHTML = `<div class="empty-state"><div class="es-icon"><i class="fas fa-calendar-times"></i></div><h4>Sin reservas</h4><p>No hay reservas para este día.</p></div>`;
        return;
    }

    lista.innerHTML = agendaData.map(r => {
        const saldo = parseFloat(r.SALDO_PENDIENTE || 0);
        const acciones = r.RESERVA_ESTADO === 'pendiente' ? `
            <button class="btn-icon-sm green" title="Confirmar" onclick="agendaConfirmar(${r.RESERVA_ID})"><i class="fas fa-check"></i></button>
            <button class="btn-icon-sm red" title="Rechazar" onclick="agendaRechazar(${r.RESERVA_ID})"><i class="fas fa-times"></i></button>
        ` : '';
        return `
        <div class="reserva-row estado-${r.RESERVA_ESTADO}" id="rrow-${r.RESERVA_ID}">
            <div class="reserva-hora">
                ${r.RESERVA_HORA_INICIO.substring(0,5)}<br>
                <span style="font-size:0.7rem;font-weight:400;color:var(--muted)">${r.RESERVA_HORA_FIN.substring(0,5)}</span>
            </div>
            <div class="reserva-info">
                <div class="res-cancha"><i class="fas ${r.TIPO_CANCHA_ICONO||'fa-futbol'}" style="color:var(--green);margin-right:6px"></i>${esc(r.CANCHA_NOMBRE)} · ${esc(r.COMPLEJO_NOMBRE)}</div>
                <div class="res-cliente"><i class="fas fa-user" style="margin-right:5px"></i>${esc(r.USUARIOS_NOMBRE)} ${esc(r.USUARIOS_APELLIDO)}
                    ${r.USUARIOS_TELEFONO ? `· <a href="https://wa.me/549${r.USUARIOS_TELEFONO.replace(/\D/g,'')}" target="_blank" style="color:var(--green)"><i class="fab fa-whatsapp"></i></a>` : ''}
                </div>
                <div style="margin-top:5px">
                    <span class="estado-badge estado-${r.RESERVA_ESTADO}">${r.RESERVA_ESTADO.charAt(0).toUpperCase()+r.RESERVA_ESTADO.slice(1)}</span>
                    ${r.RESERVA_ES_FIJA ? '<span class="estado-badge" style="background:rgba(52,152,219,0.15);color:var(--blue)">Fijo</span>' : ''}
                </div>
            </div>
            <div class="reserva-finanzas">
                <div class="res-precio">$${parseFloat(r.RESERVA_PRECIO).toLocaleString('es-AR')}</div>
                <div class="res-saldo ${saldo<=0?'pagado':''}">${saldo>0?'Saldo: $'+saldo.toLocaleString('es-AR'):'✓ Pagado'}</div>
            </div>
            <div class="reserva-actions">
                ${acciones}
                <button class="btn-icon-sm blue" title="Registrar pago / Ver pagos" onclick="abrirModalPago(${r.RESERVA_ID})"><i class="fas fa-dollar-sign"></i></button>
            </div>
        </div>`;
    }).join('');
}

async function agendaConfirmar(id) {
    const fd = new FormData();
    fd.append('action','confirmar');
    fd.append('reserva_id', id);
    try {
        const j = await (await fetch('api/reservas.php',{method:'POST',body:fd})).json();
        toast(j.msg || (j.ok ? 'Reserva confirmada' : 'Error'), j.ok ? 'ok' : 'err');
        if (j.ok) {
            loadAgenda();
            actualizarBadgePendientes();
            if (j.data?.cliente_tel) notifWspCliente(j.data);
        }
    } catch(e) { toast('Error de conexión','err'); }
}

async function agendaRechazar(id) {
    if (!confirm('¿Rechazar esta reserva?')) return;
    const fd = new FormData();
    fd.append('action','rechazar');
    fd.append('reserva_id', id);
    try {
        const j = await (await fetch('api/reservas.php',{method:'POST',body:fd})).json();
        toast(j.msg || (j.ok ? 'Reserva rechazada' : 'Error'), j.ok ? 'ok' : 'err');
        if (j.ok) { loadAgenda(); actualizarBadgePendientes(); }
    } catch(e) { toast('Error de conexión','err'); }
}

// ── Modal de pago ──
let pagoReservaId = null;

async function abrirModalPago(reservaId, nombre, saldo, precio) {
    pagoReservaId = reservaId;
    document.getElementById('pagoObs').value = '';

    // Título del modal
    const titulo = document.getElementById('modalPagoTitulo');
    if (titulo) titulo.textContent = nombre ? `Cobrar — ${nombre}` : 'Registrar pago';

    // Pre-llenar monto con el saldo pendiente si se conoce
    const montoEl = document.getElementById('pagoMonto');
    if (montoEl) montoEl.value = (saldo != null && saldo > 0) ? saldo : '';

    // Cargar historial de pagos
    const hist = document.getElementById('pagoHistorial');
    hist.innerHTML = '<div style="color:var(--muted);font-size:0.85rem">Cargando pagos…</div>';
    openModal('modalPago');

    try {
        const r = await fetch(`api/reservas.php?action=pagos&reserva_id=${reservaId}`);
        const j = await r.json();
        if (!j.ok || !j.data || !j.data.length) {
            hist.innerHTML = '<p style="color:var(--muted);font-size:0.82rem">Sin pagos registrados aún.</p>';
            return;
        }
        hist.innerHTML = `
            <h4 style="font-size:0.85rem;color:var(--muted);margin-bottom:10px;text-transform:uppercase;letter-spacing:0.05em">Pagos registrados</h4>
            ${j.data.map(p => `
                <div style="display:flex;justify-content:space-between;align-items:center;
                            padding:8px 12px;border-radius:8px;background:rgba(255,255,255,0.04);margin-bottom:6px;font-size:0.83rem">
                    <span>${p.PAGO_TIPO.charAt(0).toUpperCase()+p.PAGO_TIPO.slice(1)} · ${esc(p.PAGO_MEDIO)}</span>
                    <span style="color:var(--green);font-weight:700">$${parseFloat(p.PAGO_MONTO).toLocaleString('es-AR')}</span>
                </div>
            `).join('')}
        `;
    } catch(e) {
        hist.innerHTML = '<p style="color:var(--muted);font-size:0.82rem">Error al cargar pagos.</p>';
    }
}

async function submitPago() {
    const monto = parseFloat(document.getElementById('pagoMonto').value);
    if (!monto || monto <= 0) { toast('Ingresá un monto válido.','err'); return; }
    const fd = new FormData();
    fd.append('action','registrar_pago');
    fd.append('reserva_id', pagoReservaId);
    fd.append('monto', monto);
    fd.append('tipo', document.getElementById('pagoTipo').value);
    fd.append('medio', document.getElementById('pagoMedio').value);
    fd.append('observacion', document.getElementById('pagoObs').value);
    try {
        const j = await (await fetch('api/reservas.php',{method:'POST',body:fd})).json();
        toast(j.msg || (j.ok ? 'Pago registrado' : 'Error'), j.ok ? 'ok' : 'err');
        if (j.ok) {
            closeModal('modalPago');
            // Refrescar la vista activa que abrió el modal
            const activeView = document.querySelector('.view.active');
            const vid = activeView ? activeView.id : '';
            if (vid === 'view-pagos')    loadPagosView();
            else if (vid === 'view-reservas') loadReservas();
            else loadAgenda();
        }
    } catch(e) { toast('Error de conexión','err'); }
}

// ═══════════════════════════════════════════════
// STAFF
// ═══════════════════════════════════════════════
const STAFF_API = 'api/usuarios.php';
function openModal(id) { document.getElementById(id).classList.add('show'); }

async function loadStaff() {
    const body = document.getElementById('staff-body');
    body.innerHTML = '<div class="empty-state"><div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div><h4>Cargando…</h4></div>';
    try {
        const res = await fetch(`${STAFF_API}?action=listar_staff`);
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg || 'Error al cargar staff');
        renderStaff(js.data);
    } catch(e) {
        body.innerHTML = `<div class="empty-state"><div class="es-icon"><i class="fas fa-exclamation-triangle"></i></div><h4>Error</h4><p>${e.message}</p></div>`;
    }
}

function renderStaff(data) {
    const body = document.getElementById('staff-body');
    if (!data || !data.length) {
        body.innerHTML = '<div class="empty-state"><div class="es-icon"><i class="fas fa-id-badge"></i></div><h4>Sin staff</h4><p>No hay encargados ni empleados registrados.</p></div>';
        return;
    }
    const rows = data.map(u => {
        const perfilLabel = u.PERFIL_ID == 3
            ? '<span class="badge" style="background:rgba(52,152,219,.18);color:var(--blue)">Encargado</span>'
            : '<span class="badge" style="background:rgba(255,149,0,.18);color:var(--orange)">Empleado</span>';
        const estado = u.ACTIVO == 1
            ? '<span class="badge active">Activo</span>'
            : '<span class="badge pending">Inactivo</span>';
        const canchas = u.CANCHAS_ASIGNADAS
            ? `<span style="color:var(--muted);font-size:11px">${u.CANCHAS_ASIGNADAS}</span>`
            : '<span style="color:var(--muted);font-size:11px">Sin asignar</span>';
        return `<tr id="staff-row-${u.USUARIOS_ID}">
            <td>
                <div class="user-cell">
                    <div class="user-av">${(u.USUARIOS_NOMBRE||'?')[0].toUpperCase()}</div>
                    <div>
                        <div class="user-name">${esc(u.USUARIOS_NOMBRE+' '+u.USUARIOS_APELLIDO)}</div>
                        <div class="user-email">${esc(u.USUARIOS_EMAIL)}</div>
                    </div>
                </div>
            </td>
            <td>${perfilLabel}</td>
            <td>${canchas}</td>
            <td>${estado}</td>
            <td>
                <div class="row-actions">
                    <button class="btn btn-sm" style="background:var(--s2);border:1px solid var(--border)" onclick="staffAbrirEditar(${u.USUARIOS_ID})" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm" style="background:var(--s2);border:1px solid var(--border)" onclick="abrirAsignarCanchas(${u.USUARIOS_ID})" title="Asignar canchas"><i class="fas fa-futbol"></i></button>
                    <button class="btn btn-sm" style="background:var(--s2);border:1px solid var(--border)" id="stoggle-${u.USUARIOS_ID}" onclick="staffToggle(${u.USUARIOS_ID}, this)" title="${u.ACTIVO==1?'Desactivar':'Activar'}">
                        <i class="fas ${u.ACTIVO==1?'fa-toggle-on':'fa-toggle-off'}"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
    body.innerHTML = `<table>
        <thead><tr>
            <th>Nombre</th><th>Perfil</th><th>Canchas asignadas</th><th>Estado</th><th>Acciones</th>
        </tr></thead>
        <tbody>${rows}</tbody>
    </table>`;
}

async function staffToggle(id, btn) {
    btn.disabled = true;
    try {
        const fd = new FormData(); fd.append('id', id);
        const res = await fetch(`${STAFF_API}?action=toggle`, {method:'POST', body:fd});
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg || 'Error');
        toast(js.msg || 'Estado actualizado', 'ok');
        loadStaff();
    } catch(e) {
        toast(e.message, 'err');
        btn.disabled = false;
    }
}

let _staffData = [];

function staffAbrirCrear() {
    document.getElementById('mStaffId').value      = '';
    document.getElementById('mStaffNombre').value  = '';
    document.getElementById('mStaffApellido').value= '';
    document.getElementById('mStaffDni').value     = '';
    document.getElementById('mStaffTel').value     = '';
    document.getElementById('mStaffEmail').value   = '';
    document.getElementById('mStaffPass').value    = '';
    document.getElementById('mStaffPerfil').value  = '3';
    document.getElementById('mStaffTitle').textContent = 'Nuevo integrante';
    document.getElementById('mStaffPassLabel').textContent = '*';
    document.getElementById('mStaffPassLabel').title = 'Requerida al crear';
    ['mStaffNombreErr','mStaffApellidoErr','mStaffEmailErr','mStaffPassErr','mStaffDuenoErr'].forEach(id => {
        const el = document.getElementById(id); if(el) el.textContent = '';
    });
    // Superadmin: mostrar selector de dueño y poblar
    const duenoRow = document.getElementById('mStaffDuenoRow');
    if (PERFIL === 1) {
        duenoRow.style.display = '';
        poblarSelectDuenos('mStaffDueno');
    } else {
        duenoRow.style.display = 'none';
    }
    openModal('modalStaff');
}

async function staffAbrirEditar(id) {
    try {
        const res = await fetch(`${STAFF_API}?action=listar_staff`);
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg);
        const u = js.data.find(x => x.USUARIOS_ID == id);
        if (!u) throw new Error('Usuario no encontrado');
        document.getElementById('mStaffId').value       = u.USUARIOS_ID;
        document.getElementById('mStaffNombre').value   = u.USUARIOS_NOMBRE || '';
        document.getElementById('mStaffApellido').value = u.USUARIOS_APELLIDO || '';
        document.getElementById('mStaffDni').value      = u.USUARIOS_DNI || '';
        document.getElementById('mStaffTel').value      = u.USUARIOS_TELEFONO || '';
        document.getElementById('mStaffEmail').value    = u.USUARIOS_EMAIL || '';
        document.getElementById('mStaffPass').value     = '';
        document.getElementById('mStaffPerfil').value   = u.PERFIL_ID || '3';
        document.getElementById('mStaffTitle').textContent = 'Editar integrante';
        document.getElementById('mStaffPassLabel').textContent = '(opcional)';
        ['mStaffNombreErr','mStaffApellidoErr','mStaffEmailErr','mStaffPassErr','mStaffDuenoErr'].forEach(id => {
            const el = document.getElementById(id); if(el) el.textContent = '';
        });
        const duenoRow = document.getElementById('mStaffDuenoRow');
        if (PERFIL === 1) {
            duenoRow.style.display = '';
            await poblarSelectDuenos('mStaffDueno');
            document.getElementById('mStaffDueno').value = u.DUENO_ID || '';
        } else {
            duenoRow.style.display = 'none';
        }
        openModal('modalStaff');
    } catch(e) {
        toast(e.message, 'err');
    }
}

async function submitStaff() {
    const id   = document.getElementById('mStaffId').value.trim();
    const nom  = document.getElementById('mStaffNombre').value.trim();
    const ape  = document.getElementById('mStaffApellido').value.trim();
    const eml  = document.getElementById('mStaffEmail').value.trim();
    const pass = document.getElementById('mStaffPass').value;
    let valid  = true;
    if (!nom) { document.getElementById('mStaffNombreErr').textContent  = 'Requerido'; valid=false; }
    if (!ape) { document.getElementById('mStaffApellidoErr').textContent= 'Requerido'; valid=false; }
    if (!eml) { document.getElementById('mStaffEmailErr').textContent   = 'Requerido'; valid=false; }
    if (!id && !pass) { document.getElementById('mStaffPassErr').textContent = 'Requerida al crear'; valid=false; }
    if (!valid) return;

    const fd = new FormData();
    if (id) {
        fd.append('id', id);
        fd.append('nombre',   nom);
        fd.append('apellido', ape);
        fd.append('dni',      document.getElementById('mStaffDni').value.trim());
        fd.append('email',    eml);
        fd.append('telefono', document.getElementById('mStaffTel').value.trim());
        if (pass) fd.append('password', pass);
    } else {
        fd.append('nombre',    nom);
        fd.append('apellido',  ape);
        fd.append('dni',       document.getElementById('mStaffDni').value.trim());
        fd.append('email',     eml);
        fd.append('telefono',  document.getElementById('mStaffTel').value.trim());
        fd.append('perfil_id', document.getElementById('mStaffPerfil').value);
        fd.append('password',  pass);
        if (PERFIL === 1) {
            const dId = document.getElementById('mStaffDueno').value;
            if (!dId) { document.getElementById('mStaffDuenoErr').textContent = 'Seleccioná un dueño'; return; }
            fd.append('dueno_id', dId);
        }
    }

    const action = id ? 'editar' : 'crear_staff';
    try {
        const res = await fetch(`${STAFF_API}?action=${action}`, {method:'POST', body:fd});
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg || 'Error al guardar');
        toast(js.msg || 'Guardado', 'ok');
        closeModal('modalStaff');
        loadStaff();
    } catch(e) {
        toast(e.message, 'err');
    }
}

// ═══════════════════════════════════════════════
// DUEÑOS (superadmin)
// ═══════════════════════════════════════════════
async function loadDuenos() {
    const body = document.getElementById('duenos-body');
    body.innerHTML = '<div class="empty-state"><div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div><h4>Cargando…</h4></div>';
    try {
        const res = await fetch(`${STAFF_API}?action=listar_duenos`);
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg || 'Error al cargar dueños');
        renderDuenos(js.data);
    } catch(e) {
        body.innerHTML = `<div class="empty-state"><div class="es-icon"><i class="fas fa-exclamation-triangle"></i></div><h4>Error</h4><p>${e.message}</p></div>`;
    }
}

function renderDuenos(data) {
    const body = document.getElementById('duenos-body');
    if (!data || !data.length) {
        body.innerHTML = '<div class="empty-state"><div class="es-icon"><i class="fas fa-users-cog"></i></div><h4>Sin dueños</h4><p>No hay clientes registrados todavía.</p></div>';
        return;
    }
    const rows = data.map(d => {
        const estado = d.ACTIVO == 1
            ? '<span class="badge active">Activo</span>'
            : '<span class="badge pending">Inactivo</span>';
        return `<tr id="dueno-row-${d.USUARIOS_ID}">
            <td>
                <div class="user-cell">
                    <div class="user-av" style="background:linear-gradient(135deg,var(--green),var(--green-d))">${(d.USUARIOS_NOMBRE||'?')[0].toUpperCase()}</div>
                    <div>
                        <div class="user-name">${esc(d.USUARIOS_NOMBRE+' '+d.USUARIOS_APELLIDO)}</div>
                        <div class="user-email">${esc(d.USUARIOS_EMAIL)}</div>
                    </div>
                </div>
            </td>
            <td style="text-align:center">${d.TOTAL_PREDIOS||0}</td>
            <td style="text-align:center">${d.TOTAL_CANCHAS||0}</td>
            <td style="text-align:center">${d.TOTAL_STAFF||0}</td>
            <td style="text-align:center">${d.RESERVAS_HOY||0}</td>
            <td>${estado}</td>
            <td>
                <div class="row-actions">
                    <button class="btn btn-sm" style="background:var(--s2);border:1px solid var(--border)" onclick="duenoAbrirEditar(${d.USUARIOS_ID})" title="Editar"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm" style="background:var(--s2);border:1px solid var(--border)" id="dtoggle-${d.USUARIOS_ID}" onclick="duenoToggle(${d.USUARIOS_ID}, this)" title="${d.ACTIVO==1?'Desactivar':'Activar'}">
                        <i class="fas ${d.ACTIVO==1?'fa-toggle-on':'fa-toggle-off'}"></i>
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
    body.innerHTML = `<table>
        <thead><tr>
            <th>Nombre</th><th style="text-align:center">Predios</th><th style="text-align:center">Canchas</th>
            <th style="text-align:center">Staff</th><th style="text-align:center">Reservas hoy</th>
            <th>Estado</th><th>Acciones</th>
        </tr></thead>
        <tbody>${rows}</tbody>
    </table>`;
}

async function duenoToggle(id, btn) {
    btn.disabled = true;
    try {
        const fd = new FormData(); fd.append('id', id);
        const res = await fetch(`${STAFF_API}?action=toggle`, {method:'POST', body:fd});
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg || 'Error');
        toast(js.msg || 'Estado actualizado', 'ok');
        loadDuenos();
    } catch(e) {
        toast(e.message, 'err');
        btn.disabled = false;
    }
}

function duenoAbrirCrear() {
    ['mDuenoNombre','mDuenoApellido','mDuenoDni','mDuenoTel','mDuenoEmail','mDuenoPass'].forEach(id => {
        const el = document.getElementById(id); if(el) el.value = '';
    });
    ['mDuenoNombreErr','mDuenoApellidoErr','mDuenoEmailErr','mDuenoPassErr'].forEach(id => {
        const el = document.getElementById(id); if(el) el.textContent = '';
    });
    openModal('modalDueno');
}

async function duenoAbrirEditar(id) {
    try {
        const res = await fetch(`${STAFF_API}?action=listar_duenos`);
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg);
        const d = js.data.find(x => x.USUARIOS_ID == id);
        if (!d) throw new Error('Dueño no encontrado');
        document.getElementById('mDuenoNombre').value   = d.USUARIOS_NOMBRE || '';
        document.getElementById('mDuenoApellido').value = d.USUARIOS_APELLIDO || '';
        document.getElementById('mDuenoDni').value      = d.USUARIOS_DNI || '';
        document.getElementById('mDuenoTel').value      = d.USUARIOS_TELEFONO || '';
        document.getElementById('mDuenoEmail').value    = d.USUARIOS_EMAIL || '';
        document.getElementById('mDuenoPass').value     = '';
        document.getElementById('mDuenoTitle').textContent = 'Editar dueño';
        // Store id for submit
        document.getElementById('mDuenoTitle').dataset.editId = id;
        openModal('modalDueno');
    } catch(e) {
        toast(e.message, 'err');
    }
}

async function submitDueno() {
    const nom  = document.getElementById('mDuenoNombre').value.trim();
    const ape  = document.getElementById('mDuenoApellido').value.trim();
    const eml  = document.getElementById('mDuenoEmail').value.trim();
    const pass = document.getElementById('mDuenoPass').value;
    const editId = document.getElementById('mDuenoTitle').dataset.editId || '';
    let valid = true;
    if (!nom) { document.getElementById('mDuenoNombreErr').textContent  = 'Requerido'; valid=false; }
    if (!ape) { document.getElementById('mDuenoApellidoErr').textContent= 'Requerido'; valid=false; }
    if (!eml) { document.getElementById('mDuenoEmailErr').textContent   = 'Requerido'; valid=false; }
    if (!editId && !pass) { document.getElementById('mDuenoPassErr').textContent = 'Requerida al crear'; valid=false; }
    if (!valid) return;

    const fd = new FormData();
    fd.append('nombre',   nom);
    fd.append('apellido', ape);
    fd.append('dni',      document.getElementById('mDuenoDni').value.trim());
    fd.append('email',    eml);
    fd.append('telefono', document.getElementById('mDuenoTel').value.trim());
    if (pass) fd.append('password', pass);

    let action = 'crear_dueno';
    if (editId) { fd.append('id', editId); action = 'editar'; }

    try {
        const res = await fetch(`${STAFF_API}?action=${action}`, {method:'POST', body:fd});
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg || 'Error al guardar');
        toast(js.msg || 'Guardado', 'ok');
        delete document.getElementById('mDuenoTitle').dataset.editId;
        closeModal('modalDueno');
        loadDuenos();
    } catch(e) {
        toast(e.message, 'err');
    }
}

// Helper: poblar select de dueños
async function poblarSelectDuenos(selectId) {
    const sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">Cargando…</option>';
    try {
        const res = await fetch(`${STAFF_API}?action=listar_duenos`);
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg);
        sel.innerHTML = '<option value="">Seleccioná dueño…</option>' +
            js.data.map(d => `<option value="${d.USUARIOS_ID}">${esc(d.USUARIOS_NOMBRE+' '+d.USUARIOS_APELLIDO)}</option>`).join('');
    } catch(e) {
        sel.innerHTML = '<option value="">Error al cargar</option>';
    }
}

// ═══════════════════════════════════════════════
// ASIGNAR CANCHAS
// ═══════════════════════════════════════════════
async function abrirAsignarCanchas(uid) {
    document.getElementById('mAsignarUid').value = uid;
    document.getElementById('mAsignarLista').innerHTML =
        '<div class="empty-state"><div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div><h4>Cargando canchas…</h4></div>';
    openModal('modalAsignarCanchas');
    try {
        const res = await fetch(`${STAFF_API}?action=canchas_asignables&usuario_id=${uid}`);
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg || 'Error');
        renderAsignarCanchas(js.canchas, js.asignadas || []);
    } catch(e) {
        document.getElementById('mAsignarLista').innerHTML =
            `<div class="empty-state"><div class="es-icon"><i class="fas fa-exclamation-triangle"></i></div><h4>Error</h4><p>${e.message}</p></div>`;
    }
}

function renderAsignarCanchas(canchas, asignadas) {
    const lista = document.getElementById('mAsignarLista');
    if (!canchas || !canchas.length) {
        lista.innerHTML = '<div class="empty-state"><div class="es-icon"><i class="fas fa-futbol"></i></div><h4>Sin canchas</h4><p>No hay canchas disponibles.</p></div>';
        return;
    }
    // Agrupar por complejo
    const grupos = {};
    canchas.forEach(c => {
        const k = c.COMPLEJO_NOMBRE || 'Sin complejo';
        if (!grupos[k]) grupos[k] = [];
        grupos[k].push(c);
    });
    const asigSet = new Set(asignadas.map(x => String(x)));
    let html = '';
    for (const [grp, items] of Object.entries(grupos)) {
        html += `<div style="margin-bottom:16px">
            <div style="font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">${esc(grp)}</div>`;
        items.forEach(c => {
            const chk = asigSet.has(String(c.CANCHA_ID)) ? 'checked' : '';
            html += `<label style="display:flex;align-items:center;gap:10px;padding:8px 10px;border-radius:8px;cursor:pointer;transition:background .15s"
                onmouseover="this.style.background='var(--s1)'" onmouseout="this.style.background=''">
                <input type="checkbox" value="${c.CANCHA_ID}" ${chk} style="accent-color:var(--green);width:16px;height:16px">
                <span style="font-size:13px">${esc(c.CANCHA_NOMBRE||'Cancha '+c.CANCHA_ID)}</span>
            </label>`;
        });
        html += '</div>';
    }
    lista.innerHTML = html;
}

async function submitAsignarCanchas() {
    const uid = document.getElementById('mAsignarUid').value;
    const checks = document.querySelectorAll('#mAsignarLista input[type=checkbox]:checked');
    const canchas = Array.from(checks).map(c => c.value);
    try {
        const fd = new FormData();
        fd.append('usuario_id', uid);
        fd.append('canchas', JSON.stringify(canchas));
        const res = await fetch(`${STAFF_API}?action=asignar_canchas`, {method:'POST', body:fd});
        const js  = await res.json();
        if (!js.ok) throw new Error(js.msg || 'Error');
        toast(js.msg || 'Canchas asignadas', 'ok');
        closeModal('modalAsignarCanchas');
        loadStaff();
    } catch(e) {
        toast(e.message, 'err');
    }
}

// Helper XSS
function esc(str) {
    return String(str||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Cerrar geoPanel al hacer click fuera
document.addEventListener('click', e => {
    const panel = document.getElementById('geoPanel');
    if (panel.style.display==='block' && !panel.contains(e.target) && !e.target.closest('select')) {
        geoPanelCerrar();
    }
});

// Enter en modal catálogo
document.getElementById('mCatNombre').addEventListener('keydown', e => {
    if (e.key === 'Enter') submitCatalogo();
});

// ══════════════════════════════════════════════
//  TURNOS FIJOS
// ══════════════════════════════════════════════
const TURNO_API = 'api/turnos_fijos.php';
let turnoSelects = null;
let turnosData   = [];
const DIAS_NOMBRES = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];

async function turnoCargarSelects() {
    if (turnoSelects) return;
    const r = await fetch(`${TURNO_API}?action=selects`);
    const j = await r.json();
    if (!j.ok) { toast('Error cargando datos','err'); return; }
    turnoSelects = j.data;

    // Poblar filtros de la vista
    const fc = document.getElementById('turnoFiltroComplejo');
    fc.innerHTML = '<option value="">Todos los predios</option>';
    turnoSelects.complejos.forEach(c =>
        fc.innerHTML += `<option value="${c.COMPLEJO_ID}">${escHtml(c.COMPLEJO_NOMBRE)}</option>`
    );
}

function turnoFiltrarCanchas() {
    const cmpId = document.getElementById('turnoFiltroComplejo').value;
    const sel   = document.getElementById('turnoFiltroCancha');
    sel.innerHTML = '<option value="">Todas las canchas</option>';
    if (!cmpId || !turnoSelects) return;
    turnoSelects.canchas.filter(c => c.COMPLEJO_ID == cmpId)
        .forEach(c => sel.innerHTML += `<option value="${c.CANCHA_ID}">${escHtml(c.CANCHA_NOMBRE)}</option>`);
}

async function loadTurnos() {
    await turnoCargarSelects();
    const cmpId   = document.getElementById('turnoFiltroComplejo').value;
    const canId   = document.getElementById('turnoFiltroCancha').value;
    const dia     = document.getElementById('turnoFiltroDia').value;
    const activos = document.getElementById('turnoSoloActivos').checked ? 1 : 0;

    let url = `${TURNO_API}?action=listar&solo_activos=${activos}`;
    if (cmpId) url += `&complejo_id=${cmpId}`;
    if (canId) url += `&cancha_id=${canId}`;
    if (dia)   url += `&dia=${dia}`;

    const lista = document.getElementById('turnosLista');
    lista.innerHTML = '<div class="empty-state"><div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div></div>';

    const j = await (await fetch(url)).json();
    if (!j.ok) { lista.innerHTML = `<div class="empty-state"><p>${j.msg}</p></div>`; return; }
    turnosData = j.data || [];

    if (!turnosData.length) {
        lista.innerHTML = `<div class="empty-state">
            <div class="es-icon"><i class="fas fa-redo-alt"></i></div>
            <h4>Sin turnos fijos</h4>
            <p>Creá el primer turno recurrente para este predio.</p>
        </div>`;
        return;
    }

    lista.innerHTML = turnosData.map(t => {
        const wsp = t.USUARIOS_TELEFONO
            ? `<a href="https://wa.me/549${t.USUARIOS_TELEFONO.replace(/\D/g,'')}" target="_blank" style="color:var(--green)"><i class="fab fa-whatsapp"></i></a>` : '';
        const cliente = t.USUARIOS_ID
            ? `<i class="fas fa-user"></i> ${escHtml(t.USUARIOS_NOMBRE)} ${escHtml(t.USUARIOS_APELLIDO)} ${wsp}`
            : `<span style="color:rgba(255,255,255,0.3)">Sin cliente asignado</span>`;
        const vigencia = t.TURNO_FIJO_FECHA_HASTA
            ? `${fmtFecha(t.TURNO_FIJO_FECHA_DESDE)} → ${fmtFecha(t.TURNO_FIJO_FECHA_HASTA)}`
            : `Desde ${fmtFecha(t.TURNO_FIJO_FECHA_DESDE)}`;

        return `<div class="turno-card ${t.ACTIVO==1?'':'inactivo'}" id="turno-${t.TURNO_FIJO_ID}">
            <div class="turno-dia-badge">
                <span class="dia-abrev">${DIAS_NOMBRES[t.TURNO_FIJO_DIA]}</span>
                <span class="dia-hora">${t.TURNO_FIJO_HORA_INICIO.substring(0,5)}</span>
            </div>
            <div class="turno-info">
                <div class="turno-title">${escHtml(t.CANCHA_NOMBRE)} · ${escHtml(t.COMPLEJO_NOMBRE)}</div>
                <div class="turno-cliente">${cliente}</div>
                <div class="turno-meta">
                    <span><i class="fas fa-clock" style="color:var(--green);margin-right:4px"></i>${t.TURNO_FIJO_HORA_INICIO.substring(0,5)} - ${t.TURNO_FIJO_HORA_FIN.substring(0,5)}</span>
                    <span><i class="fas fa-calendar" style="margin-right:4px"></i>${vigencia}</span>
                    ${!t.ACTIVO ? '<span style="color:var(--red)">Inactivo</span>' : ''}
                    ${t.ACTIVO && !t.VIGENTE ? '<span style="color:var(--muted)">Vencido</span>' : ''}
                </div>
            </div>
            <div class="turno-precio">$${parseFloat(t.TURNO_FIJO_PRECIO).toLocaleString('es-AR')}</div>
            <div class="row-actions" style="flex-shrink:0">
                <button class="btn btn-ghost btn-sm" title="Editar" onclick="turnoAbrirEditar(${t.TURNO_FIJO_ID})">
                    <i class="fas fa-pen"></i>
                </button>
                <button class="btn btn-ghost btn-sm" title="${t.ACTIVO?'Desactivar':'Activar'}"
                    onclick="turnoToggle(${t.TURNO_FIJO_ID})">
                    <i class="fas fa-${t.ACTIVO?'eye-slash':'eye'}"></i>
                </button>
                <button class="btn btn-ghost btn-sm" style="color:var(--red)" title="Eliminar"
                    onclick="turnoEliminar(${t.TURNO_FIJO_ID})">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>`;
    }).join('');
}

// Modal crear
async function turnoAbrirCrear() {
    await turnoCargarSelects();
    document.getElementById('mTurnoId').value        = '';
    document.getElementById('mTurnoDesde').value     = new Date().toISOString().split('T')[0];
    document.getElementById('mTurnoHasta').value     = '';
    document.getElementById('mTurnoHoraDesde').value = '';
    document.getElementById('mTurnoHoraHasta').value = '';
    document.getElementById('mTurnoPrecio').value    = '';
    document.getElementById('mTurnoDia').value       = '';
    document.getElementById('mTurnoTitle').textContent = 'Nuevo turno fijo';
    turnoPopularSelects();
    openModal('modalTurno');
}

// Modal editar
async function turnoAbrirEditar(id) {
    await turnoCargarSelects();
    const t = turnosData.find(x => x.TURNO_FIJO_ID == id);
    if (!t) { toast('Turno no encontrado.','err'); return; }
    document.getElementById('mTurnoId').value        = t.TURNO_FIJO_ID;
    document.getElementById('mTurnoDia').value       = t.TURNO_FIJO_DIA;
    document.getElementById('mTurnoHoraDesde').value = t.TURNO_FIJO_HORA_INICIO.substring(0,5);
    document.getElementById('mTurnoHoraHasta').value = t.TURNO_FIJO_HORA_FIN.substring(0,5);
    document.getElementById('mTurnoPrecio').value    = t.TURNO_FIJO_PRECIO;
    document.getElementById('mTurnoDesde').value     = t.TURNO_FIJO_FECHA_DESDE;
    document.getElementById('mTurnoHasta').value     = t.TURNO_FIJO_FECHA_HASTA || '';
    document.getElementById('mTurnoTitle').textContent = 'Editar turno fijo';
    turnoPopularSelects(t.COMPLEJO_ID, t.CANCHA_ID, t.FRANJA_ID, t.USUARIOS_ID);
    openModal('modalTurno');
}

function turnoPopularSelects(cmpId='', canId='', franjaId='', clienteId='') {
    const selCmp = document.getElementById('mTurnoComplejo');
    selCmp.innerHTML = '<option value="">Seleccioná predio…</option>';
    turnoSelects.complejos.forEach(c =>
        selCmp.innerHTML += `<option value="${c.COMPLEJO_ID}" ${c.COMPLEJO_ID==cmpId?'selected':''}>${escHtml(c.COMPLEJO_NOMBRE)}</option>`
    );
    turnoModalFiltrarCanchas(cmpId, canId, franjaId);

    const selCli = document.getElementById('mTurnoCliente');
    selCli.innerHTML = '<option value="">Sin cliente asignado</option>';
    turnoSelects.clientes.forEach(u =>
        selCli.innerHTML += `<option value="${u.USUARIOS_ID}" ${u.USUARIOS_ID==clienteId?'selected':''}>${escHtml(u.USUARIOS_NOMBRE)} ${escHtml(u.USUARIOS_APELLIDO)}</option>`
    );
}

function turnoModalFiltrarCanchas(cmpId='', canId='', franjaId='') {
    cmpId = cmpId || document.getElementById('mTurnoComplejo').value;
    const selCan = document.getElementById('mTurnoCancha');
    selCan.innerHTML = '<option value="">Seleccioná cancha…</option>';
    turnoSelects.canchas.filter(c => c.COMPLEJO_ID == cmpId)
        .forEach(c => selCan.innerHTML += `<option value="${c.CANCHA_ID}" ${c.CANCHA_ID==canId?'selected':''}>${escHtml(c.CANCHA_NOMBRE)}</option>`);
    turnoModalCargarFranjas(canId, franjaId);
}

function turnoModalCargarFranjas(canId='', franjaId='') {
    canId = canId || document.getElementById('mTurnoCancha').value;
    const selF = document.getElementById('mTurnoFranja');
    selF.innerHTML = '<option value="">— Horario manual —</option>';
    turnoSelects.franjas.filter(f => f.CANCHA_ID == canId)
        .forEach(f => selF.innerHTML +=
            `<option value="${f.FRANJA_ID}" ${f.FRANJA_ID==franjaId?'selected':''}>${f.FRANJA_HORA_INICIO.substring(0,5)} - ${f.FRANJA_HORA_FIN.substring(0,5)} · $${parseFloat(f.FRANJA_PRECIO).toLocaleString('es-AR')}</option>`
        );
}

function turnoAutocompletarHorario() {
    const fid = document.getElementById('mTurnoFranja').value;
    if (!fid || !turnoSelects) return;
    const f = turnoSelects.franjas.find(x => x.FRANJA_ID == fid);
    if (!f) return;
    document.getElementById('mTurnoHoraDesde').value = f.FRANJA_HORA_INICIO.substring(0,5);
    document.getElementById('mTurnoHoraHasta').value = f.FRANJA_HORA_FIN.substring(0,5);
    document.getElementById('mTurnoPrecio').value    = f.FRANJA_PRECIO;
}

async function submitTurno() {
    const id     = document.getElementById('mTurnoId').value;
    const canId  = document.getElementById('mTurnoCancha').value;
    const dia    = document.getElementById('mTurnoDia').value;
    const hDesde = document.getElementById('mTurnoHoraDesde').value;
    const hHasta = document.getElementById('mTurnoHoraHasta').value;
    const precio = document.getElementById('mTurnoPrecio').value;
    const fDesde = document.getElementById('mTurnoDesde').value;

    if (!canId)   { toast('Seleccioná una cancha.','err'); return; }
    if (!dia)     { toast('Seleccioná el día de la semana.','err'); return; }
    if (!hDesde || !hHasta) { toast('El horario es obligatorio.','err'); return; }
    if (hHasta <= hDesde)   { toast('La hora hasta debe ser posterior a desde.','err'); return; }
    if (!precio || precio <= 0) { toast('El precio debe ser mayor a 0.','err'); return; }
    if (!fDesde)  { toast('La fecha de inicio es obligatoria.','err'); return; }

    const fd = new FormData();
    fd.append('action',      id ? 'editar' : 'crear');
    if (id) fd.append('turno_id', id);
    fd.append('cancha_id',   canId);
    const franjaId = document.getElementById('mTurnoFranja').value;
    if (franjaId) fd.append('franja_id', franjaId);
    const clienteId = document.getElementById('mTurnoCliente').value;
    if (clienteId) fd.append('usuario_id', clienteId);
    fd.append('dia',         dia);
    fd.append('hora_inicio', hDesde);
    fd.append('hora_fin',    hHasta);
    fd.append('precio',      precio);
    fd.append('fecha_desde', fDesde);
    const fHasta = document.getElementById('mTurnoHasta').value;
    if (fHasta) fd.append('fecha_hasta', fHasta);

    const j = await (await fetch(TURNO_API, { method:'POST', body:fd })).json();
    toast(j.msg, j.ok ? 'ok' : 'err');
    if (j.ok) { closeModal('modalTurno'); loadTurnos(); turnoSelects = null; }
}

async function turnoToggle(id) {
    const fd = new FormData();
    fd.append('action','toggle'); fd.append('turno_id', id);
    const j = await (await fetch(TURNO_API,{method:'POST',body:fd})).json();
    toast(j.msg, j.ok?'ok':'err');
    if (j.ok) loadTurnos();
}

async function turnoEliminar(id) {
    if (!confirm('¿Eliminar este turno fijo?')) return;
    const fd = new FormData();
    fd.append('action','eliminar'); fd.append('turno_id', id);
    const j = await (await fetch(TURNO_API,{method:'POST',body:fd})).json();
    toast(j.msg, j.ok?'ok':'err');
    if (j.ok) loadTurnos();
}
</script>

<!-- ── GEO MINI-PANEL ──────────────────────────────────────────── -->
<div id="geoPanel" style="
    display:none;position:fixed;z-index:9999;
    background:var(--s2);border:1px solid var(--border);
    border-radius:12px;padding:14px 16px;width:260px;
    box-shadow:0 8px 32px rgba(0,0,0,.45);
    backdrop-filter:blur(12px)">
    <div style="font-size:11px;font-weight:700;color:var(--muted);letter-spacing:.5px;
        text-transform:uppercase;margin-bottom:10px" id="geoPanelLabel">Agregar</div>
    <input id="geoPanelInput" class="form-input" type="text"
        placeholder="Nombre…" maxlength="100"
        style="margin-bottom:8px"
        onkeydown="if(event.key==='Enter')geoPanelGuardar();if(event.key==='Escape')geoPanelCerrar()">
    <div id="geoPanelErr" style="font-size:11px;color:var(--red);margin-bottom:8px;display:none"></div>
    <div style="display:flex;gap:8px">
        <button class="btn btn-primary btn-sm" style="flex:1" onclick="geoPanelGuardar()">
            <i class="fas fa-check"></i> Guardar
        </button>
        <button class="btn btn-sm" onclick="geoPanelCerrar()"
            style="background:var(--s1);border:1px solid var(--border)">
            Cancelar
        </button>
    </div>
</div>

<!-- ══════════════════════════════
     MODAL: CREAR/EDITAR STAFF
══════════════════════════════ -->
<div class="modal-overlay" id="modalStaff">
    <div class="modal" style="max-width:540px">
        <div class="modal-head">
            <div class="modal-head-icon b" id="mStaffIcon"><i class="fas fa-plus"></i></div>
            <div>
                <h3 id="mStaffTitle">Nuevo integrante</h3>
                <p id="mStaffSub">Completá los datos del encargado o empleado</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalStaff')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:20px;max-height:65vh;overflow-y:auto">
            <input type="hidden" id="mStaffId">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Nombre <span>*</span></label>
                    <input type="text" class="form-input" id="mStaffNombre" placeholder="Ej: Juan" maxlength="80">
                    <div class="form-error" id="mStaffNombreErr"></div>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Apellido <span>*</span></label>
                    <input type="text" class="form-input" id="mStaffApellido" placeholder="Ej: Pérez" maxlength="80">
                    <div class="form-error" id="mStaffApellidoErr"></div>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">DNI</label>
                    <input type="text" class="form-input" id="mStaffDni" placeholder="Ej: 30123456" maxlength="15">
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-input" id="mStaffTel" placeholder="Ej: 11 4444-5555" maxlength="30">
                </div>
                <div class="form-row" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Email <span>*</span></label>
                    <input type="email" class="form-input" id="mStaffEmail" placeholder="juan@ejemplo.com" maxlength="150">
                    <div class="form-error" id="mStaffEmailErr"></div>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Perfil <span>*</span></label>
                    <select class="form-select" id="mStaffPerfil">
                        <option value="3">Encargado</option>
                        <option value="4">Empleado</option>
                    </select>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Contraseña <span id="mStaffPassLabel">*</span></label>
                    <input type="password" class="form-input" id="mStaffPass" placeholder="Mínimo 6 caracteres" maxlength="100">
                    <div class="form-error" id="mStaffPassErr"></div>
                </div>
                <!-- Solo superadmin -->
                <div class="form-row" style="margin:0;grid-column:1/-1;display:none" id="mStaffDuenoRow">
                    <label class="form-label">Dueño <span>*</span></label>
                    <select class="form-select" id="mStaffDueno">
                        <option value="">Seleccioná dueño…</option>
                    </select>
                    <div class="form-error" id="mStaffDuenoErr"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display:flex;gap:10px;justify-content:flex-end;padding:16px 20px;border-top:1px solid var(--border)">
            <button class="btn" style="background:var(--s1);border:1px solid var(--border)" onclick="closeModal('modalStaff')">Cancelar</button>
            <button class="btn btn-primary" onclick="submitStaff()"><i class="fas fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════
     MODAL: CREAR DUEÑO (superadmin)
══════════════════════════════ -->
<div class="modal-overlay" id="modalDueno">
    <div class="modal" style="max-width:500px">
        <div class="modal-head">
            <div class="modal-head-icon g"><i class="fas fa-plus"></i></div>
            <div>
                <h3 id="mDuenoTitle">Nuevo dueño</h3>
                <p>Registrá un nuevo cliente en el sistema</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalDueno')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:20px;max-height:65vh;overflow-y:auto">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Nombre <span>*</span></label>
                    <input type="text" class="form-input" id="mDuenoNombre" placeholder="Ej: María" maxlength="80">
                    <div class="form-error" id="mDuenoNombreErr"></div>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Apellido <span>*</span></label>
                    <input type="text" class="form-input" id="mDuenoApellido" placeholder="Ej: García" maxlength="80">
                    <div class="form-error" id="mDuenoApellidoErr"></div>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">DNI</label>
                    <input type="text" class="form-input" id="mDuenoDni" placeholder="Ej: 25000000" maxlength="15">
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Teléfono</label>
                    <input type="text" class="form-input" id="mDuenoTel" placeholder="Ej: 11 3333-4444" maxlength="30">
                </div>
                <div class="form-row" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Email <span>*</span></label>
                    <input type="email" class="form-input" id="mDuenoEmail" placeholder="cliente@ejemplo.com" maxlength="150">
                    <div class="form-error" id="mDuenoEmailErr"></div>
                </div>
                <div class="form-row" style="margin:0;grid-column:1/-1">
                    <label class="form-label">Contraseña <span>*</span></label>
                    <input type="password" class="form-input" id="mDuenoPass" placeholder="Mínimo 6 caracteres" maxlength="100">
                    <div class="form-error" id="mDuenoPassErr"></div>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display:flex;gap:10px;justify-content:flex-end;padding:16px 20px;border-top:1px solid var(--border)">
            <button class="btn" style="background:var(--s1);border:1px solid var(--border)" onclick="closeModal('modalDueno')">Cancelar</button>
            <button class="btn btn-primary" onclick="submitDueno()"><i class="fas fa-save"></i> Guardar</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════
     MODAL: ASIGNAR CANCHAS AL STAFF
══════════════════════════════ -->
<div class="modal-overlay" id="modalAsignarCanchas">
    <div class="modal" style="max-width:500px">
        <div class="modal-head">
            <div class="modal-head-icon b"><i class="fas fa-futbol"></i></div>
            <div>
                <h3>Asignar canchas</h3>
                <p id="mAsignarSub">Seleccioná las canchas que puede gestionar</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalAsignarCanchas')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="padding:20px;max-height:60vh;overflow-y:auto">
            <input type="hidden" id="mAsignarUid">
            <div id="mAsignarLista">
                <div class="empty-state">
                    <div class="es-icon"><i class="fas fa-spinner fa-spin"></i></div>
                    <h4>Cargando canchas…</h4>
                </div>
            </div>
        </div>
        <div class="modal-footer" style="display:flex;gap:10px;justify-content:flex-end;padding:16px 20px;border-top:1px solid var(--border)">
            <button class="btn" style="background:var(--s1);border:1px solid var(--border)" onclick="closeModal('modalAsignarCanchas')">Cancelar</button>
            <button class="btn btn-primary" onclick="submitAsignarCanchas()"><i class="fas fa-save"></i> Guardar asignación</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════
     MODAL: PAGO DE RESERVA
══════════════════════════════ -->
<div class="modal-overlay" id="modalPago">
    <div class="modal" style="max-width:480px">
        <div class="modal-head">
            <div class="modal-head-icon g"><i class="fas fa-dollar-sign"></i></div>
            <div>
                <h3 id="modalPagoTitulo">Registrar pago</h3>
                <p>Historial y nuevo pago de la reserva</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalPago')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body" style="max-height:60vh;overflow-y:auto">
            <!-- Historial de pagos -->
            <div id="pagoHistorial" style="margin-bottom:20px"></div>
            <!-- Form de nuevo pago -->
            <div id="pagoFormWrap">
                <h4 style="font-size:0.85rem;color:var(--muted);margin-bottom:12px;text-transform:uppercase;letter-spacing:0.05em">Nuevo pago</h4>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Monto <span>*</span></label>
                        <input type="number" class="form-input" id="pagoMonto" min="0" step="0.01" placeholder="0.00">
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Tipo <span>*</span></label>
                        <select class="form-select" id="pagoTipo">
                            <option value="sena">Seña</option>
                            <option value="parcial">Parcial</option>
                            <option value="total">Total</option>
                        </select>
                    </div>
                </div>
                <div class="form-row">
                    <label class="form-label">Medio de pago <span>*</span></label>
                    <select class="form-select" id="pagoMedio">
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="transferencia">🏦 Transferencia</option>
                        <option value="tarjeta">💳 Tarjeta</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Observación</label>
                    <input type="text" class="form-input" id="pagoObs" placeholder="Opcional">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalPago')">Cerrar</button>
            <button class="btn btn-primary" onclick="submitPago()"><i class="fas fa-check"></i> Registrar pago</button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════
     MODAL: CIERRE DE CANCHA
══════════════════════════════ -->
<div class="modal-overlay" id="modalCierre">
    <div class="modal" style="max-width:520px">
        <div class="modal-head">
            <div class="modal-head-icon" style="background:rgba(255,149,0,0.15);color:var(--orange)">
                <i class="fas fa-ban"></i>
            </div>
            <div>
                <h3 id="mCierreTitle">Nuevo cierre</h3>
                <p style="font-size:0.8rem;color:var(--muted)">Bloqueá fechas y horarios</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalCierre')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mCierreId">

            <div class="form-row">
                <label class="form-label">Predio <span>*</span></label>
                <select class="form-select" id="mCierreComplejo" onchange="cierreOnComplejoChange()">
                    <option value="">Seleccioná predio…</option>
                </select>
            </div>

            <div class="form-row">
                <label class="form-label">Cancha (opcional — dejá vacío para cerrar todo el predio)</label>
                <select class="form-select" id="mCierreCancha">
                    <option value="">Todo el predio</option>
                </select>
            </div>

            <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Desde <span>*</span></label>
                    <input type="date" class="form-input" id="mCierreFechaDesde">
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Hasta <span>*</span></label>
                    <input type="date" class="form-input" id="mCierreFechaHasta">
                </div>
            </div>

            <div style="margin:16px 0 8px;display:flex;align-items:center;gap:8px">
                <input type="checkbox" id="mCierreParcial" onchange="cierreToggleParcial()">
                <label for="mCierreParcial" style="font-size:0.85rem;cursor:pointer">Cierre parcial (solo un rango horario)</label>
            </div>

            <div id="mCierreHorasWrap" style="display:none">
                <div class="form-grid" style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Hora desde</label>
                        <input type="time" class="form-input" id="mCierreHoraDesde">
                    </div>
                    <div class="form-row" style="margin:0">
                        <label class="form-label">Hora hasta</label>
                        <input type="time" class="form-input" id="mCierreHoraHasta">
                    </div>
                </div>
            </div>

            <div class="form-row" style="margin-top:14px">
                <label class="form-label">Motivo</label>
                <input type="text" class="form-input" id="mCierreMotivo"
                    placeholder="Ej: Feriado, mantenimiento, evento privado…">
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalCierre')">Cancelar</button>
            <button class="btn btn-primary" onclick="submitCierre()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════
     MODAL: TURNO FIJO
══════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalTurno">
    <div class="modal" style="max-width:540px">
        <div class="modal-head">
            <div class="modal-head-icon g">
                <i class="fas fa-redo-alt"></i>
            </div>
            <div>
                <h3 id="mTurnoTitle">Nuevo turno fijo</h3>
                <p>Reserva semanal recurrente</p>
            </div>
            <button class="modal-close" onclick="closeModal('modalTurno')"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="mTurnoId">

            <div class="form-row">
                <label class="form-label">Predio <span>*</span></label>
                <select class="form-select" id="mTurnoComplejo" onchange="turnoModalFiltrarCanchas()">
                    <option value="">Seleccioná predio…</option>
                </select>
            </div>
            <div class="form-row">
                <label class="form-label">Cancha <span>*</span></label>
                <select class="form-select" id="mTurnoCancha" onchange="turnoModalCargarFranjas()">
                    <option value="">Seleccioná cancha…</option>
                </select>
            </div>

            <div class="form-row">
                <label class="form-label">Día de la semana <span>*</span></label>
                <select class="form-select" id="mTurnoDia">
                    <option value="">Seleccioná día…</option>
                    <option value="1">Lunes</option><option value="2">Martes</option>
                    <option value="3">Miércoles</option><option value="4">Jueves</option>
                    <option value="5">Viernes</option><option value="6">Sábado</option>
                    <option value="7">Domingo</option>
                </select>
            </div>

            <!-- Opción A: elegir franja existente -->
            <div class="form-row" id="mTurnoFranjaRow">
                <label class="form-label">Franja horaria (o cargá horario manual abajo)</label>
                <select class="form-select" id="mTurnoFranja" onchange="turnoAutocompletarHorario()">
                    <option value="">— Horario manual —</option>
                </select>
            </div>

            <!-- Horario manual -->
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:12px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Desde <span>*</span></label>
                    <input type="time" class="form-input" id="mTurnoHoraDesde">
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Hasta <span>*</span></label>
                    <input type="time" class="form-input" id="mTurnoHoraHasta">
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Precio <span>*</span></label>
                    <input type="number" class="form-input" id="mTurnoPrecio" min="0" step="0.01" placeholder="0.00">
                </div>
            </div>

            <div class="form-row" style="margin-top:16px">
                <label class="form-label">Cliente (opcional)</label>
                <select class="form-select" id="mTurnoCliente">
                    <option value="">Sin cliente asignado</option>
                </select>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-row" style="margin:0">
                    <label class="form-label">Vigente desde <span>*</span></label>
                    <input type="date" class="form-input" id="mTurnoDesde">
                </div>
                <div class="form-row" style="margin:0">
                    <label class="form-label">Vigente hasta (vacío = indefinido)</label>
                    <input type="date" class="form-input" id="mTurnoHasta">
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" onclick="closeModal('modalTurno')">Cancelar</button>
            <button class="btn btn-primary" onclick="submitTurno()">
                <i class="fas fa-save"></i> Guardar
            </button>
        </div>
    </div>
</div>

<script>
// ══════════════════════════════════════════════
//  REPORTES
// ══════════════════════════════════════════════
const REP_API = 'api/reportes.php';
let repPeriodo = 'mes';

function repSetPeriodo(periodo) {
    repPeriodo = periodo;
    document.querySelectorAll('.rep-periodo-btn').forEach(b => {
        b.classList.toggle('active', b.dataset.periodo === periodo);
    });
    const wrap = document.getElementById('repCustomWrap');
    wrap.style.display = periodo === 'custom' ? 'flex' : 'none';
    if (periodo !== 'custom') loadReportes();
}

function repExportar(formato) {
    let url = `api/export_reportes.php?formato=${formato}&periodo=${repPeriodo}`;
    if (repPeriodo === 'custom') {
        const d = document.getElementById('repDesde').value;
        const h = document.getElementById('repHasta').value;
        if (!d || !h) { toast('Seleccioná un rango de fechas primero.', 'err'); return; }
        url += `&desde=${d}&hasta=${h}`;
    }
    if (formato === 'pdf') {
        window.open(url, 'rep_print');
    } else {
        // Excel: descarga directa
        const a = document.createElement('a');
        a.href = url;
        a.download = '';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
}

function repBuildUrl(action) {
    let url = `${REP_API}?action=${action}&periodo=${repPeriodo}`;
    if (repPeriodo === 'custom') {
        url += `&desde=${document.getElementById('repDesde').value}`;
        url += `&hasta=${document.getElementById('repHasta').value}`;
    }
    return url;
}

async function loadReportes() {
    const subtitulos = { hoy:'Hoy', semana:'Esta semana', mes:'Este mes', año:'Este año', custom:'Período personalizado' };
    document.getElementById('repSubtitulo').textContent = subtitulos[repPeriodo] || '';

    // Mostrar spinner en chart y canchas mientras cargan
    document.getElementById('repChart').innerHTML =
        '<div style="text-align:center;padding:40px;color:var(--muted)"><i class="fas fa-spinner fa-spin"></i></div>';
    document.getElementById('repCanchas').innerHTML = '';

    try {
        const [jRes, jDia, jCan] = await Promise.all([
            fetch(repBuildUrl('resumen')).then(r => r.json()),
            fetch(repBuildUrl('por_dia')).then(r => r.json()),
            fetch(repBuildUrl('por_cancha')).then(r => r.json()),
        ]);

        if (jRes.ok) renderRepResumen(jRes.data);
        if (jDia.ok) renderRepChart(jDia.data);
        if (jCan.ok) renderRepCanchas(jCan.data);
    } catch(e) {
        toast('Error al cargar reportes', 'err');
    }
}

function renderRepResumen(d) {
    const fmt = n => '$' + parseFloat(n||0).toLocaleString('es-AR', {minimumFractionDigits:0, maximumFractionDigits:0});
    document.getElementById('kRepCobrado').textContent    = fmt(d.ingresos_cobrados);
    document.getElementById('kRepSaldo').textContent      = d.saldo_pendiente > 0 ? `Saldo pendiente: ${fmt(d.saldo_pendiente)}` : '✓ Todo cobrado';
    document.getElementById('kRepReservas').textContent   = d.reservas_total || 0;
    document.getElementById('kRepConfirmadas').textContent = `${d.reservas_confirmadas||0} confirmadas · ${d.reservas_canceladas||0} canceladas`;
    document.getElementById('kRepTicket').textContent     = fmt(d.ticket_promedio);
    document.getElementById('kRepTop').textContent        = d.dia_top ? `Día más activo: ${d.dia_top}` : '';
    document.getElementById('kRepCanchaTop').textContent  = d.cancha_top || '—';
    document.getElementById('kRepDiaTop').textContent     = d.dia_top ? `Mejor día: ${d.dia_top}` : '';
}

function renderRepChart(data) {
    const wrap = document.getElementById('repChart');
    if (!data || !data.length) {
        wrap.innerHTML = '<div style="text-align:center;padding:40px;color:var(--muted)">Sin datos para el período</div>';
        return;
    }

    const maxIngresos = Math.max(...data.map(d => parseFloat(d.ingresos||0)), 1);
    const barW = 34;
    const gap  = 18;
    const W    = Math.max(data.length * (barW + gap) + gap, 400);
    const H    = 180;
    const padB = 32;

    const bars = data.map((d, i) => {
        const x        = i * (barW + gap) + gap;
        const ingresos = parseFloat(d.ingresos||0);
        const cobrado  = parseFloat(d.cobrado||0);
        const hIng     = Math.round((ingresos / maxIngresos) * (H - padB - 10));
        const hCob     = Math.round((cobrado  / maxIngresos) * (H - padB - 10));
        const yIng     = H - padB - hIng;
        const yCob     = H - padB - hCob;

        return `
            <rect x="${x}" y="${yIng}" width="${barW}" height="${hIng}" fill="rgba(76,217,100,0.25)" rx="4"/>
            <rect x="${x}" y="${yCob}" width="${barW}" height="${hCob}" fill="#4cd964" rx="4"/>
            <text x="${x + barW/2}" y="${H - 6}" text-anchor="middle" font-size="10" fill="rgba(255,255,255,0.4)">${escHtml(d.dia_nombre)}</text>
            ${ingresos > 0 ? `<title>${escHtml(d.fecha)}: $${ingresos.toLocaleString('es-AR')} · Cobrado: $${cobrado.toLocaleString('es-AR')}</title>` : ''}
        `;
    }).join('');

    wrap.innerHTML = `
        <div style="display:flex;gap:16px;margin-bottom:12px;font-size:0.75rem;color:var(--muted)">
            <span><span style="display:inline-block;width:12px;height:12px;background:#4cd964;border-radius:3px;margin-right:5px;vertical-align:middle"></span>Cobrado</span>
            <span><span style="display:inline-block;width:12px;height:12px;background:rgba(76,217,100,0.25);border-radius:3px;margin-right:5px;vertical-align:middle"></span>Total reservado</span>
        </div>
        <div style="overflow-x:auto">
            <svg viewBox="0 0 ${W} ${H}" width="${W}" height="${H}" xmlns="http://www.w3.org/2000/svg">
                ${bars}
            </svg>
        </div>
    `;
}

function renderRepCanchas(data) {
    const cont = document.getElementById('repCanchas');
    if (!data || !data.length) {
        cont.innerHTML = '<div class="empty-state"><p>Sin datos</p></div>';
        return;
    }
    const maxIngresos = Math.max(...data.map(d => parseFloat(d.ingresos||0)), 1);
    cont.innerHTML = data.map((c, i) => {
        const pct = Math.round((parseFloat(c.ingresos||0) / maxIngresos) * 100);
        return `<div class="rep-cancha-row">
            <div class="rc-rank">${i+1}</div>
            <div class="rc-info">
                <div class="rc-nombre"><i class="fas ${escHtml(c.TIPO_CANCHA_ICONO||'fa-futbol')}" style="color:var(--green);margin-right:5px"></i>${escHtml(c.CANCHA_NOMBRE)}</div>
                <div class="rc-predio">${escHtml(c.COMPLEJO_NOMBRE)}</div>
            </div>
            <div class="rc-bar-wrap"><div class="rc-bar" style="width:${pct}%"></div></div>
            <div class="rc-vals">
                <div class="rc-ingresos">$${parseFloat(c.ingresos||0).toLocaleString('es-AR',{minimumFractionDigits:0})}</div>
                <div class="rc-res">${c.reservas} reservas · ${c.ocupacion_pct}% ocup.</div>
            </div>
        </div>`;
    }).join('');
}

// Inicializar botones de período al cargar
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.rep-periodo-btn').forEach(btn => {
        btn.addEventListener('click', () => repSetPeriodo(btn.dataset.periodo));
    });
    const hoy    = new Date().toISOString().split('T')[0];
    const mesIni = hoy.substring(0, 8) + '01';
    const repD   = document.getElementById('repDesde');
    const repH   = document.getElementById('repHasta');
    if (repD) repD.value = mesIni;
    if (repH) repH.value = hoy;
});

// ══════════════════════════════════════════════
//  CLIENTES / RAVIOLES  (SuperAdmin)
// ══════════════════════════════════════════════
let _clientes = [];

async function loadClientes() {
    const r = await fetch('api/usuarios.php?action=listar_duenos');
    const j = await r.json();
    if (!j.ok) { toast(j.msg || 'Error cargando clientes', 'err'); return; }
    _clientes = j.data || [];
    renderClientes(_clientes);
}

function filtrarClientes(q) {
    q = q.toLowerCase().trim();
    renderClientes(!q ? _clientes : _clientes.filter(c =>
        ((c.USUARIOS_NOMBRE||'') + ' ' + (c.USUARIOS_APELLIDO||'')).toLowerCase().includes(q) ||
        (c.USUARIOS_EMAIL||'').toLowerCase().includes(q) ||
        (c.USUARIOS_TELEFONO||'').includes(q)
    ));
}

function renderClientes(list) {
    const g = document.getElementById('clientesGrid');
    if (!g) return;
    if (!list.length) {
        g.innerHTML = '<div class="empty-state" style="grid-column:1/-1"><div class="es-icon"><i class="fas fa-users-slash"></i></div><h4>Sin resultados</h4></div>';
        return;
    }
    g.innerHTML = list.map(raviolCardHtml).join('');
}

function raviolCardHtml(c) {
    const nombre   = escHtml(((c.USUARIOS_NOMBRE||'') + ' ' + (c.USUARIOS_APELLIDO||'')).trim());
    const ini1     = (c.USUARIOS_NOMBRE || ' ')[0] || '?';
    const ini2     = (c.USUARIOS_APELLIDO || ' ')[0] || '';
    const iniciales = (ini1 + ini2).toUpperCase();
    const paleta   = [
        ['rgba(76,217,100,0.18)','#4cd964'],
        ['rgba(52,152,219,0.18)','#3498db'],
        ['rgba(255,149,0,0.18)','#ff9500'],
        ['rgba(155,89,182,0.18)','#9b59b6'],
    ];
    const col    = paleta[((c.USUARIOS_ID || 0) % paleta.length)];
    const activo = c.ACTIVO == 1;
    const badge  = activo
        ? '<span class="raviol-status-badge rs-activo">Activo</span>'
        : '<span class="raviol-status-badge rs-inactivo">Inactivo</span>';

    return `<div class="raviol-card${activo ? '' : ' inactivo'}">
        <div class="raviol-top">
            <div class="raviol-avatar" style="background:${col[0]};color:${col[1]}">${iniciales}</div>
            <div class="raviol-info">
                <div class="raviol-nombre">${nombre}</div>
                <div class="raviol-email">${escHtml(c.USUARIOS_EMAIL||'')}</div>
            </div>
            ${badge}
        </div>
        <div class="raviol-stats">
            <div class="raviol-stat"><strong>${c.TOTAL_PREDIOS||0}</strong><span>predios</span></div>
            <div class="raviol-sep"></div>
            <div class="raviol-stat"><strong>${c.TOTAL_CANCHAS||0}</strong><span>canchas</span></div>
            <div class="raviol-sep"></div>
            <div class="raviol-stat"><strong>${c.RESERVAS_HOY||0}</strong><span>hoy</span></div>
            <div class="raviol-sep"></div>
            <div class="raviol-stat"><strong>${c.TOTAL_STAFF||0}</strong><span>staff</span></div>
        </div>
        <div class="raviol-actions">
            <button class="btn-gestionar" onclick="gestionarDueno(${c.USUARIOS_ID},'${nombre.replace(/'/g,"\\'")}')"
                    ${activo ? '' : 'disabled'}>
                <i class="fas fa-arrow-right"></i> Gestionar
            </button>
            <button class="btn-raviol-more" onclick="raviolToggle(${c.USUARIOS_ID},${c.ACTIVO})" title="${activo ? 'Desactivar' : 'Activar'}">
                ${activo ? '<i class="fas fa-toggle-on" style="color:var(--green)"></i>' : '<i class="fas fa-toggle-off"></i>'}
            </button>
        </div>
    </div>`;
}

async function gestionarDueno(duenoId, nombre) {
    const fd = new FormData();
    fd.append('action', 'set');
    fd.append('dueno_id', duenoId);
    const r = await fetch('api/admin_context.php', { method:'POST', body:fd });
    const j = await r.json();
    if (!j.ok) { toast(j.msg || 'Error', 'err'); return; }
    toast('Entrando al panel de ' + nombre + '…', 'ok');
    setTimeout(() => window.location.reload(), 700);
}

async function salirContextoAdmin() {
    const fd = new FormData();
    fd.append('action', 'clear');
    await fetch('api/admin_context.php', { method:'POST', body:fd });
    window.location.reload();
}

async function raviolToggle(id, activo) {
    if (!confirm(`¿${activo ? 'Desactivar' : 'Activar'} este cliente?`)) return;
    const fd = new FormData();
    fd.append('action', 'toggle');
    fd.append('id', id);
    const r = await fetch('api/usuarios.php', { method:'POST', body:fd });
    const j = await r.json();
    if (j.ok) { toast(j.msg, 'ok'); loadClientes(); }
    else toast(j.msg, 'err');
}

// ══════════════════════════════════════════════
//  MI PERFIL
// ══════════════════════════════════════════════
async function loadPerfil() {
    const r = await fetch('api/perfil.php?action=get');
    const j = await r.json();
    if (!j.ok) { toast(j.msg || 'Error', 'err'); return; }
    const d = j.data;
    document.getElementById('perfilNombre').value   = d.USUARIOS_NOMBRE   || '';
    document.getElementById('perfilApellido').value = d.USUARIOS_APELLIDO || '';
    document.getElementById('perfilEmail').value    = d.USUARIOS_EMAIL    || '';
    document.getElementById('perfilTel').value      = d.USUARIOS_TELEFONO || '';
    document.getElementById('perfilPass').value  = '';
    document.getElementById('perfilPass2').value = '';
}

async function submitPerfil(e) {
    e.preventDefault();
    const btn = document.getElementById('btnPerfilSubmit');
    const pass  = document.getElementById('perfilPass').value;
    const pass2 = document.getElementById('perfilPass2').value;
    if (pass && pass !== pass2) { toast('Las contraseñas no coinciden', 'err'); return; }

    btn.disabled = true;
    const fd = new FormData(e.target);
    fd.append('action', 'update');
    const r = await fetch('api/perfil.php', { method:'POST', body:fd });
    const j = await r.json();
    btn.disabled = false;
    if (j.ok) {
        toast('Perfil actualizado correctamente', 'ok');
        document.getElementById('perfilPass').value  = '';
        document.getElementById('perfilPass2').value = '';
    } else {
        toast(j.msg, 'err');
    }
}

// ── Confirmar desde el Dashboard (recarga la página para actualizar KPIs) ──
async function dashConfirmar(id, btn) {
    if (!confirm('¿Confirmar esta reserva?')) return;
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    const fd = new FormData();
    fd.append('action','confirmar'); fd.append('reserva_id', id);
    const j = await (await fetch('api/reservas.php',{method:'POST',body:fd})).json();
    if (j.ok) {
        toast('Reserva confirmada','ok');
        btn.closest('div[style]').querySelector('[style*="orange"]') &&
            (btn.closest('div[style]').querySelector('[style*="orange"]').style.color = 'var(--green)');
        btn.closest('div[style]').querySelector('span[style*="orange"]') &&
            (btn.closest('div[style]').querySelector('span[style*="orange"]').textContent = 'confirmada');
        btn.remove();
    } else {
        toast(j.msg,'err');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check"></i> Confirmar';
    }
}

// ══════════════════════════════════════════════
//  RESERVAS
// ══════════════════════════════════════════════
const RES_API = 'api/reservas.php';
let _resEstado = '';

function resSetEstado(btn, estado) {
    document.querySelectorAll('[data-resf]').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    _resEstado = estado;
    loadReservas();
}

async function resCargarCanchas() {
    const sel = document.getElementById('resFiltroCancha');
    if (sel.options.length > 1) return; // ya cargado
    try {
        const r = await fetch('api/canchas.php?action=listar');
        const j = await r.json();
        if (!j.ok) return;
        (j.data || []).forEach(c => {
            const o = document.createElement('option');
            o.value = c.CANCHA_ID;
            o.textContent = c.CANCHA_NOMBRE + ' — ' + c.COMPLEJO_NOMBRE;
            sel.appendChild(o);
        });
    } catch(e) {}
}

async function loadReservas() {
    const fecha   = document.getElementById('resFecha').value;
    const cancha  = document.getElementById('resFiltroCancha').value;
    const lista   = document.getElementById('resLista');
    const label   = document.getElementById('resFechaLabel');
    const counter = document.getElementById('resContador');

    lista.innerHTML = '<div style="text-align:center;padding:40px;color:var(--muted)"><i class="fas fa-spinner fa-spin"></i></div>';

    // Actualizar label de fecha
    const opcFecha = { weekday:'long', day:'numeric', month:'long' };
    label.textContent = fecha === new Date().toISOString().split('T')[0]
        ? 'Hoy · ' + new Date(fecha+'T12:00:00').toLocaleDateString('es-AR', opcFecha)
        : new Date(fecha+'T12:00:00').toLocaleDateString('es-AR', opcFecha);

    let url = `${RES_API}?action=listar&fecha=${fecha}`;
    if (cancha)    url += `&cancha_id=${cancha}`;
    if (_resEstado) url += `&estado=${_resEstado}`;

    const r = await fetch(url);
    const j = await r.json();
    if (!j.ok) { lista.innerHTML = `<div class="empty-state"><p>${escHtml(j.msg)}</p></div>`; return; }

    const rows = j.data || [];
    counter.textContent = rows.length ? `${rows.length} resultado${rows.length!==1?'s':''}` : '';

    if (!rows.length) {
        lista.innerHTML = `<div class="empty-state">
            <div class="es-icon"><i class="fas fa-calendar-times"></i></div>
            <h4>Sin reservas</h4>
            <p>No hay reservas para los filtros seleccionados.</p>
        </div>`;
        return;
    }

    lista.innerHTML = rows.map(resCardHtml).join('');
}

function resCardHtml(r) {
    const estadoCol = { pendiente:'var(--orange)', confirmada:'var(--green)', cancelada:'var(--muted)' };
    const estadoIco = { pendiente:'fa-clock', confirmada:'fa-check-circle', cancelada:'fa-times-circle' };
    const col  = estadoCol[r.RESERVA_ESTADO] || 'var(--muted)';
    const ico  = estadoIco[r.RESERVA_ESTADO] || 'fa-circle';
    const saldo = parseFloat(r.SALDO_PENDIENTE || 0);
    const pagado = parseFloat(r.PAGADO_TOTAL || 0);
    const precio = parseFloat(r.RESERVA_PRECIO || 0);
    const fmtPeso = n => '$' + parseFloat(n).toLocaleString('es-AR', {minimumFractionDigits:0});

    const acciones = r.RESERVA_ESTADO === 'pendiente' ? `
        <button class="btn btn-ghost btn-sm" style="color:var(--green);border-color:rgba(76,217,100,.3)"
                onclick="resConfirmar(${r.RESERVA_ID})">
            <i class="fas fa-check"></i> Confirmar
        </button>
        <button class="btn btn-ghost btn-sm" style="color:var(--red);border-color:rgba(231,76,60,.3)"
                onclick="resRechazar(${r.RESERVA_ID})">
            <i class="fas fa-times"></i> Rechazar
        </button>` : '';

    const pagoBtn = r.RESERVA_ESTADO !== 'cancelada' && saldo > 0 ? `
        <button class="btn btn-ghost btn-sm" style="color:var(--blue);border-color:rgba(52,152,219,.3)"
                onclick="abrirModalPago(${r.RESERVA_ID},'${escHtml(r.USUARIOS_NOMBRE+' '+r.USUARIOS_APELLIDO)}',${saldo},${precio})">
            <i class="fas fa-dollar-sign"></i> Cobrar ${fmtPeso(saldo)}
        </button>` : '';

    return `<div class="res-card" style="background:var(--s1);border:1px solid var(--b1);border-radius:12px;padding:14px 16px;margin-bottom:10px;display:flex;gap:14px;align-items:flex-start;flex-wrap:wrap">
        <!-- Hora -->
        <div style="min-width:70px;text-align:center;padding-top:2px">
            <div style="font-size:1.1rem;font-weight:800;line-height:1">${escHtml(r.RESERVA_HORA_INICIO?.slice(0,5)||'')}</div>
            <div style="font-size:0.72rem;color:var(--muted)">${escHtml(r.RESERVA_HORA_FIN?.slice(0,5)||'')}</div>
        </div>
        <!-- Info -->
        <div style="flex:1;min-width:160px">
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                <i class="fas ${escHtml(r.TIPO_CANCHA_ICONO||'fa-futbol')}" style="color:var(--green);font-size:12px"></i>
                <span style="font-weight:700;font-size:0.9rem">${escHtml(r.CANCHA_NOMBRE)}</span>
                <span style="font-size:0.72rem;color:var(--muted)">${escHtml(r.COMPLEJO_NOMBRE)}</span>
            </div>
            <div style="font-size:0.83rem;color:var(--muted)">
                <i class="fas fa-user" style="font-size:10px;margin-right:4px"></i>
                ${escHtml(r.USUARIOS_NOMBRE+' '+r.USUARIOS_APELLIDO)}
                ${r.USUARIOS_TELEFONO ? `· <i class="fas fa-phone" style="font-size:10px"></i> ${escHtml(r.USUARIOS_TELEFONO)}` : ''}
            </div>
        </div>
        <!-- Estado + precio -->
        <div style="text-align:right;min-width:120px">
            <div style="display:inline-flex;align-items:center;gap:5px;padding:3px 9px;border-radius:20px;background:${col}18;color:${col};font-size:0.72rem;font-weight:700;margin-bottom:6px">
                <i class="fas ${ico}" style="font-size:10px"></i>
                ${escHtml(r.RESERVA_ESTADO)}
            </div>
            <div style="font-weight:800;font-size:0.95rem">${fmtPeso(precio)}</div>
            ${pagado > 0 ? `<div style="font-size:0.72rem;color:var(--green)">Pagado: ${fmtPeso(pagado)}</div>` : ''}
            ${saldo > 0 && r.RESERVA_ESTADO!=='cancelada' ? `<div style="font-size:0.72rem;color:var(--orange)">Debe: ${fmtPeso(saldo)}</div>` : ''}
        </div>
        <!-- Acciones -->
        ${acciones || pagoBtn ? `<div style="width:100%;display:flex;gap:8px;flex-wrap:wrap;padding-top:10px;border-top:1px solid var(--b1)">${acciones}${pagoBtn}</div>` : ''}
    </div>`;
}

function notifWspCliente(d) {
    // Normalizar teléfono: sacar todo lo que no sea dígito y agregar 54 si no arranca con él
    let tel = (d.cliente_tel || '').replace(/\D/g,'');
    if (!tel) return;
    if (!tel.startsWith('54')) tel = '54' + tel;

    const fecha = new Date(d.fecha + 'T12:00:00').toLocaleDateString('es-AR',
        { weekday:'long', day:'numeric', month:'long' });
    const msg = `¡Hola ${d.cliente_nombre}! ✅ Tu reserva en *${d.complejo}* fue *confirmada*.\n\n` +
        `📅 ${fecha}\n` +
        `⏰ ${d.hora_ini} – ${d.hora_fin}\n` +
        `🏟️ ${d.cancha}\n` +
        `💰 $${parseFloat(d.precio).toLocaleString('es-AR')}\n\n` +
        `¡Te esperamos! 🎉`;

    window.open('https://wa.me/' + tel + '?text=' + encodeURIComponent(msg), '_blank');
}

async function resConfirmar(id) {
    if (!confirm('¿Confirmar esta reserva?')) return;
    const fd = new FormData();
    fd.append('action','confirmar'); fd.append('reserva_id',id);
    const j = await (await fetch(RES_API,{method:'POST',body:fd})).json();
    if (j.ok) {
        toast('Reserva confirmada','ok');
        loadReservas();
        actualizarBadgePendientes();
        if (j.data?.cliente_tel) notifWspCliente(j.data);
    } else toast(j.msg,'err');
}

async function resRechazar(id) {
    if (!confirm('¿Rechazar esta reserva?')) return;
    const fd = new FormData();
    fd.append('action','rechazar'); fd.append('reserva_id',id);
    const j = await (await fetch(RES_API,{method:'POST',body:fd})).json();
    if (j.ok) { toast('Reserva rechazada','ok'); loadReservas(); actualizarBadgePendientes(); }
    else toast(j.msg,'err');
}

function resAbrirNueva() {
    // Preseleccionar la cancha del filtro si hay una activa
    const canchaFiltro = document.getElementById('resFiltroCancha')?.value || '';
    abrirModalReserva(canchaFiltro || null);
}

// ══════════════════════════════════════════════
//  PAGOS / COBROS
// ══════════════════════════════════════════════
async function loadPagosView() {
    const fecha = document.getElementById('pagosFecha').value;
    const lista = document.getElementById('pagosLista');
    const label = document.getElementById('pagosFechaLabel');

    const opcFecha = { weekday:'long', day:'numeric', month:'long' };
    label.textContent = new Date(fecha+'T12:00:00').toLocaleDateString('es-AR', opcFecha);

    lista.innerHTML = '<div style="text-align:center;padding:40px;color:var(--muted)"><i class="fas fa-spinner fa-spin"></i></div>';

    // Cargamos reservas del día y mostramos las que tienen movimiento de pago o saldo pendiente
    const r = await fetch(`${RES_API}?action=listar&fecha=${fecha}`);
    const j = await r.json();
    if (!j.ok) { lista.innerHTML = `<div class="empty-state"><p>${escHtml(j.msg)}</p></div>`; return; }

    const rows = (j.data || []).filter(r => r.RESERVA_ESTADO !== 'cancelada');

    const fmtPeso = n => '$' + parseFloat(n||0).toLocaleString('es-AR',{minimumFractionDigits:0});
    const totalCobrado  = rows.reduce((s,r) => s + parseFloat(r.PAGADO_TOTAL||0), 0);
    const totalPendiente= rows.reduce((s,r) => s + parseFloat(r.SALDO_PENDIENTE||0), 0);

    document.getElementById('pvCobrado').textContent      = fmtPeso(totalCobrado);
    document.getElementById('pvPendiente').textContent    = fmtPeso(totalPendiente);
    document.getElementById('pvTransacciones').textContent= rows.length;

    if (!rows.length) {
        lista.innerHTML = `<div class="empty-state">
            <div class="es-icon"><i class="fas fa-receipt"></i></div>
            <h4>Sin movimientos</h4>
            <p>No hay reservas activas para esta fecha.</p>
        </div>`;
        return;
    }

    lista.innerHTML = `<div class="card" style="padding:0">
        <table class="tbl" style="margin:0">
            <thead><tr>
                <th>Hora</th><th>Cancha</th><th>Cliente</th>
                <th>Total</th><th>Cobrado</th><th>Saldo</th><th></th>
            </tr></thead>
            <tbody>
            ${rows.map(row => {
                const saldo  = parseFloat(row.SALDO_PENDIENTE||0);
                const pagado = parseFloat(row.PAGADO_TOTAL||0);
                const precio = parseFloat(row.RESERVA_PRECIO||0);
                const saldoCol = saldo > 0 ? 'var(--orange)' : 'var(--green)';
                return `<tr>
                    <td style="font-weight:700">${escHtml(row.RESERVA_HORA_INICIO?.slice(0,5)||'')}</td>
                    <td>${escHtml(row.CANCHA_NOMBRE)}</td>
                    <td>${escHtml(row.USUARIOS_NOMBRE+' '+row.USUARIOS_APELLIDO)}</td>
                    <td>${fmtPeso(precio)}</td>
                    <td style="color:var(--green);font-weight:600">${fmtPeso(pagado)}</td>
                    <td style="color:${saldoCol};font-weight:600">${fmtPeso(saldo)}</td>
                    <td>
                        ${saldo > 0 ? `<button class="btn btn-ghost btn-sm" style="font-size:0.75rem"
                            onclick="abrirModalPago(${row.RESERVA_ID},'${escHtml(row.USUARIOS_NOMBRE+' '+row.USUARIOS_APELLIDO).replace(/'/g,"\\'")}',${saldo},${precio})">
                            <i class="fas fa-dollar-sign"></i> Cobrar
                        </button>` : '<span style="color:var(--green);font-size:0.8rem"><i class="fas fa-check"></i> Saldado</span>'}
                    </td>
                </tr>`;
            }).join('')}
            </tbody>
        </table>
    </div>`;
}

// ══════════════════════════════════════════════════════════════════
//  AGENDA: MODO LISTA / GRILLA
// ══════════════════════════════════════════════════════════════════
let agendaModo = 'lista'; // 'lista' | 'grilla'

// Reemplaza el loadAgenda() original con un dispatcher
function agendaRecargar() {
    if (agendaModo === 'lista') loadAgenda();
    else                        loadAgendaGrilla();
}

function agendaSetModo(modo) {
    agendaModo = modo;
    document.getElementById('agendaBtnLista').classList.toggle('active', modo==='lista');
    document.getElementById('agendaBtnGrilla').classList.toggle('active', modo==='grilla');
    document.getElementById('agendaLista').style.display       = modo==='lista'   ? '' : 'none';
    document.getElementById('agendaKpis').style.display        = modo==='lista'   ? 'grid' : 'none';
    document.getElementById('agendaGrillaWrap').style.display  = modo==='grilla'  ? '' : 'none';
    document.getElementById('agendaFiltroEstado').style.display = modo==='lista'  ? '' : 'none';
    agendaRecargar();
}

// Cargar complejos para el filtro (se llama al entrar a la vista)
async function agendaCargarComplejos() {
    const sel = document.getElementById('agendaFiltroComplejo');
    if (sel.options.length > 1) return; // ya cargado
    try {
        const j = await fetch('api/horarios.php?action=complejos').then(r=>r.json());
        if (j.ok) j.data.forEach(c => {
            const o = new Option(c.COMPLEJO_NOMBRE, c.COMPLEJO_ID);
            sel.add(o);
        });
    } catch(e) {}
}

// ── GRILLA ────────────────────────────────────────────────────────
async function loadAgendaGrilla() {
    const fecha      = agendaFechaStr(agendaFecha);
    const complejoId = document.getElementById('agendaFiltroComplejo').value;

    const body  = document.getElementById('agendaGridBody');
    const thead = document.querySelector('#agendaGridTable thead tr');
    body.innerHTML  = '<tr><td colspan="20" style="text-align:center;padding:40px;color:var(--muted)"><i class="fas fa-spinner fa-spin"></i> Cargando grilla…</td></tr>';
    thead.innerHTML = '<th><div class="grid-time" style="font-size:10px;font-weight:700;color:var(--muted);text-align:center">HORA</div></th>';

    document.getElementById('agendaSubtitulo').textContent =
        new Date(fecha+'T12:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});

    try {
        const url = `api/reservas.php?action=agenda_grid&fecha=${fecha}${complejoId?'&complejo_id='+complejoId:''}`;
        const j   = await fetch(url).then(r=>r.json());
        if (!j.ok) { body.innerHTML = `<tr><td colspan="20" style="padding:30px;color:var(--red);text-align:center">${escHtml(j.msg)}</td></tr>`; return; }
        renderAgendaGrilla(j.data, fecha);
    } catch(e) {
        body.innerHTML = '<tr><td colspan="20" style="padding:30px;color:var(--red);text-align:center">Error de conexión.</td></tr>';
    }
}

function renderAgendaGrilla({ canchas, franjas, reservas, cierres }, fecha) {
    const thead = document.querySelector('#agendaGridTable thead tr');
    const body  = document.getElementById('agendaGridBody');

    if (!canchas.length) {
        thead.innerHTML = '<th><div class="grid-time">HORA</div></th>';
        body.innerHTML  = '<tr><td style="padding:60px;text-align:center;color:var(--muted)">Sin canchas configuradas para este día.</td></tr>';
        return;
    }

    // ── Indexar por clave para acceso rápido ──
    // reservas por FRANJA_ID+CANCHA_ID
    const resIdx = {};
    reservas.forEach(r => {
        const key = `${r.CANCHA_ID}_${r.FRANJA_ID}`;
        resIdx[key] = r;
    });

    // franjas por CANCHA_ID
    const franjasByCan = {};
    franjas.forEach(f => {
        const cid = parseInt(f.CANCHA_ID);
        if (!franjasByCan[cid]) franjasByCan[cid] = [];
        franjasByCan[cid].push(f);
    });

    // cierres: mapear { cancha_id: [{desde,hasta}], complejo_id: [...] }
    const cierresByCan = {};   // cancha_id → [{desde,hasta}]
    const cierresByCo  = {};   // complejo_id → [{desde,hasta}]
    cierres.forEach(c => {
        const entry = { desde: c.CIERRE_HORA_DESDE, hasta: c.CIERRE_HORA_HASTA, motivo: c.CIERRE_MOTIVO };
        if (c.CANCHA_ID) {
            const cid = parseInt(c.CANCHA_ID);
            if (!cierresByCan[cid]) cierresByCan[cid] = [];
            cierresByCan[cid].push(entry);
        } else {
            const cod = parseInt(c.COMPLEJO_ID);
            if (!cierresByCo[cod]) cierresByCo[cod] = [];
            cierresByCo[cod].push(entry);
        }
    });

    function isCierre(cancha, horaIni, horaFin) {
        const cid = parseInt(cancha.CANCHA_ID);
        const cod = parseInt(cancha.COMPLEJO_ID);
        const check = (list) => list && list.some(c => {
            if (!c.desde) return true; // cierre todo el día
            return c.desde < horaFin && c.hasta > horaIni;
        });
        return check(cierresByCan[cid]) || check(cierresByCo[cod]);
    }

    // ── Construir eje de tiempo: union de todos los slots ──
    const tiemposSet = {};
    franjas.forEach(f => {
        const key = `${f.FRANJA_HORA_INICIO}|${f.FRANJA_HORA_FIN}`;
        tiemposSet[key] = { ini: f.FRANJA_HORA_INICIO, fin: f.FRANJA_HORA_FIN };
    });
    const tiempos = Object.values(tiemposSet).sort((a,b) => a.ini.localeCompare(b.ini));

    if (!tiempos.length) {
        body.innerHTML = '<tr><td colspan="20" style="padding:40px;text-align:center;color:var(--muted)">Sin franjas horarias configuradas para este día.</td></tr>';
        return;
    }

    // ── Cabecera ──
    thead.innerHTML = '<th><div class="grid-time" style="font-size:10px;font-weight:700;color:var(--muted);text-align:center">HORA</div></th>' +
        canchas.map(c => `
            <th>
                <div style="display:flex;align-items:center;gap:6px;padding:0 4px">
                    <i class="fas ${escHtml(c.TIPO_CANCHA_ICONO||'fa-futbol')}" style="color:var(--blue);font-size:12px;flex-shrink:0"></i>
                    <div>
                        <div class="cancha-head-name">${escHtml(c.CANCHA_NOMBRE)}</div>
                        <div class="cancha-head-sub">${escHtml(c.COMPLEJO_NOMBRE)}</div>
                    </div>
                </div>
            </th>
        `).join('');

    // ── Filas ──
    const fmt = n => '$'+parseFloat(n||0).toLocaleString('es-AR',{minimumFractionDigits:0});

    body.innerHTML = tiempos.map(t => {
        const horaLabel = `${t.ini.slice(0,5)}<br><span style="font-size:9px;opacity:.5">${t.fin.slice(0,5)}</span>`;

        const celdas = canchas.map(cancha => {
            const cid  = parseInt(cancha.CANCHA_ID);
            const cfrs = franjasByCan[cid] || [];
            const fr   = cfrs.find(f => f.FRANJA_HORA_INICIO === t.ini && f.FRANJA_HORA_FIN === t.fin);

            // Sin franja en esta cancha a esta hora
            if (!fr) return `<td><div class="grid-slot sin-franja"></div></td>`;

            const key = `${cid}_${fr.FRANJA_ID}`;
            const res = resIdx[key];
            const cerrado = isCierre(cancha, t.ini, t.fin);

            if (cerrado) {
                return `<td><div class="grid-slot cierre">
                    <span class="slot-badge cierre"><i class="fas fa-ban"></i> Cerrado</span>
                </div></td>`;
            }

            if (res) {
                const nombre  = `${escHtml(res.USUARIOS_NOMBRE)} ${escHtml(res.USUARIOS_APELLIDO)}`;
                const tel     = escHtml(res.USUARIOS_TELEFONO || '');
                const pagado  = parseFloat(res.PAGADO_TOTAL || 0);
                const saldo   = parseFloat(res.RESERVA_PRECIO) - pagado;
                const tieneSena = pagado > 0 && saldo > 0;
                const saldado   = saldo <= 0;
                const estado  = res.RESERVA_ESTADO;
                return `<td onclick="abrirModalPago(${res.RESERVA_ID},'${nombre.replace(/'/g,"\\'")}',${saldo},${res.RESERVA_PRECIO})"
                            title="Click para gestionar pago">
                    <div class="grid-slot ${escHtml(estado)}">
                        <div class="slot-nombre" title="${nombre}">${nombre}</div>
                        ${tel ? `<div class="slot-tel"><i class="fas fa-phone" style="opacity:.5;font-size:9px;margin-right:3px"></i>${tel}</div>` : ''}
                        <div class="slot-badges">
                            <span class="slot-badge ${estado==='confirmada'?'conf':'pendiente'}">${estado==='confirmada'?'Confirmada':'Pendiente'}</span>
                            ${tieneSena  ? `<span class="slot-badge sena"><i class="fas fa-hand-holding-usd"></i> Seña ${fmt(pagado)}</span>` : ''}
                            ${saldado    ? `<span class="slot-badge conf"><i class="fas fa-check"></i> Saldado</span>` : ''}
                            ${!saldado && !tieneSena ? `<span class="slot-badge saldo">Debe ${fmt(saldo)}</span>` : ''}
                        </div>
                    </div>
                </td>`;
            }

            // Libre
            const precio = parseFloat(fr.FRANJA_PRECIO || 0);
            const sena   = parseFloat(fr.FRANJA_SENA   || 0);
            return `<td onclick="abrirModalReserva(${cid},'${fecha}')" title="Reservar este turno">
                <div class="grid-slot libre">
                    <div class="slot-libre-plus">+</div>
                    <div class="slot-precio">${fmt(precio)}</div>
                    ${sena > 0 ? `<div style="font-size:10px;color:rgba(76,217,100,.4)">seña ${fmt(sena)}</div>` : ''}
                </div>
            </td>`;
        }).join('');

        return `<tr>
            <td><div class="grid-time">${horaLabel}</div></td>
            ${celdas}
        </tr>`;
    }).join('');
}

// ══════════════════════════════════════════════════════════════════
//  MODAL: NUEVA RESERVA
// ══════════════════════════════════════════════════════════════════
const NR = { franjaId: null, canchaId: null, clienteId: null, precio: 0, sena: 0, buscaTmr: null };

async function abrirModalReserva(canchaIdPresel, fechaPresel) {
    NR.franjaId = null; NR.canchaId = null; NR.clienteId = null;
    NR.precio = 0; NR.sena = 0;

    document.getElementById('nrPasoB').style.display = 'none';
    document.getElementById('nrPasoC').style.display = 'none';
    document.getElementById('nrBtnConfirmar').style.display = 'none';
    document.getElementById('nrClienteBusca').value = '';
    document.getElementById('nrClienteDrop').style.display = 'none';
    document.getElementById('nrClienteSelWrap').style.display = 'none';
    document.getElementById('nrObs').value = '';
    // Resetear tab al estado inicial (buscar)
    nrSetTab('buscar');
    ['nrNewNombre','nrNewApellido','nrNewTel','nrNewDni','nrNewEmail']
        .forEach(id => { const el = document.getElementById(id); if(el) el.value=''; });

    // Si venimos de la grilla, prellenar la fecha de la agenda
    if (fechaPresel) document.getElementById('nrFecha').value = fechaPresel;
    else if (agendaModo === 'grilla') document.getElementById('nrFecha').value = agendaFechaStr(agendaFecha);

    // Poblar select de canchas
    const sel = document.getElementById('nrCancha');
    sel.innerHTML = '<option value="">Cargando…</option>';
    try {
        const j = await fetch('api/horarios.php?action=complejos').then(r=>r.json());
        // En realidad necesitamos canchas — usamos canchas.php?action=listar
        const jc = await fetch('api/canchas.php?action=listar').then(r=>r.json());
        if (jc.ok && jc.data.length) {
            sel.innerHTML = '<option value="">— Seleccioná cancha —</option>' +
                jc.data.filter(c=>c.ACTIVO=='1').map(c=>
                    `<option value="${c.CANCHA_ID}"
                             data-complejo="${escHtml(c.COMPLEJO_NOMBRE)}">
                        ${escHtml(c.CANCHA_NOMBRE)} · ${escHtml(c.COMPLEJO_NOMBRE)}
                    </option>`
                ).join('');
            if (canchaIdPresel) sel.value = canchaIdPresel;
        } else {
            sel.innerHTML = '<option value="">Sin canchas activas</option>';
        }
    } catch(e) { sel.innerHTML = '<option value="">Error al cargar</option>'; }

    openModal('modalNuevaReserva');
}

// Alias que usa resAbrirNueva()
function resAbrirNuevaModal() { abrirModalReserva(); }

function nrResetDisp() {
    NR.franjaId = null;
    document.getElementById('nrPasoB').style.display = 'none';
    document.getElementById('nrPasoC').style.display = 'none';
    document.getElementById('nrBtnConfirmar').style.display = 'none';
}

async function nrCargarDisp() {
    const canchaId = document.getElementById('nrCancha').value;
    const fecha    = document.getElementById('nrFecha').value;
    if (!canchaId) { toast('Seleccioná una cancha.','err'); return; }
    if (!fecha)    { toast('Seleccioná una fecha.','err'); return; }

    NR.canchaId = canchaId;
    const grid = document.getElementById('nrFranjasGrid');
    const label = document.getElementById('nrDispLabel');
    grid.innerHTML = '<div style="color:var(--muted);font-size:12px;padding:8px 0"><i class="fas fa-spinner fa-spin"></i> Cargando turnos…</div>';
    document.getElementById('nrPasoB').style.display = '';
    document.getElementById('nrPasoC').style.display = 'none';
    document.getElementById('nrBtnConfirmar').style.display = 'none';

    try {
        const j = await fetch(`api/reservas.php?action=disponibilidad&cancha_id=${canchaId}&fecha=${fecha}`).then(r=>r.json());
        if (!j.ok || !j.data || !j.data.length) {
            grid.innerHTML = '<div style="text-align:center;padding:20px;color:var(--muted);font-size:13px">Sin franjas configuradas para esta cancha en este día.</div>';
            label.textContent = 'Sin turnos disponibles';
            return;
        }
        const libres = j.data.filter(f=>f.disponible).length;
        label.textContent = `${libres} turno${libres!==1?'s':''} disponible${libres!==1?'s':''}`;

        grid.innerHTML = j.data.map(f => {
            const libre = f.disponible;
            const motivo = f.motivo_no_disponible || '';
            const ico = libre ? 'fa-check' : (motivo==='turno_fijo'?'fa-lock':motivo==='cierre'?'fa-ban':'fa-times');
            const lbl = libre ? 'Disponible' : (motivo==='turno_fijo'?'Turno fijo':motivo==='cierre'?'Cerrado':'Ocupado');
            return `<div onclick="${libre?`nrSelFranja(${f.FRANJA_ID},${f.FRANJA_PRECIO},${f.FRANJA_SENA},'${f.FRANJA_HORA_INICIO}','${f.FRANJA_HORA_FIN}',this)`:'void 0'}"
                style="border-radius:10px;border:1px solid ${libre?'rgba(76,217,100,.3)':'rgba(255,255,255,.07)'};
                       background:${libre?'rgba(76,217,100,.06)':'rgba(255,255,255,.02)'};
                       padding:10px;text-align:center;cursor:${libre?'pointer':'default'};
                       transition:all .15s;opacity:${libre?'1':'.45'}"
                class="nr-franja-slot${libre?' nr-libre':''}"
                data-franja="${f.FRANJA_ID}">
              <i class="fas ${ico}" style="color:${libre?'var(--green)':'var(--muted)'};font-size:13px;display:block;margin-bottom:4px"></i>
              <div style="font-weight:700;font-size:13px">${f.FRANJA_HORA_INICIO.slice(0,5)}</div>
              <div style="font-size:10px;color:var(--muted)">${f.FRANJA_HORA_FIN.slice(0,5)}</div>
              <div style="font-size:10px;margin-top:3px;color:${libre?'var(--green)':'var(--muted)'}">${libre?'$'+parseFloat(f.FRANJA_PRECIO).toLocaleString('es-AR'):lbl}</div>
            </div>`;
        }).join('');
    } catch(e) { grid.innerHTML = '<div style="color:var(--red);font-size:12px">Error al cargar disponibilidad.</div>'; }
}

function nrSelFranja(id, precio, sena, ini, fin, el) {
    NR.franjaId = id; NR.precio = precio; NR.sena = sena;
    // Highlight
    document.querySelectorAll('.nr-franja-slot.nr-libre').forEach(s => {
        s.style.border = 'none';
        s.style.border = '1px solid rgba(76,217,100,.3)';
        s.style.background = 'rgba(76,217,100,.06)';
    });
    el.style.border = '2px solid var(--green)';
    el.style.background = 'rgba(76,217,100,.15)';
    el.style.boxShadow = '0 0 12px rgba(76,217,100,.25)';

    // Mostrar paso C
    document.getElementById('nrResHorario').textContent = `${ini.slice(0,5)} – ${fin.slice(0,5)}`;
    document.getElementById('nrResPrecio').textContent  = `Precio: $${parseFloat(precio).toLocaleString('es-AR')}${sena>0?' · Seña: $'+parseFloat(sena).toLocaleString('es-AR'):''}`;
    document.getElementById('nrPasoC').style.display = '';
    document.getElementById('nrBtnConfirmar').style.display = '';
    document.getElementById('nrClienteBusca').focus();
}

function nrDeseleccionar() {
    NR.franjaId = null;
    document.getElementById('nrPasoC').style.display = 'none';
    document.getElementById('nrBtnConfirmar').style.display = 'none';
    document.querySelectorAll('.nr-franja-slot.nr-libre').forEach(s => {
        s.style.border = '1px solid rgba(76,217,100,.3)';
        s.style.background = 'rgba(76,217,100,.06)';
        s.style.boxShadow = '';
    });
}

// Búsqueda de cliente con debounce
function nrBuscarCliente(q) {
    clearTimeout(NR.buscaTmr);
    const drop = document.getElementById('nrClienteDrop');
    if (q.length < 2) { drop.style.display = 'none'; return; }
    NR.buscaTmr = setTimeout(async () => {
        try {
            const j = await fetch(`api/usuarios.php?action=buscar_clientes&q=${encodeURIComponent(q)}`).then(r=>r.json());
            if (!j.ok || !j.data.length) {
                drop.style.display = '';
                drop.innerHTML = '<div style="padding:12px 16px;font-size:12px;color:var(--muted)">Sin resultados.</div>';
                return;
            }
            drop.style.display = '';
            drop.innerHTML = j.data.map(c => `
                <div onclick='nrSelCliente(${c.USUARIOS_ID},"${escHtml(c.USUARIOS_NOMBRE)} ${escHtml(c.USUARIOS_APELLIDO)}","${escHtml(c.USUARIOS_EMAIL||c.USUARIOS_TELEFONO||'')}",false)'
                     style="padding:10px 16px;cursor:pointer;display:flex;align-items:center;gap:10px;
                            border-bottom:1px solid rgba(255,255,255,.06);transition:background .1s"
                     onmouseover="this.style.background='rgba(255,255,255,.05)'"
                     onmouseout="this.style.background='transparent'">
                  <div style="width:30px;height:30px;border-radius:50%;background:rgba(52,152,219,.2);
                              color:var(--blue);display:flex;align-items:center;justify-content:center;
                              font-weight:700;font-size:13px;flex-shrink:0">
                    ${escHtml(c.USUARIOS_NOMBRE.charAt(0).toUpperCase())}
                  </div>
                  <div>
                    <div style="font-weight:600;font-size:13px">${escHtml(c.USUARIOS_NOMBRE)} ${escHtml(c.USUARIOS_APELLIDO)}</div>
                    <div style="font-size:11px;color:var(--muted)">${c.USUARIOS_TELEFONO?'<i class="fas fa-phone" style="opacity:.5;font-size:9px"></i> '+escHtml(c.USUARIOS_TELEFONO):''} ${c.USUARIOS_EMAIL&&!c.USUARIOS_EMAIL.includes('@walkin')?' · '+escHtml(c.USUARIOS_EMAIL):''}</div>
                  </div>
                </div>
            `).join('');
        } catch(e) {}
    }, 280);
}

function nrSelCliente(id, nombre, meta, esNuevo) {
    NR.clienteId = id;
    document.getElementById('nrClienteBusca').value = '';
    document.getElementById('nrClienteDrop').style.display = 'none';
    document.getElementById('nrCliAvatar').textContent  = nombre.charAt(0).toUpperCase();
    document.getElementById('nrCliNombre').textContent  = nombre;
    document.getElementById('nrCliMeta').textContent    = meta || '';
    const badge = document.getElementById('nrCliBadge');
    badge.textContent = esNuevo ? '✦ Nuevo' : 'Existente';
    badge.style.background = esNuevo ? 'rgba(255,149,0,.15)' : 'rgba(76,217,100,.12)';
    badge.style.color      = esNuevo ? 'var(--orange)' : 'var(--green)';
    document.getElementById('nrClienteSelWrap').style.display = '';
}

// Toggle tab buscar / nuevo
function nrSetTab(tab) {
    const esBuscar = tab === 'buscar';
    document.getElementById('nrPanelBuscar').style.display = esBuscar ? '' : 'none';
    document.getElementById('nrPanelNuevo').style.display  = esBuscar ? 'none' : '';
    document.getElementById('nrTabBuscar').style.background = esBuscar ? 'rgba(76,217,100,.15)' : 'var(--s1)';
    document.getElementById('nrTabBuscar').style.color      = esBuscar ? 'var(--green)' : 'var(--muted)';
    document.getElementById('nrTabNuevo').style.background  = esBuscar ? 'var(--s1)' : 'rgba(255,149,0,.12)';
    document.getElementById('nrTabNuevo').style.color       = esBuscar ? 'var(--muted)' : 'var(--orange)';
    // Limpiar cliente seleccionado al cambiar de tab
    nrLimpiarCliente();
}

// Crear cliente rápido (walk-in)
async function nrCrearCliente() {
    const nombre   = document.getElementById('nrNewNombre').value.trim();
    const apellido = document.getElementById('nrNewApellido').value.trim();
    const tel      = document.getElementById('nrNewTel').value.trim();
    const dni      = document.getElementById('nrNewDni').value.trim();
    const email    = document.getElementById('nrNewEmail').value.trim();

    if (!nombre)   { toast('El nombre es obligatorio.','err'); return; }
    if (!apellido) { toast('El apellido es obligatorio.','err'); return; }
    if (!tel)      { toast('El teléfono es obligatorio.','err'); return; }

    const btn = document.getElementById('nrBtnCrearCli');
    btn.disabled = true; btn.textContent = 'Registrando…';

    try {
        const fd = new FormData();
        fd.append('action','crear_cliente_rapido');
        fd.append('nombre', nombre); fd.append('apellido', apellido);
        fd.append('telefono', tel);
        if (dni)   fd.append('dni',   dni);
        if (email) fd.append('email', email);

        const j = await fetch('api/usuarios.php',{method:'POST',body:fd}).then(r=>r.json());
        if (!j.ok) { toast(j.msg || 'Error al crear el cliente.','err'); return; }

        // Limpiar form
        ['nrNewNombre','nrNewApellido','nrNewTel','nrNewDni','nrNewEmail']
            .forEach(id => document.getElementById(id).value = '');

        // Seleccionar el cliente recién creado
        nrSelCliente(j.data.id, j.data.nombre, j.data.email || j.data.telefono, true);
        toast(`Cliente ${j.data.nombre} registrado.`,'ok');
    } catch(e) { toast('Error de conexión.','err'); }
    finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-user-plus"></i> Registrar y seleccionar';
    }
}

function nrLimpiarCliente() {
    NR.clienteId = null;
    document.getElementById('nrClienteSelWrap').style.display = 'none';
    document.getElementById('nrClienteBusca').value = '';
}

async function nrConfirmar() {
    if (!NR.franjaId)  { toast('Seleccioná un turno.','err'); return; }
    if (!NR.clienteId) { toast('Seleccioná un cliente.','err'); return; }

    const btn = document.getElementById('nrBtnConfirmar');
    btn.disabled = true; btn.textContent = 'Guardando…';

    try {
        const fd = new FormData();
        fd.append('action','crear_admin');
        fd.append('cancha_id', NR.canchaId);
        fd.append('franja_id', NR.franjaId);
        fd.append('fecha', document.getElementById('nrFecha').value);
        fd.append('usuario_id', NR.clienteId);
        fd.append('estado', document.getElementById('nrEstado').value);
        fd.append('observacion', document.getElementById('nrObs').value);

        const j = await fetch('api/reservas.php',{method:'POST',body:fd}).then(r=>r.json());
        if (!j.ok) { toast(j.msg || 'Error al crear la reserva.','err'); return; }

        toast('Reserva creada correctamente.','ok');
        closeModal('modalNuevaReserva');
        // Refrescar la vista activa
        const av = document.querySelector('.view.active');
        const vid = av ? av.id : '';
        if (vid === 'view-reservas') loadReservas();
        else if (vid === 'view-agenda') loadAgenda();
        else if (vid === 'view-pagos') loadPagosView();
    } catch(e) { toast('Error de conexión.','err'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-check"></i> Confirmar reserva'; }
}

// Cerrar dropdown cliente al click fuera
document.addEventListener('click', e => {
    const drop = document.getElementById('nrClienteDrop');
    if (drop && !drop.contains(e.target) && e.target.id !== 'nrClienteBusca') {
        drop.style.display = 'none';
    }
});

// ══════════════════════════════════════════════════════════════════
//  WIZARD: ALTA NUEVO CLIENTE
// ══════════════════════════════════════════════════════════════════
const WIZ = {
    step: 1,
    duenoId: null,
    duenoNombre: '',
    duenoEmail: '',
    duenoPass: '',
    complejoId: null,
    complejoNombre: '',
    canchas: [],          // [{id, nombre, tipoId, tipoNombre, icono}]
    franjas: {},          // {cancha_id: [{inicio,fin,precio,sena,dias}]}
    horTabActivo: null,   // cancha_id activo en paso 4
    selects: null,        // {localidades, tipos_comp, tipos_cancha}
};

const DIAS_NOMBRE = {1:'Dom',2:'Lun',3:'Mar',4:'Mié',5:'Jue',6:'Vie',7:'Sáb'};

async function abrirWizard() {
    // Reset
    Object.assign(WIZ, {
        step:1, duenoId:null, duenoNombre:'', duenoEmail:'', duenoPass:'',
        complejoId:null, complejoNombre:'', canchas:[], franjas:{}, horTabActivo:null,
    });
    ['wNombre','wApellido','wDni','wTelefono','wEmail','wPass','wPass2',
     'wCmpNombre','wCmpDir','wCmpTel','wCmpEmail','wCaName']
        .forEach(id => { const el = document.getElementById(id); if(el) el.value = ''; });

    wizRenderStep(1);

    // Cargar selects (localidades, tipos_complejo, tipos_cancha)
    if (!WIZ.selects) {
        try {
            const j = await fetch('api/complejos.php?action=selects').then(r=>r.json());
            if (j.ok) {
                WIZ.selects = j.data;
                wizPoblarSelects();
            }
        } catch(e) { console.error('Error cargando selects del wizard', e); }
    } else {
        wizPoblarSelects();
    }

    document.getElementById('modalWizard').classList.add('show');
}

function wizPoblarSelects() {
    const { localidades, tipos_comp, tipos_cancha } = WIZ.selects;

    const selLoc = document.getElementById('wCmpLocalidad');
    selLoc.innerHTML = '<option value="">— Seleccioná —</option>' +
        localidades.map(l => `<option value="${l.LOCALIDAD_ID}">${escHtml(l.LOCALIDAD_NOMBRE)}</option>`).join('');

    const selTipo = document.getElementById('wCmpTipo');
    selTipo.innerHTML = '<option value="">— Sin especificar —</option>' +
        tipos_comp.map(t => `<option value="${t.TIPO_COMPLEJO_ID}">${escHtml(t.TIPO_COMPLEJO_NOMBRE)}</option>`).join('');

    const selCaTipo = document.getElementById('wCaTipo');
    selCaTipo.innerHTML = tipos_cancha.map(t =>
        `<option value="${t.TIPO_CANCHA_ID}" data-icono="${escHtml(t.TIPO_CANCHA_ICONO)}">${escHtml(t.TIPO_CANCHA_NOMBRE)}</option>`
    ).join('');
}

function wizCerrar() {
    if (WIZ.duenoId && WIZ.step < 5) {
        if (!confirm('¿Cerrar el wizard? El dueño quedará creado pero inactivo (sin acceso) hasta que completes la configuración desde la lista de clientes.')) return;
    }
    document.getElementById('modalWizard').classList.remove('show');
    if (WIZ.duenoId) loadClientes();
}

function wizRenderStep(step) {
    WIZ.step = step;
    for (let i = 1; i <= 5; i++) {
        const p = document.getElementById('wizPanel' + i);
        if (p) p.style.display = (i === step) ? '' : 'none';
    }

    // Actualizar stepper (solo steps 1-4 visibles)
    for (let i = 1; i <= 4; i++) {
        const s = document.getElementById('wizS' + i);
        const l = document.getElementById('wizL' + i);
        if (!s) continue;
        s.classList.remove('active','done');
        if (i < step) s.classList.add('done');
        else if (i === step) s.classList.add('active');
        if (l) l.classList.toggle('done', i < step);
    }

    // Subtítulo
    const subs = ['','Cuenta del dueño','Datos del predio','Canchas del predio','Franjas horarias',''];
    const el = document.getElementById('wizSubtitle');
    if (el) el.textContent = step <= 4 ? `Paso ${step} de 4 — ${subs[step]}` : '¡Configuración completada!';

    // Botones footer
    const btnBack = document.getElementById('wizBtnBack');
    const btnNext = document.getElementById('wizBtnNext');
    const btnCancel = document.getElementById('wizBtnCancel');
    const footer = document.getElementById('wizFooter');

    if (step === 5) {
        if (footer) footer.style.display = 'none';
    } else {
        if (footer) footer.style.display = '';
        btnBack.style.visibility = step > 1 ? 'visible' : 'hidden';
        if (step === 4) {
            btnNext.innerHTML = 'Finalizar <i class="fas fa-check"></i>';
        } else {
            btnNext.innerHTML = 'Siguiente <i class="fas fa-arrow-right"></i>';
        }
    }
}

async function wizNext() {
    const step = WIZ.step;
    if (step === 1) await wizGuardarCuenta();
    else if (step === 2) await wizGuardarComplejo();
    else if (step === 3) wizAvanzarPaso4();
    else if (step === 4) wizFinalizar();
}

function wizBack() {
    if (WIZ.step > 1) wizRenderStep(WIZ.step - 1);
}

// ── PASO 1: Crear cuenta del dueño ───────────────────────────────
async function wizGuardarCuenta() {
    const nombre   = document.getElementById('wNombre').value.trim();
    const apellido = document.getElementById('wApellido').value.trim();
    const dni      = document.getElementById('wDni').value.trim();
    const email    = document.getElementById('wEmail').value.trim();
    const tel      = document.getElementById('wTelefono').value.trim();
    const pass     = document.getElementById('wPass').value;
    const pass2    = document.getElementById('wPass2').value;

    if (!nombre || !apellido)       { toast('Nombre y apellido son obligatorios.','err'); return; }
    if (!dni)                        { toast('El DNI es obligatorio.','err'); return; }
    if (!email)                      { toast('El email es obligatorio.','err'); return; }
    if (pass.length < 6)             { toast('La contraseña debe tener al menos 6 caracteres.','err'); return; }
    if (pass !== pass2)              { toast('Las contraseñas no coinciden.','err'); return; }

    // Si ya creamos la cuenta antes (volvimos), no recrear
    if (WIZ.duenoId) { wizRenderStep(2); return; }

    const btnNext = document.getElementById('wizBtnNext');
    btnNext.disabled = true;
    btnNext.textContent = 'Creando…';

    try {
        const fd = new FormData();
        fd.append('action','crear_dueno');
        fd.append('nombre',nombre); fd.append('apellido',apellido);
        fd.append('dni',dni); fd.append('email',email);
        fd.append('telefono',tel); fd.append('password',pass);

        const j = await fetch('api/usuarios.php',{method:'POST',body:fd}).then(r=>r.json());
        if (!j.ok) { toast(j.msg || 'Error al crear la cuenta.','err'); return; }

        WIZ.duenoId     = j.data.id;
        WIZ.duenoNombre = nombre + ' ' + apellido;
        WIZ.duenoEmail  = email;
        WIZ.duenoPass   = pass;
        toast('Cuenta creada correctamente.','ok');
        wizRenderStep(2);
    } catch(e) { toast('Error de conexión.','err'); }
    finally {
        btnNext.disabled = false;
        btnNext.innerHTML = 'Siguiente <i class="fas fa-arrow-right"></i>';
    }
}

// ── PASO 2: Crear complejo ────────────────────────────────────────
async function wizGuardarComplejo() {
    const nombre = document.getElementById('wCmpNombre').value.trim();
    const dir    = document.getElementById('wCmpDir').value.trim();
    const loc    = document.getElementById('wCmpLocalidad').value;
    const tipo   = document.getElementById('wCmpTipo').value;
    const tel    = document.getElementById('wCmpTel').value.trim();
    const email  = document.getElementById('wCmpEmail').value.trim();

    if (!nombre) { toast('El nombre del predio es obligatorio.','err'); return; }
    if (!dir)    { toast('La dirección es obligatoria.','err'); return; }
    if (!loc)    { toast('Seleccioná una localidad.','err'); return; }

    if (WIZ.complejoId) { wizRenderStep(3); return; }

    const btnNext = document.getElementById('wizBtnNext');
    btnNext.disabled = true; btnNext.textContent = 'Guardando…';

    try {
        const fd = new FormData();
        fd.append('action','crear');
        fd.append('nombre',nombre); fd.append('direccion',dir);
        fd.append('localidad_id',loc); fd.append('tipo_complejo_id',tipo);
        fd.append('telefono',tel); fd.append('email',email);
        fd.append('usuarios_id', WIZ.duenoId);
        fd.append('actividades','[]'); fd.append('horarios','[]');

        const j = await fetch('api/complejos.php',{method:'POST',body:fd}).then(r=>r.json());
        if (!j.ok) { toast(j.msg || 'Error al crear el predio.','err'); return; }

        WIZ.complejoId    = j.data.id;
        WIZ.complejoNombre = nombre;
        toast('Predio creado correctamente.','ok');
        wizRenderStep(3);
        wizRenderCanchasList();
    } catch(e) { toast('Error de conexión.','err'); }
    finally {
        btnNext.disabled = false;
        btnNext.innerHTML = 'Siguiente <i class="fas fa-arrow-right"></i>';
    }
}

// ── PASO 3: Agregar cancha ────────────────────────────────────────
async function wizAgregarCancha() {
    const nombre  = document.getElementById('wCaName').value.trim();
    const selTipo = document.getElementById('wCaTipo');
    const tipoId  = selTipo.value;
    const tipoNom = selTipo.options[selTipo.selectedIndex]?.text || '';
    const icono   = selTipo.options[selTipo.selectedIndex]?.dataset.icono || 'fa-futbol';

    if (!nombre)  { toast('Ingresá el nombre de la cancha.','err'); return; }
    if (!tipoId)  { toast('Seleccioná el tipo de cancha.','err'); return; }

    const btn = document.querySelector('#wizPanel3 button.btn-primary');
    btn.disabled = true; btn.textContent = 'Creando…';

    try {
        const fd = new FormData();
        fd.append('action','crear');
        fd.append('nombre',nombre);
        fd.append('complejo_id', WIZ.complejoId);
        fd.append('tipo_cancha_id', tipoId);
        fd.append('descripcion','');
        fd.append('encargados','[]');

        const j = await fetch('api/canchas.php',{method:'POST',body:fd}).then(r=>r.json());
        if (!j.ok) { toast(j.msg || 'Error al crear la cancha.','err'); return; }

        WIZ.canchas.push({ id: j.data.id, nombre, tipoId, tipoNombre: tipoNom, icono });
        WIZ.franjas[j.data.id] = [];
        document.getElementById('wCaName').value = '';
        wizRenderCanchasList();
        toast(`Cancha "${nombre}" agregada.`,'ok');
    } catch(e) { toast('Error de conexión.','err'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plus"></i> Agregar'; }
}

function wizRenderCanchasList() {
    const cont = document.getElementById('wizCanchasList');
    if (!WIZ.canchas.length) {
        cont.innerHTML = `<div style="text-align:center;padding:24px;color:var(--muted);font-size:13px">
            <i class="fas fa-futbol" style="font-size:24px;display:block;margin-bottom:8px;opacity:.3"></i>
            Aún no agregaste canchas
        </div>`;
        return;
    }
    cont.innerHTML = WIZ.canchas.map(c => `
        <div class="wiz-cancha-row">
            <div class="cancha-ico"><i class="fas ${escHtml(c.icono)}"></i></div>
            <div class="cancha-info">
                <div class="cancha-name">${escHtml(c.nombre)}</div>
                <div class="cancha-tipo">${escHtml(c.tipoNombre)}</div>
            </div>
            <span style="background:rgba(76,217,100,.1);color:var(--green);
                         padding:2px 8px;border-radius:12px;font-size:10px;font-weight:700">
                ID #${c.id}
            </span>
        </div>
    `).join('');
}

function wizAvanzarPaso4() {
    if (!WIZ.canchas.length) {
        // Preguntar si quiere omitir
        if (!confirm('No agregaste ninguna cancha. ¿Querés continuar igual y configurarlas después?')) return;
        wizFinalizar(); return;
    }
    wizRenderStep(4);
    wizRenderHorTabs();
}

// ── PASO 4: Franjas horarias ──────────────────────────────────────
function wizRenderHorTabs() {
    const tabs = document.getElementById('wizHorTabs');
    if (!WIZ.canchas.length) { tabs.innerHTML = ''; return; }

    tabs.innerHTML = WIZ.canchas.map(c => `
        <div class="wiz-tab${WIZ.horTabActivo===c.id?' active':''}"
             onclick="wizSelHorTab(${c.id})">${escHtml(c.nombre)}</div>
    `).join('');

    if (!WIZ.horTabActivo || !WIZ.canchas.find(c=>c.id===WIZ.horTabActivo)) {
        WIZ.horTabActivo = WIZ.canchas[0].id;
        document.querySelectorAll('.wiz-tab')[0]?.classList.add('active');
    }
    wizRenderFranjasList();
}

function wizSelHorTab(canchaId) {
    WIZ.horTabActivo = canchaId;
    document.querySelectorAll('.wiz-tab').forEach(t => t.classList.remove('active'));
    WIZ.canchas.forEach((c,i) => {
        if (c.id === canchaId) document.querySelectorAll('.wiz-tab')[i]?.classList.add('active');
    });
    // Reset form
    document.getElementById('wFrInicio').value = '08:00';
    document.getElementById('wFrFin').value    = '09:00';
    document.getElementById('wFrPrecio').value = '';
    document.getElementById('wFrSena').value   = '';
    document.querySelectorAll('.wiz-dia-btn').forEach(b=>b.classList.remove('sel'));
    wizRenderFranjasList();
}

function wizToggleDia(el) {
    el.classList.toggle('sel');
}

async function wizAgregarFranja() {
    const canchaId = WIZ.horTabActivo;
    if (!canchaId) { toast('Seleccioná una cancha.','err'); return; }

    const inicio = document.getElementById('wFrInicio').value;
    const fin    = document.getElementById('wFrFin').value;
    const precio = parseFloat(document.getElementById('wFrPrecio').value) || 0;
    const sena   = parseFloat(document.getElementById('wFrSena').value) || 0;
    const dias   = [...document.querySelectorAll('.wiz-dia-btn.sel')].map(b => parseInt(b.dataset.dia));

    if (!inicio || !fin)    { toast('Ingresá hora de inicio y fin.','err'); return; }
    if (fin <= inicio)       { toast('La hora de fin debe ser posterior al inicio.','err'); return; }
    if (precio <= 0)         { toast('El precio debe ser mayor a 0.','err'); return; }
    if (!dias.length)        { toast('Seleccioná al menos un día.','err'); return; }

    const btn = document.querySelector('#wizPanel4 button.btn-primary');
    btn.disabled = true; btn.textContent = 'Guardando…';

    try {
        const fd = new FormData();
        fd.append('action','crear');
        fd.append('cancha_id', canchaId);
        fd.append('hora_inicio', inicio); fd.append('hora_fin', fin);
        fd.append('precio', precio); fd.append('sena', sena);
        fd.append('dias', JSON.stringify(dias));

        const j = await fetch('api/horarios.php',{method:'POST',body:fd}).then(r=>r.json());
        if (!j.ok) { toast(j.msg || 'Error al crear la franja.','err'); return; }

        if (!WIZ.franjas[canchaId]) WIZ.franjas[canchaId] = [];
        WIZ.franjas[canchaId].push({ id: j.data.id, inicio, fin, precio, sena, dias });
        document.getElementById('wFrPrecio').value = '';
        document.getElementById('wFrSena').value   = '';
        document.querySelectorAll('.wiz-dia-btn').forEach(b=>b.classList.remove('sel'));
        wizRenderFranjasList();
        toast('Turno agregado.','ok');
    } catch(e) { toast('Error de conexión.','err'); }
    finally { btn.disabled = false; btn.innerHTML = '<i class="fas fa-plus"></i> Agregar turno'; }
}

function wizRenderFranjasList() {
    const cont  = document.getElementById('wizFranjasList');
    const lista = WIZ.franjas[WIZ.horTabActivo] || [];
    if (!lista.length) {
        cont.innerHTML = `<p style="color:var(--muted);font-size:12px;text-align:center;padding:12px 0">
            Sin turnos cargados para esta cancha aún.
        </p>`;
        return;
    }
    cont.innerHTML = lista.map(f => `
        <div class="wiz-franja-row">
            <span class="fr-time"><i class="fas fa-clock" style="opacity:.5;margin-right:4px"></i>${f.inicio}–${f.fin}</span>
            <span class="fr-price">$${parseFloat(f.precio).toLocaleString('es-AR')}</span>
            ${f.sena > 0 ? `<span style="color:var(--orange);font-size:11px">Seña $${parseFloat(f.sena).toLocaleString('es-AR')}</span>` : ''}
            <span class="fr-dias">${f.dias.map(d=>DIAS_NOMBRE[d]||d).join(', ')}</span>
        </div>
    `).join('');
}

// ── PASO 5: Pantalla final ────────────────────────────────────────
async function wizFinalizar() {
    // Activar cuenta del dueño (fue creada inactiva)
    if (WIZ.duenoId) {
        try {
            const fd = new FormData();
            fd.append('action','activar_dueno');
            fd.append('id', WIZ.duenoId);
            const j = await fetch('api/usuarios.php',{method:'POST',body:fd}).then(r=>r.json());
            if (!j.ok) { toast('No se pudo activar la cuenta: ' + (j.msg||'Error'),'err'); return; }
        } catch(e) { toast('Error de conexión al activar cuenta.','err'); return; }
    }

    const totalFranjas = Object.values(WIZ.franjas).reduce((s,a)=>s+a.length, 0);

    const credText = `Usuario: ${WIZ.duenoEmail}\nContraseña: ${WIZ.duenoPass}`;
    document.getElementById('wizSummary').innerHTML = `
        <div class="wiz-summary-row">
            <span class="lbl"><i class="fas fa-user" style="width:14px;opacity:.6"></i> Dueño</span>
            <span class="val">${escHtml(WIZ.duenoNombre)}</span>
        </div>
        <div class="wiz-summary-row">
            <span class="lbl"><i class="fas fa-building" style="width:14px;opacity:.6"></i> Predio</span>
            <span class="val">${escHtml(WIZ.complejoNombre || '—')}</span>
        </div>
        <div class="wiz-summary-row">
            <span class="lbl"><i class="fas fa-futbol" style="width:14px;opacity:.6"></i> Canchas</span>
            <span class="val" style="color:var(--blue)">${WIZ.canchas.length} cancha${WIZ.canchas.length!==1?'s':''}</span>
        </div>
        <div class="wiz-summary-row">
            <span class="lbl"><i class="fas fa-clock" style="width:14px;opacity:.6"></i> Turnos cargados</span>
            <span class="val" style="color:var(--green)">${totalFranjas} franja${totalFranjas!==1?'s':''}</span>
        </div>
        <div style="margin-top:14px;background:rgba(76,217,100,0.06);border:1px solid rgba(76,217,100,0.2);
                    border-radius:10px;padding:12px 14px">
            <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
                        color:var(--muted);margin-bottom:8px">
                <i class="fas fa-key" style="margin-right:5px"></i>Credenciales de acceso
            </div>
            <div style="font-size:12px;margin-bottom:4px">
                <span style="color:var(--muted)">Email:</span>
                <strong style="margin-left:6px">${escHtml(WIZ.duenoEmail)}</strong>
            </div>
            <div style="font-size:12px;margin-bottom:10px">
                <span style="color:var(--muted)">Contraseña:</span>
                <strong style="margin-left:6px">${escHtml(WIZ.duenoPass)}</strong>
            </div>
            <button onclick="navigator.clipboard.writeText(${JSON.stringify(credText)}).then(()=>toast('Credenciales copiadas','ok'))"
                    style="width:100%;padding:8px;background:rgba(76,217,100,0.12);border:1px solid rgba(76,217,100,0.3);
                           border-radius:8px;color:var(--green);font-size:12px;font-weight:700;cursor:pointer">
                <i class="fas fa-copy"></i> Copiar credenciales
            </button>
        </div>
    `;
    document.getElementById('wizDoneMsg').textContent =
        `Compartí las credenciales con ${escHtml(WIZ.duenoNombre)} para que pueda ingresar.`;

    wizRenderStep(5);
}

function wizVerCliente() {
    wizCerrar();
    // Entrar en modo soporte como el dueño creado
    if (WIZ.duenoId) gestionarDueno(WIZ.duenoId, WIZ.duenoNombre);
}
</script>

<!-- ══════════════════════════════════════════════════════════════════
     MODAL: NUEVA RESERVA (admin/staff)
══════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalNuevaReserva">
  <div class="modal modal-lg" style="max-width:560px">
    <div class="modal-head">
      <div class="modal-head-icon g"><i class="fas fa-calendar-plus"></i></div>
      <div>
        <h3>Nueva reserva</h3>
        <p id="nrSubtitulo">Seleccioná cancha, fecha y turno</p>
      </div>
      <button class="modal-close" onclick="closeModal('modalNuevaReserva')"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-body" style="max-height:72vh;overflow-y:auto">

      <!-- PASO A: Cancha + Fecha -->
      <div id="nrPasoA">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
          <div>
            <label class="form-label">Cancha <span>*</span></label>
            <select class="form-select" id="nrCancha" onchange="nrResetDisp()">
              <option value="">Seleccioná…</option>
            </select>
          </div>
          <div>
            <label class="form-label">Fecha <span>*</span></label>
            <input class="form-input" type="date" id="nrFecha" onchange="nrResetDisp()"
                   value="<?= date('Y-m-d') ?>" min="<?= date('Y-m-d') ?>">
          </div>
        </div>
        <button class="btn btn-primary" style="width:100%" onclick="nrCargarDisp()">
          <i class="fas fa-search"></i> Ver disponibilidad
        </button>
      </div>

      <!-- PASO B: Grid de franjas -->
      <div id="nrPasoB" style="display:none;margin-top:18px">
        <div style="font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;
                    color:var(--muted);margin-bottom:10px" id="nrDispLabel">Turnos disponibles</div>
        <div id="nrFranjasGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:8px"></div>
      </div>

      <!-- PASO C: Cliente + confirmación -->
      <div id="nrPasoC" style="display:none;margin-top:18px;
           padding-top:18px;border-top:1px solid var(--border)">

        <!-- Franja seleccionada (resumen) -->
        <div id="nrFranjaResumen" style="background:rgba(76,217,100,.08);border:1px solid rgba(76,217,100,.25);
             border-radius:10px;padding:12px 16px;margin-bottom:16px;
             display:flex;align-items:center;gap:12px">
          <i class="fas fa-clock" style="color:var(--green);font-size:18px"></i>
          <div>
            <div id="nrResHorario" style="font-weight:700;font-size:14px"></div>
            <div id="nrResPrecio" style="font-size:12px;color:var(--muted)"></div>
          </div>
          <button onclick="nrDeseleccionar()" style="margin-left:auto;background:none;border:none;
                  color:var(--muted);cursor:pointer;font-size:13px" title="Cambiar franja">
            <i class="fas fa-times"></i>
          </button>
        </div>

        <!-- Toggle: Buscar / Nuevo cliente -->
        <div style="display:flex;gap:0;margin-bottom:14px;border-radius:10px;overflow:hidden;
                    border:1px solid var(--border)">
          <button id="nrTabBuscar" onclick="nrSetTab('buscar')"
                  style="flex:1;padding:8px;font-size:12px;font-weight:700;border:none;cursor:pointer;
                         background:rgba(76,217,100,.15);color:var(--green);transition:all .15s">
            <i class="fas fa-search"></i> Buscar cliente
          </button>
          <button id="nrTabNuevo" onclick="nrSetTab('nuevo')"
                  style="flex:1;padding:8px;font-size:12px;font-weight:700;border:none;cursor:pointer;
                         background:var(--s1);color:var(--muted);border-left:1px solid var(--border);transition:all .15s">
            <i class="fas fa-user-plus"></i> Nuevo cliente
          </button>
        </div>

        <!-- PANEL: Buscar cliente existente -->
        <div id="nrPanelBuscar">
          <div class="form-row" style="margin-bottom:8px">
            <div style="position:relative">
              <input class="form-input" type="text" id="nrClienteBusca"
                     placeholder="Nombre, email, DNI o teléfono…"
                     oninput="nrBuscarCliente(this.value)">
              <div id="nrClienteDrop"
                   style="display:none;position:absolute;top:calc(100% + 4px);left:0;right:0;
                          background:#1a1a1a;border:1px solid var(--border);border-radius:10px;
                          z-index:600;overflow:hidden;box-shadow:0 8px 32px rgba(0,0,0,.4)"></div>
            </div>
          </div>
        </div>

        <!-- PANEL: Registrar nuevo cliente (walk-in) -->
        <div id="nrPanelNuevo" style="display:none">
          <div style="background:rgba(255,149,0,.06);border:1px solid rgba(255,149,0,.2);
                      border-radius:10px;padding:14px;margin-bottom:12px">
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
              <div>
                <label class="form-label">Nombre <span>*</span></label>
                <input class="form-input" type="text" id="nrNewNombre" placeholder="Juan">
              </div>
              <div>
                <label class="form-label">Apellido <span>*</span></label>
                <input class="form-input" type="text" id="nrNewApellido" placeholder="García">
              </div>
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:10px">
              <div>
                <label class="form-label">Teléfono <span>*</span></label>
                <input class="form-input" type="text" id="nrNewTel" placeholder="1154321234">
              </div>
              <div>
                <label class="form-label">DNI <span style="color:var(--muted);font-weight:400">(opcional)</span></label>
                <input class="form-input" type="text" id="nrNewDni" placeholder="Sin DNI">
              </div>
            </div>
            <div>
              <label class="form-label">Email <span style="color:var(--muted);font-weight:400">(opcional)</span></label>
              <input class="form-input" type="email" id="nrNewEmail" placeholder="Sin email">
            </div>
            <button class="btn btn-primary" onclick="nrCrearCliente()"
                    id="nrBtnCrearCli" style="width:100%;margin-top:12px">
              <i class="fas fa-user-plus"></i> Registrar y seleccionar
            </button>
          </div>
        </div>

        <!-- Cliente seleccionado (shared entre los dos paneles) -->
        <div id="nrClienteSelWrap" style="display:none;margin-bottom:14px">
          <div style="background:rgba(52,152,219,.08);border:1px solid rgba(52,152,219,.25);
                      border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px">
            <div style="width:34px;height:34px;border-radius:50%;background:rgba(52,152,219,.2);
                        color:var(--blue);display:flex;align-items:center;justify-content:center;
                        font-weight:700;font-size:15px;flex-shrink:0" id="nrCliAvatar"></div>
            <div>
              <div id="nrCliNombre" style="font-weight:700;font-size:13px"></div>
              <div id="nrCliMeta"   style="font-size:11px;color:var(--muted)"></div>
            </div>
            <div id="nrCliBadge" style="margin-left:auto;font-size:10px;font-weight:700;
                 padding:2px 8px;border-radius:10px;background:rgba(76,217,100,.12);color:var(--green)"></div>
            <button onclick="nrLimpiarCliente()" style="background:none;border:none;
                    color:var(--muted);cursor:pointer;font-size:13px;padding:4px">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>

        <!-- Estado inicial + observación -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
          <div>
            <label class="form-label">Estado inicial</label>
            <select class="form-select" id="nrEstado">
              <option value="confirmada">Confirmada</option>
              <option value="pendiente">Pendiente</option>
            </select>
          </div>
          <div>
            <label class="form-label">Observación</label>
            <input class="form-input" type="text" id="nrObs" placeholder="Opcional">
          </div>
        </div>
      </div>

    </div>
    <div class="modal-footer">
      <button class="btn btn-ghost" onclick="closeModal('modalNuevaReserva')">Cancelar</button>
      <button class="btn btn-primary" id="nrBtnConfirmar" onclick="nrConfirmar()" style="display:none">
        <i class="fas fa-check"></i> Confirmar reserva
      </button>
    </div>
  </div>
</div>
<!-- /MODAL NUEVA RESERVA -->

<!-- ══════════════════════════════════════════════════════════════════
     WIZARD: NUEVO CLIENTE / ONBOARDING
══════════════════════════════════════════════════════════════════ -->
<div class="modal-overlay" id="modalWizard">
  <div class="modal modal-wiz">

    <!-- CABECERA -->
    <div class="modal-head">
      <div class="modal-head-icon g"><i class="fas fa-magic"></i></div>
      <div>
        <h3>Alta de nuevo cliente</h3>
        <p id="wizSubtitle">Seguí los pasos para configurar todo desde cero</p>
      </div>
      <button class="modal-close" onclick="wizCerrar()"><i class="fas fa-times"></i></button>
    </div>

    <!-- STEPPER -->
    <div class="wiz-stepper" id="wizStepper">
      <div class="wiz-step active" id="wizS1">
        <div class="wiz-step-dot"><span>1</span></div>
        <div class="wiz-step-lbl">Cuenta</div>
      </div>
      <div class="wiz-step-line" id="wizL1"></div>
      <div class="wiz-step" id="wizS2">
        <div class="wiz-step-dot"><span>2</span></div>
        <div class="wiz-step-lbl">Predio</div>
      </div>
      <div class="wiz-step-line" id="wizL2"></div>
      <div class="wiz-step" id="wizS3">
        <div class="wiz-step-dot"><span>3</span></div>
        <div class="wiz-step-lbl">Canchas</div>
      </div>
      <div class="wiz-step-line" id="wizL3"></div>
      <div class="wiz-step" id="wizS4">
        <div class="wiz-step-dot"><span>4</span></div>
        <div class="wiz-step-lbl">Horarios</div>
      </div>
    </div>

    <!-- PANELES -->
    <div class="wiz-body">

      <!-- ── PASO 1: CUENTA ── -->
      <div class="wiz-panel" id="wizPanel1">
        <p style="font-size:12px;color:var(--muted);margin-bottom:18px">
          Creá la cuenta del dueño del predio. Recibirá acceso al panel para gestionar todo.
        </p>
        <div class="wiz-2col">
          <div class="form-row" style="margin:0">
            <label class="form-label">Nombre <span>*</span></label>
            <input class="form-input" id="wNombre" type="text" placeholder="Juan">
          </div>
          <div class="form-row" style="margin:0">
            <label class="form-label">Apellido <span>*</span></label>
            <input class="form-input" id="wApellido" type="text" placeholder="García">
          </div>
        </div>
        <div style="height:12px"></div>
        <div class="wiz-2col">
          <div class="form-row" style="margin:0">
            <label class="form-label">DNI <span>*</span></label>
            <input class="form-input" id="wDni" type="text" placeholder="12345678">
          </div>
          <div class="form-row" style="margin:0">
            <label class="form-label">Teléfono</label>
            <input class="form-input" id="wTelefono" type="text" placeholder="1154321234">
          </div>
        </div>
        <div style="height:12px"></div>
        <div class="form-row">
          <label class="form-label">Email <span>*</span></label>
          <input class="form-input" id="wEmail" type="email" placeholder="juan@ejemplo.com">
        </div>
        <div class="wiz-2col">
          <div class="form-row" style="margin:0">
            <label class="form-label">Contraseña <span>*</span></label>
            <input class="form-input" id="wPass" type="password" placeholder="Mín. 6 caracteres">
          </div>
          <div class="form-row" style="margin:0">
            <label class="form-label">Confirmar <span>*</span></label>
            <input class="form-input" id="wPass2" type="password" placeholder="Repetir">
          </div>
        </div>
      </div>

      <!-- ── PASO 2: PREDIO ── -->
      <div class="wiz-panel" id="wizPanel2" style="display:none">
        <p style="font-size:12px;color:var(--muted);margin-bottom:18px">
          Configurá el predio deportivo del cliente. Podés agregar más después.
        </p>
        <div class="form-row">
          <label class="form-label">Nombre del predio <span>*</span></label>
          <input class="form-input" id="wCmpNombre" type="text" placeholder="Ej: Complejo El Barrio">
        </div>
        <div class="form-row">
          <label class="form-label">Dirección <span>*</span></label>
          <input class="form-input" id="wCmpDir" type="text" placeholder="Calle 123, Piso 2">
        </div>
        <div class="wiz-2col">
          <div class="form-row" style="margin:0">
            <label class="form-label">Tipo de complejo</label>
            <select class="form-select" id="wCmpTipo">
              <option value="">— Sin especificar —</option>
            </select>
          </div>
          <div class="form-row" style="margin:0">
            <label class="form-label">Localidad <span>*</span></label>
            <select class="form-select" id="wCmpLocalidad">
              <option value="">Cargando…</option>
            </select>
          </div>
        </div>
        <div class="form-row">
          <label class="form-label">Teléfono del predio</label>
          <input class="form-input" id="wCmpTel" type="text" placeholder="Opcional">
        </div>
        <div class="form-row" style="margin:0">
          <label class="form-label">Email del predio</label>
          <input class="form-input" id="wCmpEmail" type="email" placeholder="Opcional">
        </div>
      </div>

      <!-- ── PASO 3: CANCHAS ── -->
      <div class="wiz-panel" id="wizPanel3" style="display:none">
        <p style="font-size:12px;color:var(--muted);margin-bottom:16px">
          Agregá las canchas del predio. Podés agregar una a la vez.
        </p>
        <!-- Mini-form para agregar una cancha -->
        <div style="display:flex;gap:8px;align-items:flex-end;margin-bottom:16px;
                    padding:14px;border-radius:10px;background:rgba(255,255,255,.03);
                    border:1px solid var(--border)">
          <div style="flex:1">
            <label class="form-label">Nombre de cancha <span>*</span></label>
            <input class="form-input" id="wCaName" type="text" placeholder="Ej: Cancha 1">
          </div>
          <div style="flex:1">
            <label class="form-label">Tipo <span>*</span></label>
            <select class="form-select" id="wCaTipo"></select>
          </div>
          <button class="btn btn-primary" onclick="wizAgregarCancha()" style="flex-shrink:0;align-self:flex-end">
            <i class="fas fa-plus"></i> Agregar
          </button>
        </div>
        <!-- Lista de canchas agregadas -->
        <div id="wizCanchasList">
          <div style="text-align:center;padding:24px;color:var(--muted);font-size:13px">
            <i class="fas fa-futbol" style="font-size:24px;display:block;margin-bottom:8px;opacity:.3"></i>
            Aún no agregaste canchas
          </div>
        </div>
      </div>

      <!-- ── PASO 4: HORARIOS ── -->
      <div class="wiz-panel" id="wizPanel4" style="display:none">
        <p style="font-size:12px;color:var(--muted);margin-bottom:14px">
          Configurá los turnos disponibles para cada cancha. Podés omitir esto y configurarlo después.
        </p>
        <!-- Tabs por cancha -->
        <div class="wiz-tabs" id="wizHorTabs"></div>
        <!-- Contenido por cancha (se reemplaza al cambiar tab) -->
        <div id="wizHorContent">
          <!-- Mini-form de franja -->
          <div style="padding:14px;border-radius:10px;background:rgba(255,255,255,.03);
                      border:1px solid var(--border);margin-bottom:12px">
            <div class="wiz-2col" style="margin-bottom:10px">
              <div>
                <label class="form-label">Hora inicio <span>*</span></label>
                <input class="form-input" id="wFrInicio" type="time" value="08:00">
              </div>
              <div>
                <label class="form-label">Hora fin <span>*</span></label>
                <input class="form-input" id="wFrFin" type="time" value="09:00">
              </div>
            </div>
            <div class="wiz-2col" style="margin-bottom:10px">
              <div>
                <label class="form-label">Precio ($) <span>*</span></label>
                <input class="form-input" id="wFrPrecio" type="number" min="0" step="0.01" placeholder="0.00">
              </div>
              <div>
                <label class="form-label">Seña ($)</label>
                <input class="form-input" id="wFrSena" type="number" min="0" step="0.01" placeholder="0.00">
              </div>
            </div>
            <label class="form-label" style="margin-bottom:4px">Días disponibles <span>*</span></label>
            <div class="wiz-dias-grid" id="wizDiasGrid">
              <div class="wiz-dia-btn" data-dia="2" onclick="wizToggleDia(this)">Lun</div>
              <div class="wiz-dia-btn" data-dia="3" onclick="wizToggleDia(this)">Mar</div>
              <div class="wiz-dia-btn" data-dia="4" onclick="wizToggleDia(this)">Mié</div>
              <div class="wiz-dia-btn" data-dia="5" onclick="wizToggleDia(this)">Jue</div>
              <div class="wiz-dia-btn" data-dia="6" onclick="wizToggleDia(this)">Vie</div>
              <div class="wiz-dia-btn" data-dia="7" onclick="wizToggleDia(this)">Sáb</div>
              <div class="wiz-dia-btn" data-dia="1" onclick="wizToggleDia(this)">Dom</div>
            </div>
            <button class="btn btn-primary btn-sm" onclick="wizAgregarFranja()"
                    style="margin-top:12px;width:100%">
              <i class="fas fa-plus"></i> Agregar turno
            </button>
          </div>
          <!-- Lista de franjas cargadas para esta cancha -->
          <div id="wizFranjasList"></div>
        </div>
      </div>

      <!-- ── PASO 5: LISTO ── -->
      <div class="wiz-panel" id="wizPanel5" style="display:none;text-align:center;padding:10px 0">
        <div class="wiz-done-icon"><i class="fas fa-check"></i></div>
        <h3 style="font-size:20px;font-weight:800;margin-bottom:6px">¡Todo listo!</h3>
        <p style="color:var(--muted);font-size:13px;margin-bottom:4px" id="wizDoneMsg">
          El cliente fue configurado exitosamente.
        </p>
        <div class="wiz-summary-box" id="wizSummary" style="text-align:left"></div>
        <div style="display:flex;gap:10px;justify-content:center;margin-top:20px">
          <button class="btn btn-primary" id="wizBtnVerCliente" onclick="wizVerCliente()">
            <i class="fas fa-eye"></i> Ver cliente
          </button>
          <button class="btn btn-ghost" onclick="wizCerrar()">Cerrar</button>
        </div>
      </div>

    </div><!-- /wiz-body -->

    <!-- PIE DE NAVEGACIÓN -->
    <div class="modal-footer" style="justify-content:space-between" id="wizFooter">
      <button class="btn btn-ghost" id="wizBtnBack" onclick="wizBack()" style="visibility:hidden">
        <i class="fas fa-arrow-left"></i> Anterior
      </button>
      <div style="display:flex;gap:8px">
        <button class="btn btn-ghost" onclick="wizCerrar()" id="wizBtnCancel">Cancelar</button>
        <button class="btn btn-primary" id="wizBtnNext" onclick="wizNext()">
          Siguiente <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </div>

  </div>
</div>
<!-- /WIZARD -->

<!-- NOTIFICACIÓN RESERVAS PENDIENTES -->
<div id="notif-pendientes" style="display:none;position:fixed;bottom:24px;right:24px;z-index:9999;
     background:linear-gradient(135deg,rgba(255,149,0,0.15),rgba(255,149,0,0.08));
     border:1px solid rgba(255,149,0,0.4);border-radius:16px;padding:16px 20px;
     backdrop-filter:blur(20px);box-shadow:0 8px 32px rgba(0,0,0,0.4);
     max-width:320px;cursor:pointer;transition:transform .2s"
     onclick="irAReservasPendientes()" title="Ver reservas pendientes">
  <div style="display:flex;align-items:center;gap:12px">
    <div style="width:40px;height:40px;border-radius:50%;background:rgba(255,149,0,0.2);
         display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-bell" style="color:var(--orange);font-size:1.1rem;animation:bellShake .5s ease infinite alternate"></i>
    </div>
    <div>
      <div style="font-weight:700;color:#fff;font-size:.9rem">Nueva reserva pendiente</div>
      <div id="notif-pendientes-msg" style="font-size:.8rem;color:var(--muted);margin-top:2px"></div>
    </div>
    <button onclick="event.stopPropagation();cerrarNotifPendientes()"
            style="background:none;border:none;color:var(--muted);font-size:1.1rem;cursor:pointer;margin-left:auto;line-height:1">×</button>
  </div>
</div>
<style>
@keyframes bellShake {
  0%   { transform: rotate(-15deg); }
  100% { transform: rotate(15deg); }
}
</style>
<script>
(function() {
    let _lastSeenId = 0;
    let _notifVisible = false;
    const RES_API_POLL = 'api/reservas.php';

    async function pollPendientes() {
        try {
            const r = await fetch(`${RES_API_POLL}?action=pendientes_count&since=${_lastSeenId}`);
            const j = await r.json();
            if (!j.ok) return;
            const { count, last_id } = j.data;
            if (count > 0 && last_id > _lastSeenId) {
                mostrarNotifPendientes(count, last_id);
            } else if (count === 0 && _notifVisible) {
                // Ya no hay pendientes (el admin confirmó desde otra pestaña)
                cerrarNotifPendientes();
                const badge = document.getElementById('badge-res-pend');
                if (badge) badge.style.display = 'none';
            }
        } catch(e) { /* red problem, ignore */ }
    }

    function mostrarNotifPendientes(count, lastId) {
        _notifVisible = true;
        const notif = document.getElementById('notif-pendientes');
        const msg   = document.getElementById('notif-pendientes-msg');
        msg.textContent = count === 1 ? '1 reserva esperando confirmación' : `${count} reservas esperando confirmación`;
        notif.style.display = 'block';
        notif.style.transform = 'translateY(0)';
        // Actualizar badge del topbar dinámicamente
        let badge = document.getElementById('badge-res-pend');
        if (!badge) {
            const tbRight = document.querySelector('.tb-right');
            if (tbRight) {
                tbRight.insertAdjacentHTML('afterbegin',
                    `<div class="tb-btn" id="badge-res-pend" onclick="irAReservasPendientes()" style="position:relative;cursor:pointer" title="${count} reserva(s) pendiente(s)">
                        <i class="fas fa-calendar-check" style="color:var(--orange)"></i>
                        <span class="tb-dot" style="background:var(--orange)"></span>
                        <span id="badge-res-pend-num" style="position:absolute;top:-4px;right:-4px;background:var(--orange);color:#000;font-size:9px;font-weight:800;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center">${count}</span>
                    </div>`
                );
            }
        } else {
            const numEl = document.getElementById('badge-res-pend-num');
            if (numEl) numEl.textContent = count;
        }
        // Guardar el last_id para no volver a notificar por el mismo
        _lastSeenId = lastId;
    }

    window.cerrarNotifPendientes = function() {
        const notif = document.getElementById('notif-pendientes');
        notif.style.display = 'none';
        _notifVisible = false;
    };

    window.irAReservasPendientes = function() {
        cerrarNotifPendientes();
        // Ir a la vista de reservas con filtro pendiente
        const reservasLink = document.querySelector('[data-view=reservas]');
        if (reservasLink) {
            showView(reservasLink);
            setTimeout(() => {
                const filtPend = document.querySelector('[data-resf="pend"]');
                if (filtPend) filtPend.click();
            }, 300);
        }
    };

    // Arrancar polling a los 5s de carga y repetir cada 30s
    setTimeout(pollPendientes, 5000);
    setInterval(pollPendientes, 30000);
})();
</script>

</body>
</html>
