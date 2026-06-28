<?php
require_once '../../config/dist/script/php/auth_view.php';
require_view(3, 4);

$nombre   = $_SESSION['usuario_nombre']   ?? 'Encargado';
$apellido = $_SESSION['usuario_apellido'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Panel Encargado · La Canchita</title>
    <?php $PWA_BASE = '../../'; require_once '../../config/dist/script/php/pwa_head.php'; ?>
    <link rel="shortcut icon" href="../../config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
    <link rel="stylesheet" href="../../config/pluggins/vendor/fontawesome-free/css/all.min.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:   #4cd964;
            --green-d: #34c759;
            --orange:  #ff9500;
            --red:     #e74c3c;
            --blue:    #3498db;
            --bg:      #0d0d0d;
            --card:    #141414;
            --border:  rgba(255,255,255,0.10);
            --text:    #ffffff;
            --muted:   rgba(255,255,255,0.45);
        }

        html, body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; height: 100%; }

        /* ── TOPBAR ── */
        .topbar {
            position: sticky; top: 0; z-index: 100;
            background: rgba(13,13,13,0.95); backdrop-filter: blur(12px);
            border-bottom: 1px solid var(--border);
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 16px; height: 56px;
        }
        .topbar-brand { display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 1rem; }
        .topbar-brand img { height: 30px; border-radius: 6px; }
        .topbar-right { display: flex; align-items: center; gap: 10px; }
        .staff-chip {
            font-size: 0.78rem; font-weight: 600; color: var(--muted);
            background: rgba(255,255,255,0.06); border-radius: 20px;
            padding: 4px 10px; border: 1px solid var(--border);
        }
        .btn-logout { background: none; border: none; color: var(--muted); font-size: 1.1rem; cursor: pointer; padding: 6px; }
        .btn-logout:hover { color: var(--red); }

        /* ── DATE NAV ── */
        .date-nav {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 16px; border-bottom: 1px solid var(--border);
            background: var(--card); gap: 8px;
        }
        .date-nav-btn { background: none; border: 1px solid var(--border); color: var(--muted); border-radius: 8px; padding: 8px 14px; font-size: 0.82rem; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 5px; }
        .date-nav-btn:hover { border-color: var(--green); color: var(--green); }
        .date-nav-center { flex: 1; text-align: center; }
        .date-label { font-size: 1rem; font-weight: 700; display: block; }
        .date-sub { font-size: 0.75rem; color: var(--muted); }
        .btn-hoy { background: rgba(76,217,100,0.1); border: 1px solid rgba(76,217,100,0.3); color: var(--green); border-radius: 6px; padding: 3px 10px; font-size: 0.72rem; font-weight: 700; cursor: pointer; margin-top: 2px; display: inline-block; transition: all 0.15s; }
        .btn-hoy:hover { background: rgba(76,217,100,0.2); }

        /* ── STATS BAR ── */
        .stats-bar {
            display: grid; grid-template-columns: repeat(3,1fr);
            border-bottom: 1px solid var(--border);
        }
        .stat-item { padding: 12px 8px; text-align: center; border-right: 1px solid var(--border); }
        .stat-item:last-child { border-right: none; }
        .stat-val { font-size: 1.3rem; font-weight: 800; }
        .stat-val.orange { color: var(--orange); }
        .stat-val.green  { color: var(--green); }
        .stat-val.blue   { color: var(--blue); }
        .stat-lbl { font-size: 0.68rem; color: var(--muted); text-transform: uppercase; letter-spacing: 0.04em; margin-top: 1px; }

        /* ── LOADING ── */
        .loading { text-align: center; padding: 48px 16px; color: var(--muted); font-size: 0.9rem; }
        .loading i { font-size: 1.8rem; margin-bottom: 10px; display: block; opacity: 0.4; }
        .empty { text-align: center; padding: 48px 16px; color: var(--muted); }
        .empty i { font-size: 2.5rem; margin-bottom: 12px; display: block; opacity: 0.2; }
        .empty p { font-size: 0.9rem; }

        /* ── LISTA ── */
        .lista { padding: 12px 16px; display: flex; flex-direction: column; gap: 10px; padding-bottom: 80px; }

        /* ── RESERVA CARD ── */
        .r-card {
            background: var(--card); border: 1px solid var(--border);
            border-radius: 14px; overflow: hidden;
            transition: border-color 0.15s;
        }
        .r-card.pendiente { border-left: 3px solid var(--orange); }
        .r-card.confirmada { border-left: 3px solid var(--green); }
        .r-card.cancelada { border-left: 3px solid var(--red); opacity: 0.5; }

        .r-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 12px 14px 8px;
        }
        .r-hora { font-size: 1.05rem; font-weight: 800; letter-spacing: -0.3px; }
        .r-cancha { font-size: 0.75rem; color: var(--muted); margin-top: 1px; }
        .r-estado { padding: 3px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; }
        .estado-pendiente  { background: rgba(255,149,0,0.15);  color: var(--orange); border: 1px solid rgba(255,149,0,0.3); }
        .estado-confirmada { background: rgba(76,217,100,0.12); color: var(--green);  border: 1px solid rgba(76,217,100,0.3); }
        .estado-cancelada  { background: rgba(231,76,60,0.12);  color: var(--red);    border: 1px solid rgba(231,76,60,0.3); }

        .r-cliente {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0 14px 10px;
        }
        .r-nombre { font-size: 0.9rem; font-weight: 600; }
        .r-tel { font-size: 0.78rem; color: var(--muted); margin-top: 1px; }
        .btn-wsp-small {
            width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
            background: rgba(37,211,102,0.1); border: 1px solid rgba(37,211,102,0.3);
            color: #25d366; font-size: 1rem; display: flex; align-items: center; justify-content: center;
            cursor: pointer; text-decoration: none; transition: all 0.15s;
        }
        .btn-wsp-small:hover { background: rgba(37,211,102,0.2); }

        .r-footer {
            display: flex; align-items: center; gap: 8px;
            padding: 0 14px 12px; flex-wrap: wrap;
        }
        .r-pago { font-size: 0.75rem; color: var(--muted); flex: 1; }
        .r-pago strong { color: var(--text); }
        .badge-pagado { background: rgba(76,217,100,0.12); color: var(--green); border: 1px solid rgba(76,217,100,0.25); border-radius: 20px; padding: 2px 8px; font-size: 0.7rem; font-weight: 700; }

        .btn-accion {
            padding: 7px 14px; border-radius: 8px; font-size: 0.8rem; font-weight: 700;
            border: none; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 5px;
        }
        .btn-confirmar { background: var(--orange); color: #000; }
        .btn-confirmar:hover { background: #e08600; }
        .btn-cobrar { background: rgba(76,217,100,0.12); color: var(--green); border: 1px solid rgba(76,217,100,0.3); }
        .btn-cobrar:hover { background: rgba(76,217,100,0.22); }
        .btn-accion:disabled { opacity: 0.4; cursor: not-allowed; }

        /* ── FAB (botón + turno) ── */
        .fab {
            position: fixed; bottom: 20px; right: 20px; z-index: 200;
            width: 52px; height: 52px; border-radius: 50%;
            background: var(--green); color: #000; font-size: 1.4rem;
            border: none; cursor: pointer; box-shadow: 0 4px 20px rgba(76,217,100,0.4);
            display: flex; align-items: center; justify-content: center;
            transition: transform 0.15s, background 0.15s;
        }
        .fab:hover { background: var(--green-d); transform: scale(1.08); }

        /* ── MODAL COBRO ── */
        .modal-overlay {
            display: none; position: fixed; inset: 0; z-index: 500;
            background: rgba(0,0,0,0.75); backdrop-filter: blur(6px);
            align-items: flex-end; justify-content: center; padding: 0;
        }
        .modal-overlay.show { display: flex; }
        .modal-sheet {
            background: #1a1a1a; border: 1px solid var(--border);
            border-radius: 20px 20px 0 0; width: 100%; max-width: 480px;
            padding: 20px 20px 32px;
            animation: slideUp 0.25s ease;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .sheet-handle { width: 36px; height: 4px; background: var(--border); border-radius: 2px; margin: 0 auto 16px; }
        .sheet-title { font-size: 1rem; font-weight: 800; margin-bottom: 4px; }
        .sheet-sub { font-size: 0.82rem; color: var(--muted); margin-bottom: 16px; }
        .sheet-saldo { background: rgba(76,217,100,0.06); border: 1px solid rgba(76,217,100,0.2); border-radius: 10px; padding: 10px 14px; margin-bottom: 16px; font-size: 0.85rem; }
        .sheet-saldo span { color: var(--green); font-weight: 800; font-size: 1.1rem; }
        .field-label { font-size: 0.72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 6px; display: block; }
        .monto-input { width: 100%; background: rgba(255,255,255,0.06); border: 1px solid var(--border); border-radius: 10px; color: var(--text); font-size: 1.2rem; font-weight: 700; padding: 12px 14px; outline: none; transition: border-color 0.15s; margin-bottom: 14px; }
        .monto-input:focus { border-color: var(--green); }
        .pills { display: flex; flex-wrap: wrap; gap: 7px; margin-bottom: 14px; }
        .pill { padding: 7px 14px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; border: 1px solid var(--border); background: transparent; color: var(--muted); cursor: pointer; transition: all 0.15s; }
        .pill.active { background: rgba(76,217,100,0.12); border-color: var(--green); color: var(--green); }
        .sheet-err { display: none; font-size: 0.82rem; color: var(--red); margin-bottom: 10px; }
        .btn-sheet-ok { width: 100%; padding: 14px; background: var(--green); color: #000; border: none; border-radius: 12px; font-size: 1rem; font-weight: 800; cursor: pointer; margin-top: 4px; transition: background 0.15s; }
        .btn-sheet-ok:hover { background: var(--green-d); }
        .btn-sheet-ok:disabled { opacity: 0.5; cursor: not-allowed; }
        .btn-sheet-cancel { width: 100%; padding: 11px; background: transparent; border: 1px solid var(--border); color: var(--muted); border-radius: 10px; font-size: 0.88rem; cursor: pointer; margin-top: 8px; }

        /* ── TOAST ── */
        .toast { position: fixed; bottom: 80px; left: 50%; transform: translateX(-50%) translateY(10px); background: rgba(20,30,20,0.96); border: 1px solid rgba(76,217,100,0.35); border-radius: 12px; padding: 12px 20px; font-size: 0.85rem; font-weight: 600; color: var(--text); display: flex; align-items: center; gap: 8px; z-index: 600; opacity: 0; transition: opacity 0.25s, transform 0.25s; pointer-events: none; white-space: nowrap; }
        .toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
        .toast.error { border-color: rgba(231,76,60,0.4); }
        .toast i.ok { color: var(--green); }
        .toast i.err { color: var(--red); }
    </style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-brand">
        <img src="../../config/dist/img/loguito_lacanchita.WEBP" alt="">
        La Canchita
    </div>
    <div class="topbar-right">
        <span class="staff-chip"><?= htmlspecialchars($nombre . ' ' . $apellido) ?></span>
        <a href="../../logout.php" class="btn-logout" title="Cerrar sesión"><i class="fas fa-sign-out-alt"></i></a>
    </div>
</div>

<!-- DATE NAV -->
<div class="date-nav">
    <button class="date-nav-btn" onclick="cambiarDia(-1)"><i class="fas fa-chevron-left"></i></button>
    <div class="date-nav-center">
        <span class="date-label" id="dateLabel">—</span>
        <button class="btn-hoy" id="btnHoy" onclick="irAHoy()">Hoy</button>
    </div>
    <button class="date-nav-btn" onclick="cambiarDia(1)"><i class="fas fa-chevron-right"></i></button>
</div>

<!-- STATS -->
<div class="stats-bar">
    <div class="stat-item">
        <div class="stat-val orange" id="statPend">–</div>
        <div class="stat-lbl">Pendientes</div>
    </div>
    <div class="stat-item">
        <div class="stat-val green" id="statConf">–</div>
        <div class="stat-lbl">Confirmadas</div>
    </div>
    <div class="stat-item">
        <div class="stat-val blue" id="statCob">–</div>
        <div class="stat-lbl">Cobrado</div>
    </div>
</div>

<!-- LISTA -->
<div id="lista">
    <div class="loading"><i class="fas fa-circle-notch fa-spin"></i>Cargando agenda...</div>
</div>

<!-- MODAL COBRO -->
<div class="modal-overlay" id="modalCobro">
    <div class="modal-sheet">
        <div class="sheet-handle"></div>
        <div class="sheet-title">Registrar cobro</div>
        <div class="sheet-sub" id="sheetSub">—</div>
        <div class="sheet-saldo">Saldo pendiente: <span id="sheetSaldo">$0</span></div>
        <label class="field-label">Monto ($)</label>
        <input type="number" class="monto-input" id="montoInput" min="1" placeholder="0">
        <label class="field-label">Tipo de cobro</label>
        <div class="pills" id="pillsTipo">
            <button class="pill active" data-v="total"   onclick="selPill('tipo','total',this)">Total</button>
            <button class="pill"        data-v="sena"    onclick="selPill('tipo','sena',this)">Seña</button>
            <button class="pill"        data-v="parcial" onclick="selPill('tipo','parcial',this)">Parcial</button>
        </div>
        <label class="field-label">Medio de pago</label>
        <div class="pills" id="pillsMedio">
            <button class="pill active"  data-v="efectivo"     onclick="selPill('medio','efectivo',this)">Efectivo</button>
            <button class="pill"         data-v="transferencia" onclick="selPill('medio','transferencia',this)">Transferencia</button>
            <button class="pill"         data-v="tarjeta"      onclick="selPill('medio','tarjeta',this)">Tarjeta</button>
            <button class="pill"         data-v="otro"         onclick="selPill('medio','otro',this)">Otro</button>
        </div>
        <div class="sheet-err" id="sheetErr"></div>
        <button class="btn-sheet-ok" id="btnRegistrar" onclick="registrarCobro()"><i class="fas fa-check" style="margin-right:6px"></i>Registrar</button>
        <button class="btn-sheet-cancel" onclick="cerrarModalCobro()">Cancelar</button>
    </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
const API = 'api/reservas.php';
let _fecha    = new Date().toISOString().split('T')[0];
let _reservas = [];
let _cobroId  = null;
let _cobroSaldo = 0;
let _selectedTipo  = 'total';
let _selectedMedio = 'efectivo';

// ── FECHA ──
function fmt(n)    { return '$' + Number(n).toLocaleString('es-AR', {maximumFractionDigits:0}); }
function esc(s)    { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function parseFecha(s) {
    const [y,m,d] = s.split('-').map(Number);
    return new Date(y, m-1, d);
}
function formatFecha(s) {
    const d = parseFecha(s);
    const DIAS  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
    const MESES = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
    return { dia: DIAS[d.getDay()], label: `${DIAS[d.getDay()].charAt(0).toUpperCase()+DIAS[d.getDay()].slice(1)} ${d.getDate()} ${MESES[d.getMonth()]}` };
}
function actualizarHeader() {
    const f    = formatFecha(_fecha);
    const hoy  = new Date().toISOString().split('T')[0];
    document.getElementById('dateLabel').textContent = f.label;
    document.getElementById('btnHoy').style.display  = (_fecha === hoy) ? 'none' : 'inline-block';
}
function cambiarDia(delta) {
    const d = parseFecha(_fecha);
    d.setDate(d.getDate() + delta);
    _fecha = d.toISOString().split('T')[0];
    actualizarHeader();
    cargar();
}
function irAHoy() {
    _fecha = new Date().toISOString().split('T')[0];
    actualizarHeader();
    cargar();
}

// ── CARGA ──
async function cargar() {
    document.getElementById('lista').innerHTML = '<div class="loading"><i class="fas fa-circle-notch fa-spin"></i>Cargando...</div>';
    document.getElementById('statPend').textContent = '–';
    document.getElementById('statConf').textContent = '–';
    document.getElementById('statCob').textContent  = '–';
    try {
        const r = await fetch(`${API}?action=listar&fecha=${_fecha}`);
        const j = await r.json();
        if (!j.ok) { mostrarError(j.msg||'Error'); return; }
        _reservas = j.data || [];
        renderStats();
        renderLista();
    } catch(e) {
        document.getElementById('lista').innerHTML = '<div class="empty"><i class="fas fa-wifi"></i><p>Error de conexión</p></div>';
    }
}

function renderStats() {
    const pend = _reservas.filter(r=>r.RESERVA_ESTADO==='pendiente').length;
    const conf = _reservas.filter(r=>r.RESERVA_ESTADO==='confirmada').length;
    const cob  = _reservas.reduce((s,r)=>s+parseFloat(r.PAGADO_TOTAL||0), 0);
    document.getElementById('statPend').textContent = pend;
    document.getElementById('statConf').textContent = conf;
    document.getElementById('statCob').textContent  = fmt(cob);
}

function renderLista() {
    const lista = document.getElementById('lista');
    if (!_reservas.length) {
        lista.innerHTML = `<div class="empty"><i class="fas fa-calendar-times"></i><p>Sin reservas para este día</p></div>`;
        return;
    }

    const hoy   = new Date().toISOString().split('T')[0];
    const ahora = new Date();

    lista.innerHTML = '<div class="lista">' + _reservas.map((r, i) => {
        const ini     = (r.RESERVA_HORA_INICIO||'').substring(0,5);
        const fin     = (r.RESERVA_HORA_FIN||'').substring(0,5);
        const nombre  = esc((r.USUARIOS_NOMBRE||'')+' '+(r.USUARIOS_APELLIDO||'')).trim();
        const tel     = (r.USUARIOS_TELEFONO||'').replace(/\D/g,'');
        const estado  = r.RESERVA_ESTADO || 'pendiente';
        const precio  = parseFloat(r.RESERVA_PRECIO||0);
        const pagado  = parseFloat(r.PAGADO_TOTAL||0);
        const saldo   = parseFloat(r.SALDO_PENDIENTE||0);
        const id      = r.RESERVA_ID;

        // Estado badge
        const estadoLabel = estado==='pendiente'?'Pendiente':estado==='confirmada'?'Confirmada':'Cancelada';
        const estadoCls   = `r-estado estado-${estado}`;

        // WhatsApp link
        const wsp = tel
            ? `<a class="btn-wsp-small" href="https://wa.me/549${tel}" target="_blank"><i class="fab fa-whatsapp"></i></a>`
            : '';

        // Botones de acción
        let acciones = '';
        if (estado === 'pendiente') {
            acciones += `<button class="btn-accion btn-confirmar" id="btn-conf-${id}" onclick="confirmar(${id},${i})"><i class="fas fa-check"></i> Confirmar</button>`;
        }
        if (estado === 'confirmada' && saldo > 0) {
            acciones += `<button class="btn-accion btn-cobrar" onclick="abrirCobro(${id},${saldo},'${esc(nombre)}')"><i class="fas fa-dollar-sign"></i> Cobrar ${fmt(saldo)}</button>`;
        }
        if (estado === 'confirmada' && saldo <= 0 && pagado > 0) {
            acciones += `<span class="badge-pagado"><i class="fas fa-check-circle"></i> Pagado ${fmt(pagado)}</span>`;
        }

        // Pago info
        let pagoInfo = '';
        if (estado !== 'cancelada') {
            if (pagado > 0 && saldo > 0) pagoInfo = `<span class="r-pago">Cobrado <strong>${fmt(pagado)}</strong> · Saldo <strong>${fmt(saldo)}</strong></span>`;
            else if (saldo > 0) pagoInfo = `<span class="r-pago">Total <strong>${fmt(precio)}</strong></span>`;
        }

        return `<div class="r-card ${estado}" id="card-${id}">
            <div class="r-head">
                <div>
                    <div class="r-hora">${esc(ini)} – ${esc(fin)}</div>
                    <div class="r-cancha"><i class="fas fa-futbol" style="opacity:.5;margin-right:3px"></i>${esc(r.CANCHA_NOMBRE||'')}</div>
                </div>
                <span class="${estadoCls}">${estadoLabel}</span>
            </div>
            <div class="r-cliente">
                <div>
                    <div class="r-nombre">${nombre||'—'}</div>
                    <div class="r-tel">${tel?r.USUARIOS_TELEFONO:r.USUARIOS_EMAIL||'Sin contacto'}</div>
                </div>
                ${wsp}
            </div>
            <div class="r-footer">
                ${pagoInfo}
                ${acciones}
            </div>
        </div>`;
    }).join('') + '</div>';
}

// ── CONFIRMAR ──
async function confirmar(id, i) {
    const btn = document.getElementById(`btn-conf-${id}`);
    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>'; }
    const fd = new FormData();
    fd.append('action', 'confirmar');
    fd.append('reserva_id', id);
    try {
        const r = await fetch(API, { method: 'POST', body: fd });
        const j = await r.json();
        if (!j.ok) { toast(j.msg, true); if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-check"></i> Confirmar';} return; }
        toast('Reserva confirmada ✓');
        cargar();
    } catch(e) { toast('Error de conexión', true); if(btn){btn.disabled=false;btn.innerHTML='<i class="fas fa-check"></i> Confirmar';} }
}

// ── COBRO ──
function abrirCobro(id, saldo, nombre) {
    _cobroId    = id;
    _cobroSaldo = saldo;
    document.getElementById('sheetSub').textContent   = nombre;
    document.getElementById('sheetSaldo').textContent = fmt(saldo);
    document.getElementById('montoInput').value       = saldo.toFixed(0);
    document.getElementById('sheetErr').style.display = 'none';
    // Reset pills
    selPill('tipo',  'total',    document.querySelector('#pillsTipo  .pill'));
    selPill('medio', 'efectivo', document.querySelector('#pillsMedio .pill'));
    document.getElementById('modalCobro').classList.add('show');
    document.getElementById('montoInput').focus();
}

function cerrarModalCobro() {
    document.getElementById('modalCobro').classList.remove('show');
    _cobroId = null;
}

function selPill(group, val, el) {
    const container = document.getElementById(group==='tipo'?'pillsTipo':'pillsMedio');
    container.querySelectorAll('.pill').forEach(p=>p.classList.remove('active'));
    el.classList.add('active');
    if (group==='tipo')  _selectedTipo  = val;
    if (group==='medio') _selectedMedio = val;
}

async function registrarCobro() {
    const monto = parseFloat(document.getElementById('montoInput').value)||0;
    const errEl = document.getElementById('sheetErr');
    errEl.style.display = 'none';
    if (monto <= 0) { errEl.textContent='Ingresá un monto válido.'; errEl.style.display='block'; return; }
    if (monto > _cobroSaldo + 0.01) { errEl.textContent=`El monto supera el saldo pendiente (${fmt(_cobroSaldo)}).`; errEl.style.display='block'; return; }

    const btn = document.getElementById('btnRegistrar');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Registrando...';

    const fd = new FormData();
    fd.append('action',     'registrar_pago');
    fd.append('reserva_id', _cobroId);
    fd.append('monto',      monto);
    fd.append('tipo',       _selectedTipo);
    fd.append('medio',      _selectedMedio);
    try {
        const r = await fetch(API, { method: 'POST', body: fd });
        const j = await r.json();
        if (!j.ok) { errEl.textContent = j.msg; errEl.style.display='block'; return; }
        cerrarModalCobro();
        toast(j.data?.auto_confirmada ? 'Cobro registrado y reserva confirmada ✓' : 'Cobro registrado ✓');
        cargar();
    } catch(e) { errEl.textContent='Error de conexión.'; errEl.style.display='block'; }
    finally { btn.disabled=false; btn.innerHTML='<i class="fas fa-check" style="margin-right:6px"></i>Registrar'; }
}

// ── TOAST ──
let _toastTimer = null;
function toast(msg, error=false) {
    const el = document.getElementById('toast');
    el.innerHTML = `<i class="fas ${error?'fa-exclamation-circle err':'fa-check-circle ok'}"></i> ${msg}`;
    el.className = 'toast' + (error?' error':'');
    void el.offsetWidth;
    el.classList.add('show');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(()=>el.classList.remove('show'), 3000);
}
function mostrarError(msg) {
    document.getElementById('lista').innerHTML = `<div class="empty"><i class="fas fa-exclamation-triangle"></i><p>${msg}</p></div>`;
}

// Cerrar modal al tocar fuera
document.getElementById('modalCobro').addEventListener('click', function(e) {
    if (e.target === this) cerrarModalCobro();
});
document.addEventListener('keydown', e => { if(e.key==='Escape') cerrarModalCobro(); });

// ── INIT ──
actualizarHeader();
cargar();

// Auto-refresh cada 60 segundos
setInterval(cargar, 60000);
</script>
</body>
</html>
