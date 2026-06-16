<?php
session_start();
require_once '../../config/dist/script/php/conn.php';

$nombre  = $_SESSION['usuario_nombre']  ?? 'Usuario';
$perfil  = $_SESSION['usuario_perfil']  ?? 5;
$hora    = (int)date('H');
$saludo  = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');

$totalCanchas   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS t FROM cancha WHERE ACTIVO=1"))['t'] ?? 0;
$totalComplejos = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS t FROM complejo WHERE ACTIVO=1"))['t'] ?? 0;
$reservasHoy    = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS t FROM reserva WHERE RESERVA_FECHA=CURDATE() AND ACTIVO=1"))['t'] ?? 0;
$reservasPend   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS t FROM reserva WHERE RESERVA_ESTADO='pendiente' AND ACTIVO=1"))['t'] ?? 0;
$ingresosHoy    = mysqli_fetch_assoc(mysqli_query($link, "SELECT COALESCE(SUM(PAGO_MONTO),0) AS t FROM pago WHERE DATE(PAGO_FECHA)=CURDATE() AND ACTIVO=1"))['t'] ?? 0;
$usuariosPend   = mysqli_fetch_assoc(mysqli_query($link, "SELECT COUNT(*) AS t FROM usuarios WHERE ACTIVO=0"))['t'] ?? 0;

$esDueno = $perfil <= 2;
$esStaff = $perfil <= 4;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <title>Panel - La Canchita</title>
    <?php $PWA_BASE = '../../'; require_once '../../config/dist/script/php/pwa_head.php'; ?>
    <link rel="shortcut icon" href="../../config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
    <link rel="stylesheet" href="../../config/pluggins/vendor/fontawesome-free/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:      #4cd964;
            --green-dark: #34c759;
            --blue:       #3498db;
            --orange:     #ff9500;
            --red:        #e74c3c;
            --purple:     #9b59b6;
            --bg:         #0d0d0d;
            --surface:    rgba(255,255,255,0.06);
            --surface2:   rgba(255,255,255,0.10);
            --border:     rgba(255,255,255,0.10);
            --text:       #ffffff;
            --text-muted: rgba(255,255,255,0.45);
            --sidebar-w:  240px;
            --header-h:   60px;
        }

        html, body { height: 100%; }
        body {
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            background: var(--bg); color: var(--text);
            display: flex; overflow: hidden;
        }

        .bg-blur {
            position: fixed; inset: 0; z-index: 0;
            background: url('../../config/dist/img/ESTADIO.webp') center/cover no-repeat;
            opacity: 0.08; filter: blur(4px);
        }

        /* ── SIDEBAR ── */
        .sidebar {
            position: fixed; left: 0; top: 0; bottom: 0;
            width: var(--sidebar-w);
            background: rgba(10,10,10,0.92);
            backdrop-filter: blur(20px);
            border-right: 1px solid var(--border);
            display: flex; flex-direction: column;
            z-index: 200; transition: transform 0.3s ease;
        }
        .sidebar.collapsed { transform: translateX(calc(-1 * var(--sidebar-w))); }

        .sidebar-logo {
            padding: 20px 20px 16px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid var(--border);
        }
        .sidebar-logo img { width: 38px; }
        .sidebar-logo span { font-size: 15px; font-weight: 700; }
        .sidebar-logo small { display: block; font-size: 10px; color: var(--text-muted); letter-spacing: 1px; }

        .sidebar-user {
            padding: 14px 20px;
            display: flex; align-items: center; gap: 10px;
            border-bottom: 1px solid var(--border);
        }
        .avatar {
            width: 36px; height: 36px; border-radius: 50%;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            display: flex; align-items: center; justify-content: center;
            font-weight: 700; font-size: 14px; color: #fff; flex-shrink: 0;
        }
        .sidebar-user-info strong { display: block; font-size: 13px; font-weight: 600; }
        .sidebar-user-info span   { font-size: 11px; color: var(--text-muted); }

        .sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
        .nav-section { padding: 8px 20px 4px; font-size: 10px; letter-spacing: 1.5px; color: var(--text-muted); text-transform: uppercase; }
        .nav-item {
            display: flex; align-items: center; gap: 10px;
            padding: 10px 20px; color: var(--text-muted);
            text-decoration: none; font-size: 13px; font-weight: 500;
            border-left: 3px solid transparent;
            transition: all 0.2s; cursor: pointer;
        }
        .nav-item:hover { color: var(--text); background: var(--surface); }
        .nav-item.active { color: var(--green); border-left-color: var(--green); background: rgba(76,217,100,0.08); }
        .nav-item i { width: 18px; text-align: center; font-size: 14px; }
        .nav-badge {
            margin-left: auto; background: #e74c3c; color: #fff;
            font-size: 10px; font-weight: 700; padding: 2px 6px; border-radius: 10px;
        }

        .sidebar-footer { padding: 14px 20px; border-top: 1px solid var(--border); }
        .btn-logout {
            display: flex; align-items: center; gap: 8px;
            padding: 9px 14px; border-radius: 10px;
            border: 1px solid rgba(231,76,60,0.3);
            background: rgba(231,76,60,0.08);
            color: #e74c3c; font-size: 13px; cursor: pointer;
            width: 100%; transition: all 0.2s; text-decoration: none;
        }
        .btn-logout:hover { background: rgba(231,76,60,0.18); border-color: #e74c3c; }

        .sidebar-overlay {
            display: none; position: fixed; inset: 0; z-index: 199;
            background: rgba(0,0,0,0.6);
        }
        .sidebar-overlay.show { display: block; }

        /* ── MAIN ── */
        .main {
            flex: 1; margin-left: var(--sidebar-w);
            display: flex; flex-direction: column;
            min-height: 100vh; transition: margin-left 0.3s;
            position: relative; z-index: 1;
        }
        .main.expanded { margin-left: 0; }

        /* ── TOPBAR ── */
        .topbar {
            height: var(--header-h);
            background: rgba(10,10,10,0.85);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center;
            padding: 0 20px; gap: 12px;
            position: sticky; top: 0; z-index: 100;
        }
        .btn-toggle {
            width: 36px; height: 36px;
            display: flex; align-items: center; justify-content: center;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text); cursor: pointer;
            transition: background 0.2s; flex-shrink: 0;
        }
        .btn-toggle:hover { background: var(--surface2); }
        .topbar-title { font-size: 15px; font-weight: 600; flex: 1; }
        .topbar-actions { display: flex; align-items: center; gap: 8px; margin-left: auto; }
        .topbar-btn {
            width: 34px; height: 34px;
            display: flex; align-items: center; justify-content: center;
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 8px; color: var(--text-muted); cursor: pointer;
            transition: all 0.2s; text-decoration: none;
        }
        .topbar-btn:hover { color: var(--text); background: var(--surface2); }

        /* ── CONTENIDO ── */
        .content {
            flex: 1; padding: 20px; overflow-y: auto;
            max-height: calc(100vh - var(--header-h));
        }

        @keyframes fadeUp { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:translateY(0); } }
        .fade-up { animation: fadeUp 0.4s ease both; }

        /* ── GREETING ── */
        .greeting { margin-bottom: 22px; }
        .greeting h1 { font-size: 22px; font-weight: 700; margin-bottom: 3px; }
        .greeting p  { font-size: 13px; color: var(--text-muted); }

        /* ── SECTION HEADER ── */
        .section-header {
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 16px;
        }
        .section-header h2 { font-size: 16px; font-weight: 700; }
        .link-btn {
            font-size: 12px; color: var(--green);
            background: none; border: none; cursor: pointer;
            transition: opacity 0.2s; text-decoration: none;
        }
        .link-btn:hover { opacity: 0.7; }

        /* ── KPI CARDS ── */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(155px, 1fr));
            gap: 14px; margin-bottom: 28px;
        }
        .kpi-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 16px;
            display: flex; flex-direction: column; gap: 8px;
            backdrop-filter: blur(10px);
            transition: transform 0.2s, box-shadow 0.2s;
            animation: fadeUp 0.5s ease both;
        }
        .kpi-card:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(0,0,0,0.3); }
        .kpi-top { display: flex; align-items: center; justify-content: space-between; }
        .kpi-icon {
            width: 36px; height: 36px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; font-size: 15px;
        }
        .kpi-icon.green  { background: rgba(76,217,100,0.15); color: var(--green); }
        .kpi-icon.blue   { background: rgba(52,152,219,0.15); color: var(--blue); }
        .kpi-icon.orange { background: rgba(255,149,0,0.15);  color: var(--orange); }
        .kpi-icon.red    { background: rgba(231,76,60,0.15);  color: var(--red); }
        .kpi-icon.purple { background: rgba(155,89,182,0.15); color: var(--purple); }
        .kpi-value { font-size: 26px; font-weight: 800; line-height: 1; }
        .kpi-label { font-size: 11px; color: var(--text-muted); font-weight: 500; }

        /* ── PREDIOS GRID ── */
        .predios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 16px; margin-bottom: 28px;
        }
        .predio-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s, border-color 0.2s;
            cursor: pointer; animation: fadeUp 0.4s ease both;
        }
        .predio-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 32px rgba(0,0,0,0.4);
            border-color: rgba(76,217,100,0.35);
        }
        .predio-thumb {
            height: 110px;
            background: linear-gradient(135deg, #0d1a0d, #1a2a1a);
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .predio-thumb::before {
            content: '';
            position: absolute; inset: 0;
            background: url('../../config/dist/img/ESTADIO.webp') center/cover;
            opacity: 0.2;
        }
        .predio-thumb .thumb-icon {
            font-size: 36px; color: rgba(76,217,100,0.55);
            position: relative; z-index: 1;
        }
        .predio-tipo-badge {
            position: absolute; top: 10px; left: 10px;
            padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;
            background: rgba(76,217,100,0.18); color: var(--green);
            border: 1px solid rgba(76,217,100,0.3); z-index: 1;
        }
        .predio-canchas-badge {
            position: absolute; top: 10px; right: 10px;
            padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;
            background: rgba(52,152,219,0.18); color: var(--blue);
            border: 1px solid rgba(52,152,219,0.3); z-index: 1;
        }
        .predio-body { padding: 14px 16px 10px; }
        .predio-body h3 { font-size: 15px; font-weight: 700; margin-bottom: 4px; }
        .predio-loc {
            font-size: 11px; color: var(--text-muted);
            display: flex; align-items: center; gap: 5px; margin-bottom: 10px;
        }
        .predio-acts { display: flex; flex-wrap: wrap; gap: 5px; margin-bottom: 12px; }
        .act-tag {
            padding: 2px 8px; border-radius: 6px; font-size: 10px; font-weight: 600;
            background: rgba(255,255,255,0.06); color: var(--text-muted);
            border: 1px solid var(--border);
        }
        .predio-footer {
            display: flex; align-items: center; justify-content: space-between;
            padding: 10px 16px 14px; border-top: 1px solid var(--border);
        }
        .predio-contact { font-size: 11px; color: var(--text-muted); }
        .predio-contact i { margin-right: 4px; color: var(--green); }
        .btn-ver-predio {
            padding: 7px 16px; border-radius: 9px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            border: none; color: #fff; font-size: 12px; font-weight: 700;
            cursor: pointer; transition: opacity 0.2s, transform 0.15s;
        }
        .btn-ver-predio:hover { opacity: 0.9; transform: scale(1.02); }

        /* ── PREDIO DETALLE ── */
        .breadcrumb {
            display: flex; align-items: center; gap: 8px;
            font-size: 13px; color: var(--text-muted); margin-bottom: 20px;
        }
        .breadcrumb .bc-link {
            color: var(--green); cursor: pointer; background: none; border: none;
            font-size: 13px; font-family: inherit; padding: 0;
        }
        .breadcrumb .bc-link:hover { text-decoration: underline; }

        .predio-header-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; padding: 20px 22px;
            display: flex; align-items: flex-start; gap: 18px;
            margin-bottom: 24px; animation: fadeUp 0.4s ease both;
        }
        .predio-header-icon {
            width: 56px; height: 56px; border-radius: 14px; flex-shrink: 0;
            background: rgba(76,217,100,0.12); border: 1px solid rgba(76,217,100,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: var(--green);
        }
        .predio-header-info { flex: 1; }
        .predio-header-info h2 { font-size: 18px; font-weight: 800; margin-bottom: 3px; }
        .predio-header-info .det-loc { font-size: 12px; color: var(--text-muted); margin-bottom: 8px; }
        .predio-header-tags { display: flex; flex-wrap: wrap; gap: 6px; }
        .header-tag {
            padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;
            background: rgba(76,217,100,0.1); color: var(--green);
            border: 1px solid rgba(76,217,100,0.2);
        }
        .header-tag.blue {
            background: rgba(52,152,219,0.1); color: var(--blue);
            border-color: rgba(52,152,219,0.2);
        }

        /* ── CANCHAS DEL PREDIO ── */
        .canchas-list { display: flex; flex-direction: column; gap: 16px; }
        .cancha-detalle-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden;
            animation: fadeUp 0.4s ease both;
        }
        .cancha-detalle-header {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 18px; border-bottom: 1px solid var(--border);
            flex-wrap: wrap;
        }
        .cancha-det-icon {
            width: 44px; height: 44px; border-radius: 12px; flex-shrink: 0;
            background: rgba(52,152,219,0.12); border: 1px solid rgba(52,152,219,0.2);
            display: flex; align-items: center; justify-content: center;
            font-size: 18px; color: var(--blue);
        }
        .cancha-det-info { flex: 1; min-width: 0; }
        .cancha-det-info h3 { font-size: 15px; font-weight: 700; margin-bottom: 2px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .cancha-det-info span { font-size: 11px; color: var(--text-muted); }
        .tipo-pill {
            padding: 4px 12px; border-radius: 20px; font-size: 11px; font-weight: 700;
            background: rgba(52,152,219,0.12); color: var(--blue);
            border: 1px solid rgba(52,152,219,0.25); white-space: nowrap;
        }
        .btn-reservar-can {
            padding: 8px 18px; border-radius: 10px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            border: none; color: #fff; font-size: 12px; font-weight: 700;
            cursor: pointer; transition: opacity 0.2s, transform 0.15s; white-space: nowrap;
        }
        .btn-reservar-can:hover { opacity: 0.9; transform: scale(1.02); }

        .franjas-section { padding: 14px 18px 16px; }
        .franjas-title {
            font-size: 11px; font-weight: 700; color: var(--text-muted);
            text-transform: uppercase; letter-spacing: 0.8px; margin-bottom: 12px;
        }
        .franjas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(190px, 1fr));
            gap: 10px;
        }
        .franja-slot {
            background: rgba(255,255,255,0.04); border: 1px solid var(--border);
            border-radius: 10px; padding: 10px 12px;
            transition: border-color 0.2s, background 0.2s;
        }
        .franja-slot:hover { border-color: rgba(76,217,100,0.3); background: rgba(76,217,100,0.04); }
        .franja-hora { font-size: 14px; font-weight: 800; color: var(--green); margin-bottom: 4px; }
        .franja-dias { font-size: 11px; color: var(--text-muted); margin-bottom: 6px; }
        .franja-precio { font-size: 13px; font-weight: 700; }
        .franja-precio small { font-size: 10px; color: var(--text-muted); font-weight: 400; }
        .no-franjas {
            text-align: center; padding: 20px; color: var(--text-muted); font-size: 12px;
        }
        .no-franjas i { font-size: 20px; opacity: 0.3; display: block; margin-bottom: 6px; }

        /* ── SPINNER / EMPTY ── */
        .spinner-wrap {
            display: flex; align-items: center; justify-content: center;
            padding: 60px 0; flex-direction: column; gap: 12px;
            color: var(--text-muted); font-size: 13px;
        }
        .spinner {
            width: 32px; height: 32px; border-radius: 50%;
            border: 3px solid var(--border); border-top-color: var(--green);
            animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
        .empty-state { text-align: center; padding: 60px 20px; color: var(--text-muted); }
        .empty-state i { font-size: 48px; opacity: 0.2; display: block; margin-bottom: 14px; }
        .empty-state h3 { font-size: 16px; font-weight: 700; margin-bottom: 6px; color: var(--text); }
        .empty-state p { font-size: 13px; }

        /* ── MIS RESERVAS ── */
        .activity-box {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 14px; padding: 18px 20px;
        }
        .reserva-row {
            display: flex; align-items: center; gap: 12px;
            padding: 12px 0; border-bottom: 1px solid var(--border);
        }
        .reserva-row:last-child { border-bottom: none; }
        .reserva-fecha { font-size: 13px; font-weight: 700; color: var(--green); min-width: 50px; }
        .reserva-info { flex: 1; }
        .reserva-info strong { display: block; font-size: 13px; }
        .reserva-info span   { font-size: 11px; color: var(--text-muted); }
        .reserva-estado { padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700; }
        .estado-pendiente  { background: rgba(255,149,0,0.15);  color: var(--orange); border: 1px solid rgba(255,149,0,0.3); }
        .estado-confirmada { background: rgba(76,217,100,0.15); color: var(--green);  border: 1px solid rgba(76,217,100,0.3); }
        .estado-cancelada  { background: rgba(231,76,60,0.15);  color: var(--red);    border: 1px solid rgba(231,76,60,0.3); }
        .activity-empty { text-align: center; padding: 40px 0; color: var(--text-muted); }
        .activity-empty i { font-size: 32px; margin-bottom: 10px; display: block; opacity: 0.3; }

        /* ── MODAL RESERVA ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 500;
            background: rgba(0,0,0,0.75); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 16px;
        }
        .modal-overlay.show { display: flex; }
        .modal-box {
            background: #141414; border: 1px solid var(--border);
            border-radius: 18px; width: 100%; max-width: 420px;
            padding: 26px 22px; animation: slideUp 0.3s ease;
        }
        @keyframes slideUp { from { opacity:0; transform:translateY(20px); } to { opacity:1; transform:translateY(0); } }
        .modal-header-row {
            display: flex; align-items: center; justify-content: space-between; margin-bottom: 20px;
        }
        .modal-header-row h3 { font-size: 17px; font-weight: 700; }
        .modal-close { background: none; border: none; color: var(--text-muted); font-size: 18px; cursor: pointer; padding: 4px; }
        .modal-close:hover { color: var(--text); }
        .modal-cancha-info {
            background: rgba(76,217,100,0.06); border: 1px solid rgba(76,217,100,0.18);
            border-radius: 10px; padding: 12px 14px; margin-bottom: 16px;
        }
        .modal-cancha-info strong { color: var(--green); display: block; margin-bottom: 2px; font-size: 14px; }
        .modal-cancha-info span { color: var(--text-muted); font-size: 11px; }
        .modal-field { margin-bottom: 14px; }
        .modal-field label { display: block; font-size: 11px; color: var(--text-muted); margin-bottom: 5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
        .modal-field input {
            width: 100%; padding: 11px 14px;
            background: rgba(255,255,255,0.06); border: 1px solid var(--border);
            border-radius: 10px; color: var(--text); font-size: 14px; outline: none;
        }
        .modal-field input:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(76,217,100,0.12); }
        .modal-footer-row { display: flex; gap: 10px; margin-top: 20px; }
        .btn-cancel-modal {
            flex: 1; padding: 12px; background: rgba(255,255,255,0.06);
            border: 1px solid var(--border); border-radius: 10px;
            color: var(--text-muted); font-size: 14px; cursor: pointer;
        }
        .btn-cancel-modal:hover { background: rgba(255,255,255,0.1); color: var(--text); }
        .btn-confirm-modal {
            flex: 2; padding: 12px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            border: none; border-radius: 10px; color: #fff;
            font-size: 14px; font-weight: 700; cursor: pointer;
        }
        .btn-confirm-modal:hover { opacity: 0.9; }

        /* ── SKELETON SHIMMER (FIX 6) ── */
        @keyframes shimmer {
            0%   { opacity: 0.4; }
            50%  { opacity: 0.8; }
            100% { opacity: 0.4; }
        }
        .skeleton-card {
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            height: 120px;
            margin-bottom: 12px;
            animation: shimmer 1.5s infinite;
        }

        /* ── MODAL SLOTS (FIX 1) ── */
        .modal-slots-label {
            font-size: 11px; color: var(--text-muted);
            font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 8px; margin-top: 4px;
        }
        .slots-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 8px; margin-bottom: 14px;
        }
        .slot-btn {
            padding: 9px 10px; border-radius: 10px;
            background: rgba(255,255,255,0.05);
            border: 1px solid var(--border);
            color: var(--text); font-size: 12px; font-weight: 600;
            cursor: pointer; text-align: left;
            transition: border-color 0.2s, background 0.2s;
            line-height: 1.4;
        }
        .slot-btn:hover { border-color: rgba(76,217,100,0.4); background: rgba(76,217,100,0.06); }
        .slot-btn.selected {
            border-color: var(--green);
            background: rgba(76,217,100,0.12);
            color: var(--green);
        }
        .slot-btn .slot-price { font-size: 11px; color: var(--text-muted); font-weight: 400; display: block; margin-top: 2px; }
        .slot-btn.selected .slot-price { color: var(--green-dark); }
        .slots-empty { font-size: 12px; color: var(--text-muted); padding: 10px 0; }

        /* ── CALENDARIO VISUAL ── */
        .lc-cal { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; margin-bottom: 14px; user-select: none; }
        .lc-cal-head { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; border-bottom: 1px solid var(--border); }
        .lc-cal-title { font-size: 13px; font-weight: 700; color: var(--text); text-transform: capitalize; }
        .lc-cal-nav { background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 5px 9px; border-radius: 7px; font-size: 12px; transition: color 0.15s, background 0.15s; line-height: 1; }
        .lc-cal-nav:hover:not(:disabled) { color: var(--green); background: rgba(76,217,100,0.08); }
        .lc-cal-nav:disabled { opacity: 0.2; cursor: not-allowed; }
        .lc-cal-week { display: grid; grid-template-columns: repeat(7,1fr); padding: 8px 10px 2px; gap: 2px; }
        .lc-cal-week span { text-align: center; font-size: 10px; font-weight: 700; color: var(--text-muted); letter-spacing: 0.4px; }
        .lc-cal-grid { display: grid; grid-template-columns: repeat(7,1fr); padding: 4px 10px 10px; gap: 3px; }
        .lc-cal-day { aspect-ratio: 1; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 12px; font-weight: 500; cursor: pointer; border: 1px solid transparent; background: none; color: var(--text); transition: all 0.12s; line-height: 1; padding: 0; }
        .lc-cal-day:hover:not(:disabled) { background: rgba(76,217,100,0.08); border-color: rgba(76,217,100,0.25); color: var(--green); }
        .lc-cal-day.today { border-color: rgba(76,217,100,0.5); color: var(--green); font-weight: 700; }
        .lc-cal-day.selected { background: var(--green) !important; border-color: var(--green) !important; color: #000 !important; font-weight: 700; }
        .lc-cal-day:disabled { opacity: 0.2; cursor: not-allowed; }
        .reserva-resumen {
            background: rgba(76,217,100,0.06); border: 1px solid rgba(76,217,100,0.15);
            border-radius: 10px; padding: 11px 14px; margin-bottom: 4px;
            font-size: 13px; color: var(--text-muted);
        }
        .reserva-resumen.filled { color: var(--text); }
        .reserva-resumen strong { color: var(--green); }
        .btn-confirm-modal:disabled {
            opacity: 0.4; cursor: not-allowed;
        }

        /* ── TOAST (FIX 1) ── */
        .toast {
            position: fixed; bottom: 28px; left: 50%; transform: translateX(-50%) translateY(20px);
            background: rgba(20,30,20,0.96); border: 1px solid rgba(76,217,100,0.35);
            border-radius: 14px; padding: 14px 22px;
            color: var(--text); font-size: 13px; font-weight: 600;
            backdrop-filter: blur(16px); z-index: 900;
            display: flex; align-items: center; gap: 10px;
            opacity: 0; transition: opacity 0.3s, transform 0.3s;
            pointer-events: none; white-space: nowrap;
        }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .toast i { color: var(--green); font-size: 16px; }

        /* ── MIS RESERVAS CARDS ── */
        .reserva-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 12px;
            animation: fadeUp 0.3s ease both;
        }
        .reserva-card-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .reserva-card-title { font-weight: 700; font-size: 0.95rem; }
        .reserva-card-predio { font-size: 0.8rem; color: var(--text-muted); margin-top: 2px; }
        .reserva-estado-badge {
            padding: 4px 10px; border-radius: 20px; font-size: 0.72rem; font-weight: 700;
            white-space: nowrap;
        }
        .reserva-card-meta {
            display: flex; gap: 20px; font-size: 0.83rem;
            color: var(--text-muted); margin-bottom: 12px;
        }
        .reserva-card-meta i { color: var(--green); margin-right: 5px; }
        .reserva-card-footer {
            display: flex; justify-content: space-between; align-items: center;
            padding-top: 12px; border-top: 1px solid var(--border);
        }
        .reserva-precio { font-weight: 800; font-size: 1rem; }
        .reserva-saldo  { font-size: 0.78rem; color: var(--orange); }
        .btn-cancelar-res {
            padding: 6px 14px; border-radius: 8px; border: 1px solid rgba(231,76,60,0.4);
            background: transparent; color: var(--red); font-size: 0.78rem; cursor: pointer;
            transition: background 0.2s;
        }
        .btn-cancelar-res:hover { background: rgba(231,76,60,0.1); }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            :root { --sidebar-w: 260px; }
            .sidebar { transform: translateX(calc(-1 * var(--sidebar-w))); }
            .sidebar.open { transform: translateX(0); }
            .main { margin-left: 0 !important; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); }
            .predios-grid { grid-template-columns: 1fr; }
            .franjas-grid { grid-template-columns: repeat(2, 1fr); }
            .predio-header-card { flex-direction: column; }
            .cancha-detalle-header { gap: 10px; }
            .reserva-card-meta { flex-wrap: wrap; gap: 10px; }
            .modal-box { padding: 18px 16px; max-height: 88vh; overflow-y: auto; -webkit-overflow-scrolling: touch; }
            .toast { white-space: normal; left: 12px; right: 12px; transform: translateY(20px); width: auto; }
            .toast.show { transform: translateY(0); }
            .slots-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (max-width: 480px) {
            .content { padding: 12px; }
            .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
            .kpi-value { font-size: 22px; }
            .greeting h1 { font-size: 18px; }
            .topbar { padding: 0 12px; gap: 8px; }
            .topbar-title { font-size: 13px; }
            .reserva-card { padding: 14px 16px; }
            .reserva-card-header { flex-wrap: wrap; gap: 6px; }
            .reserva-card-footer { flex-direction: column; align-items: flex-start; gap: 8px; }
            .predio-header-card { padding: 14px 16px; gap: 14px; }
            .activity-box { padding: 12px 14px; }
        }
    </style>
</head>
<body>

<div class="bg-blur"></div>
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <img src="../../config/dist/img/loguito_lacanchita.webp" alt="Logo">
        <div>
            <span>La Canchita</span>
            <small>Sistema de gestión</small>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="avatar"><?= strtoupper(substr($nombre, 0, 1)) ?></div>
        <div class="sidebar-user-info">
            <strong><?= htmlspecialchars($nombre) ?></strong>
            <span><?= ['','Administrador','Dueño','Encargado','Empleado','Cliente'][$perfil] ?? 'Cliente' ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">Principal</div>
        <a class="nav-item active" id="nav-dashboard" onclick="showView('dashboard')">
            <i class="fas fa-th-large"></i> Dashboard
        </a>
        <a class="nav-item" id="nav-predios" onclick="showView('predios')">
            <i class="fas fa-map-marker-alt"></i> Predios
        </a>
        <a class="nav-item" id="nav-reservas" onclick="showView('reservas')">
            <i class="fas fa-calendar-check"></i> Mis Reservas
            <?php if($reservasPend > 0): ?>
                <span class="nav-badge"><?= $reservasPend ?></span>
            <?php endif; ?>
        </a>
        <?php if(isset($_SESSION['usuario_id'])): ?>
        <a class="nav-item" id="nav-perfil" onclick="showView('perfil')">
            <i class="fas fa-user-circle"></i> Mi Perfil
        </a>
        <?php endif; ?>

        <?php if($esStaff): ?>
        <div class="nav-section">Gestión</div>
        <a class="nav-item" id="nav-agenda" onclick="showView('agenda')">
            <i class="fas fa-calendar-alt"></i> Agenda del día
        </a>
        <a class="nav-item" id="nav-pagos" onclick="showView('pagos')">
            <i class="fas fa-dollar-sign"></i> Pagos
        </a>
        <?php endif; ?>

        <?php if($esDueno): ?>
        <div class="nav-section">Administración</div>
        <a class="nav-item" href="../../view/maquetaAdmin/Dashboard.php">
            <i class="fas fa-cog"></i> Panel Admin
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <a href="../../logout.php" class="btn-logout">
            <i class="fas fa-sign-out-alt"></i> Cerrar sesión
        </a>
    </div>
</aside>

<!-- MAIN -->
<div class="main" id="main">

    <div class="topbar">
        <button class="btn-toggle" onclick="toggleSidebar()">
            <i class="fas fa-bars"></i>
        </button>
        <span class="topbar-title" id="topbarTitle">Dashboard</span>
        <div class="topbar-actions">
            <a href="../../logout.php" class="topbar-btn" title="Cerrar sesión">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>

    <div class="content">

        <!-- ══ DASHBOARD ══ -->
        <div id="view-dashboard">
            <div class="greeting fade-up">
                <h1><?= $saludo ?>, <?= htmlspecialchars(explode(' ', $nombre)[0]) ?> 👋</h1>
                <p><?= date('l j \d\e F \d\e Y') ?> &mdash; <?= $totalComplejos ?> predio<?= $totalComplejos != 1 ? 's' : '' ?>, <?= $totalCanchas ?> cancha<?= $totalCanchas != 1 ? 's' : '' ?> activa<?= $totalCanchas != 1 ? 's' : '' ?></p>
            </div>

            <div class="kpi-grid">
                <div class="kpi-card" style="animation-delay:0s;cursor:pointer" onclick="showView('predios')">
                    <div class="kpi-top"><div class="kpi-icon green"><i class="fas fa-map-marker-alt"></i></div></div>
                    <div class="kpi-value"><?= $totalComplejos ?></div>
                    <div class="kpi-label">Predios</div>
                </div>
                <div class="kpi-card" style="animation-delay:0.05s;cursor:pointer" onclick="showView('predios')">
                    <div class="kpi-top"><div class="kpi-icon blue"><i class="fas fa-futbol"></i></div></div>
                    <div class="kpi-value"><?= $totalCanchas ?></div>
                    <div class="kpi-label">Canchas activas</div>
                </div>
                <div class="kpi-card" style="animation-delay:0.1s;cursor:pointer" onclick="showView('reservas')">
                    <div class="kpi-top"><div class="kpi-icon orange"><i class="fas fa-calendar-check"></i></div></div>
                    <div class="kpi-value"><?= $reservasHoy ?></div>
                    <div class="kpi-label">Reservas hoy</div>
                </div>
                <?php if($esStaff): ?>
                <div class="kpi-card" style="animation-delay:0.15s">
                    <div class="kpi-top"><div class="kpi-icon green"><i class="fas fa-dollar-sign"></i></div></div>
                    <div class="kpi-value">$<?= number_format($ingresosHoy, 0, ',', '.') ?></div>
                    <div class="kpi-label">Ingresos hoy</div>
                </div>
                <?php endif; ?>
            </div>

            <div class="section-header">
                <h2>Predios disponibles</h2>
                <button class="link-btn" onclick="showView('predios')">Ver todos <i class="fas fa-arrow-right"></i></button>
            </div>
            <div id="dashPrediosGrid" class="predios-grid">
                <div class="spinner-wrap"><div class="spinner"></div><span>Cargando predios…</span></div>
            </div>
        </div>

        <!-- ══ PREDIOS ══ -->
        <div id="view-predios" style="display:none">
            <div class="greeting fade-up">
                <h1>Predios</h1>
                <p>Todos los predios con sus canchas disponibles</p>
            </div>
            <div id="prediosGrid" class="predios-grid">
                <div class="spinner-wrap"><div class="spinner"></div><span>Cargando…</span></div>
            </div>
        </div>

        <!-- ══ PREDIO DETALLE ══ -->
        <div id="view-predio" style="display:none">
            <div class="breadcrumb fade-up">
                <button class="bc-link" onclick="showView('predios')"><i class="fas fa-map-marker-alt"></i> Predios</button>
                <i class="fas fa-chevron-right" style="font-size:10px"></i>
                <span id="predioDetNombre">—</span>
            </div>
            <div id="predioDetHeader" class="predio-header-card"></div>
            <div class="section-header">
                <h2>Canchas</h2>
            </div>
            <div id="predioDetCanchas" class="canchas-list">
                <div class="spinner-wrap"><div class="spinner"></div><span>Cargando canchas…</span></div>
            </div>
        </div>

        <!-- ══ MI PERFIL ══ -->
        <div id="view-perfil" style="display:none">
            <div class="section-header fade-up">
                <div>
                    <h1><i class="fas fa-user-circle" style="color:var(--green);margin-right:8px"></i>Mi Perfil</h1>
                    <p style="color:var(--text-muted);font-size:0.85rem">Tus datos personales y contraseña</p>
                </div>
            </div>
            <div style="max-width:540px">
                <!-- Mensaje de resultado -->
                <div id="perfilMsg" style="display:none;margin-bottom:16px;padding:12px 16px;border-radius:10px;font-size:0.85rem"></div>

                <form id="frmPerfilCliente" onsubmit="submitPerfilCliente(event)">
                    <div class="activity-box" style="margin-bottom:16px">
                        <!-- Datos personales -->
                        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.4);margin-bottom:12px">
                            Datos personales
                        </div>
                        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
                            <div>
                                <label style="font-size:0.75rem;color:rgba(255,255,255,0.6);display:block;margin-bottom:5px">Nombre *</label>
                                <input type="text" id="pcNombre" name="nombre"
                                       style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);border-radius:9px;color:#fff;font-size:0.85rem;outline:none"
                                       required>
                            </div>
                            <div>
                                <label style="font-size:0.75rem;color:rgba(255,255,255,0.6);display:block;margin-bottom:5px">Apellido *</label>
                                <input type="text" id="pcApellido" name="apellido"
                                       style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);border-radius:9px;color:#fff;font-size:0.85rem;outline:none"
                                       required>
                            </div>
                        </div>
                        <div style="margin-bottom:12px">
                            <label style="font-size:0.75rem;color:rgba(255,255,255,0.6);display:block;margin-bottom:5px">Email *</label>
                            <input type="email" id="pcEmail" name="email"
                                   style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);border-radius:9px;color:#fff;font-size:0.85rem;outline:none"
                                   required>
                        </div>
                        <div>
                            <label style="font-size:0.75rem;color:rgba(255,255,255,0.6);display:block;margin-bottom:5px">Teléfono</label>
                            <input type="text" id="pcTel" name="telefono"
                                   style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);border-radius:9px;color:#fff;font-size:0.85rem;outline:none"
                                   placeholder="Ej: 221 555-1234">
                        </div>
                    </div>

                    <div class="activity-box" style="margin-bottom:16px">
                        <!-- Cambiar contraseña -->
                        <div style="font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.08em;color:rgba(255,255,255,0.4);margin-bottom:12px">
                            Cambiar contraseña <span style="font-weight:400;text-transform:none">(opcional)</span>
                        </div>
                        <div style="margin-bottom:12px">
                            <label style="font-size:0.75rem;color:rgba(255,255,255,0.6);display:block;margin-bottom:5px">Nueva contraseña</label>
                            <input type="password" id="pcPass" name="password" minlength="6"
                                   style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);border-radius:9px;color:#fff;font-size:0.85rem;outline:none"
                                   placeholder="Mínimo 6 caracteres">
                        </div>
                        <div>
                            <label style="font-size:0.75rem;color:rgba(255,255,255,0.6);display:block;margin-bottom:5px">Confirmar contraseña</label>
                            <input type="password" id="pcPass2" name="password2"
                                   style="width:100%;padding:10px 12px;background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);border-radius:9px;color:#fff;font-size:0.85rem;outline:none"
                                   placeholder="Repetí la contraseña">
                        </div>
                    </div>

                    <button type="submit" id="btnPcSubmit"
                            style="width:100%;padding:12px;border-radius:10px;background:#4cd964;color:#000;font-weight:800;font-size:0.9rem;border:none;cursor:pointer">
                        <i class="fas fa-save"></i> Guardar cambios
                    </button>
                </form>
            </div>
        </div>

        <!-- ══ MIS RESERVAS ══ -->
        <div id="view-reservas" style="display:none">
            <div class="section-header fade-up">
                <div>
                    <h1>Mis Reservas</h1>
                    <p style="color:var(--text-muted);font-size:0.85rem">Tu historial de turnos</p>
                </div>
            </div>
            <div id="misReservasContainer"></div>
        </div>

        <?php if($esStaff): ?>
        <!-- ══ AGENDA ══ -->
        <div id="view-agenda" style="display:none">
            <div class="greeting fade-up"><h1>Agenda del día</h1><p><?= date('l j \d\e F') ?></p></div>
            <div class="activity-box">
                <?php
                $hoyR = mysqli_query($link,
                    "SELECT r.*, c.CANCHA_NOMBRE, u.USUARIOS_NOMBRE, u.USUARIOS_APELLIDO
                     FROM reserva r
                     JOIN cancha c ON r.CANCHA_ID = c.CANCHA_ID
                     JOIN usuarios u ON r.USUARIOS_ID = u.USUARIOS_ID
                     WHERE r.RESERVA_FECHA = CURDATE() AND r.ACTIVO = 1
                     ORDER BY r.RESERVA_HORA_INICIO"
                );
                if(mysqli_num_rows($hoyR) === 0):
                ?>
                <div class="activity-empty"><i class="fas fa-calendar-day"></i><p>No hay reservas para hoy.</p></div>
                <?php else: while($r = mysqli_fetch_assoc($hoyR)): ?>
                <div class="reserva-row">
                    <span class="reserva-fecha"><?= substr($r['RESERVA_HORA_INICIO'],0,5) ?></span>
                    <div class="reserva-info">
                        <strong><?= htmlspecialchars($r['CANCHA_NOMBRE']) ?></strong>
                        <span><?= htmlspecialchars($r['USUARIOS_NOMBRE'].' '.$r['USUARIOS_APELLIDO']) ?> &middot; hasta <?= substr($r['RESERVA_HORA_FIN'],0,5) ?></span>
                    </div>
                    <span class="reserva-estado estado-<?= $r['RESERVA_ESTADO'] ?>"><?= ucfirst($r['RESERVA_ESTADO']) ?></span>
                </div>
                <?php endwhile; endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /content -->
</div><!-- /main -->

<!-- MODAL RESERVA (FIX 1) -->
<div class="modal-overlay" id="modalReserva">
    <div class="modal-box">
        <div class="modal-header-row">
            <h3><i class="fas fa-calendar-plus" style="color:var(--green);margin-right:8px"></i>Reservar turno</h3>
            <button class="modal-close" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
        </div>
        <div class="modal-cancha-info">
            <strong id="modalCanchaNombre">—</strong>
            <span id="modalCanchaDetalle">—</span>
        </div>
        <input type="hidden" id="modalFecha">
        <div id="calModalContainer"></div>
        <div class="modal-slots-label"><i class="fas fa-clock" style="margin-right:5px"></i>Horarios disponibles</div>
        <div class="slots-grid" id="modalSlotsGrid">
            <span class="slots-empty">Elegí una fecha para ver los horarios.</span>
        </div>
        <div class="reserva-resumen" id="reservaResumen">Seleccioná un horario</div>
        <div class="modal-footer-row">
            <button class="btn-cancel-modal" onclick="cerrarModal()">Cancelar</button>
            <button class="btn-confirm-modal" id="btnConfirmarReserva" onclick="confirmarReserva()" disabled>
                <i class="fas fa-check" style="margin-right:6px"></i>Confirmar reserva
            </button>
        </div>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toastReserva"></div>

<script>
const DIAS = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];

let prediosCache  = null;
let predioActual  = null;
let canchaResId   = null;

// ── SIDEBAR ──
function toggleSidebar() {
    const sb      = document.getElementById('sidebar');
    const ov      = document.getElementById('sidebarOverlay');
    const mobile  = window.innerWidth < 768;
    if (mobile) { sb.classList.toggle('open'); ov.classList.toggle('show'); }
    else        { sb.classList.toggle('collapsed'); document.getElementById('main').classList.toggle('expanded'); }
}

// ── VISTAS ──
let _prediosData = []; // global para evitar pasar JSON en onclicks
const VIEWS  = ['dashboard','predios','predio','reservas','agenda','pagos','perfil'];
const TITLES = { dashboard:'Dashboard', predios:'Predios', predio:'Predio', reservas:'Mis Reservas', agenda:'Agenda del día', pagos:'Pagos', perfil:'Mi Perfil' };

function showView(name) {
    VIEWS.forEach(v => { const el = document.getElementById('view-'+v); if(el) el.style.display='none'; });
    const t = document.getElementById('view-'+name);
    if (t) t.style.display = 'block';
    document.getElementById('topbarTitle').textContent = TITLES[name] || name;
    document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
    const nav = document.getElementById('nav-'+name);
    if (nav) nav.classList.add('active');
    if (window.innerWidth < 768) {
        document.getElementById('sidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('show');
    }
    if (name === 'dashboard') renderDashPredios();
    if (name === 'predios')   renderPrediosGrid();
    if (name === 'reservas')  loadMisReservas();
    if (name === 'perfil')    loadPerfilCliente();
}

// ── FETCH PREDIOS ──
async function getPredios() {
    if (prediosCache) return prediosCache;
    try {
        const r = await fetch('api/predios.php?action=listar');
        const j = await r.json();
        if (j.ok) { prediosCache = j.data; _prediosData = j.data; }
    } catch(e) {}
    return prediosCache || [];
}

// ── RENDER CARD PREDIO ──
function predioCardHtml(p, idx, delay) {
    const acts = p.ACTIVIDADES
        ? p.ACTIVIDADES.split('||').map(a => `<span class="act-tag">${escHtml(a)}</span>`).join('')
        : '';
    const loc = [p.LOCALIDAD_NOMBRE, p.PARTIDO_NOMBRE, p.PROVINCIA_NOMBRE].filter(Boolean).join(', ');
    const icon = p.TIPO_COMPLEJO_ICONO || 'fa-map-marker-alt';
    return `
    <div class="predio-card" style="animation-delay:${delay}s" onclick="abrirPredioIdx(${idx})">
        <div class="predio-thumb">
            <i class="fas ${icon} thumb-icon"></i>
            ${p.TIPO_COMPLEJO_NOMBRE ? `<span class="predio-tipo-badge">${escHtml(p.TIPO_COMPLEJO_NOMBRE)}</span>` : ''}
            <span class="predio-canchas-badge"><i class="fas fa-futbol" style="margin-right:4px"></i>${p.TOTAL_CANCHAS} cancha${p.TOTAL_CANCHAS!=1?'s':''}</span>
        </div>
        <div class="predio-body">
            <h3>${escHtml(p.COMPLEJO_NOMBRE)}</h3>
            ${loc ? `<div class="predio-loc"><i class="fas fa-map-pin" style="color:var(--green);font-size:10px"></i>${escHtml(loc)}</div>` : ''}
            ${acts ? `<div class="predio-acts">${acts}</div>` : ''}
        </div>
        <div class="predio-footer">
            <span class="predio-contact">${p.COMPLEJO_TELEFONO ? `<i class="fas fa-phone"></i>${escHtml(p.COMPLEJO_TELEFONO)}` : ''}</span>
            <button class="btn-ver-predio" onclick="event.stopPropagation();abrirPredioIdx(${idx})">
                Ver canchas <i class="fas fa-arrow-right" style="margin-left:5px"></i>
            </button>
        </div>
    </div>`;
}

function abrirPredioIdx(idx) {
    const p = _prediosData[idx];
    if (!p) return;
    abrirPredio(p.COMPLEJO_ID, p.COMPLEJO_NOMBRE, p);
}

async function renderDashPredios() {
    const grid = document.getElementById('dashPrediosGrid');
    const data = await getPredios();
    if (!data.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
            <i class="fas fa-map-marker-alt"></i><h3>Sin predios</h3><p>Aún no hay predios cargados.</p></div>`;
        return;
    }
    grid.innerHTML = data.slice(0,6).map((p,i) => predioCardHtml(p, i, i*0.05)).join('');
}

async function renderPrediosGrid() {
    const grid = document.getElementById('prediosGrid');
    const data = await getPredios();
    if (!data.length) {
        grid.innerHTML = `<div class="empty-state" style="grid-column:1/-1">
            <i class="fas fa-map-marker-alt"></i><h3>Sin predios</h3><p>Aún no hay predios cargados.</p></div>`;
        return;
    }
    grid.innerHTML = data.map((p,i) => predioCardHtml(p, i, i*0.04)).join('');
}

// ── ABRIR PREDIO DETALLE (FIX 6: skeleton inmediato) ──
async function abrirPredio(id, nombre, pDataStr) {
    const p = typeof pDataStr === 'string' ? JSON.parse(pDataStr) : pDataStr;
    predioActual = { id, nombre, obj: p };

    document.getElementById('predioDetNombre').textContent = nombre;

    const loc  = [p.LOCALIDAD_NOMBRE, p.PARTIDO_NOMBRE, p.PROVINCIA_NOMBRE].filter(Boolean).join(', ');
    const acts = p.ACTIVIDADES
        ? p.ACTIVIDADES.split('||').map(a => `<span class="header-tag">${escHtml(a)}</span>`).join('') : '';
    const icon = p.TIPO_COMPLEJO_ICONO || 'fa-map-marker-alt';

    const wspTel = p.COMPLEJO_TELEFONO ? p.COMPLEJO_TELEFONO.replace(/\D/g,'') : '';
    const wspMsg = encodeURIComponent(`Hola! Quiero reservar una cancha en ${nombre}`);
    const wspBtn = wspTel
        ? `<a href="https://wa.me/549${wspTel}?text=${wspMsg}" target="_blank" rel="noopener noreferrer"
              style="display:inline-flex;align-items:center;gap:7px;background:#25D366;color:#fff;
              border-radius:8px;padding:7px 14px;font-size:0.8rem;font-weight:700;text-decoration:none;
              margin-top:10px;transition:opacity 0.2s;" onmouseover="this.style.opacity='.85'" onmouseout="this.style.opacity='1'">
              <svg viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
                <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.528 5.845L.057 23.428a.5.5 0 0 0 .615.612l5.747-1.504A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.95 9.95 0 0 1-5.073-1.38l-.363-.215-3.761.984.999-3.667-.236-.375A9.953 9.953 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
              </svg>Consultar por WhatsApp</a>` : '';

    document.getElementById('predioDetHeader').innerHTML = `
        <div class="predio-header-icon"><i class="fas ${icon}"></i></div>
        <div class="predio-header-info">
            <h2>${escHtml(nombre)}</h2>
            <p class="det-loc"><i class="fas fa-map-pin" style="color:var(--green);margin-right:5px"></i>${escHtml(loc||'Sin ubicación')}</p>
            <div class="predio-header-tags">
                ${p.TIPO_COMPLEJO_NOMBRE ? `<span class="header-tag">${escHtml(p.TIPO_COMPLEJO_NOMBRE)}</span>` : ''}
                <span class="header-tag blue"><i class="fas fa-futbol" style="margin-right:4px"></i>${p.TOTAL_CANCHAS} cancha${p.TOTAL_CANCHAS!=1?'s':''}</span>
                ${acts}
            </div>
            ${wspBtn}
        </div>`;

    // FIX 6: mostrar vista de detalle con skeleton ANTES del fetch
    showView('predio');
    const container = document.getElementById('predioDetCanchas');
    container.innerHTML = `
        <div class="skeleton-loading">
            ${[1,2,3].map(() => `<div class="skeleton-card"></div>`).join('')}
        </div>`;

    try {
        const r = await fetch(`api/predios.php?action=canchas&complejo_id=${id}`);
        const j = await r.json();
        if (!j.ok || !j.data || !j.data.length) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-futbol"></i><h3>Sin canchas</h3><p>Este predio no tiene canchas configuradas.</p></div>`;
            return;
        }
        // Guardar canchas en caché del predio actual para el modal de reserva
        predioActual.canchas = j.data;
        container.innerHTML = j.data.map((c,i) => canchaDetalleHtml(c, i)).join('');
    } catch(e) {
        container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error</h3><p>No se pudieron cargar las canchas.</p></div>`;
    }
}

function canchaDetalleHtml(c, idx) {
    const rawIcon = (c.TIPO_CANCHA_ICONO || 'fas fa-futbol');
    const iconClass = rawIcon.includes('fa-') ? rawIcon : 'fas fa-futbol';
    const franjas = c.franjas && c.franjas.length
        ? `<div class="franjas-grid">${c.franjas.map(f => franjaSlotHtml(f)).join('')}</div>`
        : `<div class="no-franjas"><i class="fas fa-clock"></i>Sin horarios configurados</div>`;
    const desc = c.CANCHA_DESCRIPCION ? escHtml(c.CANCHA_DESCRIPCION) : '';
    return `
    <div class="cancha-detalle-card" style="animation-delay:${idx*0.06}s">
        <div class="cancha-detalle-header">
            <div class="cancha-det-icon"><i class="${iconClass}"></i></div>
            <div class="cancha-det-info">
                <h3>${escHtml(c.CANCHA_NOMBRE)}</h3>
                <span>${desc}</span>
            </div>
            <span class="tipo-pill">${escHtml(c.TIPO_CANCHA_NOMBRE)}</span>
            <button class="btn-reservar-can"
                onclick="abrirReserva(${c.CANCHA_ID},${JSON.stringify(c.CANCHA_NOMBRE)},${JSON.stringify(c.TIPO_CANCHA_NOMBRE)})">
                <i class="fas fa-calendar-plus" style="margin-right:5px"></i>Reservar
            </button>
        </div>
        <div class="franjas-section">
            <div class="franjas-title"><i class="fas fa-clock" style="margin-right:5px"></i>Horarios y precios</div>
            ${franjas}
        </div>
    </div>`;
}

function franjaSlotHtml(f) {
    const diasArr = Array.isArray(f.DIAS)
        ? f.DIAS
        : (f.DIAS ? String(f.DIAS).split(',').map(Number) : []);
    const dias = diasArr.length
        ? diasArr.map(d => DIAS[parseInt(d)]||'').filter(Boolean).join(' · ')
        : '—';
    const ini = (f.FRANJA_HORA_INICIO||'--:--').substring(0,5);
    const fin = (f.FRANJA_HORA_FIN||'--:--').substring(0,5);
    const precio = f.FRANJA_PRECIO
        ? '$' + Number(f.FRANJA_PRECIO).toLocaleString('es-AR')
        : 'Sin precio';
    const dur = f.FRANJA_DURACION ? `/ ${f.FRANJA_DURACION} min` : '';
    return `
    <div class="franja-slot">
        <div class="franja-hora">${ini} – ${fin}</div>
        <div class="franja-dias">${dias}</div>
        <div class="franja-precio">${precio} <small>${dur}</small></div>
    </div>`;
}

// ── MODAL RESERVA (FIX 1 + FIX 5) ──
let franjasModal = [];      // franjas de la cancha actual en modal
let franjaSeleccionada = null;

function abrirReserva(id, nombre, tipo) {
    canchaResId = id;
    franjaSeleccionada = null;
    document.getElementById('modalCanchaNombre').textContent = nombre;
    document.getElementById('modalCanchaDetalle').textContent =
        tipo + (predioActual ? ' · ' + predioActual.nombre : '');

    // Precargar franjas desde caché del predio (ya cargadas al abrir detalle)
    franjasModal = [];
    if (predioActual && predioActual.canchas) {
        const cancha = predioActual.canchas.find(c => c.CANCHA_ID == id);
        if (cancha && cancha.franjas) franjasModal = cancha.franjas;
    }

    const hoy = new Date().toISOString().split('T')[0];
    document.getElementById('modalFecha').value = hoy;
    if (!window._lcCal) {
        window._lcCal = new CalendarioLC('calModalContainer', function(fecha) {
            document.getElementById('modalFecha').value = fecha;
            actualizarSlots(fecha);
        });
    } else {
        window._lcCal.setDate(hoy);
    }

    actualizarSlots(hoy);
    document.getElementById('modalReserva').classList.add('show');
}

async function actualizarSlots(fechaStr) {
    franjaSeleccionada = null;
    actualizarResumen();

    const grid = document.getElementById('modalSlotsGrid');
    if (!fechaStr) {
        grid.innerHTML = '<span class="slots-empty">Elegí una fecha para ver los horarios.</span>';
        return;
    }

    // Mostrar spinner mientras carga disponibilidad real
    grid.innerHTML = '<div style="text-align:center;padding:16px;color:var(--text-muted)"><i class="fas fa-spinner fa-spin"></i> Verificando disponibilidad…</div>';

    try {
        const r = await fetch(`api/reservas.php?action=disponibilidad&cancha_id=${canchaResId}&fecha=${fechaStr}`);
        const j = await r.json();

        if (!j.ok || !j.data || !j.data.length) {
            grid.innerHTML = '<span class="slots-empty">No hay horarios configurados para este día.</span>';
            return;
        }

        const hoy = new Date().toISOString().split('T')[0];
        const esHoy = fechaStr === hoy;
        const ahora = new Date();

        let slots = j.data;

        // Si es hoy, excluir slots ya terminados
        if (esHoy) {
            slots = slots.filter(f => {
                const [h, m] = (f.FRANJA_HORA_FIN || '00:00').split(':').map(Number);
                const finSlot = new Date();
                finSlot.setHours(h, m, 0, 0);
                return finSlot > ahora;
            });
        }

        if (!slots.length) {
            const msg = esHoy
                ? 'No hay horarios disponibles para hoy. Elegí otra fecha.'
                : 'No hay horarios disponibles para este día.';
            grid.innerHTML = `<span class="slots-empty">${msg}</span>`;
            return;
        }

        grid.innerHTML = slots.map(f => {
            const ini    = (f.FRANJA_HORA_INICIO || '--:--').substring(0, 5);
            const fin    = (f.FRANJA_HORA_FIN    || '--:--').substring(0, 5);
            const precio = f.FRANJA_PRECIO
                ? '$' + Number(f.FRANJA_PRECIO).toLocaleString('es-AR')
                : 'Sin precio';
            const senaBadge = (f.FRANJA_SENA && Number(f.FRANJA_SENA) > 0)
                ? `<span style="display:inline-block;margin-top:3px;font-size:10px;color:var(--orange);font-weight:600">Seña $${Number(f.FRANJA_SENA).toLocaleString('es-AR')}</span>`
                : '';

            if (!f.disponible) {
                const motivo = f.motivo_no_disponible ? escHtml(f.motivo_no_disponible) : 'no disponible';
                return `<button class="slot-btn" disabled title="${motivo}"
                            style="opacity:0.4;cursor:not-allowed;">
                            <span style="text-decoration:line-through">${ini} – ${fin}</span>
                            <span class="slot-price"><i class="fas fa-lock" style="margin-right:3px"></i>${motivo}</span>
                            ${senaBadge}
                        </button>`;
            }

            return `<button class="slot-btn" data-franja-id="${f.FRANJA_ID}"
                        data-ini="${ini}" data-fin="${fin}" data-precio="${precio}"
                        onclick="seleccionarSlot(this, ${JSON.stringify(f)})">
                        ${ini} – ${fin}
                        <span class="slot-price">${precio}/hora</span>
                        ${senaBadge}
                    </button>`;
        }).join('');

    } catch(e) {
        grid.innerHTML = '<span class="slots-empty">Error al cargar disponibilidad. Intentá de nuevo.</span>';
    }
}

function seleccionarSlot(btn, franja) {
    document.querySelectorAll('#modalSlotsGrid .slot-btn').forEach(b => b.classList.remove('selected'));
    btn.classList.add('selected');
    franjaSeleccionada = franja;
    actualizarResumen();
}

function actualizarResumen() {
    const resumen = document.getElementById('reservaResumen');
    const btnConf = document.getElementById('btnConfirmarReserva');
    if (!franjaSeleccionada) {
        resumen.className = 'reserva-resumen';
        resumen.textContent = 'Seleccioná un horario';
        btnConf.disabled = true;
        return;
    }
    const fechaStr = document.getElementById('modalFecha').value;
    const fechaObj = new Date(fechaStr + 'T00:00:00');
    const opciones = { weekday:'long', day:'2-digit', month:'2-digit', year:'numeric' };
    const fechaFmt = fechaObj.toLocaleDateString('es-AR', opciones);
    const ini      = (franjaSeleccionada.FRANJA_HORA_INICIO || '').substring(0, 5);
    const fin      = (franjaSeleccionada.FRANJA_HORA_FIN    || '').substring(0, 5);
    const precio   = franjaSeleccionada.FRANJA_PRECIO
        ? '$' + Number(franjaSeleccionada.FRANJA_PRECIO).toLocaleString('es-AR')
        : '';
    resumen.className = 'reserva-resumen filled';
    resumen.innerHTML = `<strong>${fechaFmt}</strong> &middot; ${ini} – ${fin} &middot; <strong>${precio}</strong>`;
    btnConf.disabled = false;
}

function cerrarModal(modalId) {
    const id = modalId || 'modalReserva';
    const el = document.getElementById(id);
    if (el) el.classList.remove('show');
    canchaResId = null;
    franjaSeleccionada = null;
    // Restaurar botón confirmar por si quedó en estado spinner
    const btn = document.getElementById('btnConfirmarReserva');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-check" style="margin-right:6px"></i>Confirmar reserva';
    }
}

async function confirmarReserva() {
    if (!franjaSeleccionada) return;
    const fecha   = document.getElementById('modalFecha').value;
    const btn     = document.getElementById('btnConfirmarReserva');

    // Spinner en el botón
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px"></i>Confirmando…';

    try {
        const fd = new FormData();
        fd.append('action', 'crear');
        fd.append('cancha_id', canchaResId);
        fd.append('franja_id', franjaSeleccionada.FRANJA_ID);
        fd.append('fecha', fecha);

        const r = await fetch('api/reservas.php', { method: 'POST', body: fd });
        const j = await r.json();

        if (j.ok) {
            cerrarModal('modalReserva');
            mostrarToast('✓ ' + j.msg);
            // Refrescar "Mis reservas" si está activa
            const viewRes = document.getElementById('view-reservas');
            if (viewRes && viewRes.style.display !== 'none') loadMisReservas();
        } else {
            mostrarToast('Error: ' + j.msg, 'error');
            // Si el slot ya estaba reservado, recargar slots y limpiar selección
            if (j.msg && j.msg.toLowerCase().includes('reserv')) {
                actualizarSlots(fecha);
            }
            // Restaurar botón
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check" style="margin-right:6px"></i>Confirmar reserva';
        }
    } catch(e) {
        mostrarToast('Error de conexión. Intentá de nuevo.', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check" style="margin-right:6px"></i>Confirmar reserva';
    }
}

function mostrarToast(msg, tipo = 'success') {
    const t = document.getElementById('toastReserva');
    t.textContent = msg;
    t.style.background = tipo === 'error' ? 'rgba(231,76,60,0.95)' : 'rgba(37,211,102,0.95)';
    t.classList.add('show');
    setTimeout(() => t.classList.remove('show'), 4000);
}

// ── MIS RESERVAS ──
async function loadMisReservas() {
    const container = document.getElementById('misReservasContainer');
    if (!container) return;

    // Skeleton de carga
    container.innerHTML = [1,2,3].map(() => '<div class="skeleton-card"></div>').join('');

    try {
        const r = await fetch('api/reservas.php?action=mis_reservas');
        const j = await r.json();

        if (!j.ok || !j.data || !j.data.length) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-calendar-times"></i>
                    <h3>Sin reservas</h3>
                    <p>Todavía no tenés reservas. ¡Buscá una cancha y reservá!</p>
                </div>`;
            return;
        }

        container.innerHTML = j.data.map(res => reservaCardHtml(res)).join('');

    } catch(e) {
        container.innerHTML = `
            <div class="empty-state">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Error</h3>
                <p>No se pudieron cargar tus reservas. Intentá de nuevo.</p>
            </div>`;
    }
}

function reservaCardHtml(res) {
    const estado   = (res.RESERVA_ESTADO || 'pendiente').toLowerCase();
    const estadoLabel = { pendiente: 'PENDIENTE 🟡', confirmada: 'CONFIRMADA ✅', cancelada: 'CANCELADA ❌' };
    const badgeClass = { pendiente: 'estado-pendiente', confirmada: 'estado-confirmada', cancelada: 'estado-cancelada' };

    // Formatear fecha
    const fechaObj   = new Date(res.RESERVA_FECHA + 'T00:00:00');
    const opcDia     = { weekday:'long', day:'numeric', month:'short', year:'numeric' };
    const fechaFmt   = fechaObj.toLocaleDateString('es-AR', opcDia);

    const horaIni = (res.RESERVA_HORA_INICIO || '--:--').substring(0, 5);
    const horaFin = (res.RESERVA_HORA_FIN    || '--:--').substring(0, 5);

    const precio  = res.RESERVA_PRECIO
        ? '$' + Number(res.RESERVA_PRECIO).toLocaleString('es-AR')
        : '—';

    const saldo   = parseFloat(res.SALDO_PENDIENTE || 0);
    const saldoHtml = (saldo > 0 && estado === 'confirmada')
        ? `<span class="reserva-saldo">Pendiente de pago: $${saldo.toLocaleString('es-AR')}</span>`
        : (saldo > 0 ? `<span class="reserva-saldo">Saldo: $${saldo.toLocaleString('es-AR')}</span>` : '');

    const btnCancelar = (estado === 'pendiente')
        ? `<button class="btn-cancelar-res" onclick="cancelarReserva(${res.RESERVA_ID})">
               <i class="fas fa-times" style="margin-right:4px"></i>Cancelar
           </button>`
        : '';

    const tipo = escHtml(res.TIPO_CANCHA_NOMBRE || '');
    const cancha = escHtml(res.CANCHA_NOMBRE || '');

    return `
    <div class="reserva-card">
        <div class="reserva-card-header">
            <div>
                <div class="reserva-card-title">
                    <i class="fas fa-futbol" style="color:var(--blue);margin-right:6px"></i>${tipo} · ${cancha}
                </div>
                <div class="reserva-card-predio">
                    ${escHtml(res.COMPLEJO_NOMBRE)} — ${escHtml(res.COMPLEJO_DIRECCION || '')}
                </div>
            </div>
            <span class="reserva-estado-badge ${badgeClass[estado] || 'estado-pendiente'}">
                ${estadoLabel[estado] || estado.toUpperCase()}
            </span>
        </div>
        <div class="reserva-card-meta">
            <span><i class="fas fa-calendar-alt"></i>${fechaFmt}</span>
            <span><i class="fas fa-clock"></i>${horaIni} - ${horaFin}</span>
            ${res.COMPLEJO_TELEFONO ? `<span><i class="fas fa-phone"></i>${escHtml(res.COMPLEJO_TELEFONO)}</span>` : ''}
        </div>
        <div class="reserva-card-footer">
            <div>
                <span class="reserva-precio">${precio}</span>
                ${saldoHtml}
            </div>
            ${btnCancelar}
        </div>
    </div>`;
}

async function cancelarReserva(id) {
    if (!confirm('¿Cancelar esta reserva?')) return;
    const fd = new FormData();
    fd.append('action', 'cancelar');
    fd.append('reserva_id', id);
    const r = await fetch('api/reservas.php', { method: 'POST', body: fd });
    const j = await r.json();
    mostrarToast(j.ok ? 'Reserva cancelada.' : j.msg, j.ok ? 'success' : 'error');
    if (j.ok) loadMisReservas();
}

document.getElementById('modalReserva').addEventListener('click', function(e) { if(e.target===this) cerrarModal(); });

// ── CALENDARIO VISUAL ──
class CalendarioLC {
    constructor(containerId, onSelect) {
        this.el       = document.getElementById(containerId);
        this.onSelect = onSelect;
        this._today   = new Date(); this._today.setHours(0,0,0,0);
        this.selected = new Date(this._today);
        this.current  = new Date(this._today.getFullYear(), this._today.getMonth(), 1);
        this._render();
    }
    setDate(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        this.selected = d;
        this.current  = new Date(d.getFullYear(), d.getMonth(), 1);
        this._render();
    }
    prevMonth() {
        const floor = new Date(this._today.getFullYear(), this._today.getMonth(), 1);
        if (this.current <= floor) return;
        this.current.setMonth(this.current.getMonth() - 1);
        this._render();
    }
    nextMonth() {
        this.current.setMonth(this.current.getMonth() + 1);
        this._render();
    }
    pick(y, m, d) {
        const date = new Date(y, m, d);
        if (date < this._today) return;
        this.selected = date;
        const yyyy = date.getFullYear();
        const mm   = String(date.getMonth() + 1).padStart(2, '0');
        const dd   = String(date.getDate()).padStart(2, '0');
        this._render();
        this.onSelect(`${yyyy}-${mm}-${dd}`);
    }
    _render() {
        const MESES = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        const DIA_L = ['L','M','X','J','V','S','D'];
        const y = this.current.getFullYear(), m = this.current.getMonth();
        const offset   = (new Date(y, m, 1).getDay() + 6) % 7;
        const daysInM  = new Date(y, m + 1, 0).getDate();
        const todayMs  = this._today.getTime();
        const selMs    = this.selected ? this.selected.getTime() : -1;
        const floor    = new Date(this._today.getFullYear(), this._today.getMonth(), 1);
        const canPrev  = this.current > floor;

        let cells = '<span></span>'.repeat(offset);
        for (let d = 1; d <= daysInM; d++) {
            const ms   = new Date(y, m, d).getTime();
            const past = ms < todayMs;
            const cls  = ['lc-cal-day', ms===todayMs?'today':'', ms===selMs?'selected':''].filter(Boolean).join(' ');
            const act  = past ? 'disabled' : `onclick="window._lcCal.pick(${y},${m},${d})"`;
            cells += `<button class="${cls}" ${act}>${d}</button>`;
        }

        this.el.innerHTML = `
<div class="lc-cal">
  <div class="lc-cal-head">
    <button class="lc-cal-nav" onclick="window._lcCal.prevMonth()" ${canPrev?'':'disabled'}><i class="fas fa-chevron-left"></i></button>
    <span class="lc-cal-title">${MESES[m]} ${y}</span>
    <button class="lc-cal-nav" onclick="window._lcCal.nextMonth()"><i class="fas fa-chevron-right"></i></button>
  </div>
  <div class="lc-cal-week">${DIA_L.map(d=>`<span>${d}</span>`).join('')}</div>
  <div class="lc-cal-grid">${cells}</div>
</div>`;
    }
}

// ── UTIL ──
function escHtml(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ══════════════════════════════
//  MI PERFIL (cliente)
// ══════════════════════════════
async function loadPerfilCliente() {
    const r = await fetch('api/perfil.php?action=get');
    const j = await r.json();
    if (!j.ok) { mostrarToast(j.msg || 'Error al cargar perfil', 'error'); return; }
    const d = j.data;
    document.getElementById('pcNombre').value   = d.USUARIOS_NOMBRE   || '';
    document.getElementById('pcApellido').value = d.USUARIOS_APELLIDO || '';
    document.getElementById('pcEmail').value    = d.USUARIOS_EMAIL    || '';
    document.getElementById('pcTel').value      = d.USUARIOS_TELEFONO || '';
    document.getElementById('pcPass').value  = '';
    document.getElementById('pcPass2').value = '';
    const msg = document.getElementById('perfilMsg');
    if (msg) msg.style.display = 'none';
}

async function submitPerfilCliente(e) {
    e.preventDefault();
    const pass  = document.getElementById('pcPass').value;
    const pass2 = document.getElementById('pcPass2').value;
    if (pass && pass !== pass2) {
        mostrarToast('Las contraseñas no coinciden', 'error');
        return;
    }
    const btn = document.getElementById('btnPcSubmit');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';

    const fd = new FormData(e.target);
    fd.append('action', 'update');
    const r = await fetch('api/perfil.php', { method: 'POST', body: fd });
    const j = await r.json();
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Guardar cambios';

    const msg = document.getElementById('perfilMsg');
    if (j.ok) {
        mostrarToast('Perfil actualizado', 'success');
        if (msg) {
            msg.style.display    = 'block';
            msg.style.background = 'rgba(76,217,100,0.12)';
            msg.style.border     = '1px solid rgba(76,217,100,0.25)';
            msg.style.color      = '#4cd964';
            msg.textContent      = '✓ ' + j.msg;
        }
        document.getElementById('pcPass').value  = '';
        document.getElementById('pcPass2').value = '';
    } else {
        mostrarToast(j.msg, 'error');
        if (msg) {
            msg.style.display    = 'block';
            msg.style.background = 'rgba(231,76,60,0.12)';
            msg.style.border     = '1px solid rgba(231,76,60,0.25)';
            msg.style.color      = '#e74c3c';
            msg.textContent      = '✗ ' + j.msg;
        }
    }
}

// ── INIT ──
if (window.innerWidth < 768) document.getElementById('sidebar').classList.remove('open');
renderDashPredios();
</script>

</body>
</html>
