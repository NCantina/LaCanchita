<?php
session_start();
require_once 'config/dist/script/php/conn.php';

$id = (int)($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }

$usuarioLogueado = isset($_SESSION['usuario_id']);
$usuarioPerfil   = (int)($_SESSION['usuario_perfil'] ?? 0);
$usuarioNombre   = $_SESSION['usuario_nombre'] ?? '';

// ── Helpers ───────────────────────────────────────────────────────────────────
function esc($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function titleCasePHP($s) {
    if (!$s) return '';
    if ($s !== mb_strtoupper($s, 'UTF-8')) return $s;
    return mb_convert_case($s, MB_CASE_TITLE, 'UTF-8');
}

function sportIconPHP($t) {
    $t = mb_strtolower($t ?? '');
    if (str_contains($t,'fútbol') || str_contains($t,'futbol')) return 'fa-futbol';
    if (str_contains($t,'pádel')  || str_contains($t,'padel'))  return 'fa-table-tennis';
    if (str_contains($t,'tenis'))                                return 'fa-table-tennis';
    if (str_contains($t,'básquet')|| str_contains($t,'basket')) return 'fa-basketball-ball';
    if (str_contains($t,'vóley')  || str_contains($t,'voley'))  return 'fa-volleyball-ball';
    return 'fa-running';
}

function sportColorPHP($t) {
    $t = mb_strtolower($t ?? '');
    if (str_contains($t,'fútbol') || str_contains($t,'futbol')) return '#4cd964';
    if (str_contains($t,'pádel')  || str_contains($t,'padel'))  return '#3498db';
    if (str_contains($t,'tenis'))                                return '#ff9500';
    if (str_contains($t,'básquet')|| str_contains($t,'basket')) return '#e74c3c';
    if (str_contains($t,'vóley')  || str_contains($t,'voley'))  return '#9b59b6';
    return '#4cd964';
}

function hex2rgb($hex) {
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
    [$r,$g,$b] = array_map('hexdec', str_split($hex, 2));
    return "$r,$g,$b";
}

function fmtARS($n) {
    return '$' . number_format((float)$n, 0, ',', '.');
}

function formatDias($diasStr) {
    if (!$diasStr) return '';
    $dias = array_unique(array_map('intval', explode(',', $diasStr)));
    sort($dias);
    $nom = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
    if ($dias === [1,2,3,4,5,6,7]) return 'Todos los días';
    if ($dias === [1,2,3,4,5])     return 'Lun a Vie';
    if ($dias === [6,7])            return 'Fines de semana';
    if ($dias === [1,2,3,4,5,6])   return 'Lun a Sáb';
    return implode(' · ', array_map(fn($d) => $nom[$d] ?? '', $dias));
}

// ── Datos: predio ─────────────────────────────────────────────────────────────
$predio = null;
if (!empty($link)) {
    $stmt = mysqli_prepare($link,
        "SELECT co.COMPLEJO_ID, co.COMPLEJO_NOMBRE, co.COMPLEJO_DIRECCION,
                co.COMPLEJO_TELEFONO, co.COMPLEJO_EMAIL, co.COMPLEJO_DESCRIPCION,
                IFNULL(co.COMPLEJO_INSTAGRAM,'') AS COMPLEJO_INSTAGRAM,
                l.LOCALIDAD_NOMBRE, par.PARTIDO_NOMBRE, prov.PROVINCIA_NOMBRE,
                tc.TIPO_COMPLEJO_NOMBRE,
                GROUP_CONCAT(DISTINCT tip.TIPO_CANCHA_NOMBRE ORDER BY tip.TIPO_CANCHA_NOMBRE SEPARATOR ',') AS ACTIVIDADES,
                COUNT(DISTINCT ca.CANCHA_ID) AS TOTAL_CANCHAS
         FROM complejo co
         LEFT JOIN localidad l      ON l.LOCALIDAD_ID     = co.LOCALIDAD_ID
         LEFT JOIN partido par      ON par.PARTIDO_ID      = l.PARTIDO_ID
         LEFT JOIN provincia prov   ON prov.PROVINCIA_ID   = par.PROVINCIA_ID
         LEFT JOIN tipo_complejo tc ON tc.TIPO_COMPLEJO_ID = co.TIPO_COMPLEJO_ID
         LEFT JOIN cancha ca        ON ca.COMPLEJO_ID = co.COMPLEJO_ID AND ca.ACTIVO = 1
         LEFT JOIN tipo_cancha tip  ON tip.TIPO_CANCHA_ID  = ca.TIPO_CANCHA_ID
         WHERE co.COMPLEJO_ID = ? AND co.ACTIVO = 1
         GROUP BY co.COMPLEJO_ID");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $predio = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}
if (!$predio) { header('Location: index.php'); exit; }

// ── Datos: canchas con rango de precio y días activos ─────────────────────────
$canchas = [];
if (!empty($link)) {
    $stmt2 = mysqli_prepare($link,
        "SELECT ca.CANCHA_ID, ca.CANCHA_NOMBRE, ca.CANCHA_DESCRIPCION,
                tc.TIPO_CANCHA_NOMBRE, tc.TIPO_CANCHA_ICONO,
                MIN(fh.FRANJA_PRECIO) AS PRECIO_DESDE,
                MAX(fh.FRANJA_PRECIO) AS PRECIO_HASTA,
                MIN(fh.FRANJA_HORA_INICIO) AS HORA_APERTURA,
                MAX(fh.FRANJA_HORA_FIN)   AS HORA_CIERRE,
                GROUP_CONCAT(DISTINCT fd.DIA_ID ORDER BY fd.DIA_ID) AS DIAS_ACTIVOS
         FROM cancha ca
         LEFT JOIN tipo_cancha tc ON tc.TIPO_CANCHA_ID = ca.TIPO_CANCHA_ID
         LEFT JOIN franja_horaria fh ON fh.CANCHA_ID = ca.CANCHA_ID AND fh.ACTIVO = 1
         LEFT JOIN franja_dia fd    ON fd.FRANJA_ID = fh.FRANJA_ID
         WHERE ca.COMPLEJO_ID = ? AND ca.ACTIVO = 1
         GROUP BY ca.CANCHA_ID
         ORDER BY ca.CANCHA_NOMBRE");
    mysqli_stmt_bind_param($stmt2, 'i', $id);
    mysqli_stmt_execute($stmt2);
    $res2 = mysqli_stmt_get_result($stmt2);
    while ($row = mysqli_fetch_assoc($res2)) $canchas[] = $row;
}

// ── Datos: planes ─────────────────────────────────────────────────────────────
$planes = [];
if (!empty($link)) {
    $res3 = @mysqli_query($link,
        "SELECT PLAN_NOMBRE, PLAN_DESCRIPCION, PLAN_PRECIO, PLAN_CREDITOS, PLAN_DURACION
         FROM plan_predio
         WHERE COMPLEJO_ID = $id AND ACTIVO = 1
         ORDER BY PLAN_PRECIO ASC");
    if ($res3) while ($row = mysqli_fetch_assoc($res3)) $planes[] = $row;
}

// ── Preparar variables para la vista ─────────────────────────────────────────
$nombre      = titleCasePHP($predio['COMPLEJO_NOMBRE']);
$dir         = $predio['COMPLEJO_DIRECCION'] ?? '';
$tel         = $predio['COMPLEJO_TELEFONO']  ?? '';
$email       = $predio['COMPLEJO_EMAIL']     ?? '';
$desc        = $predio['COMPLEJO_DESCRIPCION'] ?? '';
$tipo        = titleCasePHP($predio['TIPO_COMPLEJO_NOMBRE'] ?? 'Complejo deportivo');
$loc         = implode(', ', array_filter([
    $predio['LOCALIDAD_NOMBRE'],
    $predio['PARTIDO_NOMBRE'],
    $predio['PROVINCIA_NOMBRE'],
]));
$instagram   = $predio['COMPLEJO_INSTAGRAM'] ?? '';
$igHandle    = ltrim($instagram, '@');
$igUrl       = $igHandle ? (str_contains($igHandle, 'instagram.com') ? 'https://' . ltrim($igHandle, 'https://') : 'https://instagram.com/' . rawurlencode($igHandle)) : '';
$wsp         = preg_replace('/\D/', '', $tel);
$mapsQuery   = urlencode(($dir ? $dir . ' ' : '') . $loc);

$actividades = array_values(array_filter(array_map('trim', explode(',', $predio['ACTIVIDADES'] ?? ''))));
$colores     = array_values(array_unique(array_map('sportColorPHP', $actividades)));
$gradA       = $colores[0] ?? '#4cd964';
$gradB       = $colores[1] ?? '#3498db';

$panelUrl = 'register.php';
if ($usuarioLogueado) {
    $panelUrl = ($usuarioPerfil === 5)
        ? 'view/maquetaCliente/LaCanchitaCliente.php'
        : 'view/maquetaAdmin/Dashboard.php';
}

$wspMsg = urlencode("Hola! Vi su predio en La Canchita y quiero hacer una reserva. ¿Me pueden ayudar?");
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= esc($nombre) ?> · La Canchita</title>
<meta name="description" content="<?= esc(substr($desc ?: "$tipo en $loc. Reservá tu turno online en La Canchita.", 0, 155)) ?>">
<meta property="og:title"       content="<?= esc($nombre) ?>">
<meta property="og:description" content="<?= esc(substr($desc ?: "$tipo en $loc", 0, 120)) ?>">
<meta property="og:type"        content="website">
<link rel="shortcut icon" href="config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
<link rel="stylesheet" href="config/pluggins/vendor/fontawesome-free/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#09090f;--s1:#101018;--s2:#16161f;--s3:#1d1d28;
  --border:rgba(255,255,255,.08);--border2:rgba(255,255,255,.14);
  --green:#4cd964;--blue:#3498db;--orange:#ff9500;--red:#e74c3c;
  --text:#f0f0f5;--muted:rgba(255,255,255,.45);
  --grad-a:<?= esc($gradA) ?>;--grad-b:<?= esc($gradB) ?>;
}
body{background:var(--bg);color:var(--text);font-family:'Segoe UI',system-ui,-apple-system,sans-serif;line-height:1.6;overflow-x:hidden}

/* ── NAVBAR ── */
.navbar{position:fixed;top:0;left:0;right:0;z-index:100;display:flex;align-items:center;justify-content:space-between;padding:0 5%;height:64px;background:rgba(9,9,15,.88);backdrop-filter:blur(14px);border-bottom:1px solid var(--border)}
.nav-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--text);font-weight:800;font-size:1rem}
.nav-brand img{height:32px;border-radius:7px}
.nav-links{display:flex;align-items:center;gap:10px}
.btn-ghost{padding:8px 16px;border:1px solid var(--border2);background:transparent;color:var(--text);border-radius:8px;font-size:.85rem;font-weight:600;cursor:pointer;text-decoration:none;transition:all .2s}
.btn-ghost:hover{border-color:rgba(255,255,255,.3);background:rgba(255,255,255,.06)}
.btn-green{padding:8px 18px;background:var(--green);color:#000;border:none;border-radius:8px;font-size:.85rem;font-weight:700;cursor:pointer;text-decoration:none;transition:opacity .2s}
.btn-green:hover{opacity:.88}
@media(max-width:600px){.nav-links .btn-ghost{display:none}}

/* ── HERO ── */
.hero{
  margin-top:64px;
  padding:60px 5% 52px;
  background:
    radial-gradient(ellipse at 20% 50%, rgba(<?= hex2rgb($gradA) ?>,.18) 0%, transparent 60%),
    radial-gradient(ellipse at 80% 30%, rgba(<?= hex2rgb($gradB) ?>,.14) 0%, transparent 55%),
    var(--s1);
  border-bottom:1px solid var(--border);
  position:relative;overflow:hidden
}
.hero::before{
  content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(90deg,rgba(255,255,255,.015) 0 1px,transparent 1px 60px),
             repeating-linear-gradient(0deg,rgba(255,255,255,.015) 0 1px,transparent 1px 60px);
  pointer-events:none
}
.hero-body{position:relative;max-width:860px}
.hero-badge{display:inline-flex;align-items:center;gap:7px;padding:5px 12px;border-radius:20px;background:rgba(<?= hex2rgb($gradA) ?>,.12);border:1px solid rgba(<?= hex2rgb($gradA) ?>,.25);color:<?= esc($gradA) ?>;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:16px}
.hero-name{font-size:clamp(1.8rem,5vw,3rem);font-weight:900;line-height:1.1;margin-bottom:10px;letter-spacing:-.03em}
.hero-loc{font-size:.95rem;color:var(--muted);display:flex;align-items:center;gap:7px;margin-bottom:20px}
.hero-loc i{color:var(--green)}
.hero-acts{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:28px}
.hero-act{padding:5px 13px;border-radius:20px;background:rgba(255,255,255,.07);border:1px solid var(--border2);font-size:.78rem;font-weight:600;color:var(--muted)}
.hero-ctas{display:flex;flex-wrap:wrap;gap:10px}
.hcta{display:inline-flex;align-items:center;gap:8px;padding:12px 22px;border-radius:10px;font-size:.9rem;font-weight:700;text-decoration:none;transition:all .2s;cursor:pointer;border:none}
.hcta-primary{background:var(--green);color:#000}
.hcta-primary:hover{opacity:.88}
.hcta-wsp{background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.3);color:#25d366}
.hcta-wsp:hover{background:rgba(37,211,102,.18)}
.hcta-tel{background:rgba(255,255,255,.07);border:1px solid var(--border2);color:var(--text)}
.hcta-tel:hover{background:rgba(255,255,255,.12)}
.hcta-ig{background:rgba(228,64,95,.1);border:1px solid rgba(228,64,95,.3);color:#e4405f}
.hcta-ig:hover{background:rgba(228,64,95,.18)}

/* ── STATS BAR ── */
.stats-bar{display:flex;gap:0;border-bottom:1px solid var(--border);background:var(--s2)}
.stat{flex:1;padding:18px 5%;border-right:1px solid var(--border);display:flex;align-items:center;gap:12px}
.stat:last-child{border-right:none}
.stat-icon{width:38px;height:38px;border-radius:9px;display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0}
.stat-val{font-size:1.1rem;font-weight:800;line-height:1}
.stat-lbl{font-size:.72rem;color:var(--muted);margin-top:2px}
@media(max-width:640px){.stats-bar{flex-wrap:wrap}.stat{min-width:50%;border-bottom:1px solid var(--border)}}

/* ── SECCIONES ── */
.section{padding:56px 5%}
.section-alt{background:var(--s1)}
.section-header{margin-bottom:32px}
.section-eyebrow{font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--green);margin-bottom:6px}
.section-title{font-size:1.6rem;font-weight:800;line-height:1.2}
.section-sub{font-size:.9rem;color:var(--muted);margin-top:6px}

/* ── DESCRIPCIÓN ── */
.desc-text{font-size:.95rem;color:rgba(255,255,255,.75);line-height:1.8;max-width:700px}

/* ── CANCHAS GRID ── */
.courts-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:16px}
.court-card{background:var(--s2);border:1px solid var(--border);border-radius:16px;overflow:hidden;transition:border-color .2s,transform .2s}
.court-card:hover{border-color:rgba(255,255,255,.18);transform:translateY(-2px)}
.court-card-head{padding:20px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:14px}
.court-icon{width:46px;height:46px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
.court-name{font-weight:700;font-size:.95rem;margin-bottom:3px}
.court-tipo{font-size:.75rem;color:var(--muted)}
.court-body{padding:16px 20px;display:flex;flex-direction:column;gap:10px}
.court-info-row{display:flex;align-items:center;gap:8px;font-size:.82rem;color:var(--muted)}
.court-info-row i{width:16px;text-align:center;flex-shrink:0}
.court-price{font-size:1.1rem;font-weight:800;color:var(--green)}
.court-price-lbl{font-size:.72rem;color:var(--muted);font-weight:400}
.court-desc{font-size:.8rem;color:var(--muted);line-height:1.5}
.court-no-hor{font-size:.8rem;color:var(--muted);font-style:italic}

/* ── PLANES GRID ── */
.plans-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:16px}
.plan-card{background:var(--s2);border:1px solid var(--border);border-radius:16px;padding:24px;display:flex;flex-direction:column;gap:8px;transition:border-color .2s,transform .2s}
.plan-card:hover{border-color:rgba(76,217,100,.3);transform:translateY(-2px)}
.plan-card.featured{border-color:rgba(76,217,100,.35);background:rgba(76,217,100,.04)}
.plan-name{font-size:1rem;font-weight:700}
.plan-price{font-size:2rem;font-weight:900;color:var(--green);line-height:1;margin:6px 0}
.plan-price span{font-size:.85rem;font-weight:400;color:var(--muted)}
.plan-desc{font-size:.8rem;color:var(--muted);line-height:1.5;flex:1}
.plan-meta{display:flex;flex-direction:column;gap:4px;margin-top:4px}
.plan-meta-item{display:flex;align-items:center;gap:7px;font-size:.78rem;color:var(--muted)}
.plan-meta-item i{color:var(--green);width:14px}
.plan-cta{margin-top:12px;padding:10px;border-radius:9px;border:1px solid rgba(76,217,100,.3);background:rgba(76,217,100,.06);color:var(--green);font-size:.85rem;font-weight:700;text-align:center;text-decoration:none;transition:all .2s;display:block}
.plan-cta:hover{background:rgba(76,217,100,.14);border-color:rgba(76,217,100,.5)}

/* ── COMO LLEGAR ── */
.location-card{background:var(--s2);border:1px solid var(--border);border-radius:16px;padding:28px;display:flex;align-items:center;gap:24px;flex-wrap:wrap}
.location-icon{width:56px;height:56px;border-radius:14px;background:rgba(76,217,100,.1);border:1px solid rgba(76,217,100,.2);display:flex;align-items:center;justify-content:center;font-size:1.4rem;color:var(--green);flex-shrink:0}
.location-info{flex:1;min-width:200px}
.location-title{font-size:1.1rem;font-weight:700;margin-bottom:4px}
.location-addr{font-size:.9rem;color:var(--muted)}
.location-sub{font-size:.8rem;color:var(--muted);margin-top:2px}
.btn-maps{display:inline-flex;align-items:center;gap:8px;padding:11px 20px;border-radius:9px;background:rgba(52,152,219,.1);border:1px solid rgba(52,152,219,.3);color:var(--blue);font-size:.85rem;font-weight:700;text-decoration:none;transition:all .2s}
.btn-maps:hover{background:rgba(52,152,219,.2)}

/* ── CTA FINAL ── */
.cta-section{padding:72px 5%;text-align:center;background:linear-gradient(180deg,var(--s1) 0%,var(--bg) 100%)}
.cta-title{font-size:clamp(1.5rem,4vw,2.4rem);font-weight:900;margin-bottom:10px}
.cta-sub{font-size:.95rem;color:var(--muted);margin-bottom:32px;max-width:480px;margin-left:auto;margin-right:auto}
.cta-btn-big{display:inline-flex;align-items:center;gap:10px;padding:16px 36px;border-radius:14px;background:var(--green);color:#000;font-size:1.05rem;font-weight:800;text-decoration:none;transition:opacity .2s;margin-bottom:12px}
.cta-btn-big:hover{opacity:.88}
.cta-note{font-size:.78rem;color:var(--muted)}
.cta-note a{color:var(--green);text-decoration:none}

/* ── FOOTER ── */
.footer{padding:24px 5%;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap;font-size:.8rem;color:var(--muted)}
.footer a{color:var(--muted);text-decoration:none;transition:color .2s}
.footer a:hover{color:var(--green)}
.footer-brand{font-weight:700;color:var(--text)}

/* ── MOBILE BOTTOM BAR ── */
.mbb{display:none;position:fixed;bottom:0;left:0;right:0;z-index:200;background:rgba(9,9,15,.95);border-top:1px solid var(--border);backdrop-filter:blur(12px);padding:10px 16px;padding-bottom:max(10px,env(safe-area-inset-bottom));gap:10px}
.mbb-btn{flex:1;display:flex;align-items:center;justify-content:center;gap:7px;padding:13px;border-radius:11px;font-size:.9rem;font-weight:700;text-decoration:none;border:none;cursor:pointer;transition:all .2s}
.mbb-wsp{background:rgba(37,211,102,.1);border:1px solid rgba(37,211,102,.25);color:#25d366}
.mbb-wsp:hover{background:rgba(37,211,102,.18)}
.mbb-res{background:var(--green);color:#000}
.mbb-res:hover{opacity:.88}
@media(max-width:768px){.mbb{display:flex}.cta-section{padding-bottom:100px}}

/* ── ANIMACIONES ── */
@keyframes fadeUp{from{opacity:0;transform:translateY(16px)}to{opacity:1;transform:translateY(0)}}
.fade-in{animation:fadeUp .4s ease both}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
    <a href="index.php" class="nav-brand">
        <img src="config/dist/img/loguito_lacanchita.WEBP" alt="La Canchita">
        La Canchita
    </a>
    <div class="nav-links">
        <?php if ($usuarioLogueado): ?>
            <a href="<?= esc($panelUrl) ?>" class="btn-ghost" style="display:flex;align-items:center;gap:8px">
                <span style="width:26px;height:26px;border-radius:50%;background:var(--green);color:#000;font-weight:700;font-size:.78rem;display:flex;align-items:center;justify-content:center;flex-shrink:0"><?= esc(mb_strtoupper(mb_substr($usuarioNombre,0,1))) ?></span>
                Mi panel
            </a>
        <?php else: ?>
            <a href="login.php"    class="btn-ghost">Iniciar sesión</a>
            <a href="register.php" class="btn-green">Reservar</a>
        <?php endif; ?>
    </div>
</nav>

<!-- HERO -->
<section class="hero fade-in">
    <div class="hero-body">
        <div class="hero-badge">
            <i class="fas fa-shield-alt"></i>
            <?= esc($tipo) ?>
        </div>
        <h1 class="hero-name"><?= esc($nombre) ?></h1>
        <?php if ($loc): ?>
        <div class="hero-loc">
            <i class="fas fa-map-marker-alt"></i>
            <?= esc($loc) ?>
            <?php if ($dir): ?> · <?= esc($dir) ?><?php endif; ?>
        </div>
        <?php endif; ?>
        <?php if ($actividades): ?>
        <div class="hero-acts">
            <?php foreach ($actividades as $act): ?>
            <span class="hero-act">
                <i class="fas <?= sportIconPHP($act) ?>" style="margin-right:5px;color:<?= esc(sportColorPHP($act)) ?>"></i>
                <?= esc($act) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="hero-ctas">
            <a href="<?= esc($panelUrl) ?>" class="hcta hcta-primary">
                <i class="fas fa-calendar-check"></i> Reservar turno
            </a>
            <?php if ($wsp): ?>
            <a href="https://wa.me/549<?= esc($wsp) ?>?text=<?= $wspMsg ?>" target="_blank" rel="noopener" class="hcta hcta-wsp">
                <i class="fab fa-whatsapp"></i> WhatsApp
            </a>
            <?php endif; ?>
            <?php if ($tel): ?>
            <a href="tel:<?= esc($tel) ?>" class="hcta hcta-tel">
                <i class="fas fa-phone"></i> <?= esc($tel) ?>
            </a>
            <?php endif; ?>
            <?php if ($igUrl): ?>
            <a href="<?= esc($igUrl) ?>" target="_blank" rel="noopener" class="hcta hcta-ig">
                <i class="fab fa-instagram"></i> Instagram
            </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- STATS BAR -->
<?php
$totalCanchas = (int)($predio['TOTAL_CANCHAS'] ?? 0);
$statItems = [];
if ($totalCanchas) $statItems[] = ['icon'=>'fa-clone','color'=>$gradA,'val'=>$totalCanchas,'lbl'=>'Canche' . ($totalCanchas!==1?'s':'')];
if ($actividades) $statItems[] = ['icon'=>'fa-running','color'=>$gradB,'val'=>count($actividades),'lbl'=>'Actividade' . (count($actividades)!==1?'s':'')];
if ($planes) $statItems[] = ['icon'=>'fa-tags','color'=>'#ff9500','val'=>count($planes),'lbl'=>'Plane' . (count($planes)!==1?'s':'')];
if ($email) $statItems[] = ['icon'=>'fa-envelope','color'=>'#9b59b6','val'=>$email,'lbl'=>'Contacto'];
?>
<?php if ($statItems): ?>
<div class="stats-bar">
    <?php foreach ($statItems as $st): ?>
    <div class="stat">
        <div class="stat-icon" style="background:rgba(<?= hex2rgb($st['color']) ?>,.1);color:<?= esc($st['color']) ?>">
            <i class="fas <?= esc($st['icon']) ?>"></i>
        </div>
        <div>
            <div class="stat-val"><?= esc((string)$st['val']) ?></div>
            <div class="stat-lbl"><?= esc($st['lbl']) ?></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- SOBRE EL PREDIO -->
<?php if ($desc): ?>
<section class="section">
    <div class="section-header">
        <div class="section-eyebrow">Sobre el predio</div>
        <h2 class="section-title">Conocé <?= esc($nombre) ?></h2>
    </div>
    <p class="desc-text"><?= nl2br(esc($desc)) ?></p>
</section>
<?php endif; ?>

<!-- NUESTRAS CANCHAS -->
<?php if ($canchas): ?>
<section class="section <?= $desc ? 'section-alt' : '' ?>">
    <div class="section-header">
        <div class="section-eyebrow">Instalaciones</div>
        <h2 class="section-title">Nuestras canchas</h2>
        <p class="section-sub">Conocé las canchas disponibles y sus horarios</p>
    </div>
    <div class="courts-grid">
        <?php foreach ($canchas as $c):
            $color   = sportColorPHP($c['TIPO_CANCHA_NOMBRE'] ?? '');
            $icon    = $c['TIPO_CANCHA_ICONO'] ? $c['TIPO_CANCHA_ICONO'] : sportIconPHP($c['TIPO_CANCHA_NOMBRE'] ?? '');
            $pDesde  = $c['PRECIO_DESDE'] ? (float)$c['PRECIO_DESDE'] : null;
            $pHasta  = $c['PRECIO_HASTA'] ? (float)$c['PRECIO_HASTA'] : null;
            $dias    = formatDias($c['DIAS_ACTIVOS'] ?? '');
            $hAper   = $c['HORA_APERTURA'] ? substr($c['HORA_APERTURA'],0,5) : null;
            $hCierre = $c['HORA_CIERRE']   ? substr($c['HORA_CIERRE'],0,5)   : null;
        ?>
        <div class="court-card">
            <div class="court-card-head">
                <div class="court-icon" style="background:rgba(<?= hex2rgb($color) ?>,.12);color:<?= esc($color) ?>">
                    <i class="fas <?= esc($icon) ?>"></i>
                </div>
                <div>
                    <div class="court-name"><?= esc($c['CANCHA_NOMBRE']) ?></div>
                    <div class="court-tipo"><?= esc($c['TIPO_CANCHA_NOMBRE'] ?? '') ?></div>
                </div>
            </div>
            <div class="court-body">
                <?php if ($c['CANCHA_DESCRIPCION']): ?>
                <p class="court-desc"><?= esc($c['CANCHA_DESCRIPCION']) ?></p>
                <?php endif; ?>

                <?php if ($pDesde): ?>
                <div>
                    <span class="court-price">
                        <?= fmtARS($pDesde) ?>
                        <?php if ($pHasta && $pHasta != $pDesde): ?> – <?= fmtARS($pHasta) ?><?php endif; ?>
                    </span>
                    <span class="court-price-lbl"> / turno</span>
                </div>
                <?php endif; ?>

                <?php if ($dias): ?>
                <div class="court-info-row">
                    <i class="fas fa-calendar-week" style="color:var(--green)"></i>
                    <?= esc($dias) ?>
                </div>
                <?php endif; ?>

                <?php if ($hAper && $hCierre): ?>
                <div class="court-info-row">
                    <i class="fas fa-clock" style="color:var(--orange)"></i>
                    <?= esc($hAper) ?> – <?= esc($hCierre) ?>hs
                </div>
                <?php endif; ?>

                <?php if (!$pDesde && !$dias): ?>
                <p class="court-no-hor">Consultar disponibilidad y precios</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- NUESTROS PLANES -->
<?php if ($planes): ?>
<section class="section <?= !$canchas || $desc ? '' : 'section-alt' ?>">
    <div class="section-header">
        <div class="section-eyebrow">Suscripciones</div>
        <h2 class="section-title">Nuestros planes</h2>
        <p class="section-sub">Acceso preferencial y precios especiales para socios</p>
    </div>
    <div class="plans-grid">
        <?php foreach ($planes as $i => $p):
            $creditos = (int)$p['PLAN_CREDITOS'];
            $duracion = (int)$p['PLAN_DURACION'];
            $featured = ($i === 0);
        ?>
        <div class="plan-card <?= $featured ? 'featured' : '' ?>">
            <?php if ($featured): ?>
            <div style="font-size:.7rem;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:var(--green);margin-bottom:2px">
                <i class="fas fa-star"></i> Más popular
            </div>
            <?php endif; ?>
            <div class="plan-name"><?= esc($p['PLAN_NOMBRE']) ?></div>
            <div class="plan-price">
                <?= fmtARS($p['PLAN_PRECIO']) ?>
                <span>/ mes</span>
            </div>
            <?php if ($p['PLAN_DESCRIPCION']): ?>
            <p class="plan-desc"><?= esc($p['PLAN_DESCRIPCION']) ?></p>
            <?php endif; ?>
            <div class="plan-meta">
                <div class="plan-meta-item">
                    <i class="fas fa-ticket-alt"></i>
                    <?= $creditos === 0 ? 'Créditos ilimitados' : "$creditos crédito" . ($creditos !== 1 ? 's' : '') ?>
                </div>
                <div class="plan-meta-item">
                    <i class="fas fa-calendar"></i>
                    Vigencia: <?= $duracion ?> días
                </div>
            </div>
            <?php if ($wsp): ?>
            <a href="https://wa.me/549<?= esc($wsp) ?>?text=<?= urlencode("Hola! Me interesa el plan {$p['PLAN_NOMBRE']} de $nombre.") ?>" target="_blank" rel="noopener" class="plan-cta">
                <i class="fab fa-whatsapp"></i> Consultar
            </a>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- CÓMO LLEGAR -->
<?php if ($dir || $loc): ?>
<section class="section section-alt">
    <div class="section-header">
        <div class="section-eyebrow">Ubicación</div>
        <h2 class="section-title">Cómo llegar</h2>
    </div>
    <div class="location-card">
        <div class="location-icon"><i class="fas fa-map-marker-alt"></i></div>
        <div class="location-info">
            <div class="location-title"><?= esc($nombre) ?></div>
            <?php if ($dir): ?>
            <div class="location-addr"><?= esc($dir) ?></div>
            <?php endif; ?>
            <?php if ($loc): ?>
            <div class="location-sub"><?= esc($loc) ?></div>
            <?php endif; ?>
        </div>
        <a href="https://www.google.com/maps/search/?api=1&query=<?= $mapsQuery ?>" target="_blank" rel="noopener" class="btn-maps">
            <i class="fas fa-directions"></i> Ver en Google Maps
        </a>
    </div>
</section>
<?php endif; ?>

<!-- CTA FINAL -->
<section class="cta-section">
    <div class="section-eyebrow" style="margin-bottom:12px;display:inline-block">Reservas online</div>
    <h2 class="cta-title">Reservá tu turno en línea</h2>
    <p class="cta-sub">Encontrá el horario que más te convenga y confirmá tu reserva en segundos.</p>
    <br>
    <a href="<?= esc($panelUrl) ?>" class="cta-btn-big">
        <i class="fas fa-calendar-check"></i> Reservar en La Canchita
    </a>
    <br>
    <span class="cta-note">
        <?php if ($usuarioLogueado): ?>
            Estás logueado como <strong><?= esc($usuarioNombre) ?></strong>
        <?php else: ?>
            ¿Ya tenés cuenta? <a href="login.php">Iniciá sesión</a>
        <?php endif; ?>
    </span>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div>
        <span class="footer-brand">La Canchita</span> · <?= esc($nombre) ?>
    </div>
    <div style="display:flex;gap:20px;flex-wrap:wrap">
        <a href="index.php">Inicio</a>
        <?php if (!$usuarioLogueado): ?>
        <a href="register.php">Registrarse</a>
        <a href="login.php">Iniciar sesión</a>
        <?php else: ?>
        <a href="<?= esc($panelUrl) ?>">Mi panel</a>
        <a href="logout.php">Salir</a>
        <?php endif; ?>
    </div>
</footer>

<!-- MOBILE BOTTOM BAR -->
<div class="mbb">
    <?php if ($wsp): ?>
    <a href="https://wa.me/549<?= esc($wsp) ?>?text=<?= $wspMsg ?>" target="_blank" rel="noopener" class="mbb-btn mbb-wsp">
        <i class="fab fa-whatsapp"></i> WhatsApp
    </a>
    <?php endif; ?>
    <a href="<?= esc($panelUrl) ?>" class="mbb-btn mbb-res">
        <i class="fas fa-calendar-check"></i> Reservar
    </a>
</div>

</body>
</html>
