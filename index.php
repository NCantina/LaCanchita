<?php
session_start();
require_once 'config/dist/script/php/conn.php';

// ── CONFIGURACIÓN COMERCIAL ────────────────────────────────────────────────
define('WSP_COMERCIAL', '5491100000000');  // ← Reemplazá con tu número (formato internacional, sin +)
define('WSP_MENSAJE',   'Hola! Quiero saber más sobre LaCanchita para mi predio.');

$usuarioLogueado = isset($_SESSION['usuario_id']);
$usuarioNombre   = $_SESSION['usuario_nombre'] ?? '';
$usuarioPerfil   = (int)($_SESSION['usuario_perfil'] ?? 0);

$totalPredios  = 0;
$totalCanchas  = 0;
$reservasMes   = 0;
$totalCiudades = 0;

// Geo data para buscador (mismo patrón que el admin)
$geoProvincias  = [];
$geoPartidos    = [];
$geoLocalidades = [];
if (isset($link)) {
    $q = mysqli_query($link,"SELECT PROVINCIA_ID,PROVINCIA_NOMBRE FROM provincia WHERE ACTIVO=1 ORDER BY PROVINCIA_NOMBRE");
    while($r=mysqli_fetch_assoc($q)) $geoProvincias[]=$r;
    $q = mysqli_query($link,"SELECT PARTIDO_ID,PARTIDO_NOMBRE,PROVINCIA_ID FROM partido WHERE ACTIVO=1 ORDER BY PARTIDO_NOMBRE");
    while($r=mysqli_fetch_assoc($q)) $geoPartidos[]=$r;
    $q = mysqli_query($link,"SELECT LOCALIDAD_ID,LOCALIDAD_NOMBRE,PARTIDO_ID FROM localidad WHERE ACTIVO=1 ORDER BY LOCALIDAD_NOMBRE");
    while($r=mysqli_fetch_assoc($q)) $geoLocalidades[]=$r;
}

if (isset($link)) {
    $r = mysqli_query($link, "SELECT COUNT(*) c FROM complejo WHERE ACTIVO=1");
    if ($r) $totalPredios = mysqli_fetch_assoc($r)['c'] ?? 0;

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM cancha WHERE ACTIVO=1");
    if ($r) $totalCanchas = mysqli_fetch_assoc($r)['c'] ?? 0;

    $r = mysqli_query($link, "SELECT COUNT(*) c FROM reserva WHERE MONTH(RESERVA_FECHA)=MONTH(NOW()) AND ACTIVO=1");
    if ($r) $reservasMes = mysqli_fetch_assoc($r)['c'] ?? 0;

    $r = mysqli_query($link, "SELECT COUNT(DISTINCT l.LOCALIDAD_ID) c FROM complejo co LEFT JOIN localidad l ON l.LOCALIDAD_ID=co.LOCALIDAD_ID WHERE co.ACTIVO=1");
    if ($r) $totalCiudades = mysqli_fetch_assoc($r)['c'] ?? 0;

    // Canchas destacadas
    $canchasQuery = mysqli_query($link, "
        SELECT c.CANCHA_ID, c.CANCHA_NOMBRE, c.CANCHA_DESCRIPCION,
               comp.COMPLEJO_NOMBRE, comp.COMPLEJO_DIRECCION,
               l.LOCALIDAD_NOMBRE,
               tc.TIPO_CANCHA_NOMBRE,
               (SELECT MIN(fh.FRANJA_PRECIO) FROM franja_horaria fh
                WHERE fh.CANCHA_ID = c.CANCHA_ID AND fh.ACTIVO = 1) AS PRECIO_DESDE
        FROM cancha c
        INNER JOIN complejo comp ON c.COMPLEJO_ID = comp.COMPLEJO_ID
        LEFT JOIN localidad l  ON l.LOCALIDAD_ID  = comp.LOCALIDAD_ID
        LEFT JOIN tipo_cancha tc ON c.TIPO_CANCHA_ID = tc.TIPO_CANCHA_ID
        WHERE c.ACTIVO = 1 AND comp.ACTIVO = 1
        ORDER BY c.CANCHA_ID DESC
        LIMIT 6
    ");
    $canchas = [];
    if ($canchasQuery) {
        while ($row = mysqli_fetch_assoc($canchasQuery)) {
            $canchas[] = $row;
        }
    }

    // Predios miembros de la comunidad
    $prediosQuery = mysqli_query($link, "
        SELECT co.COMPLEJO_ID, co.COMPLEJO_NOMBRE, co.COMPLEJO_DIRECCION,
               l.LOCALIDAD_NOMBRE, p.PARTIDO_NOMBRE,
               COUNT(DISTINCT c.CANCHA_ID) AS TOTAL_CANCHAS,
               GROUP_CONCAT(DISTINCT tc.TIPO_CANCHA_NOMBRE ORDER BY tc.TIPO_CANCHA_NOMBRE SEPARATOR ',') AS DEPORTES,
               MIN(fh.FRANJA_PRECIO) AS PRECIO_DESDE
        FROM complejo co
        LEFT JOIN localidad l ON l.LOCALIDAD_ID = co.LOCALIDAD_ID
        LEFT JOIN partido p ON p.PARTIDO_ID = l.PARTIDO_ID
        LEFT JOIN cancha c ON c.COMPLEJO_ID = co.COMPLEJO_ID AND c.ACTIVO = 1
        LEFT JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = c.TIPO_CANCHA_ID
        LEFT JOIN franja_horaria fh ON fh.CANCHA_ID = c.CANCHA_ID AND fh.ACTIVO = 1
        WHERE co.ACTIVO = 1
        GROUP BY co.COMPLEJO_ID
        ORDER BY TOTAL_CANCHAS DESC, co.COMPLEJO_NOMBRE ASC
        LIMIT 8
    ");
    $prediosMiembros = [];
    if ($prediosQuery) {
        while ($row = mysqli_fetch_assoc($prediosQuery)) {
            $prediosMiembros[] = $row;
        }
    }
}

// Icono según tipo de cancha
function iconoTipo($tipo) {
    $tipo = strtolower($tipo ?? '');
    if (strpos($tipo, 'f') !== false && strpos($tipo, 'tbol') !== false) return 'fa-futbol';
    if (strpos($tipo, 'padel') !== false || strpos($tipo, 'pádel') !== false) return 'fa-table-tennis';
    if (strpos($tipo, 'tenis') !== false) return 'fa-table-tennis';
    if (strpos($tipo, 'basket') !== false || strpos($tipo, 'básquet') !== false) return 'fa-basketball-ball';
    if (strpos($tipo, 'voley') !== false || strpos($tipo, 'vóley') !== false) return 'fa-volleyball-ball';
    return 'fa-running';
}

function colorTipo($tipo) {
    $tipo = strtolower($tipo ?? '');
    if (strpos($tipo, 'f') !== false && strpos($tipo, 'tbol') !== false) return '#4cd964';
    if (strpos($tipo, 'padel') !== false || strpos($tipo, 'pádel') !== false) return '#3498db';
    if (strpos($tipo, 'tenis') !== false) return '#ff9500';
    if (strpos($tipo, 'basket') !== false) return '#e74c3c';
    if (strpos($tipo, 'voley') !== false) return '#9b59b6';
    return '#3498db';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0">
    <title>La Canchita — Reservá tu cancha online</title>
    <meta name="description" content="Reservá canchas de fútbol, pádel, tenis y más en segundos. La plataforma líder en Argentina.">
    <meta name="author" content="EFEGENE DesarrollosWeb">
    <link rel="shortcut icon" href="config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
    <link rel="stylesheet" href="config/pluggins/vendor/fontawesome-free/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:      #4cd964;
            --green-dark: #34c759;
            --blue:       #3498db;
            --orange:     #ff9500;
            --red:        #e74c3c;
            --bg:         #0d0d0d;
            --surface:    rgba(255,255,255,0.06);
            --surface-2:  rgba(255,255,255,0.10);
            --border:     rgba(255,255,255,0.10);
            --text:       #ffffff;
            --text-muted: rgba(255,255,255,0.45);
        }

        html { scroll-behavior: smooth; }

        body {
            background: var(--bg);
            color: var(--text);
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* ============ NAVBAR ============ */
        .navbar {
            position: fixed;
            top: 0; left: 0; right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 5%;
            height: 68px;
            background: rgba(13,13,13,0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
            transition: background 0.3s;
        }
        .navbar.scrolled {
            background: rgba(13,13,13,0.95);
        }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: var(--text);
            font-weight: 700;
            font-size: 1.2rem;
            flex-shrink: 0;
        }
        .nav-brand img {
            height: 40px;
            width: auto;
            border-radius: 8px;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 32px;
            list-style: none;
        }
        .nav-links a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.92rem;
            font-weight: 500;
            transition: color 0.2s;
        }
        .nav-links a:hover { color: var(--text); }
        .nav-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .btn-ghost {
            padding: 8px 18px;
            border: 1px solid var(--border);
            background: transparent;
            color: var(--text);
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .btn-ghost:hover {
            border-color: rgba(255,255,255,0.3);
            background: var(--surface);
        }
        .btn-green {
            padding: 8px 18px;
            background: var(--green);
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-green:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
        }

        /* Hamburger */
        .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px;
        }
        .hamburger span {
            display: block;
            width: 24px;
            height: 2px;
            background: var(--text);
            border-radius: 2px;
            transition: all 0.3s;
        }
        .hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .hamburger.open span:nth-child(2) { opacity: 0; }
        .hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }

        .nav-mobile {
            display: none;
            position: absolute;
            top: 68px; left: 0; right: 0;
            background: rgba(13,13,13,0.97);
            border-bottom: 1px solid var(--border);
            padding: 20px 5%;
            backdrop-filter: blur(16px);
            flex-direction: column;
            gap: 16px;
        }
        .nav-mobile.open { display: flex; }
        .nav-mobile a {
            color: var(--text-muted);
            text-decoration: none;
            font-size: 1rem;
            padding: 8px 0;
            border-bottom: 1px solid var(--border);
            transition: color 0.2s;
        }
        .nav-mobile a:hover { color: var(--text); }
        .nav-mobile .mob-actions {
            display: flex;
            gap: 10px;
            padding-top: 8px;
        }
        .nav-mobile .mob-actions a { border: none; padding: 0; }

        /* ============ HERO ============ */
        #inicio {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 5% 60px;
            overflow: hidden;
        }
        .hero-bg {
            position: absolute;
            inset: 0;
            background-image: url('config/dist/img/ESTADIO.webp');
            background-size: cover;
            background-position: center;
            filter: blur(3px) brightness(0.35);
            transform: scale(1.05);
            z-index: 0;
        }
        .hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(to bottom, rgba(13,13,13,0.5) 0%, rgba(13,13,13,0.85) 100%);
            z-index: 1;
        }
        .hero-content {
            position: relative;
            z-index: 2;
            text-align: center;
            max-width: 780px;
            margin: 0 auto;
            animation: fadeUp 0.8s ease both;
        }
        .hero-badge {
            display: inline-block;
            background: rgba(76,217,100,0.15);
            border: 1px solid rgba(76,217,100,0.3);
            color: var(--green);
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            letter-spacing: 0.04em;
            margin-bottom: 20px;
            text-transform: uppercase;
        }
        .hero-title {
            font-size: clamp(2rem, 5vw, 3.6rem);
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 16px;
            letter-spacing: -0.02em;
        }
        .hero-title span { color: var(--green); }
        .hero-subtitle {
            font-size: clamp(1rem, 2vw, 1.2rem);
            color: var(--text-muted);
            margin-bottom: 40px;
            max-width: 560px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Buscador */
        .searcher {
            background: rgba(255,255,255,0.07);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 24px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            grid-template-rows: auto auto;
            gap: 12px;
            align-items: end;
            animation: fadeUp 0.9s 0.2s ease both;
        }
        .searcher-field label {
            display: block;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .searcher-field select,
        .searcher-field input {
            width: 100%;
            background: rgba(255,255,255,0.08);
            border: 1px solid var(--border);
            color: var(--text);
            border-radius: 8px;
            padding: 10px 14px;
            font-size: 0.92rem;
            font-family: inherit;
            outline: none;
            transition: border-color 0.2s, background 0.2s;
        }
        .searcher-field select:focus,
        .searcher-field input:focus {
            border-color: var(--green);
            background: rgba(76,217,100,0.06);
        }
        .searcher-field select option { background: #1a1a1a; color: var(--text); }
        .searcher-field input[type="date"]::-webkit-calendar-picker-indicator {
            filter: invert(1);
            opacity: 0.4;
            cursor: pointer;
        }
        .btn-search {
            width: 100%;
            padding: 11px 20px;
            background: var(--green);
            color: #000;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-search:hover { background: var(--green-dark); transform: translateY(-1px); }

        /* ============ STATS BAR ============ */
        .stats-bar {
            background: var(--surface);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
            padding: 28px 5%;
            animation: fadeUp 0.7s 0.1s ease both;
        }
        .stats-inner {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            text-align: center;
        }
        .stat-item {}
        .stat-number {
            font-size: 2.2rem;
            font-weight: 800;
            color: var(--green);
            line-height: 1;
        }
        .stat-label {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 4px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        /* ============ SECTIONS ============ */
        section {
            padding: 80px 5%;
        }
        .container {
            max-width: 1100px;
            margin: 0 auto;
        }
        .section-header {
            text-align: center;
            margin-bottom: 48px;
            animation: fadeUp 0.7s ease both;
        }
        .section-eyebrow {
            display: inline-block;
            color: var(--green);
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 10px;
        }
        .section-title {
            font-size: clamp(1.6rem, 3vw, 2.4rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        .section-sub {
            color: var(--text-muted);
            font-size: 1rem;
            max-width: 520px;
            margin: 0 auto;
        }

        /* ============ CANCHAS GRID ============ */
        #canchas { background: #111; }
        .canchas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .cancha-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            transition: transform 0.25s, border-color 0.25s, box-shadow 0.25s;
            animation: fadeUp 0.6s ease both;
        }
        .cancha-card:hover {
            transform: translateY(-4px);
            border-color: rgba(255,255,255,0.2);
            box-shadow: 0 12px 40px rgba(0,0,0,0.4);
        }
        .cancha-thumb {
            height: 160px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.8rem;
            position: relative;
            overflow: hidden;
        }
        .cancha-thumb-icon {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            background: rgba(0,0,0,0.35);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255,255,255,0.15);
        }
        .cancha-body { padding: 18px; }
        .cancha-tipo-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 10px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.12);
        }
        .cancha-name {
            font-size: 1.05rem;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .cancha-complejo {
            color: var(--text-muted);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 16px;
        }
        .btn-reservar {
            display: block;
            width: 100%;
            padding: 10px;
            background: transparent;
            border: 1px solid var(--green);
            color: var(--green);
            border-radius: 8px;
            font-size: 0.88rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: background 0.2s, color 0.2s;
        }
        .btn-reservar:hover {
            background: var(--green);
            color: #000;
        }
        .cancha-precio {
            font-size: 0.82rem;
            color: var(--text-muted);
            margin-top: 4px;
            margin-bottom: 0;
        }
        .cancha-precio strong {
            color: var(--green);
            font-size: 0.95rem;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            grid-column: 1 / -1;
            color: var(--text-muted);
        }
        .empty-state i { font-size: 3.5rem; margin-bottom: 16px; display: block; opacity: 0.3; }
        .empty-state p { font-size: 1rem; }

        /* ============ RESULTADOS BÚSQUEDA ============ */
        #resultados {
            display: none;
            background: linear-gradient(180deg, #0a0a0a 0%, #0d0d0d 100%);
            border-top: 2px solid rgba(76,217,100,0.25);
            padding: 60px 5%;
        }
        #resultados.visible { display: block; }
        .resultados-header {
            max-width: 1100px; margin: 0 auto 36px;
            display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;
        }
        .resultados-titulo {
            font-size: 1.5rem; font-weight: 800;
        }
        .resultados-titulo span { color: var(--green); }
        .resultados-meta {
            font-size: 0.85rem; color: var(--text-muted);
            background: rgba(76,217,100,0.08); border: 1px solid rgba(76,217,100,0.2);
            padding: 6px 14px; border-radius: 20px;
        }
        .resultados-grid {
            max-width: 1100px; margin: 0 auto;
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;
        }
        .res-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 18px;
            overflow: hidden;
            transition: border-color .25s, transform .25s, box-shadow .25s;
            animation: fadeUp .45s ease both;
        }
        .res-card:hover {
            border-color: rgba(76,217,100,0.45);
            transform: translateY(-5px);
            box-shadow: 0 12px 36px rgba(76,217,100,0.12);
        }
        .res-thumb {
            height: 110px;
            display: flex; align-items: center; justify-content: center;
            position: relative; overflow: hidden;
        }
        .res-thumb-icon {
            width: 64px; height: 64px; border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.8rem;
            border: 2px solid rgba(255,255,255,0.12);
            backdrop-filter: blur(6px);
        }
        .res-badge-tipo {
            position: absolute; top: 12px; left: 14px;
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em; padding: 3px 10px; border-radius: 20px;
            background: rgba(0,0,0,0.45); backdrop-filter: blur(4px);
        }
        .res-precio-tag {
            position: absolute; top: 12px; right: 14px;
            background: rgba(76,217,100,0.15); border: 1px solid rgba(76,217,100,0.35);
            color: var(--green); font-size: 0.78rem; font-weight: 800;
            padding: 3px 10px; border-radius: 20px;
        }
        .res-body { padding: 18px 20px 20px; }
        .res-name { font-size: 1.1rem; font-weight: 800; margin-bottom: 4px; }
        .res-complejo {
            display: flex; align-items: center; gap: 6px;
            font-size: 0.82rem; color: var(--text-muted); margin-bottom: 14px;
        }
        .res-slots {
            display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 16px; min-height: 28px;
        }
        .res-slot {
            background: rgba(76,217,100,0.1); border: 1px solid rgba(76,217,100,0.3);
            color: var(--green); font-size: 0.75rem; font-weight: 700;
            padding: 4px 10px; border-radius: 6px; cursor: pointer;
            transition: background .15s;
        }
        .res-slot:hover { background: rgba(76,217,100,0.22); }
        .res-slot.ocupado {
            background: rgba(255,255,255,0.04); border-color: rgba(255,255,255,0.1);
            color: var(--text-muted); cursor: default; text-decoration: line-through; opacity: .5;
        }
        .res-no-slots { font-size: 0.8rem; color: var(--text-muted); font-style: italic; }
        .btn-reservar-big {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            width: 100%; padding: 12px;
            background: var(--green); color: #000;
            border: none; border-radius: 10px;
            font-size: 0.92rem; font-weight: 800;
            cursor: pointer; text-decoration: none;
            transition: background .2s, transform .15s, box-shadow .2s;
        }
        .btn-reservar-big:hover {
            background: var(--green-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(76,217,100,0.3);
        }
        .resultados-empty {
            max-width: 1100px; margin: 0 auto;
            text-align: center; padding: 60px 20px;
            color: var(--text-muted);
        }
        .resultados-empty i { font-size: 3rem; margin-bottom: 16px; display: block; opacity: .3; }
        .resultados-empty p { font-size: 1rem; }

        /* ============ COMUNIDAD PREDIOS ============ */
        #comunidad {
            background: linear-gradient(135deg, rgba(13,13,13,1) 0%, rgba(16,20,16,1) 100%);
            border-top: 1px solid var(--border);
        }
        .predios-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
            gap: 18px;
        }
        .predio-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 22px 20px;
            display: flex; flex-direction: column; gap: 12px;
            transition: border-color .25s, transform .25s, box-shadow .25s;
            animation: fadeUp .5s ease both;
        }
        .predio-card:hover {
            border-color: rgba(76,217,100,0.35);
            transform: translateY(-4px);
            box-shadow: 0 10px 30px rgba(76,217,100,0.08);
        }
        .predio-card-top {
            display: flex; align-items: flex-start; gap: 14px;
        }
        .predio-avatar {
            width: 50px; height: 50px; border-radius: 12px; flex-shrink: 0;
            background: rgba(76,217,100,0.12); border: 1.5px solid rgba(76,217,100,0.25);
            display: flex; align-items: center; justify-content: center;
            font-size: 1.3rem; font-weight: 800; color: var(--green);
        }
        .predio-info-name { font-size: 0.97rem; font-weight: 800; margin-bottom: 3px; }
        .predio-info-loc {
            font-size: 0.78rem; color: var(--text-muted);
            display: flex; align-items: center; gap: 5px;
        }
        .predio-deportes {
            display: flex; flex-wrap: wrap; gap: 5px;
        }
        .predio-deporte-tag {
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            padding: 2px 8px; border-radius: 10px;
            background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.55);
        }
        .predio-footer {
            display: flex; align-items: center; justify-content: space-between;
            padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.06);
        }
        .predio-canchas {
            font-size: 0.78rem; color: var(--text-muted);
        }
        .predio-canchas strong { color: var(--green); }
        .predio-precio {
            font-size: 0.8rem; color: var(--green); font-weight: 700;
        }
        .predio-miembro-badge {
            display: inline-flex; align-items: center; gap: 5px;
            font-size: 0.68rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: .05em; padding: 2px 8px; border-radius: 10px;
            background: rgba(76,217,100,0.1); border: 1px solid rgba(76,217,100,0.25);
            color: rgba(76,217,100,0.8);
        }
        @media (max-width: 600px) {
            .resultados-grid { grid-template-columns: 1fr; }
            .predios-grid { grid-template-columns: 1fr; }
        }

        /* ============ ADS ============ */
        #publicidad {
            background: linear-gradient(135deg, rgba(13,13,13,1) 0%, rgba(20,20,30,1) 100%);
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }
        .ads-label {
            display: flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
            margin-bottom: 10px;
        }
        .ads-label-text {
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: var(--text-muted);
            background: var(--surface);
            border: 1px solid var(--border);
            padding: 3px 10px;
            border-radius: 4px;
        }
        .ads-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
        }
        .ad-card {
            background: rgba(255,255,255,0.04);
            border: 1px dashed rgba(255,255,255,0.15);
            border-radius: 14px;
            padding: 28px 24px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            transition: border-color 0.2s, background 0.2s;
        }
        .ad-card:hover {
            border-color: rgba(255,255,255,0.28);
            background: rgba(255,255,255,0.06);
        }
        .ad-icon {
            width: 60px;
            height: 60px;
            border-radius: 14px;
            background: rgba(255,149,0,0.1);
            border: 1px solid rgba(255,149,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: var(--orange);
        }
        .ad-card h3 {
            font-size: 1rem;
            font-weight: 700;
            color: var(--text-muted);
        }
        .ad-card p {
            font-size: 0.85rem;
            color: var(--text-muted);
            opacity: 0.7;
        }
        .btn-ad {
            padding: 8px 20px;
            background: transparent;
            border: 1px solid var(--orange);
            color: var(--orange);
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }
        .btn-ad:hover { background: var(--orange); color: #000; }

        /* ============ COMO FUNCIONA ============ */
        #como-funciona { background: #0d0d0d; }
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 32px;
            position: relative;
        }
        .steps-grid::before {
            content: '';
            position: absolute;
            top: 36px;
            left: calc(33.33% - 24px);
            right: calc(33.33% - 24px);
            height: 2px;
            background: linear-gradient(to right, var(--green), rgba(76,217,100,0.2));
        }
        .step-card {
            text-align: center;
            padding: 32px 20px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
            animation: fadeUp 0.6s ease both;
            transition: border-color 0.25s, transform 0.25s;
        }
        .step-card:hover {
            border-color: rgba(76,217,100,0.3);
            transform: translateY(-4px);
        }
        .step-number {
            width: 54px;
            height: 54px;
            border-radius: 50%;
            background: rgba(76,217,100,0.12);
            border: 2px solid rgba(76,217,100,0.3);
            color: var(--green);
            font-size: 1.3rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
        }
        .step-icon { font-size: 1.6rem; margin-bottom: 14px; display: block; }
        .step-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .step-desc {
            font-size: 0.87rem;
            color: var(--text-muted);
            line-height: 1.6;
        }

        /* ============ PARA DUEÑOS ============ */
        #duenos {
            background: linear-gradient(135deg, rgba(76,217,100,0.04) 0%, rgba(52,199,89,0.08) 100%);
            border-top: 1px solid rgba(76,217,100,0.12);
            border-bottom: 1px solid rgba(76,217,100,0.12);
        }
        .duenos-inner {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            align-items: center;
        }
        .duenos-content {}
        .duenos-title {
            font-size: clamp(1.6rem, 3vw, 2.2rem);
            font-weight: 800;
            letter-spacing: -0.02em;
            margin-bottom: 12px;
        }
        .duenos-subtitle {
            color: var(--text-muted);
            font-size: 1rem;
            margin-bottom: 28px;
            line-height: 1.7;
        }
        .benefits-list {
            list-style: none;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 32px;
        }
        .benefits-list li {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 0.9rem;
        }
        .benefit-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            background: rgba(76,217,100,0.1);
            border: 1px solid rgba(76,217,100,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--green);
            font-size: 0.85rem;
            flex-shrink: 0;
        }
        .benefit-text strong { display: block; font-size: 0.88rem; font-weight: 700; }
        .benefit-text span { font-size: 0.8rem; color: var(--text-muted); }
        .btn-cta {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 13px 28px;
            background: var(--green);
            color: #000;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
        }
        .btn-cta:hover {
            background: var(--green-dark);
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(76,217,100,0.25);
        }
        .duenos-visual {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 32px;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }
        .dashboard-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 18px;
        }
        .dashboard-row-label {
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 0.88rem;
            color: var(--text-muted);
        }
        .dashboard-row-label i { color: var(--green); }
        .dashboard-row-value {
            font-size: 1rem;
            font-weight: 700;
            color: var(--green);
        }

        /* ============ FOOTER ============ */
        #contacto {
            background: #080808;
            border-top: 1px solid var(--border);
            padding: 60px 5% 32px;
        }
        .footer-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 48px;
            margin-bottom: 48px;
        }
        .footer-brand-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 14px;
        }
        .footer-brand-logo img { height: 36px; border-radius: 6px; }
        .footer-brand-logo span { font-weight: 700; font-size: 1.1rem; }
        .footer-desc {
            font-size: 0.87rem;
            color: var(--text-muted);
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .footer-socials {
            display: flex;
            gap: 10px;
        }
        .footer-socials a {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            background: var(--surface);
            border: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--text-muted);
            font-size: 0.9rem;
            text-decoration: none;
            transition: background 0.2s, color 0.2s, border-color 0.2s;
        }
        .footer-socials a:hover {
            background: rgba(76,217,100,0.1);
            border-color: rgba(76,217,100,0.3);
            color: var(--green);
        }
        .footer-col-title {
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-muted);
            margin-bottom: 16px;
        }
        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .footer-links a {
            color: rgba(255,255,255,0.55);
            text-decoration: none;
            font-size: 0.88rem;
            transition: color 0.2s;
        }
        .footer-links a:hover { color: var(--text); }
        .footer-bottom {
            max-width: 1100px;
            margin: 0 auto;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.82rem;
            color: var(--text-muted);
        }

        /* ============ TOAST ============ */
        .toast {
            position: fixed;
            bottom: 24px;
            right: 24px;
            z-index: 9999;
            background: rgba(30,30,30,0.95);
            border: 1px solid var(--border);
            border-left: 4px solid var(--green);
            border-radius: 10px;
            padding: 14px 20px;
            max-width: 320px;
            backdrop-filter: blur(12px);
            display: flex;
            align-items: flex-start;
            gap: 12px;
            transform: translateY(100px);
            opacity: 0;
            transition: all 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
            pointer-events: none;
        }
        .toast.show { transform: translateY(0); opacity: 1; }
        .toast-icon { color: var(--green); font-size: 1.2rem; margin-top: 1px; }
        .toast-body {}
        .toast-title { font-weight: 700; font-size: 0.9rem; margin-bottom: 3px; }
        .toast-msg { font-size: 0.82rem; color: var(--text-muted); }

        /* ============ ANIMATIONS ============ */
        @keyframes fadeUp {
            from { opacity: 0; transform: translateY(28px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .anim { opacity: 0; transform: translateY(28px); transition: opacity 0.6s ease, transform 0.6s ease; }
        .anim.visible { opacity: 1; transform: translateY(0); }

        /* ============ RESPONSIVE ============ */
        @media (max-width: 900px) {
            .nav-links, .nav-actions { display: none; }
            .hamburger { display: flex; }
            .stats-inner { grid-template-columns: repeat(2, 1fr); }
            .steps-grid { grid-template-columns: 1fr; }
            .steps-grid::before { display: none; }
            .duenos-inner { grid-template-columns: 1fr; gap: 32px; }
            .footer-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
        }
        @media (max-width: 600px) {
            section { padding: 60px 4%; }
            .hero-title { font-size: 2rem; }
            .searcher { grid-template-columns: 1fr; }
            .canchas-grid { grid-template-columns: 1fr; }
            .stats-inner { grid-template-columns: repeat(2, 1fr); gap: 16px; }
            .benefits-list { grid-template-columns: 1fr; }
            .footer-grid { grid-template-columns: 1fr; gap: 28px; }
            .footer-bottom { flex-direction: column; gap: 8px; text-align: center; }
        }
    </style>
</head>
<body>

<!-- ============ NAVBAR ============ -->
<nav class="navbar" id="mainNav">
    <a href="#inicio" class="nav-brand">
        <img src="config/dist/img/loguito_lacanchita.WEBP" alt="La Canchita">
        La Canchita
    </a>
    <ul class="nav-links">
        <li><a href="#inicio">Inicio</a></li>
        <li><a href="#comunidad">Predios</a></li>
        <li><a href="#como-funciona">Cómo funciona</a></li>
        <li><a href="#contacto">Contacto</a></li>
    </ul>
    <div class="nav-actions">
        <?php if ($usuarioLogueado): ?>
            <?php $panelUrl = ($usuarioPerfil === 5) ? 'view/maquetaCliente/LaCanchitaCliente.php' : 'view/maquetaAdmin/Dashboard.php'; ?>
            <a href="<?= $panelUrl ?>" class="btn-ghost" style="display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;border-radius:50%;background:var(--green);color:#000;font-weight:700;font-size:0.82rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= strtoupper(mb_substr($usuarioNombre, 0, 1)) ?></span>
                Mi panel
            </a>
        <?php else: ?>
            <a href="login.php" class="btn-ghost">Iniciar sesión</a>
            <a href="register.php" class="btn-green">Registrarse</a>
        <?php endif; ?>
    </div>
    <button class="hamburger" id="hamburger" aria-label="Menú">
        <span></span><span></span><span></span>
    </button>
</nav>

<!-- Mobile menu -->
<div class="nav-mobile" id="navMobile">
    <a href="#inicio" onclick="closeMobile()">Inicio</a>
    <a href="#comunidad" onclick="closeMobile()">Predios</a>
    <a href="#como-funciona" onclick="closeMobile()">Cómo funciona</a>
    <a href="#contacto" onclick="closeMobile()">Contacto</a>
    <div class="mob-actions">
        <?php if ($usuarioLogueado): ?>
            <?php $panelUrl = ($usuarioPerfil === 5) ? 'view/maquetaCliente/LaCanchitaCliente.php' : 'view/maquetaAdmin/Dashboard.php'; ?>
            <a href="<?= $panelUrl ?>" class="btn-ghost" style="flex:1;text-align:center;display:flex;align-items:center;justify-content:center;gap:8px;">
                <span style="width:26px;height:26px;border-radius:50%;background:var(--green);color:#000;font-weight:700;font-size:0.78rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= strtoupper(mb_substr($usuarioNombre, 0, 1)) ?></span>
                Mi panel
            </a>
        <?php else: ?>
            <a href="login.php" class="btn-ghost" style="flex:1;text-align:center;">Iniciar sesión</a>
            <a href="register.php" class="btn-green" style="flex:1;text-align:center;">Registrarse</a>
        <?php endif; ?>
    </div>
</div>

<!-- ============ HERO ============ -->
<section id="inicio">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <span class="hero-badge"><i class="fas fa-bolt"></i> &nbsp;Reservas online</span>
        <h1 class="hero-title">
            Encontrá tu cancha,<br>
            <span>jugá cuando quieras</span>
        </h1>
        <p class="hero-subtitle">
            Reservá canchas de fútbol, pádel, tenis y más en segundos. Sin llamadas, sin esperas.
        </p>

        <!-- Buscador -->
        <form class="searcher" onsubmit="handleSearch(event)">
            <div class="searcher-field">
                <label for="s-deporte"><i class="fas fa-futbol"></i> &nbsp;Deporte</label>
                <select id="s-deporte" name="deporte">
                    <option value="">Todos los deportes</option>
                    <option value="futbol">Fútbol</option>
                    <option value="padel">Pádel</option>
                    <option value="tenis">Tenis</option>
                    <option value="basket">Básquet</option>
                    <option value="voley">Vóley</option>
                </select>
            </div>
            <div class="searcher-field">
                <label for="s-provincia"><i class="fas fa-map"></i> &nbsp;Provincia</label>
                <?php
                $bsAsId = '';
                foreach ($geoProvincias as $p) {
                    if (stripos($p['PROVINCIA_NOMBRE'], 'Buenos Aires') !== false
                        && stripos($p['PROVINCIA_NOMBRE'], 'Ciudad') === false
                        && stripos($p['PROVINCIA_NOMBRE'], 'Aut') === false) {
                        $bsAsId = $p['PROVINCIA_ID'];
                        break;
                    }
                }
                ?>
                <select id="s-provincia" name="provincia" onchange="geoFiltrarPartidos()">
                    <option value="">Todas las provincias</option>
                    <?php foreach($geoProvincias as $p): ?>
                    <option value="<?= $p['PROVINCIA_ID'] ?>" <?= ($p['PROVINCIA_ID'] == $bsAsId) ? 'selected' : '' ?>><?= htmlspecialchars($p['PROVINCIA_NOMBRE']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="searcher-field">
                <label for="s-partido"><i class="fas fa-map-marker-alt"></i> &nbsp;Partido</label>
                <select id="s-partido" name="partido" onchange="geoFiltrarLocalidades()" disabled>
                    <option value="">Seleccioná provincia</option>
                </select>
            </div>
            <div class="searcher-field">
                <label for="s-localidad"><i class="fas fa-map-pin"></i> &nbsp;Localidad</label>
                <select id="s-localidad" name="localidad" disabled>
                    <option value="">Seleccioná partido</option>
                </select>
            </div>
            <div class="searcher-field">
                <label for="s-fecha"><i class="fas fa-calendar"></i> &nbsp;Fecha</label>
                <input type="date" id="s-fecha" name="fecha" value="<?= date('Y-m-d') ?>">
            </div>
            <div class="searcher-field">
                <label for="s-horario"><i class="fas fa-clock"></i> &nbsp;Horario</label>
                <select id="s-horario" name="horario">
                    <option value="">Cualquier hora</option>
                    <option>Mañana (8–12h)</option>
                    <option>Tarde (12–18h)</option>
                    <option>Noche (18–24h)</option>
                </select>
            </div>
            <div class="searcher-field">
                <label>&nbsp;</label>
                <button type="submit" class="btn-search">
                    <i class="fas fa-search"></i> Buscar
                </button>
            </div>
        </form>
    </div>
</section>

<!-- ============ RESULTADOS DE BÚSQUEDA ============ -->
<div id="resultados">
    <div class="resultados-header">
        <div class="resultados-titulo">Canchas <span id="res-count-label">disponibles</span></div>
        <div class="resultados-meta" id="res-meta">Buscando...</div>
    </div>
    <div class="resultados-grid" id="res-grid"></div>
    <div class="resultados-empty" id="res-empty" style="display:none">
        <i class="fas fa-search"></i>
        <p>No encontramos canchas con esos filtros.<br>Probá con otra fecha o zona.</p>
    </div>
</div>

<!-- ============ STATS BAR ============ -->
<div class="stats-bar">
    <div class="stats-inner">
        <div class="stat-item anim">
            <div class="stat-number"><?= $totalPredios ?></div>
            <div class="stat-label">Predios activos</div>
        </div>
        <div class="stat-item anim" style="transition-delay:.08s">
            <div class="stat-number"><?= $totalCanchas ?></div>
            <div class="stat-label">Canchas disponibles</div>
        </div>
        <div class="stat-item anim" style="transition-delay:.16s">
            <div class="stat-number"><?= $reservasMes ?></div>
            <div class="stat-label">Reservas este mes</div>
        </div>
        <div class="stat-item anim" style="transition-delay:.24s">
            <div class="stat-number"><?= $totalCiudades ?></div>
            <div class="stat-label">Ciudades</div>
        </div>
    </div>
</div>

<!-- ============ COMUNIDAD LA CANCHITA ============ -->
<section id="comunidad">
    <div class="container">
        <div class="section-header anim">
            <span class="section-eyebrow"><i class="fas fa-shield-alt"></i> &nbsp;Miembros verificados</span>
            <h2 class="section-title">La comunidad <span style="color:var(--green)">La Canchita</span></h2>
            <p class="section-sub">Estos son los predios que confían en nuestra plataforma. Reservá online al instante.</p>
        </div>
        <?php if (empty($prediosMiembros)): ?>
        <div class="empty-state anim">
            <i class="fas fa-building"></i>
            <p>Próximamente los primeros predios miembros</p>
        </div>
        <?php else: ?>
        <div class="predios-grid">
            <?php foreach ($prediosMiembros as $i => $pr):
                $inicial = strtoupper(mb_substr($pr['COMPLEJO_NOMBRE'], 0, 1));
                $deportes = array_filter(array_map('trim', explode(',', $pr['DEPORTES'] ?? '')));
            ?>
            <div class="predio-card anim" style="transition-delay:<?= $i * 0.07 ?>s">
                <div class="predio-card-top">
                    <div class="predio-avatar"><?= htmlspecialchars($inicial) ?></div>
                    <div>
                        <div class="predio-info-name"><?= htmlspecialchars($pr['COMPLEJO_NOMBRE']) ?></div>
                        <div class="predio-info-loc">
                            <i class="fas fa-map-marker-alt" style="color:var(--green);font-size:10px"></i>
                            <?= htmlspecialchars($pr['PARTIDO_NOMBRE'] ?: ($pr['LOCALIDAD_NOMBRE'] ?: '')) ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($deportes)): ?>
                <div class="predio-deportes">
                    <?php foreach (array_slice($deportes, 0, 4) as $dep): ?>
                    <span class="predio-deporte-tag"><?= htmlspecialchars($dep) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div class="predio-footer">
                    <div class="predio-canchas">
                        <strong><?= (int)$pr['TOTAL_CANCHAS'] ?></strong> <?= $pr['TOTAL_CANCHAS'] == 1 ? 'cancha' : 'canchas' ?>
                    </div>
                    <?php if (!empty($pr['PRECIO_DESDE'])): ?>
                    <div class="predio-precio">desde $<?= number_format($pr['PRECIO_DESDE'], 0, ',', '.') ?>/h</div>
                    <?php else: ?>
                    <span class="predio-miembro-badge"><i class="fas fa-check-circle"></i> Miembro</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>


<!-- ============ PUBLICIDAD ============ -->
<section id="publicidad">
    <div class="container">
        <div class="section-header anim">
            <div class="ads-label">
                <span class="ads-label-text">Espacios publicitarios</span>
            </div>
            <h2 class="section-title">¿Querés publicitar acá?</h2>
            <p class="section-sub">Llegá a miles de deportistas activos cada día</p>
        </div>
        <div class="ads-grid">
            <!-- WEVOS — Slot 1 -->
            <div class="ad-card anim" style="transition-delay:0s;border-color:rgba(255,193,7,0.25);background:rgba(255,193,7,0.04)">
                <div style="width:90px;height:90px;border-radius:16px;overflow:hidden;background:rgba(255,255,255,0.06);border:1px solid rgba(255,193,7,0.2);display:flex;align-items:center;justify-content:center;">
                    <img src="config/dist/img/wevosmarca.jpg" alt="WEVOS" style="width:100%;height:100%;object-fit:contain;padding:6px"
                         onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                    <div style="display:none;width:100%;height:100%;align-items:center;justify-content:center;color:#ffc107;font-size:1.8rem"><i class="fas fa-egg"></i></div>
                </div>
                <h3 style="color:#fff;font-size:1.3rem;letter-spacing:-.01em">WEVOS</h3>
                <p style="opacity:.85">Tu marca favorita en La Plata. Seguinos y escribinos para más info.</p>
                <div style="display:flex;flex-direction:column;gap:8px;width:100%">
                    <a href="https://www.instagram.com/wevos.lp" target="_blank" rel="noopener noreferrer" class="btn-ad" style="border-color:#e1306c;color:#e1306c;display:flex;align-items:center;justify-content:center;gap:7px">
                        <i class="fab fa-instagram"></i> @wevos.lp
                    </a>
                    <a href="https://wa.me/542212000438?text=Hola%20WEVOS!" target="_blank" rel="noopener noreferrer" class="btn-ad" style="border-color:#25d366;color:#25d366;display:flex;align-items:center;justify-content:center;gap:7px">
                        <i class="fab fa-whatsapp"></i> 221-200-0438
                    </a>
                </div>
            </div>
            <!-- Slot 2 disponible -->
            <div class="ad-card anim" style="transition-delay:.1s">
                <div class="ad-icon">
                    <i class="fas fa-star"></i>
                </div>
                <h3>Tu publicidad aquí</h3>
                <p>Slot #2 disponible — llegá a toda la comunidad deportiva de La Canchita</p>
                <a href="mailto:efegene@domain.com?subject=Publicidad%20La%20Canchita" class="btn-ad">
                    <i class="fas fa-envelope"></i> &nbsp;Contactanos
                </a>
            </div>
            <!-- Slot 3 disponible -->
            <div class="ad-card anim" style="transition-delay:.2s">
                <div class="ad-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Tu publicidad aquí</h3>
                <p>Slot #3 disponible — llegá a toda la comunidad deportiva de La Canchita</p>
                <a href="mailto:efegene@domain.com?subject=Publicidad%20La%20Canchita" class="btn-ad">
                    <i class="fas fa-envelope"></i> &nbsp;Contactanos
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ============ CÓMO FUNCIONA ============ -->
<section id="como-funciona">
    <div class="container">
        <div class="section-header anim">
            <span class="section-eyebrow">Proceso</span>
            <h2 class="section-title">Cómo funciona</h2>
            <p class="section-sub">Tres pasos simples para estar en la cancha</p>
        </div>
        <div class="steps-grid">
            <div class="step-card anim">
                <div class="step-number">1</div>
                <span class="step-icon">🔍</span>
                <div class="step-title">Buscá tu cancha</div>
                <p class="step-desc">Filtrá por deporte, zona y horario. Encontrá la cancha perfecta para vos y tu grupo.</p>
            </div>
            <div class="step-card anim" style="transition-delay:.1s">
                <div class="step-number">2</div>
                <span class="step-icon">📅</span>
                <div class="step-title">Elegí fecha y hora</div>
                <p class="step-desc">Consultá disponibilidad en tiempo real y reservá el turno que mejor te quede.</p>
            </div>
            <div class="step-card anim" style="transition-delay:.2s">
                <div class="step-number">3</div>
                <span class="step-icon">✅</span>
                <div class="step-title">Confirmá tu reserva</div>
                <p class="step-desc">Recibís la confirmación al instante y listo. ¡A jugar!</p>
            </div>
        </div>
    </div>
</section>

<!-- ============ PARA DUEÑOS ============ -->
<section id="duenos">
    <div class="container">
        <div class="duenos-inner">
            <div class="duenos-content anim">
                <span class="section-eyebrow">Para propietarios</span>
                <h2 class="duenos-title">¿Tenés un predio? <br>Digitalizá tu negocio</h2>
                <p class="duenos-subtitle">
                    Sumate a La Canchita y gestioná tus canchas de forma 100% online. Más reservas, menos trabajo manual.
                </p>
                <ul class="benefits-list">
                    <li>
                        <div class="benefit-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="benefit-text">
                            <strong>Reservas 24/7</strong>
                            <span>Tus clientes reservan en cualquier momento</span>
                        </div>
                    </li>
                    <li>
                        <div class="benefit-icon"><i class="fas fa-chart-bar"></i></div>
                        <div class="benefit-text">
                            <strong>Estadísticas en tiempo real</strong>
                            <span>Conocé tu rendimiento al instante</span>
                        </div>
                    </li>
                    <li>
                        <div class="benefit-icon"><i class="fas fa-credit-card"></i></div>
                        <div class="benefit-text">
                            <strong>Pagos digitales</strong>
                            <span>Cobrá de forma segura y automática</span>
                        </div>
                    </li>
                    <li>
                        <div class="benefit-icon"><i class="fas fa-mobile-alt"></i></div>
                        <div class="benefit-text">
                            <strong>Gestión online</strong>
                            <span>Administrá todo desde el celular</span>
                        </div>
                    </li>
                </ul>
                <a href="mailto:efegene@domain.com?subject=Quiero%20sumar%20mi%20predio" class="btn-cta">
                    <i class="fas fa-handshake"></i> Contactanos
                </a>
            </div>
            <div class="duenos-visual anim" style="transition-delay:.15s">
                <div class="dashboard-row">
                    <div class="dashboard-row-label"><i class="fas fa-calendar-check"></i> Reservas hoy</div>
                    <div class="dashboard-row-value">12</div>
                </div>
                <div class="dashboard-row">
                    <div class="dashboard-row-label"><i class="fas fa-dollar-sign"></i> Ingreso del mes</div>
                    <div class="dashboard-row-value">$84.500</div>
                </div>
                <div class="dashboard-row">
                    <div class="dashboard-row-label"><i class="fas fa-users"></i> Clientes nuevos</div>
                    <div class="dashboard-row-value">+28</div>
                </div>
                <div class="dashboard-row">
                    <div class="dashboard-row-label"><i class="fas fa-futbol"></i> Canchas activas</div>
                    <div class="dashboard-row-value">4</div>
                </div>
                <div class="dashboard-row">
                    <div class="dashboard-row-label"><i class="fas fa-star"></i> Puntuación</div>
                    <div class="dashboard-row-value">4.9 ★</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ============ FOOTER ============ -->
<footer id="contacto">
    <div class="footer-grid">
        <div>
            <div class="footer-brand-logo">
                <img src="config/dist/img/loguito_lacanchita.WEBP" alt="La Canchita">
                <span>La Canchita</span>
            </div>
            <p class="footer-desc">
                La plataforma líder en reserva de canchas deportivas. Conectamos jugadores con los mejores predios de Argentina.
            </p>
            <div class="footer-socials">
                <a href="#" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" aria-label="Twitter / X"><i class="fab fa-x-twitter"></i></a>
                <a href="#" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>
        <div>
            <div class="footer-col-title">Plataforma</div>
            <ul class="footer-links">
                <li><a href="#canchas">Canchas</a></li>
                <li><a href="#como-funciona">Cómo funciona</a></li>
                <li><a href="#duenos">Para dueños</a></li>
                <li><a href="register.php">Registrarse</a></li>
            </ul>
        </div>
        <div>
            <div class="footer-col-title">Legal</div>
            <ul class="footer-links">
                <li><a href="#">Términos de uso</a></li>
                <li><a href="#">Privacidad</a></li>
                <li><a href="#">Cookies</a></li>
            </ul>
        </div>
        <div>
            <div class="footer-col-title">Contacto</div>
            <ul class="footer-links">
                <li><a href="mailto:efegene@domain.com"><i class="fas fa-envelope"></i> &nbsp;efegene@domain.com</a></li>
                <li><a href="tel:+542210000000"><i class="fab fa-whatsapp"></i> &nbsp;221-000-0000</a></li>
                <li><a href="#"><i class="fas fa-map-marker-alt"></i> &nbsp;Calle 54 nro. 630, La Plata</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <span>&copy; <?= date('Y') ?> La Canchita — Todos los derechos reservados</span>
        <span>Desarrollado por <strong>EFEGENE DesarrollosWeb</strong></span>
    </div>
</footer>

<!-- ============ TOAST ============ -->
<div class="toast" id="toast" role="alert">
    <span class="toast-icon"><i class="fas fa-info-circle"></i></span>
    <div class="toast-body">
        <div class="toast-title">¡Próximamente!</div>
        <div class="toast-msg">El buscador estará disponible muy pronto. ¡Gracias por tu paciencia!</div>
    </div>
</div>

<script>
// Navbar scroll
const nav = document.getElementById('mainNav');
window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
});

// Hamburger
const hamburger = document.getElementById('hamburger');
const navMobile = document.getElementById('navMobile');
hamburger.addEventListener('click', () => {
    const open = navMobile.classList.toggle('open');
    hamburger.classList.toggle('open', open);
});
function closeMobile() {
    navMobile.classList.remove('open');
    hamburger.classList.remove('open');
}

// ── GEO CASCADA ──────────────────────────────────────────────────────────
const GEO_PROVINCIAS  = <?= json_encode($geoProvincias,  JSON_UNESCAPED_UNICODE) ?>;
const GEO_PARTIDOS    = <?= json_encode($geoPartidos,    JSON_UNESCAPED_UNICODE) ?>;
const GEO_LOCALIDADES = <?= json_encode($geoLocalidades, JSON_UNESCAPED_UNICODE) ?>;
const USUARIO_LOGUEADO = <?= $usuarioLogueado ? 'true' : 'false' ?>;

function geoFiltrarPartidos() {
    const provId   = parseInt(document.getElementById('s-provincia').value) || 0;
    const selPar   = document.getElementById('s-partido');
    const selLoc   = document.getElementById('s-localidad');

    selPar.innerHTML = '<option value="">— Todos los partidos —</option>';
    selLoc.innerHTML = '<option value="">— Seleccioná partido —</option>';
    selLoc.disabled  = true;

    if (!provId) {
        selPar.disabled = true;
        return;
    }
    const partidos = GEO_PARTIDOS.filter(p => p.PROVINCIA_ID == provId);
    partidos.forEach(p => {
        const o = document.createElement('option');
        o.value = p.PARTIDO_ID;
        o.textContent = p.PARTIDO_NOMBRE;
        selPar.appendChild(o);
    });
    selPar.disabled = false;
}

function geoFiltrarLocalidades() {
    const parId  = parseInt(document.getElementById('s-partido').value) || 0;
    const selLoc = document.getElementById('s-localidad');

    selLoc.innerHTML = '<option value="">— Todas las localidades —</option>';

    if (!parId) {
        selLoc.disabled = true;
        return;
    }
    const locs = GEO_LOCALIDADES.filter(l => l.PARTIDO_ID == parId);
    locs.forEach(l => {
        const o = document.createElement('option');
        o.value = l.LOCALIDAD_ID;
        o.textContent = l.LOCALIDAD_NOMBRE;
        selLoc.appendChild(o);
    });
    selLoc.disabled = locs.length === 0;
}

// ── SEARCHER ─────────────────────────────────────────────────────────────
const SPORT_ICONS = {
    'fútbol': 'fa-futbol', 'futbol': 'fa-futbol',
    'pádel': 'fa-table-tennis', 'padel': 'fa-table-tennis',
    'tenis': 'fa-table-tennis',
    'básquet': 'fa-basketball-ball', 'basket': 'fa-basketball-ball',
    'vóley': 'fa-volleyball-ball', 'voley': 'fa-volleyball-ball'
};
const SPORT_COLORS = {
    'fútbol': '#4cd964', 'futbol': '#4cd964',
    'pádel': '#3498db', 'padel': '#3498db',
    'tenis': '#ff9500',
    'básquet': '#e74c3c', 'basket': '#e74c3c',
    'vóley': '#9b59b6', 'voley': '#9b59b6'
};
function sportIcon(t) {
    if (!t) return 'fa-running';
    const tl = t.toLowerCase();
    for (const k in SPORT_ICONS) if (tl.includes(k)) return SPORT_ICONS[k];
    return 'fa-running';
}
function sportColor(t) {
    if (!t) return '#3498db';
    const tl = t.toLowerCase();
    for (const k in SPORT_COLORS) if (tl.includes(k)) return SPORT_COLORS[k];
    return '#3498db';
}
function fmt(n) {
    return '$' + Number(n).toLocaleString('es-AR', {maximumFractionDigits:0});
}
function escHtml(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function handleSearch(e) {
    e.preventDefault();
    const provincia = document.getElementById('s-provincia').value;
    const partido   = document.getElementById('s-partido').value;
    const localidad = document.getElementById('s-localidad').value;
    const deporte   = document.getElementById('s-deporte').value;
    const fecha     = document.getElementById('s-fecha').value;
    const horario   = document.getElementById('s-horario').value;

    const params = new URLSearchParams();
    if (provincia) params.set('provincia', provincia);
    if (partido)   params.set('partido',   partido);
    if (localidad) params.set('localidad', localidad);
    if (deporte)   params.set('deporte',   deporte);
    if (fecha)     params.set('fecha',     fecha);
    if (horario)   params.set('horario',   horario);

    const resDiv  = document.getElementById('resultados');
    const grid    = document.getElementById('res-grid');
    const empty   = document.getElementById('res-empty');
    const meta    = document.getElementById('res-meta');
    const label   = document.getElementById('res-count-label');

    resDiv.classList.add('visible');
    grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:60px 0;color:var(--text-muted)"><i class="fas fa-circle-notch fa-spin" style="font-size:2rem;opacity:.4"></i></div>';
    empty.style.display = 'none';
    meta.textContent = 'Buscando…';
    label.textContent = 'encontradas';

    resDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });

    fetch('api/buscar_canchas.php?' + params.toString())
        .then(r => r.json())
        .then(data => {
            if (!data.ok || !data.canchas || data.canchas.length === 0) {
                grid.innerHTML = '';
                empty.style.display = 'block';
                meta.textContent = 'Sin resultados';
                return;
            }
            const canchas = data.canchas;
            window._resData = canchas; // guardamos para que el botón pueda leerlos sin romper el HTML
            window._resFecha = fecha;
            const fechaLabel = fecha ? new Date(fecha + 'T00:00:00').toLocaleDateString('es-AR', {weekday:'long',day:'numeric',month:'long'}) : '';
            meta.textContent = canchas.length + ' resultado' + (canchas.length > 1 ? 's' : '') + (fechaLabel ? ' · ' + fechaLabel : '');
            label.textContent = 'disponibles';

            grid.innerHTML = canchas.map((c, i) => {
                const color  = sportColor(c.TIPO_CANCHA_NOMBRE);
                const icon   = sportIcon(c.TIPO_CANCHA_NOMBRE);
                const precio = c.PRECIO_DESDE ? `<div class="res-precio-tag">${fmt(c.PRECIO_DESDE)}/h</div>` : '';
                const tipo   = c.TIPO_CANCHA_NOMBRE || 'Cancha';

                // Slots horarios
                let slotsHtml = '';
                if (c.slots && c.slots.length) {
                    slotsHtml = c.slots.slice(0, 6).map(s => {
                        const cls = s.libre ? 'res-slot' : 'res-slot ocupado';
                        return `<span class="${cls}">${escHtml(s.hora)}</span>`;
                    }).join('');
                } else {
                    slotsHtml = '<span class="res-no-slots">Consultá disponibilidad</span>';
                }

                return `<div class="res-card" style="animation-delay:${i*0.07}s">
                    <div class="res-thumb" style="background:linear-gradient(135deg,${color}22 0%,${color}11 100%)">
                        <div class="res-thumb-icon" style="color:${color};border-color:${color}44;background:${color}18">
                            <i class="fas ${icon}"></i>
                        </div>
                        <div class="res-badge-tipo" style="color:${color};border:1px solid ${color}55">${escHtml(tipo)}</div>
                        ${precio}
                    </div>
                    <div class="res-body">
                        <div class="res-name">${escHtml(c.CANCHA_NOMBRE)}</div>
                        <div class="res-complejo">
                            <i class="fas fa-building" style="color:${color};font-size:11px"></i>
                            ${escHtml(c.COMPLEJO_NOMBRE)}
                            ${c.LOCALIDAD_NOMBRE ? ' &mdash; ' + escHtml(c.LOCALIDAD_NOMBRE) : ''}
                        </div>
                        <div class="res-slots">${slotsHtml}</div>
                        <button onclick="iniciarReservaIdx(${i})" class="btn-reservar-big">
                            <i class="fas fa-calendar-check"></i> Reservar ahora
                        </button>
                    </div>
                </div>`;
            }).join('');
        })
        .catch(() => {
            grid.innerHTML = '';
            empty.style.display = 'block';
            meta.textContent = 'Error al buscar';
        });
}

// Scroll animations (IntersectionObserver)
const animEls = document.querySelectorAll('.anim');
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            entry.target.classList.add('visible');
            observer.unobserve(entry.target);
        }
    });
}, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
animEls.forEach(el => observer.observe(el));

// Default date input to today
const fechaInput = document.getElementById('s-fecha');
if (fechaInput && !fechaInput.value) {
    fechaInput.value = new Date().toISOString().split('T')[0];
}

// Pre-cargar partidos de la provincia pre-seleccionada y seleccionar La Plata
(function() {
    const sel = document.getElementById('s-provincia');
    if (!sel || !sel.value) return;
    geoFiltrarPartidos();
    // Pre-seleccionar partido La Plata
    const laPlata = GEO_PARTIDOS.find(p =>
        p.PROVINCIA_ID == sel.value &&
        p.PARTIDO_NOMBRE.toLowerCase().includes('la plata')
    );
    if (laPlata) {
        const selPar = document.getElementById('s-partido');
        selPar.value = laPlata.PARTIDO_ID;
        geoFiltrarLocalidades();
    }
})();
</script>

<!-- ============ MODAL AUTH ============ -->
<div id="modal-auth" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px">
    <div style="background:#141414;border:1px solid rgba(255,255,255,0.12);border-radius:20px;width:100%;max-width:420px;overflow:hidden;animation:fadeUp .3s ease both">
        <!-- Header tabs -->
        <div style="display:flex;border-bottom:1px solid rgba(255,255,255,0.08)">
            <button id="tab-login" onclick="authTab('login')"
                style="flex:1;padding:16px;background:transparent;border:none;color:#fff;font-weight:700;font-size:.92rem;cursor:pointer;border-bottom:2px solid var(--green);transition:color .2s">
                <i class="fas fa-sign-in-alt"></i> &nbsp;Iniciar sesión
            </button>
            <button id="tab-reg" onclick="authTab('reg')"
                style="flex:1;padding:16px;background:transparent;border:none;color:rgba(255,255,255,0.45);font-weight:700;font-size:.92rem;cursor:pointer;border-bottom:2px solid transparent;transition:color .2s">
                <i class="fas fa-user-plus"></i> &nbsp;Registrarse
            </button>
            <button onclick="closeAuthModal()" style="padding:16px 20px;background:transparent;border:none;color:rgba(255,255,255,.4);font-size:1.2rem;cursor:pointer">×</button>
        </div>
        <!-- Login form -->
        <div id="pane-login" style="padding:28px">
            <p style="font-size:.85rem;color:rgba(255,255,255,.45);margin-bottom:20px">Ingresá para confirmar tu reserva</p>
            <div id="auth-err" style="display:none;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.3);border-radius:8px;padding:10px 14px;font-size:.83rem;color:#e74c3c;margin-bottom:16px"></div>
            <div style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="font-size:.75rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px">Email o DNI</label>
                    <input id="l-user" type="text" placeholder="usuario@email.com"
                        style="width:100%;padding:12px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;font-size:.9rem;outline:none;transition:border-color .2s"
                        onfocus="this.style.borderColor='rgba(76,217,100,.5)'" onblur="this.style.borderColor='rgba(255,255,255,.12)'"
                        onkeydown="if(event.key==='Enter')doLogin()">
                </div>
                <div>
                    <label style="font-size:.75rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px">Contraseña</label>
                    <input id="l-pass" type="password" placeholder="••••••••"
                        style="width:100%;padding:12px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;font-size:.9rem;outline:none;transition:border-color .2s"
                        onfocus="this.style.borderColor='rgba(76,217,100,.5)'" onblur="this.style.borderColor='rgba(255,255,255,.12)'"
                        onkeydown="if(event.key==='Enter')doLogin()">
                </div>
                <button onclick="doLogin()" id="btn-login"
                    style="padding:13px;background:var(--green);color:#000;border:none;border-radius:10px;font-weight:800;font-size:.95rem;cursor:pointer;transition:background .2s">
                    Ingresar
                </button>
                <a href="login.php" style="text-align:center;font-size:.8rem;color:rgba(255,255,255,.35);text-decoration:none">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
        <!-- Register form -->
        <div id="pane-reg" style="display:none;padding:28px;max-height:80vh;overflow-y:auto">
            <p style="font-size:.85rem;color:rgba(255,255,255,.45);margin-bottom:20px">Creá tu cuenta gratis en segundos</p>
            <div id="reg-err" style="display:none;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.3);border-radius:8px;padding:10px 14px;font-size:.83rem;color:#e74c3c;margin-bottom:16px"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                <?php
                $rFields = [
                    ['r-nombre','text','Nombre','Juan','col:span 1'],
                    ['r-apellido','text','Apellido','García','col:span 1'],
                    ['r-dni','text','DNI','12345678','col:span 1'],
                    ['r-tel','tel','Teléfono','221-000-0000','col:span 1'],
                    ['r-email','email','Email','juan@email.com','col:span 2'],
                    ['r-pass','password','Contraseña','••••••••','col:span 2'],
                    ['r-pass2','password','Repetir contraseña','••••••••','col:span 2'],
                ];
                foreach ($rFields as $f):
                    $span = $f[4] === 'col:span 2' ? 'grid-column:1/-1;' : '';
                ?>
                <div style="<?= $span ?>display:flex;flex-direction:column;gap:5px">
                    <label style="font-size:.72rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.05em"><?= $f[2] ?></label>
                    <input id="<?= $f[0] ?>" type="<?= $f[1] ?>" placeholder="<?= $f[3] ?>"
                        style="padding:11px 12px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:8px;color:#fff;font-size:.88rem;outline:none;transition:border-color .2s"
                        onfocus="this.style.borderColor='rgba(76,217,100,.5)'" onblur="this.style.borderColor='rgba(255,255,255,.12)'">
                </div>
                <?php endforeach; ?>
            </div>
            <button onclick="doRegister()" id="btn-reg"
                style="margin-top:16px;width:100%;padding:13px;background:var(--green);color:#000;border:none;border-radius:10px;font-weight:800;font-size:.95rem;cursor:pointer;transition:background .2s">
                Crear cuenta y reservar
            </button>
        </div>
    </div>
</div>

<!-- ============ MODAL RESERVA ============ -->
<div id="modal-reserva" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px">
    <div style="background:#141414;border:1px solid rgba(255,255,255,0.12);border-radius:20px;width:100%;max-width:460px;overflow:hidden;animation:fadeUp .3s ease both">
        <!-- Header -->
        <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.08)">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:36px;height:36px;border-radius:9px;background:rgba(76,217,100,.12);border:1px solid rgba(76,217,100,.25);display:flex;align-items:center;justify-content:center;color:var(--green)">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <div style="font-weight:800;font-size:.95rem">Confirmar reserva</div>
                    <div id="res-subtitulo" style="font-size:.75rem;color:rgba(255,255,255,.4)"></div>
                </div>
            </div>
            <button onclick="closeReservaModal()" style="background:transparent;border:none;color:rgba(255,255,255,.4);font-size:1.2rem;cursor:pointer">×</button>
        </div>
        <!-- Resumen -->
        <div style="padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06)">
            <div id="res-info" style="display:grid;grid-template-columns:1fr 1fr;gap:10px"></div>
        </div>
        <!-- Selector horario -->
        <div style="padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.06)">
            <div style="font-size:.75rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">Horario seleccionado</div>
            <div id="res-slots-confirm" style="display:flex;flex-wrap:wrap;gap:8px"></div>
        </div>
        <!-- Método de pago -->
        <div style="padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.06)">
            <div style="font-size:.75rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">¿Cómo vas a pagar?</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px" id="metodos-pago">
                <?php
                $metodos = [
                    ['efectivo','fa-money-bill-wave','Efectivo','En el predio'],
                    ['transferencia','fa-university','Transferencia','Banco / CVU'],
                    ['mercadopago','fa-credit-card','MercadoPago','Online'],
                ];
                foreach ($metodos as $m): ?>
                <label style="cursor:pointer">
                    <input type="radio" name="metodo" value="<?= $m[0] ?>" <?= $m[0]==='efectivo'?'checked':'' ?> style="display:none" onchange="selectMetodo('<?= $m[0] ?>')">
                    <div id="mp-<?= $m[0] ?>" onclick="selectMetodo('<?= $m[0] ?>')"
                        style="padding:12px 8px;border-radius:10px;border:1px solid <?= $m[0]==='efectivo'?'rgba(76,217,100,.5)':'rgba(255,255,255,.1)' ?>;background:<?= $m[0]==='efectivo'?'rgba(76,217,100,.08)':'rgba(255,255,255,.03)' ?>;text-align:center;transition:all .15s">
                        <i class="fas <?= $m[1] ?>" style="font-size:1.2rem;color:<?= $m[0]==='efectivo'?'var(--green)':'rgba(255,255,255,.4)' ?>;display:block;margin-bottom:5px"></i>
                        <div style="font-size:.78rem;font-weight:700;color:<?= $m[0]==='efectivo'?'#fff':'rgba(255,255,255,.5)' ?>"><?= $m[2] ?></div>
                        <div style="font-size:.68rem;color:rgba(255,255,255,.3)"><?= $m[3] ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- Precio -->
        <div style="padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between">
            <div>
                <div id="res-precio-label" style="font-size:1.5rem;font-weight:800;color:var(--green)"></div>
                <div id="res-sena-label" style="font-size:.8rem;color:rgba(255,255,255,.4);margin-top:2px"></div>
            </div>
            <div id="res-err" style="display:none;font-size:.8rem;color:#e74c3c;text-align:right;max-width:200px"></div>
        </div>
        <!-- Acciones -->
        <div style="padding:20px 24px;display:flex;flex-direction:column;gap:10px">
            <button id="btn-confirmar" onclick="confirmarReserva()"
                style="padding:14px;background:var(--green);color:#000;border:none;border-radius:12px;font-weight:800;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .2s">
                <i class="fas fa-check-circle"></i> Confirmar reserva
            </button>
            <button id="btn-wsp-reserva" onclick="reservarPorWsp()"
                style="padding:12px;background:rgba(37,211,102,.1);color:#25d366;border:1px solid rgba(37,211,102,.3);border-radius:12px;font-weight:700;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s">
                <i class="fab fa-whatsapp"></i> Consultar por WhatsApp
            </button>
        </div>
    </div>
</div>

<!-- ============ MODAL ÉXITO ============ -->
<div id="modal-exito" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px">
    <div style="background:#141414;border:1px solid rgba(76,217,100,.25);border-radius:20px;width:100%;max-width:400px;padding:40px 32px;text-align:center;animation:fadeUp .3s ease both">
        <div style="width:72px;height:72px;border-radius:50%;background:rgba(76,217,100,.12);border:2px solid rgba(76,217,100,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem;color:var(--green)">
            <i class="fas fa-check"></i>
        </div>
        <h3 style="font-size:1.3rem;font-weight:800;margin-bottom:8px">¡Reserva enviada!</h3>
        <p id="exito-msg" style="color:rgba(255,255,255,.5);font-size:.9rem;line-height:1.6;margin-bottom:24px"></p>
        <div style="display:flex;flex-direction:column;gap:10px">
            <button id="exito-wsp" onclick="exitoWsp()" style="display:none;padding:12px;background:rgba(37,211,102,.1);color:#25d366;border:1px solid rgba(37,211,102,.3);border-radius:10px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px">
                <i class="fab fa-whatsapp"></i> Avisar al predio por WhatsApp
            </button>
            <a href="view/maquetaCliente/LaCanchitaCliente.php" style="padding:12px;background:var(--green);color:#000;border:none;border-radius:10px;font-weight:800;text-decoration:none;display:block">
                Ver mis reservas
            </a>
            <button onclick="closeExitoModal()" style="padding:10px;background:transparent;border:none;color:rgba(255,255,255,.35);cursor:pointer;font-size:.85rem">
                Seguir explorando
            </button>
        </div>
    </div>
</div>

<style>
.modal-open { overflow: hidden; }
</style>

<script>
// ── ESTADO RESERVA ─────────────────────────────────────────────────────────
let _ctx = {}; // cancha_id, cancha_nombre, complejo_nombre, complejo_tel, fecha, slots
let _selectedHora = null;
let _selectedMetodo = 'efectivo';
let _exitoData = null;
let _loggedIn = USUARIO_LOGUEADO;

function openModal(el) { el.style.display='flex'; document.body.classList.add('modal-open'); }
function closeModal(el) { el.style.display='none'; document.body.classList.remove('modal-open'); }

// ── ABRIR FLUJO RESERVA ────────────────────────────────────────────────────
function iniciarReservaIdx(i) {
    const c = (window._resData || [])[i];
    if (!c) return;
    iniciarReserva(c.CANCHA_ID, c.CANCHA_NOMBRE, c.COMPLEJO_NOMBRE, c.COMPLEJO_TEL || '', window._resFecha || '', c.slots || [], c.PRECIO_DESDE || 0);
}

function iniciarReserva(canchaId, canchaNombre, complejoNombre, complejoTel, fecha, slots, precioDesde) {
    _ctx = { canchaId, canchaNombre, complejoNombre, complejoTel, fecha, slots, precioDesde };
    _selectedHora = slots && slots.length ? slots.find(s=>s.libre)?.hora || null : null;
    _selectedMetodo = 'efectivo';

    if (!_loggedIn) {
        openModal(document.getElementById('modal-auth'));
        authTab('login');
    } else {
        abrirModalReserva();
    }
}

// Actualizar el botón Reservar en cada card de resultado
function buildResCards(canchas, fecha) {
    // Esta función es llamada desde renderResultados — reemplaza el href por onclick
}

// ── AUTH MODAL ─────────────────────────────────────────────────────────────
function authTab(t) {
    const isLogin = t === 'login';
    document.getElementById('pane-login').style.display = isLogin ? 'block' : 'none';
    document.getElementById('pane-reg').style.display   = isLogin ? 'none' : 'block';
    document.getElementById('tab-login').style.borderBottomColor = isLogin ? 'var(--green)' : 'transparent';
    document.getElementById('tab-login').style.color = isLogin ? '#fff' : 'rgba(255,255,255,.45)';
    document.getElementById('tab-reg').style.borderBottomColor  = isLogin ? 'transparent' : 'var(--green)';
    document.getElementById('tab-reg').style.color = isLogin ? 'rgba(255,255,255,.45)' : '#fff';
    document.getElementById('auth-err').style.display = 'none';
    document.getElementById('reg-err').style.display  = 'none';
}

function closeAuthModal() { closeModal(document.getElementById('modal-auth')); }

function setLoading(btnId, loading, txt) {
    const b = document.getElementById(btnId);
    b.disabled = loading;
    b.textContent = loading ? '...' : txt;
}

async function doLogin() {
    const user = document.getElementById('l-user').value.trim();
    const pass = document.getElementById('l-pass').value;
    const errEl = document.getElementById('auth-err');
    errEl.style.display = 'none';
    if (!user || !pass) { errEl.textContent='Completá los campos.'; errEl.style.display='block'; return; }
    setLoading('btn-login', true, 'Ingresando...');
    try {
        const r = await fetch('api/login_ajax.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({username:user,password:pass}) });
        const d = await r.json();
        if (!d.ok) { errEl.textContent=d.msg; errEl.style.display='block'; return; }
        _loggedIn = true;
        closeAuthModal();
        abrirModalReserva();
    } catch(e) {
        errEl.textContent='Error de conexión.'; errEl.style.display='block';
    } finally {
        setLoading('btn-login', false, 'Ingresar');
    }
}

async function doRegister() {
    const errEl = document.getElementById('reg-err');
    errEl.style.display = 'none';
    const body = {
        nombre:   document.getElementById('r-nombre').value.trim(),
        apellido: document.getElementById('r-apellido').value.trim(),
        dni:      document.getElementById('r-dni').value.trim(),
        telefono: document.getElementById('r-tel').value.trim(),
        email:    document.getElementById('r-email').value.trim(),
        password: document.getElementById('r-pass').value,
        password2:document.getElementById('r-pass2').value,
    };
    setLoading('btn-reg', true, 'Registrando...');
    try {
        const r = await fetch('api/register_ajax.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
        const d = await r.json();
        if (!d.ok) { errEl.textContent=d.msg; errEl.style.display='block'; return; }
        _loggedIn = true;
        closeAuthModal();
        abrirModalReserva();
    } catch(e) {
        errEl.textContent='Error de conexión.'; errEl.style.display='block';
    } finally {
        setLoading('btn-reg', false, 'Crear cuenta y reservar');
    }
}

// ── MODAL RESERVA ──────────────────────────────────────────────────────────
function abrirModalReserva() {
    const m = document.getElementById('modal-reserva');
    const c = _ctx;

    // Subtítulo
    document.getElementById('res-subtitulo').textContent = c.complejoNombre;

    // Info
    const fechaFmt = new Date(c.fecha + 'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});
    document.getElementById('res-info').innerHTML = `
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:12px">
            <div style="font-size:.7rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px"><i class="fas fa-futbol" style="margin-right:4px"></i>Cancha</div>
            <div style="font-weight:700;font-size:.9rem">${escHtml(c.canchaNombre)}</div>
        </div>
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:12px">
            <div style="font-size:.7rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px"><i class="fas fa-calendar" style="margin-right:4px"></i>Fecha</div>
            <div style="font-weight:700;font-size:.9rem">${fechaFmt}</div>
        </div>
    `;

    // Slots
    const slotsEl = document.getElementById('res-slots-confirm');
    if (c.slots && c.slots.length) {
        slotsEl.innerHTML = c.slots.map(s => {
            const libre = s.libre;
            const sel   = s.hora === _selectedHora;
            return `<button onclick="selectSlot('${s.hora}')" ${!libre?'disabled':''} class="slot-confirm-btn"
                style="padding:8px 14px;border-radius:8px;border:1px solid ${sel?'var(--green)':libre?'rgba(76,217,100,.25)':'rgba(255,255,255,.08)'};
                       background:${sel?'rgba(76,217,100,.18)':libre?'rgba(76,217,100,.06)':'rgba(255,255,255,.03)'};
                       color:${sel?'var(--green)':libre?'rgba(76,217,100,.7)':'rgba(255,255,255,.2)'};
                       font-weight:700;font-size:.82rem;cursor:${libre?'pointer':'not-allowed'};
                       text-decoration:${libre?'none':'line-through'};transition:all .15s"
                data-hora="${s.hora}">${s.hora}</button>`;
        }).join('');
    } else {
        slotsEl.innerHTML = '<span style="font-size:.82rem;color:rgba(255,255,255,.35)">Consultá disponibilidad al predio</span>';
    }

    actualizarPrecio();
    selectMetodo('efectivo');
    document.getElementById('res-err').style.display = 'none';
    openModal(m);
}

function selectSlot(hora) {
    _selectedHora = hora;
    document.querySelectorAll('[data-hora]').forEach(b => {
        const sel = b.dataset.hora === hora;
        b.style.borderColor  = sel ? 'var(--green)' : 'rgba(76,217,100,.25)';
        b.style.background   = sel ? 'rgba(76,217,100,.18)' : 'rgba(76,217,100,.06)';
        b.style.color        = sel ? 'var(--green)' : 'rgba(76,217,100,.7)';
    });
}

function selectMetodo(m) {
    _selectedMetodo = m;
    const metodos = ['efectivo','transferencia','mercadopago'];
    const icons   = {efectivo:'fa-money-bill-wave', transferencia:'fa-university', mercadopago:'fa-credit-card'};
    const labels  = {efectivo:'Efectivo', transferencia:'Transferencia', mercadopago:'MercadoPago'};
    const subs    = {efectivo:'En el predio', transferencia:'Banco / CVU', mercadopago:'Online'};
    metodos.forEach(k => {
        const el = document.getElementById('mp-'+k);
        if (!el) return;
        const sel = k === m;
        el.style.borderColor = sel ? 'rgba(76,217,100,.5)' : 'rgba(255,255,255,.1)';
        el.style.background  = sel ? 'rgba(76,217,100,.08)' : 'rgba(255,255,255,.03)';
        el.innerHTML = `<i class="fas ${icons[k]}" style="font-size:1.2rem;color:${sel?'var(--green)':'rgba(255,255,255,.4)'};display:block;margin-bottom:5px"></i>
            <div style="font-size:.78rem;font-weight:700;color:${sel?'#fff':'rgba(255,255,255,.5)'}">${labels[k]}</div>
            <div style="font-size:.68rem;color:rgba(255,255,255,.3)">${subs[k]}</div>`;
    });
}

function actualizarPrecio() {
    const p = _ctx.precioDesde;
    const precioEl = document.getElementById('res-precio-label');
    const senaEl   = document.getElementById('res-sena-label');
    if (p) {
        precioEl.textContent = fmt(p) + '/hora';
        senaEl.textContent   = 'El monto exacto puede variar según el turno';
    } else {
        precioEl.textContent = 'Consultar precio';
        senaEl.textContent   = '';
    }
}

function closeReservaModal() { closeModal(document.getElementById('modal-reserva')); }

async function confirmarReserva() {
    const errEl = document.getElementById('res-err');
    errEl.style.display = 'none';

    if (!_selectedHora && _ctx.slots && _ctx.slots.length) {
        errEl.textContent = 'Elegí un horario.';
        errEl.style.display = 'block';
        return;
    }

    const btn = document.getElementById('btn-confirmar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Confirmando...';

    try {
        const r = await fetch('api/reservar_publico.php', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({
                cancha_id: _ctx.canchaId,
                fecha:     _ctx.fecha,
                hora:      _selectedHora || '00:00',
                metodo:    _selectedMetodo,
            })
        });
        const d = await r.json();
        if (!d.ok) {
            errEl.textContent = d.msg;
            errEl.style.display = 'block';
            return;
        }
        _exitoData = d.data;
        closeReservaModal();
        abrirModalExito(d.data);
    } catch(e) {
        errEl.textContent = 'Error de conexión. Intentá de nuevo.';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar reserva';
    }
}

function reservarPorWsp() {
    const tel = _ctx.complejoTel ? _ctx.complejoTel.replace(/\D/g,'') : '';
    const hora = _selectedHora || 'a confirmar';
    const fecha = new Date(_ctx.fecha + 'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});
    const msg = `Hola! Quiero reservar ${_ctx.canchaNombre} en ${_ctx.complejoNombre} para el ${fecha} a las ${hora}hs. ¿Está disponible?`;
    if (tel) {
        window.open(`https://wa.me/549${tel}?text=${encodeURIComponent(msg)}`, '_blank');
    } else {
        alert('El predio no tiene número de WhatsApp registrado.');
    }
}

// ── MODAL ÉXITO ────────────────────────────────────────────────────────────
function abrirModalExito(data) {
    const hora  = data.HORA_INICIO + ' – ' + data.HORA_FIN;
    const fecha = new Date(_ctx.fecha + 'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});
    document.getElementById('exito-msg').innerHTML =
        `<strong>${escHtml(data.CANCHA_NOMBRE)}</strong> en <strong>${escHtml(data.COMPLEJO_NOMBRE)}</strong><br>
        ${fecha} · ${hora}<br><br>
        Estado: <span style="color:var(--green);font-weight:700">Pendiente de confirmación</span><br>
        El predio te contactará para coordinar el pago.`;

    const wspBtn = document.getElementById('exito-wsp');
    if (data.COMPLEJO_TEL) {
        wspBtn.style.display = 'flex';
    }
    openModal(document.getElementById('modal-exito'));
}

function exitoWsp() {
    if (!_exitoData) return;
    const tel  = (_exitoData.COMPLEJO_TEL||'').replace(/\D/g,'');
    const hora  = _exitoData.HORA_INICIO + ' – ' + _exitoData.HORA_FIN;
    const fecha = new Date(_ctx.fecha + 'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});
    const msg = `Hola! Acabo de reservar ${_exitoData.CANCHA_NOMBRE} para el ${fecha} a las ${hora}hs (Reserva #${_exitoData.RESERVA_ID}). ¡Quedo a la espera de confirmación!`;
    window.open(`https://wa.me/549${tel}?text=${encodeURIComponent(msg)}`, '_blank');
}

function closeExitoModal() { closeModal(document.getElementById('modal-exito')); }

// Cerrar modales con Escape
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['modal-auth','modal-reserva','modal-exito'].forEach(id => {
            const el = document.getElementById(id);
            if (el && el.style.display !== 'none') closeModal(el);
        });
    }
});
</script>

<!-- ── BOTÓN WHATSAPP FLOTANTE ──────────────────────────────────────────── -->
<a href="https://wa.me/<?= WSP_COMERCIAL ?>?text=<?= rawurlencode(WSP_MENSAJE) ?>"
   target="_blank" rel="noopener noreferrer"
   class="wsp-flotante" title="Contactanos por WhatsApp" aria-label="Contacto WhatsApp">
    <svg viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
        <path d="M12 0C5.373 0 0 5.373 0 12c0 2.123.554 4.117 1.528 5.845L.057 23.428a.5.5 0 0 0 .615.612l5.747-1.504A11.95 11.95 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 22a9.95 9.95 0 0 1-5.073-1.38l-.363-.215-3.761.984.999-3.667-.236-.375A9.953 9.953 0 0 1 2 12C2 6.477 6.477 2 12 2s10 4.477 10 10-4.477 10-10 10z"/>
    </svg>
    <span class="wsp-label">¿Tenés un predio?</span>
</a>

<style>
.wsp-flotante {
    position: fixed;
    bottom: 28px;
    right: 28px;
    z-index: 1000;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #25D366;
    color: #fff;
    border-radius: 50px;
    padding: 14px 20px 14px 16px;
    text-decoration: none;
    box-shadow: 0 4px 20px rgba(37,211,102,0.45);
    transition: transform 0.2s ease, box-shadow 0.2s ease, padding 0.3s ease;
    animation: wspPop 0.5s cubic-bezier(0.34,1.56,0.64,1) both;
    animation-delay: 1.5s;
    opacity: 0;
}
@keyframes wspPop {
    from { opacity: 0; transform: scale(0.5) translateY(20px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.wsp-flotante:hover {
    transform: translateY(-3px) scale(1.04);
    box-shadow: 0 8px 28px rgba(37,211,102,0.55);
}
.wsp-label {
    font-size: 0.85rem;
    font-weight: 700;
    white-space: nowrap;
    letter-spacing: 0.01em;
}
@media (max-width: 480px) {
    .wsp-flotante { padding: 14px; border-radius: 50%; }
    .wsp-label { display: none; }
    .wsp-flotante { bottom: 20px; right: 20px; }
}
</style>

</body>
</html>
