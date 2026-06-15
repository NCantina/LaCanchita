<?php
require_once '../../config/dist/script/php/auth_view.php';
require_view(1, 1); // solo SuperAdmin
$admin_nombre = $_SESSION['usuario_nombre'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Panel Desarrollador · La Canchita</title>
<link rel="shortcut icon" href="../../config/dist/img/loguito_lacanchita.WEBP" type="image/webp">
<link rel="stylesheet" href="../../config/pluggins/vendor/fontawesome-free/css/all.min.css">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --green:  #4cd964; --green-d: #34c759;
    --orange: #ff9500; --red: #e74c3c; --blue: #3498db; --purple: #9b59b6;
    --bg:     #0d0d0d; --card: #141414; --card2: #1a1a1a;
    --border: rgba(255,255,255,.10); --text: #fff; --muted: rgba(255,255,255,.45);
}
html, body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', system-ui, sans-serif; min-height: 100%; }

/* TOPBAR */
.topbar {
    position: sticky; top: 0; z-index: 100;
    background: rgba(13,13,13,.96); backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 24px; height: 58px;
}
.topbar-left { display: flex; align-items: center; gap: 16px; }
.topbar-brand { display: flex; align-items: center; gap: 10px; font-weight: 800; font-size: 1rem; }
.topbar-brand img { height: 30px; border-radius: 6px; }
.topbar-sep { width: 1px; height: 22px; background: var(--border); }
.topbar-title { font-size: .85rem; color: var(--muted); font-weight: 500; }
.topbar-right { display: flex; align-items: center; gap: 12px; }
.btn-back {
    display: flex; align-items: center; gap: 6px;
    background: rgba(255,255,255,.06); border: 1px solid var(--border);
    color: var(--muted); border-radius: 8px; padding: 6px 12px;
    font-size: .8rem; text-decoration: none; transition: all .15s;
}
.btn-back:hover { border-color: var(--green); color: var(--green); }
.admin-chip { font-size: .78rem; color: var(--purple); background: rgba(155,89,182,.12); border: 1px solid rgba(155,89,182,.3); border-radius: 20px; padding: 4px 10px; }

/* CONTENIDO */
.container { max-width: 1200px; margin: 0 auto; padding: 28px 24px 80px; }

/* STATS */
.stats-grid {
    display: grid; grid-template-columns: repeat(4,1fr); gap: 14px; margin-bottom: 28px;
}
@media(max-width:768px){ .stats-grid { grid-template-columns: repeat(2,1fr); } }
.stat-card {
    background: var(--card); border: 1px solid var(--border); border-radius: 14px;
    padding: 18px 20px;
}
.stat-card .ic { font-size: 1.3rem; margin-bottom: 10px; }
.stat-card .val { font-size: 1.6rem; font-weight: 800; line-height: 1; }
.stat-card .lbl { font-size: .72rem; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-top: 4px; }

/* BARRA FILTROS + ACCIÓN */
.toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; }
.pills-filtro { display: flex; gap: 6px; flex-wrap: wrap; }
.pill-f {
    padding: 7px 14px; border-radius: 20px; font-size: .8rem; font-weight: 600;
    border: 1px solid var(--border); background: transparent; color: var(--muted); cursor: pointer; transition: all .15s;
}
.pill-f.active { background: rgba(76,217,100,.12); border-color: var(--green); color: var(--green); }
.btn-nuevo {
    display: flex; align-items: center; gap: 7px;
    background: var(--green); color: #000; border: none;
    border-radius: 10px; padding: 9px 18px; font-size: .85rem; font-weight: 700;
    cursor: pointer; transition: background .15s; white-space: nowrap;
}
.btn-nuevo:hover { background: var(--green-d); }

/* TABLA */
.tabla-wrap { overflow-x: auto; }
table { width: 100%; border-collapse: collapse; font-size: .87rem; }
thead th {
    text-align: left; padding: 10px 14px; font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .06em; color: var(--muted);
    border-bottom: 1px solid var(--border); white-space: nowrap;
}
tbody tr { border-bottom: 1px solid rgba(255,255,255,.04); transition: background .1s; }
tbody tr:hover { background: rgba(255,255,255,.03); }
tbody td { padding: 13px 14px; vertical-align: middle; }
.td-nombre strong { display: block; font-weight: 700; }
.td-nombre span { font-size: .75rem; color: var(--muted); }
.td-inicial {
    width: 36px; height: 36px; border-radius: 50%;
    background: rgba(76,217,100,.15); color: var(--green);
    font-weight: 800; font-size: .9rem;
    display: inline-flex; align-items: center; justify-content: center;
    margin-right: 10px; flex-shrink: 0;
}
.badge {
    display: inline-block; padding: 3px 10px; border-radius: 20px;
    font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .04em;
}
.b-activo    { background: rgba(76,217,100,.12);  color: var(--green);  border: 1px solid rgba(76,217,100,.3); }
.b-prueba    { background: rgba(52,152,219,.12);  color: var(--blue);   border: 1px solid rgba(52,152,219,.3); }
.b-vencido   { background: rgba(231,76,60,.12);   color: var(--red);    border: 1px solid rgba(231,76,60,.3); }
.b-cancelado { background: rgba(255,255,255,.06); color: var(--muted);  border: 1px solid var(--border); }
.b-sin_plan  { background: rgba(255,255,255,.04); color: var(--muted);  border: 1px solid var(--border); }
.b-cuenta-off{ background: rgba(231,76,60,.08);   color: var(--red);    border: 1px solid rgba(231,76,60,.2); font-size:.65rem; }

.prox-ok   { color: var(--green);  font-weight: 600; }
.prox-warn { color: var(--orange); font-weight: 700; }
.prox-late { color: var(--red);    font-weight: 700; }
.prox-null { color: var(--muted); }

.btn-table {
    padding: 5px 11px; border-radius: 7px; font-size: .75rem; font-weight: 700;
    border: 1px solid var(--border); background: transparent; color: var(--muted);
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.btn-table:hover { border-color: var(--green); color: var(--green); }
.btn-table.primary { background: rgba(76,217,100,.1); border-color: rgba(76,217,100,.3); color: var(--green); }
.btn-table.danger  { border-color: rgba(231,76,60,.3); color: var(--red); }
.btn-table.danger:hover { background: rgba(231,76,60,.1); }
.acciones { display: flex; gap: 6px; align-items: center; }

.empty { text-align: center; padding: 60px 20px; color: var(--muted); }
.empty i { font-size: 2.5rem; opacity: .2; display: block; margin-bottom: 12px; }
.loading { text-align: center; padding: 60px; color: var(--muted); }

/* MODAL */
.overlay {
    display: none; position: fixed; inset: 0; z-index: 400;
    background: rgba(0,0,0,.8); backdrop-filter: blur(6px);
    align-items: center; justify-content: center; padding: 20px;
}
.overlay.show { display: flex; }
.modal {
    background: var(--card2); border: 1px solid var(--border);
    border-radius: 18px; width: 100%; max-width: 520px;
    padding: 28px; animation: fadeIn .2s ease;
    max-height: 90vh; overflow-y: auto;
}
@keyframes fadeIn { from { opacity:0; transform: scale(.97); } to { opacity:1; transform: scale(1); } }
.modal-title { font-size: 1.1rem; font-weight: 800; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
.form-row { margin-bottom: 14px; }
.form-row label { display: block; font-size: .72rem; font-weight: 700; color: var(--muted); text-transform: uppercase; letter-spacing: .05em; margin-bottom: 6px; }
.form-row input, .form-row select, .form-row textarea {
    width: 100%; background: rgba(255,255,255,.06); border: 1px solid var(--border);
    border-radius: 10px; color: var(--text); font-size: .9rem; padding: 10px 12px;
    outline: none; transition: border-color .15s; font-family: inherit;
}
.form-row input:focus, .form-row select:focus, .form-row textarea:focus { border-color: var(--green); }
.form-row select option { background: #1a1a1a; }
.form-row textarea { min-height: 80px; resize: vertical; }
.form-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.modal-err { color: var(--red); font-size: .82rem; margin-bottom: 12px; display: none; }
.modal-actions { display: flex; gap: 10px; margin-top: 20px; }
.btn-modal-ok {
    flex: 1; padding: 12px; background: var(--green); color: #000;
    border: none; border-radius: 10px; font-size: .9rem; font-weight: 800; cursor: pointer;
}
.btn-modal-ok:hover { background: var(--green-d); }
.btn-modal-ok:disabled { opacity: .5; cursor: not-allowed; }
.btn-modal-cancel {
    padding: 12px 20px; background: transparent; border: 1px solid var(--border);
    color: var(--muted); border-radius: 10px; font-size: .88rem; cursor: pointer;
}

/* HISTORIAL PANEL (slide from right) */
.hist-panel {
    display: none; position: fixed; top: 0; right: 0; bottom: 0; z-index: 500;
    width: 400px; max-width: 100vw;
    background: var(--card2); border-left: 1px solid var(--border);
    flex-direction: column; animation: slideIn .22s ease;
}
.hist-panel.show { display: flex; }
@keyframes slideIn { from { transform: translateX(100%); } to { transform: translateX(0); } }
.hist-head { padding: 20px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; }
.hist-head strong { font-size: 1rem; font-weight: 800; }
.btn-close-hist { background: none; border: none; color: var(--muted); font-size: 1.2rem; cursor: pointer; }
.hist-body { flex: 1; overflow-y: auto; padding: 16px; }
.hist-item { background: var(--card); border: 1px solid var(--border); border-radius: 10px; padding: 12px 14px; margin-bottom: 10px; }
.hist-item-top { display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; }
.hist-monto { font-size: 1rem; font-weight: 800; color: var(--green); }
.hist-fecha { font-size: .75rem; color: var(--muted); }
.hist-meta  { font-size: .75rem; color: var(--muted); }
.hist-empty { text-align: center; padding: 40px; color: var(--muted); }
.btn-del-cobro { float: right; background: none; border: none; color: var(--muted); font-size: .8rem; cursor: pointer; }
.btn-del-cobro:hover { color: var(--red); }

/* TOAST */
.toast {
    position: fixed; bottom: 24px; left: 50%; transform: translateX(-50%) translateY(10px);
    background: rgba(20,30,20,.97); border: 1px solid rgba(76,217,100,.35); border-radius: 12px;
    padding: 12px 22px; font-size: .85rem; font-weight: 600; color: var(--text);
    display: flex; align-items: center; gap: 8px; z-index: 900; opacity: 0;
    transition: opacity .25s, transform .25s; pointer-events: none; white-space: nowrap;
}
.toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.toast.error { border-color: rgba(231,76,60,.4); }
.toast i.ok  { color: var(--green); }
.toast i.err { color: var(--red); }
</style>
</head>
<body>

<!-- TOPBAR -->
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-brand">
            <img src="../../config/dist/img/loguito_lacanchita.WEBP" alt="">
            La Canchita
        </div>
        <div class="topbar-sep"></div>
        <span class="topbar-title"><i class="fas fa-code" style="margin-right:6px;color:var(--purple)"></i>Panel Desarrollador</span>
    </div>
    <div class="topbar-right">
        <span class="admin-chip"><i class="fas fa-user-shield" style="margin-right:4px"></i><?= htmlspecialchars($admin_nombre) ?></span>
        <a href="../maquetaAdmin/Dashboard.php" class="btn-back"><i class="fas fa-th-large"></i> Dashboard</a>
    </div>
</div>

<!-- CONTENIDO -->
<div class="container">

    <!-- STATS -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="ic">👥</div>
            <div class="val" id="sActivos" style="color:var(--green)">–</div>
            <div class="lbl">Clientes activos</div>
        </div>
        <div class="stat-card">
            <div class="ic">📈</div>
            <div class="val" id="sMrr" style="color:var(--purple)">–</div>
            <div class="lbl">MRR estimado</div>
        </div>
        <div class="stat-card">
            <div class="ic">💰</div>
            <div class="val" id="sCobrado" style="color:var(--blue)">–</div>
            <div class="lbl">Cobrado este mes</div>
        </div>
        <div class="stat-card">
            <div class="ic">⚠️</div>
            <div class="val" id="sAtrasados" style="color:var(--red)">–</div>
            <div class="lbl">Cobros atrasados</div>
        </div>
    </div>

    <!-- TOOLBAR -->
    <div class="toolbar">
        <div class="pills-filtro">
            <button class="pill-f active" onclick="setFiltro('todos',this)">Todos</button>
            <button class="pill-f"        onclick="setFiltro('por_cobrar',this)"><i class="fas fa-bell" style="margin-right:4px"></i>Por cobrar</button>
            <button class="pill-f"        onclick="setFiltro('activos',this)">Activos</button>
            <button class="pill-f"        onclick="setFiltro('prueba',this)">En prueba</button>
            <button class="pill-f"        onclick="setFiltro('cancelados',this)">Cancelados</button>
        </div>
        <button class="btn-nuevo" onclick="abrirModalPlan(null)"><i class="fas fa-plus"></i> Asignar plan</button>
    </div>

    <!-- TABLA -->
    <div class="tabla-wrap">
        <table>
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Plan</th>
                    <th>Precio</th>
                    <th>Próximo cobro</th>
                    <th>Medio</th>
                    <th>Estado</th>
                    <th>Predios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaBody">
                <tr><td colspan="8" class="loading"><i class="fas fa-circle-notch fa-spin" style="font-size:1.4rem;opacity:.4;display:block;margin-bottom:8px"></i>Cargando clientes...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PLAN -->
<div class="overlay" id="overlayPlan">
    <div class="modal">
        <div class="modal-title"><i class="fas fa-file-invoice-dollar" style="color:var(--green)"></i> <span id="modalPlanTitulo">Asignar plan</span></div>
        <input type="hidden" id="planUid">
        <div class="form-row">
            <label>Cliente (Dueño)</label>
            <select id="planCliente"></select>
        </div>
        <div class="form-2col">
            <div class="form-row">
                <label>Nombre del plan</label>
                <input type="text" id="planNombre" placeholder="Ej: Básico, Pro...">
            </div>
            <div class="form-row">
                <label>Estado</label>
                <select id="planEstado">
                    <option value="prueba">En prueba</option>
                    <option value="activo" selected>Activo</option>
                    <option value="vencido">Vencido</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
        </div>
        <div class="form-2col">
            <div class="form-row">
                <label>Precio del plan ($)</label>
                <input type="number" id="planPrecio" placeholder="0" min="0" step="100">
            </div>
            <div class="form-row">
                <label>Ciclo de cobro</label>
                <select id="planCiclo">
                    <option value="mensual">Mensual</option>
                    <option value="trimestral">Trimestral</option>
                    <option value="anual">Anual</option>
                </select>
            </div>
        </div>
        <div class="form-2col">
            <div class="form-row">
                <label>Próximo cobro</label>
                <input type="date" id="planProximo">
            </div>
            <div class="form-row">
                <label>Medio habitual de cobro</label>
                <select id="planMedio">
                    <option value="transferencia">Transferencia</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="mercadopago">Mercado Pago</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <label>Notas internas</label>
            <textarea id="planNotas" placeholder="Observaciones, descuentos, acuerdos especiales..."></textarea>
        </div>
        <div class="modal-err" id="planErr"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="cerrarModal('overlayPlan')">Cancelar</button>
            <button class="btn-modal-ok" id="btnGuardarPlan" onclick="guardarPlan()"><i class="fas fa-save" style="margin-right:6px"></i>Guardar plan</button>
        </div>
    </div>
</div>

<!-- MODAL COBRO -->
<div class="overlay" id="overlayCobro">
    <div class="modal">
        <div class="modal-title"><i class="fas fa-dollar-sign" style="color:var(--green)"></i> Registrar cobro</div>
        <input type="hidden" id="cobroUid">
        <div class="form-row" id="cobroClienteRow">
            <label>Cliente</label>
            <input type="text" id="cobroClienteNombre" readonly style="opacity:.7">
        </div>
        <div class="form-2col">
            <div class="form-row">
                <label>Monto ($)</label>
                <input type="number" id="cobroMonto" placeholder="0" min="1" step="100">
            </div>
            <div class="form-row">
                <label>Fecha del cobro</label>
                <input type="date" id="cobroFecha">
            </div>
        </div>
        <div class="form-2col">
            <div class="form-row">
                <label>Período que cubre (AAAA-MM)</label>
                <input type="month" id="cobroPeriodo">
            </div>
            <div class="form-row">
                <label>Medio de pago</label>
                <select id="cobroMedio">
                    <option value="transferencia">Transferencia</option>
                    <option value="efectivo">Efectivo</option>
                    <option value="mercadopago">Mercado Pago</option>
                    <option value="tarjeta">Tarjeta</option>
                    <option value="otro">Otro</option>
                </select>
            </div>
        </div>
        <div class="form-row">
            <label>Notas</label>
            <input type="text" id="cobroNotas" placeholder="Comprobante, referencia...">
        </div>
        <div class="modal-err" id="cobroErr"></div>
        <div class="modal-actions">
            <button class="btn-modal-cancel" onclick="cerrarModal('overlayCobro')">Cancelar</button>
            <button class="btn-modal-ok" id="btnGuardarCobro" onclick="guardarCobro()"><i class="fas fa-check" style="margin-right:6px"></i>Registrar</button>
        </div>
    </div>
</div>

<!-- PANEL HISTORIAL -->
<div class="hist-panel" id="histPanel">
    <div class="hist-head">
        <strong id="histTitulo">Historial de cobros</strong>
        <button class="btn-close-hist" onclick="cerrarHistorial()"><i class="fas fa-times"></i></button>
    </div>
    <div class="hist-body" id="histBody"></div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
const API = 'api/clientes.php';
let _filtro   = 'todos';
let _clientes = [];

const fmt = n => '$' + Number(n).toLocaleString('es-AR', {maximumFractionDigits:0});
const esc = s => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

// ── STATS ──────────────────────────────────────────────────────────────────────
async function cargarStats() {
    const j = await fetch(`${API}?action=stats`).then(r=>r.json()).catch(()=>null);
    if (!j?.ok) return;
    document.getElementById('sActivos').textContent  = j.data.activos;
    document.getElementById('sMrr').textContent      = fmt(j.data.mrr) + '/mes';
    document.getElementById('sCobrado').textContent  = fmt(j.data.cobrado);
    document.getElementById('sAtrasados').textContent= j.data.atrasados;
}

// ── FILTROS ────────────────────────────────────────────────────────────────────
function setFiltro(f, el) {
    _filtro = f;
    document.querySelectorAll('.pill-f').forEach(p=>p.classList.remove('active'));
    el.classList.add('active');
    cargarClientes();
}

// ── TABLA ──────────────────────────────────────────────────────────────────────
async function cargarClientes() {
    const tbody = document.getElementById('tablaBody');
    tbody.innerHTML = '<tr><td colspan="8" class="loading"><i class="fas fa-circle-notch fa-spin"></i></td></tr>';
    const j = await fetch(`${API}?action=listar&filtro=${_filtro}`).then(r=>r.json()).catch(()=>null);
    if (!j?.ok) { tbody.innerHTML = `<tr><td colspan="8" class="empty"><i class="fas fa-exclamation-triangle"></i>${esc(j?.msg||'Error')}</td></tr>`; return; }
    _clientes = j.data || [];
    if (!_clientes.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="empty"><i class="fas fa-users"></i><p>Sin clientes en este filtro</p></td></tr>';
        return;
    }
    tbody.innerHTML = _clientes.map(c => filaCliente(c)).join('');
}

function filaCliente(c) {
    const inicial = (c.USUARIOS_NOMBRE||'?')[0].toUpperCase();
    const nombre  = esc(c.USUARIOS_NOMBRE + ' ' + c.USUARIOS_APELLIDO);
    const email   = esc(c.USUARIOS_EMAIL||'');

    // Estado badge
    const eMap = {activo:'b-activo',prueba:'b-prueba',vencido:'b-vencido',cancelado:'b-cancelado',sin_plan:'b-sin_plan'};
    const eLbl = {activo:'Activo',prueba:'Prueba',vencido:'Vencido',cancelado:'Cancelado',sin_plan:'Sin plan'};
    const estadoCls = eMap[c.ESTADO] || 'b-sin_plan';
    const estadoLbl = eLbl[c.ESTADO] || c.ESTADO;

    // Próximo cobro
    let proxHtml = '<span class="prox-null">—</span>';
    if (c.PROXIMO_COBRO) {
        const hoy  = new Date(); hoy.setHours(0,0,0,0);
        const [y,m,d] = c.PROXIMO_COBRO.split('-').map(Number);
        const pDate = new Date(y, m-1, d);
        const diff  = Math.round((pDate - hoy) / 86400000);
        const label = pDate.toLocaleDateString('es-AR', {day:'2-digit',month:'2-digit',year:'numeric'});
        if (diff < 0)       proxHtml = `<span class="prox-late"><i class="fas fa-exclamation-circle"></i> ${label}<br><small>${Math.abs(diff)}d atrasado</small></span>`;
        else if (diff <= 7) proxHtml = `<span class="prox-warn"><i class="fas fa-clock"></i> ${label}<br><small>en ${diff}d</small></span>`;
        else                proxHtml = `<span class="prox-ok">${label}</span>`;
    }

    // Ciclo abrev
    const cicloLbl = {mensual:'/ mes', trimestral:'/ trim.', anual:'/ año'}[c.PLAN_CICLO] || '';

    // Cuenta activa
    const cuentaOff = c.CUENTA_ACTIVA == 0 ? ' <span class="badge b-cuenta-off">BLOQUEADO</span>' : '';

    const data = encodeData(c);

    return `<tr>
        <td>
            <div style="display:flex;align-items:center">
                <span class="td-inicial">${inicial}</span>
                <div>
                    <strong>${nombre}</strong>${cuentaOff}
                    <span style="display:block;font-size:.75rem;color:var(--muted)">${email}</span>
                </div>
            </div>
        </td>
        <td>${esc(c.PLAN_NOMBRE)}</td>
        <td>${c.PLAN_PRECIO > 0 ? '<strong>' + fmt(c.PLAN_PRECIO) + '</strong> <span style="color:var(--muted);font-size:.75rem">' + cicloLbl + '</span>' : '<span style="color:var(--muted)">—</span>'}</td>
        <td>${proxHtml}</td>
        <td style="color:var(--muted);font-size:.8rem">${esc(c.MEDIO_COBRO)}</td>
        <td><span class="badge ${estadoCls}">${estadoLbl}</span></td>
        <td style="text-align:center;color:var(--muted)">${c.TOTAL_PREDIOS}</td>
        <td>
            <div class="acciones">
                <button class="btn-table primary" onclick='abrirCobro(${data})' title="Registrar cobro"><i class="fas fa-dollar-sign"></i></button>
                <button class="btn-table" onclick='abrirModalPlan(${data})' title="Editar plan"><i class="fas fa-edit"></i></button>
                <button class="btn-table" onclick="verHistorial(${c.USUARIOS_ID},'${nombre}')" title="Historial"><i class="fas fa-history"></i></button>
                <button class="btn-table ${c.CUENTA_ACTIVA==0?'':'danger'}" onclick="toggleCliente(${c.USUARIOS_ID},${c.CUENTA_ACTIVA})" title="${c.CUENTA_ACTIVA==0?'Activar cuenta':'Desactivar cuenta'}">
                    <i class="fas fa-${c.CUENTA_ACTIVA==0?'unlock':'ban'}"></i>
                </button>
            </div>
        </td>
    </tr>`;
}

function encodeData(c) {
    return JSON.stringify({
        USUARIOS_ID:  c.USUARIOS_ID,
        USUARIOS_NOMBRE: c.USUARIOS_NOMBRE,
        USUARIOS_APELLIDO: c.USUARIOS_APELLIDO,
        PLAN_NOMBRE:  c.PLAN_NOMBRE,
        PLAN_PRECIO:  c.PLAN_PRECIO,
        PLAN_CICLO:   c.PLAN_CICLO,
        PROXIMO_COBRO:c.PROXIMO_COBRO,
        ESTADO:       c.ESTADO,
        MEDIO_COBRO:  c.MEDIO_COBRO,
        NOTAS:        c.NOTAS,
        SUSCRIPCION_ID: c.SUSCRIPCION_ID,
    }).replace(/'/g,"&#39;");
}

// ── MODAL PLAN ─────────────────────────────────────────────────────────────────
let _todosClientes = [];
async function abrirModalPlan(data) {
    // Cargar lista de dueños si no está cargada
    if (!_todosClientes.length) {
        const j = await fetch(`${API}?action=listar&filtro=todos`).then(r=>r.json()).catch(()=>null);
        _todosClientes = j?.data || [];
    }

    const sel = document.getElementById('planCliente');
    sel.innerHTML = _todosClientes.map(c =>
        `<option value="${c.USUARIOS_ID}">${esc(c.USUARIOS_NOMBRE+' '+c.USUARIOS_APELLIDO)} — ${esc(c.USUARIOS_EMAIL)}</option>`
    ).join('');

    if (data) {
        document.getElementById('modalPlanTitulo').textContent = 'Editar plan';
        document.getElementById('planUid').value       = data.USUARIOS_ID;
        sel.value = data.USUARIOS_ID; sel.disabled = true;
        document.getElementById('planNombre').value    = data.PLAN_NOMBRE !== '—' ? data.PLAN_NOMBRE : '';
        document.getElementById('planPrecio').value    = data.PLAN_PRECIO;
        document.getElementById('planCiclo').value     = data.PLAN_CICLO;
        document.getElementById('planProximo').value   = data.PROXIMO_COBRO || '';
        document.getElementById('planEstado').value    = data.ESTADO !== 'sin_plan' ? data.ESTADO : 'activo';
        document.getElementById('planMedio').value     = data.MEDIO_COBRO !== '—' ? data.MEDIO_COBRO : 'transferencia';
        document.getElementById('planNotas').value     = data.NOTAS || '';
    } else {
        document.getElementById('modalPlanTitulo').textContent = 'Asignar plan';
        document.getElementById('planUid').value = '';
        sel.disabled = false;
        document.getElementById('planNombre').value  = 'Estándar';
        document.getElementById('planPrecio').value  = '';
        document.getElementById('planCiclo').value   = 'mensual';
        document.getElementById('planProximo').value = nextMonthDate();
        document.getElementById('planEstado').value  = 'activo';
        document.getElementById('planMedio').value   = 'transferencia';
        document.getElementById('planNotas').value   = '';
    }
    document.getElementById('planErr').style.display = 'none';
    document.getElementById('overlayPlan').classList.add('show');
    document.getElementById('planNombre').focus();
}

async function guardarPlan() {
    const uid    = document.getElementById('planUid').value || document.getElementById('planCliente').value;
    const nombre = document.getElementById('planNombre').value.trim();
    const precio = document.getElementById('planPrecio').value;
    const errEl  = document.getElementById('planErr');
    errEl.style.display = 'none';

    if (!uid)    { errEl.textContent='Seleccioná un cliente.'; errEl.style.display='block'; return; }
    if (!nombre) { errEl.textContent='Ingresá el nombre del plan.'; errEl.style.display='block'; return; }
    if (!precio || precio < 0) { errEl.textContent='Ingresá un precio válido.'; errEl.style.display='block'; return; }

    const btn = document.getElementById('btnGuardarPlan');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';

    const fd = new FormData();
    fd.append('action',        'upsert_plan');
    fd.append('usuarios_id',   uid);
    fd.append('plan_nombre',   nombre);
    fd.append('plan_precio',   precio);
    fd.append('plan_ciclo',    document.getElementById('planCiclo').value);
    fd.append('proximo_cobro', document.getElementById('planProximo').value);
    fd.append('estado',        document.getElementById('planEstado').value);
    fd.append('medio_cobro',   document.getElementById('planMedio').value);
    fd.append('notas',         document.getElementById('planNotas').value);

    const j = await fetch(API, {method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-save" style="margin-right:6px"></i>Guardar plan';

    if (!j?.ok) { errEl.textContent = j?.msg || 'Error'; errEl.style.display='block'; return; }
    cerrarModal('overlayPlan');
    toast('Plan guardado ✓');
    cargarStats(); cargarClientes();
    _todosClientes = [];
}

// ── MODAL COBRO ────────────────────────────────────────────────────────────────
function abrirCobro(data) {
    document.getElementById('cobroUid').value          = data.USUARIOS_ID;
    document.getElementById('cobroClienteNombre').value= data.USUARIOS_NOMBRE + ' ' + data.USUARIOS_APELLIDO;
    document.getElementById('cobroMonto').value        = data.PLAN_PRECIO > 0 ? data.PLAN_PRECIO : '';
    document.getElementById('cobroFecha').value        = today();
    document.getElementById('cobroPeriodo').value      = today().substring(0,7);
    document.getElementById('cobroMedio').value        = data.MEDIO_COBRO !== '—' ? data.MEDIO_COBRO : 'transferencia';
    document.getElementById('cobroNotas').value        = '';
    document.getElementById('cobroErr').style.display  = 'none';
    document.getElementById('overlayCobro').classList.add('show');
    document.getElementById('cobroMonto').focus();
}

async function guardarCobro() {
    const uid   = document.getElementById('cobroUid').value;
    const monto = parseFloat(document.getElementById('cobroMonto').value)||0;
    const errEl = document.getElementById('cobroErr');
    errEl.style.display = 'none';
    if (monto <= 0) { errEl.textContent='Ingresá un monto válido.'; errEl.style.display='block'; return; }

    const btn = document.getElementById('btnGuardarCobro');
    btn.disabled = true; btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';

    const fd = new FormData();
    fd.append('action',      'registrar_cobro');
    fd.append('usuarios_id', uid);
    fd.append('monto',       monto);
    fd.append('fecha',       document.getElementById('cobroFecha').value);
    fd.append('periodo',     document.getElementById('cobroPeriodo').value);
    fd.append('medio',       document.getElementById('cobroMedio').value);
    fd.append('notas',       document.getElementById('cobroNotas').value);

    const j = await fetch(API, {method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
    btn.disabled = false; btn.innerHTML = '<i class="fas fa-check" style="margin-right:6px"></i>Registrar';

    if (!j?.ok) { errEl.textContent = j?.msg || 'Error'; errEl.style.display='block'; return; }
    cerrarModal('overlayCobro');
    toast('Cobro registrado ✓');
    cargarStats(); cargarClientes();
}

// ── HISTORIAL ──────────────────────────────────────────────────────────────────
async function verHistorial(uid, nombre) {
    document.getElementById('histTitulo').textContent = 'Cobros · ' + nombre;
    document.getElementById('histBody').innerHTML = '<div class="hist-empty"><i class="fas fa-circle-notch fa-spin"></i></div>';
    document.getElementById('histPanel').classList.add('show');

    const j = await fetch(`${API}?action=historial&usuarios_id=${uid}`).then(r=>r.json()).catch(()=>null);
    const rows = j?.data || [];
    if (!rows.length) {
        document.getElementById('histBody').innerHTML = '<div class="hist-empty"><i class="fas fa-inbox" style="font-size:2rem;opacity:.2;display:block;margin-bottom:8px"></i>Sin cobros registrados</div>';
        return;
    }
    document.getElementById('histBody').innerHTML = rows.map(r => `
        <div class="hist-item">
            <div class="hist-item-top">
                <span class="hist-monto">${fmt(r.COBRO_MONTO)}</span>
                <span style="display:flex;align-items:center;gap:8px">
                    <span class="hist-fecha">${fmtFecha(r.COBRO_FECHA)}</span>
                    <button class="btn-del-cobro" onclick="eliminarCobro(${r.COBRO_ID},${uid},'${nombre}')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
                </span>
            </div>
            <div class="hist-meta">
                ${r.COBRO_PERIODO ? '📅 Período: ' + r.COBRO_PERIODO + ' · ' : ''}
                ${r.COBRO_MEDIO ? '💳 ' + esc(r.COBRO_MEDIO) : ''}
                ${r.COBRO_NOTAS ? '<br>📝 ' + esc(r.COBRO_NOTAS) : ''}
            </div>
        </div>
    `).join('');
}

async function eliminarCobro(cobro_id, uid, nombre) {
    if (!confirm('¿Eliminar este cobro?')) return;
    const fd = new FormData();
    fd.append('action', 'eliminar_cobro'); fd.append('cobro_id', cobro_id);
    const j = await fetch(API, {method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
    if (!j?.ok) { toast(j?.msg||'Error', true); return; }
    toast('Cobro eliminado');
    cargarStats(); cargarClientes();
    verHistorial(uid, nombre);
}

function cerrarHistorial() {
    document.getElementById('histPanel').classList.remove('show');
}

// ── TOGGLE CLIENTE ─────────────────────────────────────────────────────────────
async function toggleCliente(uid, activo) {
    const accion = activo == 1 ? 'desactivar' : 'activar';
    if (!confirm(`¿${accion.charAt(0).toUpperCase()+accion.slice(1)} este cliente?`)) return;
    const fd = new FormData();
    fd.append('action', 'toggle_cliente'); fd.append('usuarios_id', uid);
    const j = await fetch(API, {method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
    if (!j?.ok) { toast(j?.msg||'Error', true); return; }
    toast(j.msg);
    cargarClientes();
}

// ── UTILS ──────────────────────────────────────────────────────────────────────
function cerrarModal(id) { document.getElementById(id).classList.remove('show'); }
function today() { return new Date().toISOString().split('T')[0]; }
function nextMonthDate() {
    const d = new Date(); d.setMonth(d.getMonth()+1);
    return d.toISOString().split('T')[0];
}
function fmtFecha(s) {
    if (!s) return '—';
    const [y,m,d] = s.split('-');
    return `${d}/${m}/${y}`;
}

let _toastT = null;
function toast(msg, error=false) {
    const el = document.getElementById('toast');
    el.innerHTML = `<i class="fas ${error?'fa-exclamation-circle err':'fa-check-circle ok'}"></i> ${msg}`;
    el.className = 'toast' + (error?' error':'');
    void el.offsetWidth; el.classList.add('show');
    clearTimeout(_toastT); _toastT = setTimeout(()=>el.classList.remove('show'), 3500);
}

// Cerrar overlays al click fuera del modal
['overlayPlan','overlayCobro'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) cerrarModal(id);
    });
});
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        cerrarModal('overlayPlan'); cerrarModal('overlayCobro'); cerrarHistorial();
    }
});

// ── INIT ──────────────────────────────────────────────────────────────────────
cargarStats();
cargarClientes();
</script>
</body>
</html>
