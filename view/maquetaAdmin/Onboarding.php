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

  /* ── Días de la semana ── */
  .dias-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 6px; }
  .dia-btn {
    padding: 8px 0; border-radius: 8px; border: 1px solid var(--bdr);
    background: var(--card2); color: var(--muted); font-size: 11px; font-weight: 600;
    cursor: pointer; text-align: center; transition: all .15s; user-select: none;
  }
  .dia-btn:hover    { border-color: rgba(76,217,100,.4); color: var(--txt); }
  .dia-btn.selected { border-color: var(--green); background: rgba(76,217,100,.12); color: var(--green); }

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

  /* ── Add more / list ── */
  .added-list { margin-top: 12px; }
  .added-item {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px; background: var(--card2); border-radius: 8px;
    font-size: 13px; margin-bottom: 6px; color: var(--muted);
  }
  .added-item i { color: var(--green); }

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
  <div class="ob-title">Configurá el primer horario</div>
  <div class="ob-sub">Definí los días y horarios en que esta cancha está disponible para reservar.</div>
  <div class="err-msg" id="err3"></div>

  <div class="field">
    <label>Días disponibles *</label>
    <div class="dias-grid" id="dias-grid">
      <div class="dia-btn selected" data-dia="1">Lun</div>
      <div class="dia-btn selected" data-dia="2">Mar</div>
      <div class="dia-btn selected" data-dia="3">Mié</div>
      <div class="dia-btn selected" data-dia="4">Jue</div>
      <div class="dia-btn selected" data-dia="5">Vie</div>
      <div class="dia-btn" data-dia="6">Sáb</div>
      <div class="dia-btn" data-dia="7">Dom</div>
    </div>
  </div>

  <div class="field-row">
    <div class="field" style="margin:0">
      <label>Hora inicio *</label>
      <input id="h-ini" type="time" value="08:00">
    </div>
    <div class="field" style="margin:0">
      <label>Hora fin *</label>
      <input id="h-fin" type="time" value="09:00">
    </div>
  </div>

  <div class="field">
    <label>Precio por turno ($) *</label>
    <input id="h-precio" type="number" min="0" step="100" placeholder="Ej: 5000">
  </div>

  <!-- Lista de franjas agregadas en este paso -->
  <div class="added-list" id="franjas-list"></div>

  <button class="btn-primary" id="btn-add-franja" onclick="guardarFranja(this)">Agregar franja</button>
  <button class="btn-primary" id="btn-finish" onclick="finalizarOnboarding()" style="display:none;background:var(--card2);border:1px solid var(--green);color:var(--green)">Ir al panel →</button>
  <button class="btn-back" onclick="goStep(2)">← Volver</button>
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
const API_GEO     = 'api/geo.php';
const API_COMP    = 'api/complejos.php';
const API_CANCHA  = 'api/canchas.php';
const API_HOR     = 'api/horarios.php';
const API_CAT     = 'api/catalogo.php';

const S = { step: 1, complejoId: null, canchaId: null, franjasCreadas: 0 };

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

// ── Días ─────────────────────────────────────────────────────────────────────
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('dia-btn')) {
    e.target.classList.toggle('selected');
  }
});

function getDiasSeleccionados() {
  return Array.from(document.querySelectorAll('.dia-btn.selected')).map(b => parseInt(b.dataset.dia));
}

// ── STEP 1: Guardar predio ────────────────────────────────────────────────────
async function guardarPredio(btn) {
  const nombre = document.getElementById('f-nombre').value.trim();
  const dir    = document.getElementById('f-dir').value.trim();
  const locId  = document.getElementById('f-loc').value;
  if (!nombre)  return showErr('err1', 'El nombre del predio es obligatorio.');
  if (!dir)     return showErr('err1', 'La dirección es obligatoria.');
  if (!locId)   return showErr('err1', 'Seleccioná provincia, partido y localidad.');

  setLoading(btn, true);
  const j = await apiFetch(API_COMP, {
    action:      'crear',
    nombre,
    direccion:   dir,
    localidad_id: locId,
    telefono:    document.getElementById('f-tel').value.trim(),
    email:       document.getElementById('f-email').value.trim(),
  });
  setLoading(btn, false);

  if (!j.ok) return showErr('err1', j.msg || 'Error al guardar el predio.');
  S.complejoId = j.data?.id;
  goStep(2);
}

// ── STEP 2: Guardar cancha ────────────────────────────────────────────────────
async function guardarCancha(btn) {
  const nombre  = document.getElementById('c-nombre').value.trim();
  const tipoId  = document.getElementById('c-tipo').value;
  if (!nombre) return showErr('err2', 'El nombre de la cancha es obligatorio.');
  if (!tipoId) return showErr('err2', 'Seleccioná el tipo de cancha.');

  setLoading(btn, true);
  const j = await apiFetch(API_CANCHA, {
    action:        'crear',
    nombre,
    tipo_cancha_id: tipoId,
    complejo_id:   S.complejoId,
  });
  setLoading(btn, false);

  if (!j.ok) return showErr('err2', j.msg || 'Error al guardar la cancha.');
  S.canchaId = j.data?.id;
  goStep(3);
}

// ── STEP 3: Agregar franja ────────────────────────────────────────────────────
async function guardarFranja(btn) {
  const ini    = document.getElementById('h-ini').value;
  const fin    = document.getElementById('h-fin').value;
  const precio = parseFloat(document.getElementById('h-precio').value);
  const dias   = getDiasSeleccionados();

  if (!ini || !fin)    return showErr('err3', 'Ingresá hora de inicio y fin.');
  if (fin <= ini)      return showErr('err3', 'La hora de fin debe ser mayor al inicio.');
  if (!precio || precio <= 0) return showErr('err3', 'Ingresá un precio válido.');
  if (!dias.length)    return showErr('err3', 'Seleccioná al menos un día.');

  setLoading(btn, true);
  const j = await apiFetch(API_HOR, {
    action:      'crear',
    cancha_id:   S.canchaId,
    hora_inicio: ini,
    hora_fin:    fin,
    precio,
    sena:        0,
    dias:        JSON.stringify(dias),
  });
  setLoading(btn, false);

  if (!j.ok) return showErr('err3', j.msg || 'Error al guardar el horario.');

  // Mostrar franja agregada
  S.franjasCreadas++;
  const diasNom = ['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
  const diasStr = dias.map(d => diasNom[d]).join(', ');
  document.getElementById('franjas-list').innerHTML +=
    `<div class="added-item"><i class="fas fa-check-circle"></i> ${ini}–${fin} · ${diasStr} · $${precio.toLocaleString('es-AR')}</div>`;

  // Limpiar para agregar otra
  document.getElementById('h-ini').value   = fin; // siguiente empieza donde terminó
  document.getElementById('h-fin').value   = '';
  document.getElementById('h-precio').value = '';

  // Mostrar botón "Ir al panel"
  document.getElementById('btn-finish').style.display = '';
  btn.textContent = '+ Agregar otro horario';
}

async function finalizarOnboarding() {
  goStep('done');
  // Armar link público
  if (S.complejoId) {
    const origin = location.origin;
    const link   = `${origin}/predio.php?id=${S.complejoId}`;
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
