<?php
require_once '../../config/dist/script/php/auth_view.php';
require_once '../../config/dist/script/php/conn.php';
require_view(2, 2); // Solo dueños

$uid    = (int)$_SESSION['usuario_id'];
$nombre = $_SESSION['usuario_nombre'] ?? '';
$PWA_BASE = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurá tu predio · La Canchita</title>
<?php require_once '../../config/dist/script/php/pwa_head.php'; ?>
<link rel="stylesheet" href="../../config/pluggins/vendor/fontawesome-free/css/all.min.css">
<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

  :root {
    --bg:    #09090f;
    --card:  #101018;
    --card2: #16161f;
    --bdr:   rgba(255,255,255,.07);
    --green: #4cd964;
    --muted: rgba(255,255,255,.45);
    --txt:   #f0f0f5;
    --red:   #e74c3c;
  }

  body {
    background: var(--bg);
    color: var(--txt);
    font-family: 'Segoe UI', system-ui, -apple-system, Arial, sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding: 24px 16px 60px;
  }

  /* ── Header ── */
  .ob-brand {
    font-size: 20px; font-weight: 800; color: var(--green);
    letter-spacing: -.4px; margin-bottom: 32px; margin-top: 8px;
  }

  /* ── Progress stepper ── */
  .stepper {
    display: flex; align-items: center; gap: 0;
    margin-bottom: 36px; width: 100%; max-width: 480px;
  }
  .step-item { display: flex; flex-direction: column; align-items: center; flex: 1; }
  .step-circle {
    width: 36px; height: 36px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px; font-weight: 700; transition: all .3s;
    border: 2px solid var(--bdr); background: var(--card2); color: var(--muted);
    position: relative; z-index: 1;
  }
  .step-circle.active { border-color: var(--green); color: var(--green); background: rgba(76,217,100,.12); }
  .step-circle.done   { background: var(--green); border-color: var(--green); color: #0d0d0d; }
  .step-label { font-size: 11px; color: var(--muted); margin-top: 6px; text-align: center; }
  .step-label.active { color: var(--green); }
  .step-connector {
    flex: 1; height: 2px; background: var(--bdr); margin-bottom: 18px; transition: background .3s;
  }
  .step-connector.done { background: var(--green); }

  /* ── Card ── */
  .ob-card {
    background: var(--card); border: 1px solid var(--bdr);
    border-radius: 20px; padding: 32px 28px;
    width: 100%; max-width: 480px;
    animation: fadeUp .3s ease;
  }
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
  }

  .ob-title { font-size: 20px; font-weight: 800; margin-bottom: 4px; }
  .ob-sub   { font-size: 13px; color: var(--muted); margin-bottom: 24px; line-height: 1.5; }

  /* ── Form controls ── */
  .field { margin-bottom: 16px; }
  .field label { display: block; font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 6px; text-transform: uppercase; letter-spacing: .04em; }
  .field input, .field select {
    width: 100%; padding: 12px 14px;
    background: var(--card2); border: 1px solid var(--bdr);
    border-radius: 10px; color: var(--txt); font-size: 14px; outline: none;
    transition: border-color .2s;
    -webkit-appearance: none; appearance: none;
  }
  .field input:focus, .field select:focus { border-color: var(--green); }
  .field input::placeholder { color: var(--muted); }
  .field select option { background: #1a1a2e; }
  .field select:disabled { opacity: .45; cursor: not-allowed; }
  .field-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }

  /* ── Tipo pills ── */
  .pill-grid { display: flex; flex-wrap: wrap; gap: 8px; }
  .pill {
    padding: 8px 14px; border-radius: 8px; border: 1px solid var(--bdr);
    background: var(--card2); color: var(--muted); font-size: 13px; cursor: pointer;
    transition: all .15s; user-select: none;
  }
  .pill:hover { border-color: rgba(76,217,100,.4); color: var(--txt); }
  .pill.selected { border-color: var(--green); background: rgba(76,217,100,.12); color: var(--green); font-weight: 600; }

  /* ── Días de la semana (colored) ── */
  .ob-dias-wrap { display: flex; gap: 7px; flex-wrap: wrap; }
  .ob-dia-btn {
    width: 38px; height: 38px; border-radius: 9px; border: 1px solid var(--bdr);
    background: var(--card2); color: var(--muted); font-size: 10px; font-weight: 800;
    cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: all .15s; user-select: none;
  }

  /* ── Mode tabs ── */
  .ob-mode-tabs { display: flex; gap: 6px; margin-bottom: 20px; }
  .ob-mode-tab {
    flex: 1; padding: 10px 8px; border-radius: 10px; border: 1px solid var(--bdr);
    background: var(--card2); color: var(--muted); font-size: 12px; font-weight: 600;
    cursor: pointer; transition: all .15s; display: flex; align-items: center;
    justify-content: center; gap: 6px;
  }
  .ob-mode-tab.active { border-color: var(--green); background: rgba(76,217,100,.1); color: var(--green); }

  /* ── Slot rows ── */
  .ob-slot-row { display: grid; grid-template-columns: 1fr auto 1fr auto; gap: 8px; align-items: center; margin-bottom: 8px; }
  .ob-slot-arrow { color: var(--muted); font-size: 14px; text-align: center; }
  .ob-slot-rm { width: 30px; height: 30px; border-radius: 7px; border: 1px solid var(--bdr); background: none; color: var(--muted); cursor: pointer; font-size: 12px; transition: all .15s; }
  .ob-slot-rm:hover { border-color: rgba(231,76,60,.4); color: #e74c3c; }
  .ob-add-slot-btn {
    background: none; border: 1px dashed var(--bdr); color: var(--muted); border-radius: 8px;
    padding: 8px 12px; font-size: 12px; cursor: pointer; width: 100%; transition: all .15s; margin-top: 4px;
  }
  .ob-add-slot-btn:hover { border-color: rgba(76,217,100,.4); color: var(--green); }

  /* ── Preview box ── */
  .ob-preview {
    background: rgba(76,217,100,.06); border: 1px solid rgba(76,217,100,.2);
    border-radius: 10px; padding: 12px 14px; margin: 12px 0;
  }
  .ob-preview-label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--green); margin-bottom: 8px; }

  /* ── Franja cards (added list) ── */
  .ob-franja-card {
    background: var(--card2); border: 1px solid var(--bdr); border-radius: 12px;
    padding: 12px 14px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px;
    animation: fadeUp .2s ease;
  }
  .ob-franja-hora { text-align: center; min-width: 72px; flex-shrink: 0; }
  .ob-franja-hora .hora-ini { font-size: 18px; font-weight: 800; line-height: 1; }
  .ob-franja-hora .hora-arrow { font-size: 10px; color: var(--muted); margin: 2px 0; }
  .ob-franja-hora .hora-fin { font-size: 16px; font-weight: 600; color: var(--muted); line-height: 1; }
  .ob-franja-body { flex: 1; min-width: 0; }
  .ob-franja-dias { display: flex; gap: 4px; flex-wrap: wrap; margin-bottom: 6px; }
  .ob-franja-precio { font-size: 17px; font-weight: 800; color: var(--green); }
  .ob-franja-del { width: 30px; height: 30px; border-radius: 8px; border: 1px solid var(--bdr); background: none; color: var(--muted); cursor: pointer; font-size: 12px; flex-shrink: 0; transition: all .15s; }
  .ob-franja-del:hover { border-color: rgba(231,76,60,.4); color: #e74c3c; }

  /* ── Franjas section header ── */
  .ob-franjas-header { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin: 16px 0 8px; display: flex; align-items: center; gap: 8px; }
  .ob-franjas-header::after { content: ''; flex: 1; height: 1px; background: var(--bdr); }

  /* ── Buttons ── */
  .btn-primary {
    width: 100%; padding: 14px; border-radius: 12px; border: 0;
    background: var(--green); color: #0d0d0d; font-size: 15px; font-weight: 700;
    cursor: pointer; transition: opacity .15s; margin-top: 8px;
  }
  .btn-primary:disabled { opacity: .45; cursor: not-allowed; }
  .btn-primary:not(:disabled):hover { opacity: .88; }

  .btn-back {
    background: none; border: 1px solid var(--bdr); color: var(--muted);
    border-radius: 12px; padding: 12px; font-size: 14px; cursor: pointer;
    width: 100%; margin-top: 8px; transition: all .15s;
  }
  .btn-back:hover { border-color: rgba(255,255,255,.2); color: var(--txt); }

  /* ── Error / toast ── */
  .err-msg {
    background: rgba(231,76,60,.12); border: 1px solid rgba(231,76,60,.3);
    border-radius: 10px; padding: 10px 14px; font-size: 13px; color: #e74c3c;
    margin-bottom: 14px; display: none;
  }
  .err-msg.show { display: block; }

  /* ── Skip link ── */
  .skip-link {
    margin-top: 20px; font-size: 13px; color: var(--muted); cursor: pointer;
    text-decoration: underline; text-underline-offset: 3px; background: none; border: 0;
  }
  .skip-link:hover { color: var(--txt); }

  /* ── Done screen ── */
  .done-screen { text-align: center; }
  .done-icon {
    width: 80px; height: 80px; border-radius: 50%;
    background: rgba(76,217,100,.15); border: 2px solid var(--green);
    display: flex; align-items: center; justify-content: center;
    font-size: 36px; margin: 0 auto 20px;
  }
  .done-link {
    background: var(--card2); border: 1px solid var(--bdr); border-radius: 10px;
    padding: 12px 14px; font-size: 12px; color: var(--muted);
    word-break: break-all; margin: 16px 0; text-align: left;
  }
  .done-link strong { display: block; font-size: 11px; text-transform: uppercase; letter-spacing: .04em; margin-bottom: 4px; }
  .done-link a { color: var(--green); text-decoration: none; }

  /* ── Add more / list (legacy, kept for other steps) ── */
  .added-list { margin-top: 12px; }

  .divider { border: none; border-top: 1px solid var(--bdr); margin: 20px 0; }

  /* Loading spinner */
  .spinner { display: inline-block; width: 18px; height: 18px; border: 2px solid rgba(0,0,0,.2); border-top-color: #0d0d0d; border-radius: 50%; animation: spin .6s linear infinite; vertical-align: middle; margin-right: 6px; }
  @keyframes spin { to { transform: rotate(360deg); } }
</style>
</head>
<body>

<div class="ob-brand">La Canchita</div>

<!-- Stepper -->
<div class="stepper" id="stepper">
  <div class="step-item">
    <div class="step-circle active" id="sc1">1</div>
    <div class="step-label active" id="sl1">Predio</div>
  </div>
  <div class="step-connector" id="conn1"></div>
  <div class="step-item">
    <div class="step-circle" id="sc2">2</div>
    <div class="step-label" id="sl2">Cancha</div>
  </div>
  <div class="step-connector" id="conn2"></div>
  <div class="step-item">
    <div class="step-circle" id="sc3">3</div>
    <div class="step-label" id="sl3">Horarios</div>
  </div>
  <div class="step-connector" id="conn3"></div>
  <div class="step-item">
    <div class="step-circle" id="sc4">✓</div>
    <div class="step-label" id="sl4">Listo</div>
  </div>
</div>

<!-- STEP 1: Predio -->
<div class="ob-card" id="step1">
  <div class="ob-title">¡Bienvenido, <?= htmlspecialchars($nombre) ?>!</div>
  <div class="ob-sub">Empecemos configurando tu predio deportivo. Solo te va a tomar unos minutos.</div>
  <div class="err-msg" id="err1"></div>

  <div class="field">
    <label>Nombre del predio *</label>
    <input id="f-nombre" type="text" placeholder="Ej: Complejo Deportivo San Martín" maxlength="120">
  </div>
  <div class="field">
    <label>Dirección *</label>
    <input id="f-dir" type="text" placeholder="Calle y número" maxlength="200">
  </div>
  <div class="field">
    <label>Provincia *</label>
    <select id="f-prov" onchange="onProv()">
      <option value="">Cargando...</option>
    </select>
  </div>
  <div class="field-row">
    <div class="field" style="margin:0">
      <label>Partido / municipio *</label>
      <select id="f-partido" onchange="onPartido()" disabled>
        <option value="">Primero elegí provincia</option>
      </select>
    </div>
    <div class="field" style="margin:0">
      <label>Localidad *</label>
      <select id="f-loc" disabled>
        <option value="">Primero elegí partido</option>
      </select>
    </div>
  </div>
  <div class="field-row" style="margin-top:16px">
    <div class="field" style="margin:0">
      <label>Teléfono</label>
      <input id="f-tel" type="text" placeholder="+54 11 ..." maxlength="30">
    </div>
    <div class="field" style="margin:0">
      <label>Email de contacto</label>
      <input id="f-email" type="email" placeholder="predio@..." maxlength="120">
    </div>
  </div>

  <button class="btn-primary" onclick="guardarPredio(this)">Continuar →</button>
  <button class="skip-link" onclick="skipOnboarding()">Configurar después</button>
</div>

<!-- STEP 2: Cancha -->
<div class="ob-card" id="step2" style="display:none">
  <div class="ob-title">Agregá tu primera cancha</div>
  <div class="ob-sub">Podés agregar más canchas desde el panel después de la configuración inicial.</div>
  <div class="err-msg" id="err2"></div>

  <div class="field">
    <label>Nombre de la cancha *</label>
    <input id="c-nombre" type="text" placeholder="Ej: Cancha 1, Pista Central..." maxlength="100">
  </div>

  <div class="field">
    <label>Tipo de cancha *</label>
    <div class="pill-grid" id="tipo-pills">
      <div class="pill" style="color:var(--muted)"><i class="fas fa-spinner fa-spin"></i> Cargando...</div>
    </div>
    <input type="hidden" id="c-tipo">
  </div>

  <hr class="divider">
  <button class="btn-primary" onclick="guardarCancha(this)">Continuar →</button>
  <button class="btn-back" onclick="goStep(1)">← Volver</button>
  <button class="skip-link" onclick="skipOnboarding()">Configurar después</button>
</div>

<!-- STEP 3: Horarios -->
<div class="ob-card" id="step3" style="display:none">
  <div class="ob-title">Configurá los horarios</div>
  <div class="ob-sub">Definí cuándo está disponible la cancha. Podés agregar múltiples franjas o generarlas automáticamente.</div>
  <div class="err-msg" id="err3"></div>

  <!-- Tabs de modo -->
  <div class="ob-mode-tabs">
    <button class="ob-mode-tab active" id="obTabManual" onclick="obSetMode('manual')">
      <i class="fas fa-clock"></i> Franja individual
    </button>
    <button class="ob-mode-tab" id="obTabGenerar" onclick="obSetMode('generar')">
      <i class="fas fa-magic"></i> Generar toda la semana
    </button>
  </div>

  <!-- ─── Panel: franja individual ─── -->
  <div id="obPanelManual">
    <div class="field">
      <label>Días disponibles *</label>
      <div class="ob-dias-wrap" id="obDiasGrid"></div>
    </div>

    <div class="field">
      <label>Horarios *</label>
      <div id="obSlotsList"></div>
      <button type="button" class="ob-add-slot-btn" onclick="obAddSlot()">
        <i class="fas fa-plus"></i> Agregar otro horario
      </button>
    </div>

    <div class="field">
      <label>Precio por turno ($) *</label>
      <input id="ob-precio" type="number" min="0" step="100" placeholder="Ej: 5000"
        oninput="obActualizarPreview()">
      <label style="display:flex;align-items:center;gap:8px;margin-top:8px;cursor:pointer;user-select:none">
        <input type="checkbox" id="obMantenerPrecio" style="width:16px;height:16px;accent-color:var(--green);cursor:pointer"
          onchange="obActualizarPreview()">
        <span style="font-size:12px;color:var(--muted)">Mantener este precio en la próxima franja</span>
      </label>
    </div>

    <div id="obPreviewManual" class="ob-preview" style="display:none">
      <div class="ob-preview-label">Vista previa</div>
      <div id="obPreviewHoras" style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:6px"></div>
      <div id="obPreviewDias" style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px"></div>
      <div id="obPreviewPrecio"></div>
    </div>

    <button class="btn-primary" id="btn-add-franja" onclick="obGuardarFranja(this)">
      Agregar franja
    </button>
  </div>

  <!-- ─── Panel: generador semanal ─── -->
  <div id="obPanelGenerar" style="display:none">
    <div class="field">
      <label>Días disponibles *</label>
      <div class="ob-dias-wrap" id="obGenDiasGrid"></div>
    </div>

    <div class="field-row">
      <div class="field" style="margin:0">
        <label>Apertura *</label>
        <input id="obGenApertura" type="time" value="08:00" style="color-scheme:dark" oninput="obGenActualizarPreview()">
      </div>
      <div class="field" style="margin:0">
        <label>Cierre *</label>
        <input id="obGenCierre" type="time" value="22:00" style="color-scheme:dark" oninput="obGenActualizarPreview()">
      </div>
    </div>

    <div class="field-row" style="margin-top:12px">
      <div class="field" style="margin:0">
        <label>Duración turno *</label>
        <select id="obGenDuracion" onchange="obGenActualizarPreview()">
          <option value="30">30 min</option>
          <option value="60" selected>60 min (1 hora)</option>
          <option value="90">90 min</option>
          <option value="120">120 min (2 hs)</option>
        </select>
      </div>
      <div class="field" style="margin:0">
        <label>Precio por turno ($) *</label>
        <input id="obGenPrecio" type="number" min="0" step="100" placeholder="Ej: 5000"
          oninput="obGenActualizarPreview()">
      </div>
    </div>

    <div id="obGenPreview" class="ob-preview" style="display:none">
      <div class="ob-preview-label">Se crearán <strong id="obGenCount">0</strong> franjas horarias</div>
      <div id="obGenSlots" style="display:flex;flex-wrap:wrap;gap:6px;margin-top:6px"></div>
    </div>

    <button class="btn-primary" id="btn-gen-franjas" onclick="obGenerarSemana(this)">
      <i class="fas fa-magic"></i> Generar franjas
    </button>
  </div>

  <!-- Franjas creadas -->
  <div id="ob-franjas-list"></div>

  <button class="btn-primary" id="btn-finish" onclick="finalizarOnboarding(this)"
    style="display:none;background:var(--card2);border:1px solid var(--green);color:var(--green);margin-top:16px">
    <i class="fas fa-check-circle"></i> Guardar y finalizar
  </button>
  <button class="btn-back" onclick="goStep(2)" style="margin-top:8px">← Volver</button>
  <button class="skip-link" onclick="skipOnboarding()">Configurar después</button>
</div>

<!-- DONE -->
<div class="ob-card done-screen" id="stepDone" style="display:none">
  <div class="done-icon">🎉</div>
  <div class="ob-title">¡Tu predio está listo!</div>
  <div class="ob-sub" id="done-sub">Ya podés compartir el link público de tu predio y empezar a recibir reservas.</div>
  <div class="done-link" id="done-link-box" style="display:none">
    <strong>Link público de tu predio</strong>
    <a id="done-link-a" href="#" target="_blank"></a>
  </div>
  <button class="btn-primary" onclick="location.href='Dashboard.php'">Ir al panel de administración</button>
</div>

<script>
const API_GEO = 'api/geo.php';
const API_CAT = 'api/catalogo.php';
const API_ONB = 'api/onboarding_completo.php';

const S = { step: 1, predioData: null, canchaData: null };

// ── Utilidades ────────────────────────────────────────────────────────────────
async function apiFetch(url, data) {
  const opts = data
    ? { method: 'POST', body: new URLSearchParams(data) }
    : { method: 'GET' };
  const r = await fetch(url, opts);
  return r.json();
}

function showErr(id, msg) {
  const el = document.getElementById(id);
  el.textContent = msg;
  el.classList.add('show');
  setTimeout(() => el.classList.remove('show'), 5000);
}

function setLoading(btn, loading) {
  if (loading) {
    btn._txt = btn.innerHTML;
    btn.innerHTML = '<span class="spinner"></span>Guardando...';
    btn.disabled = true;
  } else {
    btn.innerHTML = btn._txt;
    btn.disabled = false;
  }
}

// ── Stepper UI ────────────────────────────────────────────────────────────────
function goStep(n) {
  ['step1','step2','step3','stepDone'].forEach(id => {
    document.getElementById(id).style.display = 'none';
  });
  const target = n === 'done' ? 'stepDone' : 'step' + n;
  document.getElementById(target).style.display = '';

  for (let i = 1; i <= 4; i++) {
    const sc = document.getElementById('sc' + i);
    const sl = document.getElementById('sl' + i);
    if (!sc) continue;
    sc.classList.remove('active','done');
    sl && sl.classList.remove('active');
    if (n === 'done') {
      sc.classList.add('done');
    } else if (i < n) {
      sc.classList.add('done');
    } else if (i === n) {
      sc.classList.add('active');
      sl && sl.classList.add('active');
    }
    const conn = document.getElementById('conn' + i);
    if (conn) conn.classList.toggle('done', n === 'done' || i < n);
  }
  if (n === 'done') {
    document.getElementById('sc4').classList.remove('active');
    document.getElementById('sc4').classList.add('done');
    document.getElementById('sl4').classList.add('active');
  }
  S.step = n;
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

// ── Geo cascade ───────────────────────────────────────────────────────────────
async function initGeo() {
  const j = await apiFetch(API_GEO + '?action=provincias');
  const sel = document.getElementById('f-prov');
  if (!j.ok || !j.data?.length) { sel.innerHTML = '<option value="">Sin datos</option>'; return; }
  sel.innerHTML = '<option value="">— Seleccioná provincia —</option>' +
    j.data.map(p => `<option value="${p.PROVINCIA_ID}">${p.PROVINCIA_NOMBRE}</option>`).join('');
}

async function onProv() {
  const provId = document.getElementById('f-prov').value;
  const sel = document.getElementById('f-partido');
  const selL = document.getElementById('f-loc');
  sel.innerHTML = '<option value="">Cargando...</option>';
  sel.disabled = true;
  selL.innerHTML = '<option value="">Primero elegí partido</option>';
  selL.disabled = true;
  if (!provId) return;
  const j = await apiFetch(API_GEO + '?action=partidos&provincia_id=' + provId);
  if (!j.ok || !j.data?.length) { sel.innerHTML = '<option value="">Sin partidos</option>'; return; }
  sel.innerHTML = '<option value="">— Seleccioná partido —</option>' +
    j.data.map(p => `<option value="${p.PARTIDO_ID}">${p.PARTIDO_NOMBRE}</option>`).join('');
  sel.disabled = false;
}

async function onPartido() {
  const partidoId = document.getElementById('f-partido').value;
  const sel = document.getElementById('f-loc');
  sel.innerHTML = '<option value="">Cargando...</option>';
  sel.disabled = true;
  if (!partidoId) return;
  const j = await apiFetch(API_GEO + '?action=localidades&partido_id=' + partidoId);
  if (!j.ok || !j.data?.length) { sel.innerHTML = '<option value="">Sin localidades</option>'; return; }
  sel.innerHTML = '<option value="">— Seleccioná localidad —</option>' +
    j.data.map(l => `<option value="${l.LOCALIDAD_ID}">${l.LOCALIDAD_NOMBRE}</option>`).join('');
  sel.disabled = false;
}

// ── Tipos de cancha ───────────────────────────────────────────────────────────
async function initTipos() {
  const j = await apiFetch(API_CAT + '?action=listar&tabla=tipo_cancha');
  const grid = document.getElementById('tipo-pills');
  if (!j.ok || !j.data?.length) {
    grid.innerHTML = '<p style="color:var(--muted);font-size:13px">No hay tipos disponibles.</p>';
    return;
  }
  grid.innerHTML = j.data.filter(t => t.ACTIVO == 1).map(t =>
    `<div class="pill" data-id="${t.TIPO_CANCHA_ID}" onclick="selTipo(this)">
      ${t.TIPO_CANCHA_ICONO ? `<i class="fas ${t.TIPO_CANCHA_ICONO}" style="margin-right:6px;color:var(--green)"></i>` : ''}
      ${t.TIPO_CANCHA_NOMBRE}
    </div>`
  ).join('');
}

function selTipo(el) {
  document.querySelectorAll('#tipo-pills .pill').forEach(p => p.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('c-tipo').value = el.dataset.id;
}

// ── Constantes de días (igual que Dashboard) ─────────────────────────────────
const OB_DIAS_COLOR = {1:'#3498db',2:'#2ecc71',3:'#9b59b6',4:'#e67e22',5:'#e74c3c',6:'#f39c12',7:'#1abc9c'};
const OB_DIAS_CORTO = {1:'Lun',2:'Mar',3:'Mié',4:'Jue',5:'Vie',6:'Sáb',7:'Dom'};

// ── Estado step 3 ─────────────────────────────────────────────────────────────
let obMode        = 'manual';
let obDiasActivos = new Set([1,2,3,4,5]);
let obSlots       = [{ ini: '08:00', fin: '09:00' }];
let obGenDiasActivos = new Set([1,2,3,4,5]);
let obFranjasCreadas = []; // [{id, ini, fin, dias, precio}]

function obHexToRgb(hex) {
  const m = hex.replace('#','').match(/.{2}/g);
  return m ? m.map(x=>parseInt(x,16)).join(',') : '255,255,255';
}

// ── Tabs de modo ─────────────────────────────────────────────────────────────
function obSetMode(mode) {
  obMode = mode;
  document.getElementById('obTabManual').classList.toggle('active', mode === 'manual');
  document.getElementById('obTabGenerar').classList.toggle('active', mode === 'generar');
  document.getElementById('obPanelManual').style.display  = mode === 'manual'  ? '' : 'none';
  document.getElementById('obPanelGenerar').style.display = mode === 'generar' ? '' : 'none';
}

// ── Selector de días (manual) ─────────────────────────────────────────────────
function obRenderDias() {
  const wrap = document.getElementById('obDiasGrid');
  wrap.innerHTML = [1,2,3,4,5,6,7].map(d => {
    const sel = obDiasActivos.has(d);
    const color = OB_DIAS_COLOR[d];
    return `<div onclick="obToggleDia(${d})" style="
        width:40px;height:40px;border-radius:9px;cursor:pointer;
        display:flex;align-items:center;justify-content:center;
        border:1px solid ${sel ? color : 'var(--bdr)'};
        background:${sel ? `rgba(${obHexToRgb(color)},.15)` : 'var(--card2)'};
        transition:all .15s;user-select:none">
      <span style="font-size:10px;font-weight:800;color:${sel ? color : 'var(--muted)'}">${OB_DIAS_CORTO[d]}</span>
    </div>`;
  }).join('');
  obActualizarPreview();
}

function obToggleDia(d) {
  obDiasActivos.has(d) ? obDiasActivos.delete(d) : obDiasActivos.add(d);
  obRenderDias();
}

// ── Slots de horario ──────────────────────────────────────────────────────────
function obRenderSlots() {
  document.getElementById('obSlotsList').innerHTML = obSlots.map((s, i) => `
    <div class="ob-slot-row">
      <input type="time" class="field input" value="${s.ini}" style="color-scheme:dark;padding:10px 12px;background:var(--card2);border:1px solid var(--bdr);border-radius:9px;color:var(--txt);font-size:14px;outline:none;width:100%"
        oninput="obSlots[${i}].ini=this.value;obActualizarPreview()">
      <span class="ob-slot-arrow">→</span>
      <input type="time" class="field input" value="${s.fin}" style="color-scheme:dark;padding:10px 12px;background:var(--card2);border:1px solid var(--bdr);border-radius:9px;color:var(--txt);font-size:14px;outline:none;width:100%"
        oninput="obSlots[${i}].fin=this.value;obActualizarPreview()">
      ${obSlots.length > 1
        ? `<button class="ob-slot-rm" onclick="obRemoveSlot(${i})"><i class="fas fa-times"></i></button>`
        : `<div style="width:30px"></div>`}
    </div>`
  ).join('');
  const addBtn = document.getElementById('obAddSlotBtn');
  if (addBtn) addBtn.style.display = '';
  obActualizarPreview();
}

function obAddSlot() {
  const last = obSlots[obSlots.length - 1];
  obSlots.push({ ini: last?.fin || '', fin: '' });
  obRenderSlots();
}

function obRemoveSlot(i) {
  if (obSlots.length <= 1) return;
  obSlots.splice(i, 1);
  obRenderSlots();
}

// ── Preview en tiempo real ────────────────────────────────────────────────────
function obActualizarPreview() {
  const valid  = obSlots.filter(s => s.ini && s.fin && s.fin > s.ini);
  const precio = parseFloat(document.getElementById('ob-precio')?.value) || 0;
  const prev   = document.getElementById('obPreviewManual');
  if (!valid.length || !precio || !obDiasActivos.size) { prev.style.display='none'; return; }
  prev.style.display = 'block';

  document.getElementById('obPreviewHoras').innerHTML = valid.map(s =>
    `<span style="padding:3px 10px;border-radius:7px;font-size:13px;font-weight:700;
      background:rgba(52,152,219,.12);color:#3498db">${s.ini} → ${s.fin}</span>`
  ).join('');

  document.getElementById('obPreviewDias').innerHTML = [...obDiasActivos].sort().map(d =>
    `<span style="padding:3px 8px;border-radius:6px;font-size:11px;font-weight:700;
      background:rgba(${obHexToRgb(OB_DIAS_COLOR[d])},.15);color:${OB_DIAS_COLOR[d]}">
      ${OB_DIAS_CORTO[d]}</span>`
  ).join('');

  document.getElementById('obPreviewPrecio').innerHTML =
    `<span style="font-size:20px;font-weight:800;color:var(--green)">$${precio.toLocaleString('es-AR')}</span>
     <span style="font-size:11px;color:var(--muted);margin-left:6px">/ turno</span>`;
}

// ── STEP 1: Validar predio (sin API — se guarda al final) ─────────────────────
function guardarPredio(btn) {
  const nombre = document.getElementById('f-nombre').value.trim();
  const dir    = document.getElementById('f-dir').value.trim();
  const locId  = document.getElementById('f-loc').value;
  if (!nombre)  return showErr('err1', 'El nombre del predio es obligatorio.');
  if (!dir)     return showErr('err1', 'La dirección es obligatoria.');
  if (!locId)   return showErr('err1', 'Seleccioná provincia, partido y localidad.');

  S.predioData = {
    nombre,
    direccion:    dir,
    localidad_id: locId,
    telefono:     document.getElementById('f-tel').value.trim(),
    email:        document.getElementById('f-email').value.trim(),
  };
  goStep(2);
}

// ── STEP 2: Validar cancha (sin API — se guarda al final) ────────────────────
function guardarCancha(btn) {
  const nombre  = document.getElementById('c-nombre').value.trim();
  const tipoId  = document.getElementById('c-tipo').value;
  if (!nombre) return showErr('err2', 'El nombre de la cancha es obligatorio.');
  if (!tipoId) return showErr('err2', 'Seleccioná el tipo de cancha.');

  S.canchaData = { nombre, tipo_cancha_id: tipoId };
  goStep(3);
  initStep3();
}

// ── STEP 3: Agregar franja individual (local, sin API) ───────────────────────
function obGuardarFranja(btn) {
  const valid  = obSlots.filter(s => s.ini && s.fin && s.fin > s.ini);
  const precio = parseFloat(document.getElementById('ob-precio').value);
  const dias   = [...obDiasActivos].sort();

  if (!valid.length)          return showErr('err3', 'Ingresá al menos un horario válido (inicio < fin).');
  if (!precio || precio <= 0) return showErr('err3', 'Ingresá un precio válido mayor a 0.');
  if (!dias.length)           return showErr('err3', 'Seleccioná al menos un día.');

  for (const slot of valid) {
    obFranjasCreadas.push({ ini: slot.ini, fin: slot.fin, dias, precio });
  }

  obRenderFranjasList();

  // Auto-sugerir el siguiente horario: ini = último fin, fin = ini + misma duración
  const lastSlot = valid[valid.length - 1];
  const [lh1, lm1] = lastSlot.ini.split(':').map(Number);
  const [lh2, lm2] = lastSlot.fin.split(':').map(Number);
  const durMin    = (lh2 * 60 + lm2) - (lh1 * 60 + lm1);
  const nextIniM  = lh2 * 60 + lm2;
  const nextFinM  = nextIniM + durMin;
  const pad       = n => String(Math.floor(n / 60)).padStart(2, '0') + ':' + String(n % 60).padStart(2, '0');
  const nextFin   = nextFinM < 24 * 60 ? pad(nextFinM) : '';
  obSlots = [{ ini: lastSlot.fin, fin: nextFin }];

  // Mantener precio si el checkbox está marcado
  if (!document.getElementById('obMantenerPrecio').checked) {
    document.getElementById('ob-precio').value = '';
  }

  obRenderSlots();
  document.getElementById('obPreviewManual').style.display = 'none';
  document.getElementById('btn-finish').style.display = '';
}

// ── Generador de semana completa ──────────────────────────────────────────────
function obGenRenderDias() {
  const wrap = document.getElementById('obGenDiasGrid');
  wrap.innerHTML = [1,2,3,4,5,6,7].map(d => {
    const sel = obGenDiasActivos.has(d);
    const color = OB_DIAS_COLOR[d];
    return `<div onclick="obGenToggleDia(${d})" style="
        width:40px;height:40px;border-radius:9px;cursor:pointer;
        display:flex;align-items:center;justify-content:center;
        border:1px solid ${sel ? color : 'var(--bdr)'};
        background:${sel ? `rgba(${obHexToRgb(color)},.15)` : 'var(--card2)'};
        transition:all .15s;user-select:none">
      <span style="font-size:10px;font-weight:800;color:${sel ? color : 'var(--muted)'}">${OB_DIAS_CORTO[d]}</span>
    </div>`;
  }).join('');
  obGenActualizarPreview();
}

function obGenToggleDia(d) {
  obGenDiasActivos.has(d) ? obGenDiasActivos.delete(d) : obGenDiasActivos.add(d);
  obGenRenderDias();
}

function obGenCalcularSlots() {
  const ap = document.getElementById('obGenApertura').value;
  const ci = document.getElementById('obGenCierre').value;
  const dur = parseInt(document.getElementById('obGenDuracion').value) || 60;
  if (!ap || !ci) return [];
  const [h1,m1] = ap.split(':').map(Number);
  const [h2,m2] = ci.split(':').map(Number);
  const start = h1*60+m1, end = h2*60+m2;
  if (end <= start) return [];
  const slots = [], pad = n => String(Math.floor(n/60)).padStart(2,'0')+':'+String(n%60).padStart(2,'0');
  for (let t = start; t+dur <= end; t += dur) slots.push({ ini: pad(t), fin: pad(t+dur) });
  return slots;
}

function obGenActualizarPreview() {
  const slots  = obGenCalcularSlots();
  const precio = parseFloat(document.getElementById('obGenPrecio').value) || 0;
  const prev   = document.getElementById('obGenPreview');
  if (!slots.length || !obGenDiasActivos.size) { prev.style.display='none'; return; }
  prev.style.display = 'block';
  document.getElementById('obGenCount').textContent = slots.length;
  document.getElementById('obGenSlots').innerHTML = slots.map(s =>
    `<span style="padding:4px 10px;border-radius:7px;font-size:11px;font-weight:700;
      background:rgba(76,217,100,.08);border:1px solid rgba(76,217,100,.2);color:var(--green)">
      ${s.ini}–${s.fin}${precio ? ` <span style="color:var(--muted);font-weight:400;font-size:10px">$${precio.toLocaleString('es-AR')}</span>` : ''}
    </span>`
  ).join('');
}

function obGenerarSemana(btn) {
  const slots  = obGenCalcularSlots();
  const precio = parseFloat(document.getElementById('obGenPrecio').value) || 0;
  const dias   = [...obGenDiasActivos].sort();

  if (!dias.length)  return showErr('err3', 'Seleccioná al menos un día.');
  if (!slots.length) return showErr('err3', 'El rango de apertura/cierre no genera franjas. Verificá los horarios.');
  if (precio <= 0)   return showErr('err3', 'Ingresá un precio válido mayor a 0.');

  for (const slot of slots) {
    obFranjasCreadas.push({ ini: slot.ini, fin: slot.fin, dias, precio });
  }

  obRenderFranjasList();
  document.getElementById('obGenPreview').style.display = 'none';
  document.getElementById('btn-finish').style.display = '';
  obSetMode('manual');
}

// ── Render lista de franjas creadas ──────────────────────────────────────────
function obRenderFranjasList() {
  const list = document.getElementById('ob-franjas-list');
  if (!obFranjasCreadas.length) { list.innerHTML = ''; return; }

  list.innerHTML = `<div class="ob-franjas-header"><i class="fas fa-check-circle" style="color:var(--green)"></i> Franjas configuradas (${obFranjasCreadas.length})</div>` +
    obFranjasCreadas.map((f, i) => {
      const diasHtml = (f.dias||[]).map(d => {
        const color = OB_DIAS_COLOR[d] || '#3498db';
        return `<span style="padding:2px 7px;border-radius:5px;font-size:10px;font-weight:700;
          background:rgba(${obHexToRgb(color)},.15);color:${color}">${OB_DIAS_CORTO[d]||'?'}</span>`;
      }).join('');
      return `<div class="ob-franja-card">
        <div class="ob-franja-hora">
          <div class="hora-ini">${f.ini}</div>
          <div class="hora-arrow">↓</div>
          <div class="hora-fin">${f.fin}</div>
        </div>
        <div class="ob-franja-body">
          <div class="ob-franja-dias">${diasHtml}</div>
          <div class="ob-franja-precio">$${Number(f.precio).toLocaleString('es-AR')}</div>
        </div>
        <button class="ob-franja-del" title="Eliminar" onclick="obEliminarFranja(${i})">
          <i class="fas fa-trash"></i>
        </button>
      </div>`;
    }).join('');
}

function obEliminarFranja(i) {
  obFranjasCreadas.splice(i, 1);
  obRenderFranjasList();
  if (!obFranjasCreadas.length) document.getElementById('btn-finish').style.display = 'none';
}

// ── Init step 3 (llamado al entrar al paso) ───────────────────────────────────
function initStep3() {
  obFranjasCreadas = [];
  obDiasActivos    = new Set([1,2,3,4,5]);
  obGenDiasActivos = new Set([1,2,3,4,5]);
  obSlots          = [{ ini: '08:00', fin: '09:00' }];
  obMode           = 'manual';
  obSetMode('manual');
  obRenderDias();
  obRenderSlots();
  obGenRenderDias();
  obGenActualizarPreview();
  obRenderFranjasList();
  document.getElementById('btn-finish').style.display = 'none';
  document.getElementById('err3').classList.remove('show');
}

async function finalizarOnboarding(btn) {
  if (!S.predioData)  return showErr('err3', 'Faltan datos del predio. Volvé al paso 1.');
  if (!S.canchaData)  return showErr('err3', 'Faltan datos de la cancha. Volvé al paso 2.');
  if (!obFranjasCreadas.length) return showErr('err3', 'Debés agregar al menos una franja horaria.');

  if (btn) setLoading(btn, true);

  let j;
  try {
    const r = await fetch(API_ONB, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ predio: S.predioData, cancha: S.canchaData, franjas: obFranjasCreadas }),
    });
    j = await r.json();
  } catch (e) {
    if (btn) setLoading(btn, false);
    return showErr('err3', 'Error de red. Revisá tu conexión e intentá nuevamente.');
  }

  if (btn) setLoading(btn, false);

  if (!j.ok) return showErr('err3', j.msg || 'Error al guardar. Intentá nuevamente.');

  // Éxito
  goStep('done');
  const cid = j.data?.complejo_id;
  if (cid) {
    const link = `${location.origin}/predio.php?id=${cid}`;
    document.getElementById('done-link-a').href        = link;
    document.getElementById('done-link-a').textContent = link;
    document.getElementById('done-link-box').style.display = '';
  }
}

function skipOnboarding() {
  fetch('api/reservas.php?action=noop', { method: 'GET' }).catch(() => {});
  location.href = 'Dashboard.php?skip_onboarding=1';
}

// ── Init ──────────────────────────────────────────────────────────────────────
initGeo();
initTipos();
</script>
</body>
</html>
