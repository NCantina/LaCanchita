<?php
session_start();
require_once 'config/dist/script/php/conn.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$usuarioLogueado = isset($_SESSION['usuario_id']);
$usuarioNombre   = $_SESSION['usuario_nombre'] ?? '';
$usuarioPerfil   = (int)($_SESSION['usuario_perfil'] ?? 0);

// Nombre para el título de la página
$predioNombre = 'Predio';
if (isset($link)) {
    $rp = mysqli_query($link, "SELECT COMPLEJO_NOMBRE FROM complejo WHERE COMPLEJO_ID=$id AND ACTIVO=1 LIMIT 1");
    if (!$rp || !($row = mysqli_fetch_assoc($rp))) { header('Location: index.php'); exit; }
    $predioNombre = $row['COMPLEJO_NOMBRE'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($predioNombre) ?> | La Canchita</title>
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

        body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, -apple-system, sans-serif; line-height: 1.6; overflow-x: hidden; }

        /* ── NAVBAR ── */
        .navbar {
            position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 5%; height: 68px;
            background: rgba(13,13,13,0.85); backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
        }
        .nav-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; color: var(--text); font-weight: 700; font-size: 1.1rem; }
        .nav-brand img { height: 36px; border-radius: 8px; }
        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .btn-ghost { padding: 8px 16px; border: 1px solid var(--border); background: transparent; color: var(--text); border-radius: 8px; font-size: 0.88rem; font-weight: 600; cursor: pointer; text-decoration: none; transition: border-color 0.2s, background 0.2s; }
        .btn-ghost:hover { border-color: rgba(255,255,255,0.3); background: var(--surface); }
        .btn-green { padding: 8px 18px; background: var(--green); color: #000; border: none; border-radius: 8px; font-size: 0.88rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: background 0.2s; }
        .btn-green:hover { background: var(--green-dark); }
        .hamburger { display: none; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: 6px; }
        .hamburger span { display: block; width: 24px; height: 2px; background: var(--text); border-radius: 2px; transition: all 0.3s; }
        .hamburger.open span:nth-child(1) { transform: translateY(7px) rotate(45deg); }
        .hamburger.open span:nth-child(2) { opacity: 0; }
        .hamburger.open span:nth-child(3) { transform: translateY(-7px) rotate(-45deg); }
        .nav-mobile { display: none; position: absolute; top: 68px; left: 0; right: 0; background: rgba(13,13,13,0.97); border-bottom: 1px solid var(--border); padding: 20px 5%; backdrop-filter: blur(16px); flex-direction: column; gap: 16px; }
        .nav-mobile.open { display: flex; }
        .nav-mobile a { color: var(--text-muted); text-decoration: none; font-size: 1rem; padding: 8px 0; border-bottom: 1px solid var(--border); transition: color 0.2s; }
        .nav-mobile a:hover { color: var(--text); }
        .nav-mobile .mob-actions { display: flex; gap: 10px; padding-top: 8px; }
        .nav-mobile .mob-actions a { border: none; padding: 0; }

        /* ── PAGE ── */
        .page-wrap { padding-top: 68px; min-height: 100vh; }

        /* ── BREADCRUMB ── */
        .breadcrumb { padding: 14px 5%; font-size: 0.82rem; color: var(--text-muted); display: flex; align-items: center; gap: 8px; border-bottom: 1px solid var(--border); }
        .breadcrumb a { color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
        .breadcrumb a:hover { color: var(--green); }
        .breadcrumb i { font-size: 10px; }

        /* ── HERO DEL PREDIO ── */
        .predio-hero {
            padding: 32px 5% 28px;
            border-bottom: 1px solid var(--border);
            background: linear-gradient(180deg, rgba(76,217,100,0.04) 0%, transparent 100%);
        }
        .predio-hero-row { display: flex; align-items: flex-start; justify-content: space-between; gap: 20px; flex-wrap: wrap; }
        .predio-hero-left { flex: 1; min-width: 260px; }
        .predio-tipo-badge { display: inline-flex; align-items: center; gap: 6px; font-size: 0.75rem; font-weight: 700; color: var(--green); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 10px; }
        .predio-title { font-size: 2rem; font-weight: 800; margin-bottom: 8px; line-height: 1.2; }
        .predio-loc { font-size: 0.9rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; margin-bottom: 14px; }
        .predio-badges { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
        .predio-badge { padding: 4px 12px; border-radius: 20px; background: var(--surface); border: 1px solid var(--border); font-size: 0.78rem; font-weight: 600; color: var(--text-muted); }
        .predio-hero-right { display: flex; flex-direction: column; gap: 10px; align-items: flex-end; flex-shrink: 0; }
        .predio-contact-btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 10px; font-size: 0.88rem; font-weight: 700; cursor: pointer; text-decoration: none; transition: all 0.2s; border: none; }
        .btn-wsp { background: rgba(37,211,102,0.12); color: #25d366; border: 1px solid rgba(37,211,102,0.3); }
        .btn-wsp:hover { background: rgba(37,211,102,0.2); }
        .btn-tel { background: var(--surface); color: var(--text); border: 1px solid var(--border); }
        .btn-tel:hover { background: var(--surface-2); }

        /* ── LAYOUT PRINCIPAL ── */
        .main-layout { display: grid; grid-template-columns: 280px 1fr; gap: 0; }
        .cal-sidebar { border-right: 1px solid var(--border); padding: 24px 20px; position: sticky; top: 68px; height: calc(100vh - 68px); overflow-y: auto; }
        .cal-sidebar-title { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 14px; }
        .canchas-area { padding: 24px 5%; }
        .canchas-count { font-size: 0.85rem; color: var(--text-muted); margin-bottom: 20px; }

        /* ── CANCHA CARD ── */
        .cancha-card {
            background: var(--surface); border: 1px solid var(--border);
            border-radius: 16px; overflow: hidden; margin-bottom: 16px;
            transition: border-color 0.2s;
        }
        .cancha-card:hover { border-color: rgba(76,217,100,0.25); }
        .cancha-card-head {
            display: flex; align-items: center; gap: 14px;
            padding: 18px 20px; border-bottom: 1px solid var(--border);
        }
        .cancha-icon-wrap { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; flex-shrink: 0; }
        .cancha-info { flex: 1; min-width: 0; }
        .cancha-name { font-weight: 700; font-size: 1rem; margin-bottom: 3px; }
        .cancha-tipo { font-size: 0.78rem; color: var(--text-muted); display: flex; align-items: center; gap: 6px; }
        .cancha-desc { font-size: 0.82rem; color: var(--text-muted); margin-top: 3px; }
        .cancha-body { padding: 16px 20px; }
        .slots-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: var(--text-muted); margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .slots-wrap { display: flex; flex-wrap: wrap; gap: 8px; }
        .slot-pill {
            padding: 8px 14px; border-radius: 10px; font-size: 0.82rem; font-weight: 700;
            border: 1px solid var(--border); background: var(--surface);
            cursor: pointer; transition: all 0.15s; color: var(--text); text-align: left;
            display: flex; flex-direction: column; gap: 2px; min-width: 90px;
        }
        .slot-pill:hover:not(:disabled) { border-color: rgba(76,217,100,0.5); background: rgba(76,217,100,0.08); color: var(--green); }
        .slot-pill:disabled { opacity: 0.35; cursor: not-allowed; }
        .slot-pill.ocupado { text-decoration: line-through; }
        .slot-price { font-size: 0.72rem; font-weight: 400; color: var(--text-muted); }
        .slot-pill:hover:not(:disabled) .slot-price { color: var(--green); }
        .slot-motivo { font-size: 0.7rem; color: var(--text-muted); }
        .no-slots { font-size: 0.85rem; color: var(--text-muted); padding: 8px 0; }

        /* ── SPINNER / SKELETON ── */
        .spinner { text-align: center; padding: 60px 0; color: var(--text-muted); }
        .spinner i { font-size: 2rem; opacity: 0.4; }

        /* ── CALENDARIO INLINE ── */
        .lc-cal { background: rgba(255,255,255,0.03); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; user-select: none; }
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

        /* ── FECHA SELECCIONADA BANNER ── */
        .fecha-banner { background: rgba(76,217,100,0.06); border: 1px solid rgba(76,217,100,0.15); border-radius: 10px; padding: 10px 14px; font-size: 0.85rem; color: var(--text-muted); margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
        .fecha-banner strong { color: var(--green); }

        /* ── ANIMACIONES ── */
        @keyframes fadeUp { from { opacity: 0; transform: translateY(16px); } to { opacity: 1; transform: translateY(0); } }
        .anim-in { animation: fadeUp 0.35s ease both; }

        /* ── RESPONSIVE ── */
        @media (max-width: 768px) {
            .main-layout { grid-template-columns: 1fr; }
            .cal-sidebar { position: static; height: auto; border-right: none; border-bottom: 1px solid var(--border); padding: 16px 5%; }
            .predio-title { font-size: 1.5rem; }
            .predio-hero-right { flex-direction: row; align-items: flex-start; }
            .hamburger { display: flex; }
            .nav-actions { display: none; }
        }
        @media (max-width: 480px) {
            .predio-hero { padding: 20px 4% 18px; }
            .breadcrumb { padding: 12px 4%; }
            .canchas-area { padding: 16px 4%; }
        }

        /* ── FILTROS TIPO DE CANCHA ── */
        .filtros-wrap { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 16px; }
        .filtro-pill { padding: 6px 16px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; border: 1px solid var(--border); background: var(--surface); color: var(--text-muted); cursor: pointer; transition: all 0.15s; white-space: nowrap; }
        .filtro-pill:hover { border-color: rgba(76,217,100,0.4); color: var(--text); }
        .filtro-pill.active { background: rgba(76,217,100,0.12); border-color: rgba(76,217,100,0.5); color: var(--green); }

        /* ── MODAL REGISTER FLOATING LABELS ── */
        .r-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .r-field { position: relative; margin-bottom: 13px; }
        .r-field input { width: 100%; padding: 16px 38px 4px 13px; background: rgba(255,255,255,.06); border: 1.5px solid rgba(255,255,255,.12); border-radius: 10px; color: #fff; font-size: .88rem; transition: border-color .25s, background .25s, box-shadow .25s; outline: none; }
        .r-field input:focus { border-color: var(--green); background: rgba(76,217,100,.05); box-shadow: 0 0 0 3px rgba(76,217,100,.1); }
        .r-field input:focus + label, .r-field input:not(:placeholder-shown) + label { transform: translateY(-8px) scale(.75); color: var(--green); }
        .r-field label { position: absolute; left: 13px; top: 12px; color: rgba(255,255,255,.4); font-size: .88rem; pointer-events: none; transition: transform .2s, color .2s; transform-origin: left top; }
        .r-field .r-icon { position: absolute; right: 11px; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,.3); font-size: .82rem; background: none; border: none; cursor: pointer; padding: 4px; transition: color .2s; }
        .r-field .r-icon:hover { color: var(--green); }
        .r-strength-bar { height: 3px; border-radius: 3px; background: rgba(255,255,255,.1); margin: -8px 0 8px; overflow: hidden; }
        .r-strength-fill { height: 100%; width: 0; border-radius: 3px; transition: width .3s, background .3s; }
        .r-strength-text { font-size: 11px; color: rgba(255,255,255,.35); display: block; margin-bottom: 12px; }
    </style>
</head>
<body>

<!-- ── NAVBAR ── -->
<nav class="navbar" id="mainNav">
    <a href="index.php" class="nav-brand">
        <img src="config/dist/img/loguito_lacanchita.WEBP" alt="La Canchita">
        La Canchita
    </a>
    <div class="nav-actions">
        <?php if ($usuarioLogueado):
            $panelUrl = ($usuarioPerfil === 5) ? 'view/maquetaCliente/LaCanchitaCliente.php' : 'view/maquetaAdmin/Dashboard.php';
        ?>
            <a href="<?= $panelUrl ?>" class="btn-ghost" style="display:flex;align-items:center;gap:8px;">
                <span style="width:28px;height:28px;border-radius:50%;background:var(--green);color:#000;font-weight:700;font-size:0.82rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;"><?= strtoupper(mb_substr($usuarioNombre,0,1)) ?></span>
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
<div class="nav-mobile" id="navMobile">
    <a href="index.php">Inicio</a>
    <?php if ($usuarioLogueado): ?>
        <a href="<?= $panelUrl ?>">Mi panel</a>
    <?php else: ?>
    <div class="mob-actions">
        <a href="login.php" class="btn-ghost" style="flex:1;text-align:center;">Iniciar sesión</a>
        <a href="register.php" class="btn-green" style="flex:1;text-align:center;">Registrarse</a>
    </div>
    <?php endif; ?>
</div>

<div class="page-wrap">
    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <a href="index.php"><i class="fas fa-home"></i> Inicio</a>
        <i class="fas fa-chevron-right"></i>
        <span id="bc-nombre"><?= htmlspecialchars($predioNombre) ?></span>
    </div>

    <!-- Hero del predio (rellenado por JS) -->
    <div class="predio-hero" id="predioHero">
        <div class="spinner"><i class="fas fa-circle-notch fa-spin"></i></div>
    </div>

    <!-- Layout principal: calendario | canchas -->
    <div class="main-layout">
        <!-- Sidebar: calendario de fecha -->
        <div class="cal-sidebar">
            <div class="cal-sidebar-title"><i class="fas fa-calendar" style="margin-right:5px"></i>Elegí una fecha</div>
            <div id="calPredioContainer"></div>
        </div>

        <!-- Área de canchas -->
        <div class="canchas-area">
            <div class="fecha-banner" id="fechaBanner">
                <i class="fas fa-calendar-check" style="color:var(--green)"></i>
                <span>Mostrando disponibilidad para <strong id="fechaLabel">hoy</strong></span>
            </div>
            <div id="canchasGrid">
                <div class="spinner"><i class="fas fa-circle-notch fa-spin"></i></div>
            </div>
        </div>
    </div>
</div>

<!-- ── MODAL AUTH ── -->
<div id="modal-auth" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px">
    <div style="background:#141414;border:1px solid rgba(255,255,255,0.12);border-radius:20px;width:100%;max-width:420px;overflow:hidden;animation:fadeUp .3s ease both">
        <div style="display:flex;border-bottom:1px solid rgba(255,255,255,0.08)">
            <button id="tab-login" onclick="authTab('login')" style="flex:1;padding:16px;background:transparent;border:none;color:#fff;font-weight:700;font-size:.92rem;cursor:pointer;border-bottom:2px solid var(--green);transition:color .2s"><i class="fas fa-sign-in-alt"></i> &nbsp;Iniciar sesión</button>
            <button id="tab-reg" onclick="authTab('reg')" style="flex:1;padding:16px;background:transparent;border:none;color:rgba(255,255,255,0.45);font-weight:700;font-size:.92rem;cursor:pointer;border-bottom:2px solid transparent;transition:color .2s"><i class="fas fa-user-plus"></i> &nbsp;Registrarse</button>
            <button onclick="closeAuthModal()" style="padding:16px 20px;background:transparent;border:none;color:rgba(255,255,255,.4);font-size:1.2rem;cursor:pointer">×</button>
        </div>
        <div id="pane-login" style="padding:28px">
            <p style="font-size:.85rem;color:rgba(255,255,255,.45);margin-bottom:20px">Ingresá para confirmar tu reserva</p>
            <div id="auth-err" style="display:none;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.3);border-radius:8px;padding:10px 14px;font-size:.83rem;color:#e74c3c;margin-bottom:16px"></div>
            <div style="display:flex;flex-direction:column;gap:14px">
                <div>
                    <label style="font-size:.75rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px">Email o DNI</label>
                    <input id="l-user" type="text" placeholder="usuario@email.com" style="width:100%;padding:12px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;font-size:.9rem;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='rgba(76,217,100,.5)'" onblur="this.style.borderColor='rgba(255,255,255,.12)'" onkeydown="if(event.key==='Enter')doLogin()">
                </div>
                <div>
                    <label style="font-size:.75rem;color:rgba(255,255,255,.45);text-transform:uppercase;letter-spacing:.05em;display:block;margin-bottom:6px">Contraseña</label>
                    <input id="l-pass" type="password" placeholder="••••••••" style="width:100%;padding:12px 14px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;font-size:.9rem;outline:none;transition:border-color .2s" onfocus="this.style.borderColor='rgba(76,217,100,.5)'" onblur="this.style.borderColor='rgba(255,255,255,.12)'" onkeydown="if(event.key==='Enter')doLogin()">
                </div>
                <button onclick="doLogin()" id="btn-login" style="padding:13px;background:var(--green);color:#000;border:none;border-radius:10px;font-weight:800;font-size:.95rem;cursor:pointer;transition:background .2s">Ingresar</button>
                <a href="login.php" style="text-align:center;font-size:.8rem;color:rgba(255,255,255,.35);text-decoration:none">¿Olvidaste tu contraseña?</a>
            </div>
        </div>
        <div id="pane-reg" style="display:none;padding:24px 28px 28px;max-height:82vh;overflow-y:auto">
            <p style="font-size:.84rem;color:rgba(255,255,255,.45);margin-bottom:16px">Creá tu cuenta gratis en segundos</p>
            <div id="reg-err" style="display:none;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.3);border-radius:8px;padding:10px 14px;font-size:.83rem;color:#e74c3c;margin-bottom:14px"></div>
            <div class="r-row-2">
                <div class="r-field">
                    <input type="text" id="r-nombre" placeholder=" " autocomplete="given-name">
                    <label>Nombre</label>
                    <i class="fas fa-user r-icon"></i>
                </div>
                <div class="r-field">
                    <input type="text" id="r-apellido" placeholder=" " autocomplete="family-name">
                    <label>Apellido</label>
                    <i class="fas fa-user r-icon"></i>
                </div>
            </div>
            <div class="r-row-2">
                <div class="r-field">
                    <input type="text" id="r-dni" placeholder=" " inputmode="numeric" maxlength="8">
                    <label>DNI</label>
                    <i class="fas fa-id-card r-icon"></i>
                </div>
                <div class="r-field">
                    <input type="tel" id="r-tel" placeholder=" " inputmode="numeric" autocomplete="tel">
                    <label>Teléfono</label>
                    <i class="fas fa-phone r-icon"></i>
                </div>
            </div>
            <div class="r-field">
                <input type="email" id="r-email" placeholder=" " autocomplete="email">
                <label>Email</label>
                <i class="fas fa-envelope r-icon"></i>
            </div>
            <div class="r-field">
                <input type="password" id="r-pass" placeholder=" " autocomplete="new-password" oninput="rStrength(this.value)">
                <label>Contraseña</label>
                <button type="button" class="r-icon" onclick="rTogglePass('r-pass','r-eye1')"><i id="r-eye1" class="fas fa-eye"></i></button>
            </div>
            <div class="r-strength-bar"><div class="r-strength-fill" id="r-sf"></div></div>
            <span class="r-strength-text" id="r-st">Mínimo 6 caracteres</span>
            <div class="r-field">
                <input type="password" id="r-pass2" placeholder=" " autocomplete="new-password">
                <label>Repetir contraseña</label>
                <button type="button" class="r-icon" onclick="rTogglePass('r-pass2','r-eye2')"><i id="r-eye2" class="fas fa-eye"></i></button>
            </div>
            <button onclick="doRegister()" id="btn-reg" style="margin-top:6px;width:100%;padding:13px;background:var(--green);color:#000;border:none;border-radius:10px;font-weight:800;font-size:.95rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:7px;transition:background .2s">
                <i class="fas fa-user-plus"></i> Crear cuenta y reservar
            </button>
        </div>
    </div>
</div>

<!-- ── MODAL RESERVA ── -->
<div id="modal-reserva" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px">
    <div style="background:#141414;border:1px solid rgba(255,255,255,0.12);border-radius:20px;width:100%;max-width:460px;overflow:hidden;animation:fadeUp .3s ease both">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.08)">
            <div style="display:flex;align-items:center;gap:10px">
                <div style="width:36px;height:36px;border-radius:9px;background:rgba(76,217,100,.12);border:1px solid rgba(76,217,100,.25);display:flex;align-items:center;justify-content:center;color:var(--green)"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <div style="font-weight:800;font-size:.95rem">Confirmar reserva</div>
                    <div id="res-subtitulo" style="font-size:.75rem;color:rgba(255,255,255,.4)"></div>
                </div>
            </div>
            <button onclick="closeReservaModal()" style="background:transparent;border:none;color:rgba(255,255,255,.4);font-size:1.2rem;cursor:pointer">×</button>
        </div>
        <div style="padding:20px 24px;border-bottom:1px solid rgba(255,255,255,.06)">
            <div id="res-info" style="display:grid;grid-template-columns:1fr 1fr;gap:10px"></div>
        </div>
        <div style="padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.06)">
            <div style="font-size:.75rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">Horario seleccionado</div>
            <div id="res-slots-confirm" style="display:flex;flex-wrap:wrap;gap:8px"></div>
        </div>
        <div style="padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.06)">
            <div style="font-size:.75rem;color:rgba(255,255,255,.4);text-transform:uppercase;letter-spacing:.06em;margin-bottom:10px">¿Cómo vas a pagar?</div>
            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px" id="metodos-pago">
                <?php foreach ([['efectivo','fa-money-bill-wave','Efectivo','En el predio'],['transferencia','fa-university','Transferencia','Banco / CVU'],['mercadopago','fa-credit-card','MercadoPago','Online']] as $m): ?>
                <label style="cursor:pointer">
                    <input type="radio" name="metodo" value="<?= $m[0] ?>" <?= $m[0]==='efectivo'?'checked':'' ?> style="display:none" onchange="selectMetodo('<?= $m[0] ?>')">
                    <div id="mp-<?= $m[0] ?>" onclick="selectMetodo('<?= $m[0] ?>')" style="padding:12px 8px;border-radius:10px;border:1px solid <?= $m[0]==='efectivo'?'rgba(76,217,100,.5)':'rgba(255,255,255,.1)' ?>;background:<?= $m[0]==='efectivo'?'rgba(76,217,100,.08)':'rgba(255,255,255,.03)' ?>;text-align:center;transition:all .15s">
                        <i class="fas <?= $m[1] ?>" style="font-size:1.2rem;color:<?= $m[0]==='efectivo'?'var(--green)':'rgba(255,255,255,.4)' ?>;display:block;margin-bottom:5px"></i>
                        <div style="font-size:.78rem;font-weight:700;color:<?= $m[0]==='efectivo'?'#fff':'rgba(255,255,255,.5)' ?>"><?= $m[2] ?></div>
                        <div style="font-size:.68rem;color:rgba(255,255,255,.3)"><?= $m[3] ?></div>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.06);display:flex;align-items:center;justify-content:space-between">
            <div>
                <div id="res-precio-label" style="font-size:1.5rem;font-weight:800;color:var(--green)"></div>
                <div id="res-sena-label" style="font-size:.8rem;color:rgba(255,255,255,.4);margin-top:2px"></div>
            </div>
            <div id="res-err" style="display:none;font-size:.8rem;color:#e74c3c;text-align:right;max-width:200px"></div>
        </div>
        <div style="padding:20px 24px;display:flex;flex-direction:column;gap:10px">
            <button id="btn-confirmar" onclick="confirmarReserva()" style="padding:14px;background:var(--green);color:#000;border:none;border-radius:12px;font-weight:800;font-size:1rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:background .2s">
                <i class="fas fa-check-circle"></i> Confirmar reserva
            </button>
            <button id="btn-wsp-reserva" onclick="reservarPorWsp()" style="padding:12px;background:rgba(37,211,102,.1);color:#25d366;border:1px solid rgba(37,211,102,.3);border-radius:12px;font-weight:700;font-size:.9rem;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;transition:all .2s">
                <i class="fab fa-whatsapp"></i> Consultar por WhatsApp
            </button>
        </div>
    </div>
</div>

<!-- ── MODAL ÉXITO ── -->
<div id="modal-exito" style="display:none;position:fixed;inset:0;z-index:9000;background:rgba(0,0,0,0.75);backdrop-filter:blur(6px);align-items:center;justify-content:center;padding:16px">
    <div style="background:#141414;border:1px solid rgba(76,217,100,.25);border-radius:20px;width:100%;max-width:400px;padding:40px 32px;text-align:center;animation:fadeUp .3s ease both">
        <div style="width:72px;height:72px;border-radius:50%;background:rgba(76,217,100,.12);border:2px solid rgba(76,217,100,.3);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-size:2rem;color:var(--green)"><i class="fas fa-check"></i></div>
        <h3 style="font-size:1.3rem;font-weight:800;margin-bottom:8px">¡Reserva enviada!</h3>
        <p id="exito-msg" style="color:rgba(255,255,255,.5);font-size:.9rem;line-height:1.6;margin-bottom:24px"></p>
        <div style="display:flex;flex-direction:column;gap:10px">
            <button id="exito-wsp" onclick="exitoWsp()" style="display:none;padding:12px;background:rgba(37,211,102,.1);color:#25d366;border:1px solid rgba(37,211,102,.3);border-radius:10px;font-weight:700;cursor:pointer;align-items:center;justify-content:center;gap:7px">
                <i class="fab fa-whatsapp"></i> Avisar al predio por WhatsApp
            </button>
            <button onclick="closeExitoModal()" style="padding:12px;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:10px;color:#fff;font-weight:600;cursor:pointer">Cerrar</button>
        </div>
    </div>
</div>

<script>
const USUARIO_LOGUEADO = <?= $usuarioLogueado ? 'true' : 'false' ?>;
const COMPLEJO_ID      = <?= $id ?>;

// ── NAVBAR MOBILE ──
const hamburger = document.getElementById('hamburger');
const navMobile = document.getElementById('navMobile');
hamburger.addEventListener('click', () => { hamburger.classList.toggle('open'); navMobile.classList.toggle('open'); });

// ── ESTADO RESERVA ──
let _ctx = {};
let _selectedHora   = null;
let _selectedMetodo = 'efectivo';
let _exitoData      = null;
let _loggedIn       = USUARIO_LOGUEADO;
let _currentFecha   = new Date().toISOString().split('T')[0];
let _predioData     = null;

// ── UTILIDADES ──
function fmt(n) { return '$' + Number(n).toLocaleString('es-AR', {maximumFractionDigits:0}); }
function escHtml(s) { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
function titleCase(s) { if(!s) return s; if(s!==s.toUpperCase()) return s; return s.replace(/\S+/g, w=>w.charAt(0)+w.slice(1).toLowerCase()); }
function openModal(el)  { el.style.display='flex'; document.body.style.overflow='hidden'; }
function closeModal(el) { el.style.display='none'; document.body.style.overflow=''; }

const SPORT_ICONS  = { 'fútbol':'fa-futbol','futbol':'fa-futbol','pádel':'fa-table-tennis','padel':'fa-table-tennis','tenis':'fa-table-tennis','básquet':'fa-basketball-ball','basket':'fa-basketball-ball','vóley':'fa-volleyball-ball','voley':'fa-volleyball-ball' };
const SPORT_COLORS = { 'fútbol':'#4cd964','futbol':'#4cd964','pádel':'#3498db','padel':'#3498db','tenis':'#ff9500','básquet':'#e74c3c','basket':'#e74c3c','vóley':'#9b59b6','voley':'#9b59b6' };
function sportIcon(t)  { if(!t)return'fa-running'; const tl=t.toLowerCase(); for(const k in SPORT_ICONS)  if(tl.includes(k))return SPORT_ICONS[k];  return'fa-running'; }
function sportColor(t) { if(!t)return'#3498db';   const tl=t.toLowerCase(); for(const k in SPORT_COLORS) if(tl.includes(k))return SPORT_COLORS[k]; return'#3498db'; }

// ── CALENDARIO ──
class CalendarioLC {
    constructor(containerId, onSelect) {
        this.el      = document.getElementById(containerId);
        this.onSel   = onSelect;
        this._today  = new Date(); this._today.setHours(0,0,0,0);
        this.sel     = new Date(this._today);
        this.current = new Date(this._today.getFullYear(), this._today.getMonth(), 1);
        this._r();
    }
    setDate(ds) { const d=new Date(ds+'T00:00:00'); this.sel=d; this.current=new Date(d.getFullYear(),d.getMonth(),1); this._r(); }
    prevMonth() { const f=new Date(this._today.getFullYear(),this._today.getMonth(),1); if(this.current<=f)return; this.current.setMonth(this.current.getMonth()-1); this._r(); }
    nextMonth() { this.current.setMonth(this.current.getMonth()+1); this._r(); }
    pick(y,m,d) { const dt=new Date(y,m,d); if(dt<this._today)return; this.sel=dt; const yy=dt.getFullYear(),mm=String(dt.getMonth()+1).padStart(2,'0'),dd=String(dt.getDate()).padStart(2,'0'); this._r(); this.onSel(`${yy}-${mm}-${dd}`); }
    _r() {
        const M=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
        const D=['L','M','X','J','V','S','D'];
        const y=this.current.getFullYear(),m=this.current.getMonth();
        const off=(new Date(y,m,1).getDay()+6)%7, dim=new Date(y,m+1,0).getDate();
        const tMs=this._today.getTime(), sMs=this.sel?this.sel.getTime():-1;
        const floor=new Date(this._today.getFullYear(),this._today.getMonth(),1);
        const canPrev=this.current>floor;
        let cells='<span></span>'.repeat(off);
        for(let d=1;d<=dim;d++){
            const ms=new Date(y,m,d).getTime(),past=ms<tMs;
            const cls=['lc-cal-day',ms===tMs?'today':'',ms===sMs?'selected':''].filter(Boolean).join(' ');
            const act=past?'disabled':`onclick="window._lcCal.pick(${y},${m},${d})"`;
            cells+=`<button class="${cls}" ${act}>${d}</button>`;
        }
        this.el.innerHTML=`<div class="lc-cal"><div class="lc-cal-head"><button class="lc-cal-nav" onclick="window._lcCal.prevMonth()" ${canPrev?'':'disabled'}><i class="fas fa-chevron-left"></i></button><span class="lc-cal-title">${M[m]} ${y}</span><button class="lc-cal-nav" onclick="window._lcCal.nextMonth()"><i class="fas fa-chevron-right"></i></button></div><div class="lc-cal-week">${D.map(d=>`<span>${d}</span>`).join('')}</div><div class="lc-cal-grid">${cells}</div></div>`;
    }
}

// ── CARGA INICIAL ──
async function cargarPredio(fecha) {
    if (!fecha) fecha = _currentFecha;
    _currentFecha = fecha;
    try {
        const r = await fetch(`api/predio_publico.php?complejo_id=${COMPLEJO_ID}&fecha=${fecha}`);
        const j = await r.json();
        if (!j.ok) { document.getElementById('predioHero').innerHTML='<p style="padding:20px;color:var(--text-muted)">No se pudo cargar el predio.</p>'; return; }
        _predioData = j;
        renderHero(j.predio);
        renderCanchas(j.canchas, fecha);
        actualizarFechaBanner(fecha);
    } catch(e) {
        document.getElementById('canchasGrid').innerHTML = '<p class="no-slots">Error de conexión.</p>';
    }
}

function actualizarFechaBanner(fecha) {
    const d   = new Date(fecha + 'T00:00:00');
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    const label = d.getTime() === hoy.getTime()
        ? 'hoy'
        : d.toLocaleDateString('es-AR', {weekday:'long', day:'numeric', month:'long'});
    document.getElementById('fechaLabel').textContent = label;
}

function renderHero(p) {
    const tel    = p.COMPLEJO_TELEFONO || '';
    const wsp    = tel.replace(/\D/g,'');
    const loc    = [p.LOCALIDAD_NOMBRE, p.PARTIDO_NOMBRE, p.PROVINCIA_NOMBRE].filter(Boolean).join(', ');
    const acts   = p.ACTIVIDADES ? p.ACTIVIDADES.split(',').map(a=>`<span class="predio-badge">${escHtml(a.trim())}</span>`).join('') : '';
    const wspBtn = wsp ? `<a href="https://wa.me/549${wsp}?text=${encodeURIComponent('Hola! Quiero reservar una cancha en ' + p.COMPLEJO_NOMBRE)}" target="_blank" class="predio-contact-btn btn-wsp"><i class="fab fa-whatsapp"></i> WhatsApp</a>` : '';
    const telBtn = tel ? `<a href="tel:${escHtml(tel)}" class="predio-contact-btn btn-tel"><i class="fas fa-phone"></i> ${escHtml(tel)}</a>` : '';
    const desc   = p.COMPLEJO_DESCRIPCION ? `<p style="font-size:0.88rem;color:var(--text-muted);margin-top:4px">${escHtml(p.COMPLEJO_DESCRIPCION)}</p>` : '';
    document.getElementById('predioHero').innerHTML = `
<div class="predio-hero-row">
  <div class="predio-hero-left">
    <div class="predio-tipo-badge"><i class="fas fa-shield-alt"></i>${escHtml(p.TIPO_COMPLEJO_NOMBRE||'Complejo deportivo')}</div>
    <h1 class="predio-title">${escHtml(titleCase(p.COMPLEJO_NOMBRE))}</h1>
    <div class="predio-loc"><i class="fas fa-map-marker-alt" style="color:var(--green)"></i>${escHtml(loc)}</div>
    <div class="predio-badges">${acts}</div>
    ${desc}
  </div>
  <div class="predio-hero-right">${wspBtn}${telBtn}</div>
</div>`;
    document.getElementById('bc-nombre').textContent = titleCase(p.COMPLEJO_NOMBRE);
    document.title = titleCase(p.COMPLEJO_NOMBRE) + ' | La Canchita';
}

let _tipoFiltro = '';
function filtrarCanchas(tipo) {
    _tipoFiltro = tipo;
    document.querySelectorAll('[data-tipo-cancha]').forEach(card => {
        card.style.display = (!tipo || card.dataset.tipoCancha === tipo) ? '' : 'none';
    });
    document.querySelectorAll('.filtro-pill').forEach(pill => {
        pill.classList.toggle('active', pill.dataset.tipo === tipo);
    });
}

function renderCanchas(canchas, fecha) {
    const grid = document.getElementById('canchasGrid');
    if (!canchas || !canchas.length) {
        grid.innerHTML = '<p class="no-slots">Este predio no tiene canchas disponibles.</p>';
        return;
    }
    _tipoFiltro = '';
    const tipos = [...new Set(canchas.map(c => c.TIPO_CANCHA_NOMBRE).filter(Boolean))];
    const filtrosHtml = tipos.length > 1
        ? `<div class="filtros-wrap"><button class="filtro-pill active" data-tipo="" onclick="filtrarCanchas('')">Todas</button>${tipos.map(t=>`<button class="filtro-pill" data-tipo="${escHtml(t)}" onclick="filtrarCanchas('${escHtml(t)}')">${escHtml(t)}</button>`).join('')}</div>`
        : '';
    const cardsHtml = canchas.map((c, i) => {
        const color = sportColor(c.TIPO_CANCHA_NOMBRE);
        const icon  = sportIcon(c.TIPO_CANCHA_NOMBRE);
        const hoy   = new Date().toISOString().split('T')[0];
        const ahora = new Date();

        let slots = c.slots || [];
        if (fecha === hoy) {
            slots = slots.filter(s => {
                const [h, m] = s.FRANJA_HORA_FIN.split(':').map(Number);
                const fin = new Date(); fin.setHours(h, m, 0, 0);
                return fin > ahora;
            });
        }

        const slotsHtml = slots.length
            ? slots.map(s => {
                const ini  = s.FRANJA_HORA_INICIO;
                const fin  = s.FRANJA_HORA_FIN;
                const prec = s.FRANJA_PRECIO ? fmt(s.FRANJA_PRECIO) + '/h' : '';
                if (!s.disponible) {
                    return `<button class="slot-pill ocupado" disabled>
                        <span>${ini} – ${fin}</span>
                        <span class="slot-motivo"><i class="fas fa-lock" style="margin-right:2px"></i>${escHtml(s.motivo)}</span>
                    </button>`;
                }
                return `<button class="slot-pill" onclick="iniciarReserva(${c.CANCHA_ID},'${escHtml(c.CANCHA_NOMBRE)}','${escHtml(_predioData.predio.COMPLEJO_NOMBRE)}','${escHtml(_predioData.predio.COMPLEJO_TELEFONO||'')}','${fecha}',[{hora:'${ini}',libre:true}],${s.FRANJA_PRECIO||0})">
                    <span>${ini} – ${fin}</span>
                    <span class="slot-price">${prec}</span>
                </button>`;
            }).join('')
            : '<span class="no-slots"><i class="fas fa-moon" style="margin-right:5px"></i>Sin horarios disponibles para este día</span>';

        const desc = c.CANCHA_DESCRIPCION ? `<div class="cancha-desc">${escHtml(c.CANCHA_DESCRIPCION)}</div>` : '';

        return `<div class="cancha-card anim-in" data-tipo-cancha="${escHtml(c.TIPO_CANCHA_NOMBRE||'')}" style="animation-delay:${i*0.06}s">
            <div class="cancha-card-head">
                <div class="cancha-icon-wrap" style="background:${color}18;color:${color}">
                    <i class="fas ${icon}"></i>
                </div>
                <div class="cancha-info">
                    <div class="cancha-name">${escHtml(c.CANCHA_NOMBRE)}</div>
                    <div class="cancha-tipo">${escHtml(c.TIPO_CANCHA_NOMBRE||'')}</div>
                    ${desc}
                </div>
            </div>
            <div class="cancha-body">
                <div class="slots-label"><i class="fas fa-clock"></i>Turnos disponibles</div>
                <div class="slots-wrap">${slotsHtml}</div>
            </div>
        </div>`;
    }).join('');
    grid.innerHTML = filtrosHtml + cardsHtml;
}

// ── FLUJO RESERVA ──
function iniciarReserva(canchaId, canchaNombre, complejoNombre, complejoTel, fecha, slots, precioDesde) {
    _ctx = { canchaId, canchaNombre, complejoNombre, complejoTel, fecha, slots, precioDesde };
    _selectedHora   = slots && slots.length ? (slots.find(s=>s.libre)||{}).hora || null : null;
    _selectedMetodo = 'efectivo';
    if (!_loggedIn) { openModal(document.getElementById('modal-auth')); authTab('login'); }
    else abrirModalReserva();
}

function abrirModalReserva() {
    const c = _ctx;
    document.getElementById('res-subtitulo').textContent = c.complejoNombre;
    const fechaFmt = new Date(c.fecha+'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});
    document.getElementById('res-info').innerHTML = `
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:12px">
            <div style="font-size:.7rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px"><i class="fas fa-futbol" style="margin-right:4px"></i>Cancha</div>
            <div style="font-weight:700;font-size:.9rem">${escHtml(c.canchaNombre)}</div>
        </div>
        <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.08);border-radius:10px;padding:12px">
            <div style="font-size:.7rem;color:rgba(255,255,255,.35);text-transform:uppercase;letter-spacing:.05em;margin-bottom:4px"><i class="fas fa-calendar" style="margin-right:4px"></i>Fecha</div>
            <div style="font-weight:700;font-size:.9rem">${fechaFmt}</div>
        </div>`;
    const slotsEl = document.getElementById('res-slots-confirm');
    slotsEl.innerHTML = (c.slots||[]).map(s => {
        const sel = s.hora === _selectedHora;
        return `<button onclick="selectSlot('${s.hora}')" data-hora="${s.hora}" class="slot-confirm-btn"
            style="padding:8px 14px;border-radius:8px;border:1px solid ${sel?'var(--green)':'rgba(76,217,100,.25)'};background:${sel?'rgba(76,217,100,.18)':'rgba(76,217,100,.06)'};color:${sel?'var(--green)':'rgba(76,217,100,.7)'};font-weight:700;font-size:.82rem;cursor:pointer;transition:all .15s"
            >${s.hora}</button>`;
    }).join('');
    actualizarPrecio();
    selectMetodo('efectivo');
    document.getElementById('res-err').style.display = 'none';
    document.getElementById('btn-wsp-reserva').style.display = c.complejoTel ? 'flex' : 'none';
    openModal(document.getElementById('modal-reserva'));
}

function selectSlot(hora) {
    _selectedHora = hora;
    document.querySelectorAll('[data-hora]').forEach(b => {
        const sel = b.dataset.hora === hora;
        b.style.borderColor = sel ? 'var(--green)' : 'rgba(76,217,100,.25)';
        b.style.background  = sel ? 'rgba(76,217,100,.18)' : 'rgba(76,217,100,.06)';
        b.style.color       = sel ? 'var(--green)' : 'rgba(76,217,100,.7)';
    });
}

function selectMetodo(m) {
    _selectedMetodo = m;
    const icons  = {efectivo:'fa-money-bill-wave',transferencia:'fa-university',mercadopago:'fa-credit-card'};
    const labels = {efectivo:'Efectivo',transferencia:'Transferencia',mercadopago:'MercadoPago'};
    const subs   = {efectivo:'En el predio',transferencia:'Banco / CVU',mercadopago:'Online'};
    ['efectivo','transferencia','mercadopago'].forEach(k => {
        const el = document.getElementById('mp-'+k); if (!el) return;
        const sel = k===m;
        el.style.borderColor = sel ? 'rgba(76,217,100,.5)' : 'rgba(255,255,255,.1)';
        el.style.background  = sel ? 'rgba(76,217,100,.08)' : 'rgba(255,255,255,.03)';
        el.innerHTML = `<i class="fas ${icons[k]}" style="font-size:1.2rem;color:${sel?'var(--green)':'rgba(255,255,255,.4)'};display:block;margin-bottom:5px"></i><div style="font-size:.78rem;font-weight:700;color:${sel?'#fff':'rgba(255,255,255,.5)'}">${labels[k]}</div><div style="font-size:.68rem;color:rgba(255,255,255,.3)">${subs[k]}</div>`;
    });
}

function actualizarPrecio() {
    const p = _ctx.precioDesde;
    document.getElementById('res-precio-label').textContent = p ? fmt(p)+'/hora' : 'Consultar precio';
    document.getElementById('res-sena-label').textContent   = p ? 'El monto exacto puede variar según el turno' : '';
}

function closeReservaModal() { closeModal(document.getElementById('modal-reserva')); }

async function confirmarReserva() {
    const errEl = document.getElementById('res-err');
    errEl.style.display = 'none';
    if (!_selectedHora) { errEl.textContent='Elegí un horario.'; errEl.style.display='block'; return; }
    const btn = document.getElementById('btn-confirmar');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Confirmando...';
    try {
        const r = await fetch('api/reservar_publico.php', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ cancha_id:_ctx.canchaId, fecha:_ctx.fecha, hora:_selectedHora, metodo:_selectedMetodo })
        });
        const d = await r.json();
        if (!d.ok) { errEl.textContent=d.msg; errEl.style.display='block'; return; }
        _exitoData = d.data;
        closeReservaModal();
        abrirModalExito(d.data);
        cargarPredio(_currentFecha);
    } catch(e) {
        errEl.textContent = 'Error de conexión. Intentá de nuevo.';
        errEl.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check-circle"></i> Confirmar reserva';
    }
}

function reservarPorWsp() {
    const tel = (_ctx.complejoTel||'').replace(/\D/g,'');
    const hora = _selectedHora||'a confirmar';
    const fecha = new Date(_ctx.fecha+'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});
    const msg = `Hola! Quiero reservar ${_ctx.canchaNombre} en ${_ctx.complejoNombre} para el ${fecha} a las ${hora}hs. ¿Está disponible?`;
    if (tel) window.open(`https://wa.me/549${tel}?text=${encodeURIComponent(msg)}`, '_blank');
}

function abrirModalExito(data) {
    const hora  = data.HORA_INICIO+' – '+data.HORA_FIN;
    const fecha = new Date(_ctx.fecha+'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});
    document.getElementById('exito-msg').innerHTML =
        `<strong>${escHtml(data.CANCHA_NOMBRE)}</strong> en <strong>${escHtml(data.COMPLEJO_NOMBRE)}</strong><br>${fecha} · ${hora}<br><br>Estado: <span style="color:var(--green);font-weight:700">Pendiente de confirmación</span><br>El predio te contactará para coordinar el pago.`;
    const wBtn = document.getElementById('exito-wsp');
    wBtn.style.display = data.COMPLEJO_TEL ? 'flex' : 'none';
    openModal(document.getElementById('modal-exito'));
}

function exitoWsp() {
    if (!_exitoData) return;
    const tel  = (_exitoData.COMPLEJO_TEL||'').replace(/\D/g,'');
    const hora  = _exitoData.HORA_INICIO+' – '+_exitoData.HORA_FIN;
    const fecha = new Date(_ctx.fecha+'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'long'});
    const msg = `Hola! Acabo de reservar ${_exitoData.CANCHA_NOMBRE} para el ${fecha} a las ${hora}hs (Reserva #${_exitoData.RESERVA_ID}). ¡Quedo a la espera de confirmación!`;
    window.open(`https://wa.me/549${tel}?text=${encodeURIComponent(msg)}`, '_blank');
}

function closeExitoModal() { closeModal(document.getElementById('modal-exito')); }

// ── AUTH ──
function updateNavAfterLogin(nombre) {
    const ini = (nombre||'?').charAt(0).toUpperCase();
    document.querySelector('.nav-actions').innerHTML =
        `<a href="view/maquetaCliente/LaCanchitaCliente.php" class="btn-ghost" style="display:flex;align-items:center;gap:8px;">
            <span style="width:28px;height:28px;border-radius:50%;background:var(--green);color:#000;font-weight:700;font-size:0.82rem;display:flex;align-items:center;justify-content:center;flex-shrink:0;">${ini}</span>
            Mi panel
        </a>`;
    const mobActions = document.querySelector('#navMobile .mob-actions');
    if (mobActions) mobActions.outerHTML = `<a href="view/maquetaCliente/LaCanchitaCliente.php">Mi panel</a>`;
}

function authTab(t) {
    const isL = t==='login';
    document.getElementById('pane-login').style.display = isL?'block':'none';
    document.getElementById('pane-reg').style.display   = isL?'none':'block';
    document.getElementById('tab-login').style.borderBottomColor = isL?'var(--green)':'transparent';
    document.getElementById('tab-login').style.color = isL?'#fff':'rgba(255,255,255,.45)';
    document.getElementById('tab-reg').style.borderBottomColor  = isL?'transparent':'var(--green)';
    document.getElementById('tab-reg').style.color = isL?'rgba(255,255,255,.45)':'#fff';
    document.getElementById('auth-err').style.display = 'none';
    document.getElementById('reg-err').style.display  = 'none';
}

function closeAuthModal() { closeModal(document.getElementById('modal-auth')); }

function setLoading(btnId, loading, txt) { const b=document.getElementById(btnId); b.disabled=loading; b.textContent=loading?'...':txt; }

function rTogglePass(inputId, iconId) {
    const inp=document.getElementById(inputId), ico=document.getElementById(iconId);
    const v=inp.type==='text'; inp.type=v?'password':'text'; ico.className=v?'fas fa-eye':'fas fa-eye-slash';
}
function rStrength(val) {
    let s=0;
    if(val.length>=6) s++; if(val.length>=10) s++;
    if(/[A-Z]/.test(val)) s++; if(/[0-9]/.test(val)) s++; if(/[^A-Za-z0-9]/.test(val)) s++;
    const fill=document.getElementById('r-sf'), text=document.getElementById('r-st');
    const pcts=['0%','25%','50%','75%','100%'], colors=['#ff4455','#ff9500','#ffcc00','#4cd964','#34c759'];
    const labels=['','Muy débil','Débil','Buena','Fuerte','Muy fuerte'];
    fill.style.width=pcts[s]||'0%'; fill.style.background=colors[s-1]||'#ff4455';
    text.textContent=val.length===0?'Mínimo 6 caracteres':(labels[s]||'');
    text.style.color=val.length===0?'rgba(255,255,255,.35)':(colors[s-1]||'rgba(255,255,255,.35)');
}

async function doLogin() {
    const user=document.getElementById('l-user').value.trim(), pass=document.getElementById('l-pass').value;
    const errEl=document.getElementById('auth-err'); errEl.style.display='none';
    if (!user||!pass) { errEl.textContent='Completá los campos.'; errEl.style.display='block'; return; }
    setLoading('btn-login',true,'Ingresando...');
    try {
        const r=await fetch('api/login_ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({username:user,password:pass})});
        const d=await r.json();
        if (!d.ok) { errEl.textContent=d.msg; errEl.style.display='block'; return; }
        _loggedIn=true; updateNavAfterLogin(d.nombre); closeAuthModal(); abrirModalReserva();
    } catch(e) { errEl.textContent='Error de conexión.'; errEl.style.display='block'; }
    finally { setLoading('btn-login',false,'Ingresar'); }
}

async function doRegister() {
    const errEl=document.getElementById('reg-err'); errEl.style.display='none';
    const pass=document.getElementById('r-pass').value, pass2=document.getElementById('r-pass2').value;
    if(!pass||pass.length<6) { errEl.textContent='La contraseña debe tener al menos 6 caracteres.'; errEl.style.display='block'; return; }
    if(pass!==pass2) { errEl.textContent='Las contraseñas no coinciden.'; errEl.style.display='block'; return; }
    const body={ nombre:document.getElementById('r-nombre').value.trim(), apellido:document.getElementById('r-apellido').value.trim(), dni:document.getElementById('r-dni').value.trim(), telefono:document.getElementById('r-tel').value.trim(), email:document.getElementById('r-email').value.trim(), password:pass, password2:pass2 };
    const btn=document.getElementById('btn-reg'); btn.disabled=true; btn.innerHTML='<i class="fas fa-circle-notch fa-spin"></i> Registrando...';
    try {
        const r=await fetch('api/register_ajax.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(body)});
        const d=await r.json();
        if (!d.ok) { errEl.textContent=d.msg; errEl.style.display='block'; return; }
        _loggedIn=true; updateNavAfterLogin(d.nombre); closeAuthModal(); abrirModalReserva();
    } catch(e) { errEl.textContent='Error de conexión.'; errEl.style.display='block'; }
    finally { btn.disabled=false; btn.innerHTML='<i class="fas fa-user-plus"></i> Crear cuenta y reservar'; }
}

// ── ESCAPE para cerrar modales ──
document.addEventListener('keydown', e => {
    if (e.key==='Escape') ['modal-auth','modal-reserva','modal-exito'].forEach(id => { const el=document.getElementById(id); if(el&&el.style.display!=='none')closeModal(el); });
});

// ── INIT ──
window._lcCal = new CalendarioLC('calPredioContainer', function(fecha) {
    cargarPredio(fecha);
});
cargarPredio(_currentFecha);

// ── PWA: botón para abrir en navegador cuando se usa como app instalada ──
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
    const pwaBtn = document.createElement('a');
    pwaBtn.href = location.href;
    pwaBtn.target = '_blank';
    pwaBtn.rel = 'noopener';
    pwaBtn.title = 'Abrir en navegador';
    pwaBtn.style.cssText = 'position:fixed;bottom:20px;right:16px;z-index:9999;background:rgba(20,20,20,0.9);color:rgba(255,255,255,0.65);border:1px solid rgba(255,255,255,0.15);border-radius:10px;padding:8px 14px;font-size:0.75rem;font-weight:600;display:flex;align-items:center;gap:7px;text-decoration:none;backdrop-filter:blur(8px);';
    pwaBtn.innerHTML = '<i class="fas fa-external-link-alt"></i> Abrir en navegador';
    document.body.appendChild(pwaBtn);
}
</script>
</body>
</html>
