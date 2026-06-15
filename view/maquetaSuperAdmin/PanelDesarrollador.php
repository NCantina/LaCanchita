<?php
require_once '../../config/dist/script/php/auth_view.php';
require_view(1, 1);
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
/* ─── RESET & TOKENS ──────────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
:root {
    --bg:       #09090f;
    --s1:       #101018;
    --s2:       #16161f;
    --s3:       #1d1d28;
    --border:   rgba(255,255,255,.08);
    --border2:  rgba(255,255,255,.14);
    --text:     #f0f0f8;
    --muted:    rgba(240,240,248,.42);
    --muted2:   rgba(240,240,248,.22);
    --green:    #4cd964;
    --green2:   #34c759;
    --orange:   #ff9f0a;
    --red:      #ff453a;
    --blue:     #0a84ff;
    --purple:   #bf5af2;
    --indigo:   #5e5ce6;
    --radius:   14px;
    --shadow:   0 4px 24px rgba(0,0,0,.45);
}
html, body { background: var(--bg); color: var(--text); font-family: -apple-system, 'Segoe UI', system-ui, sans-serif; height: 100%; font-size: 14px; }

/* ─── TOPBAR ──────────────────────────────────────────────────────────── */
.topbar {
    position: sticky; top: 0; z-index: 200;
    display: flex; align-items: center; height: 56px;
    padding: 0 20px; gap: 0;
    background: rgba(9,9,15,.92); backdrop-filter: blur(20px) saturate(180%);
    border-bottom: 1px solid var(--border);
}
.tb-brand { display: flex; align-items: center; gap: 9px; font-weight: 800; font-size: .95rem; white-space: nowrap; }
.tb-brand img { height: 28px; border-radius: 6px; }
.tb-sep { width: 1px; height: 20px; background: var(--border2); margin: 0 14px; }
.tb-badge {
    display: flex; align-items: center; gap: 6px;
    font-size: .72rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    color: var(--purple); background: rgba(191,90,242,.12); border: 1px solid rgba(191,90,242,.25);
    border-radius: 20px; padding: 3px 10px; white-space: nowrap;
}
/* Tab navigation — centered */
.tab-nav { display: flex; align-items: center; gap: 2px; flex: 1; justify-content: center; }
.tab-btn {
    padding: 6px 18px; border-radius: 8px; font-size: .85rem; font-weight: 600;
    border: none; background: transparent; color: var(--muted); cursor: pointer;
    transition: all .15s; white-space: nowrap;
}
.tab-btn.active { background: var(--s3); color: var(--text); }
.tab-btn:hover:not(.active) { color: var(--text); }
.tb-right { display: flex; align-items: center; gap: 10px; white-space: nowrap; }
.admin-chip { font-size: .75rem; color: var(--muted); background: var(--s2); border: 1px solid var(--border); border-radius: 20px; padding: 4px 10px; }
.btn-dash {
    display: flex; align-items: center; gap: 6px; text-decoration: none;
    font-size: .78rem; font-weight: 600; color: var(--muted);
    background: var(--s2); border: 1px solid var(--border); border-radius: 8px; padding: 5px 12px;
    transition: all .15s;
}
.btn-dash:hover { border-color: var(--border2); color: var(--text); }

/* ─── LAYOUT ─────────────────────────────────────────────────────────── */
.layout { display: flex; height: calc(100vh - 56px); overflow: hidden; }
.content { flex: 1; overflow-y: auto; padding: 24px; }
.content::-webkit-scrollbar { width: 6px; }
.content::-webkit-scrollbar-track { background: transparent; }
.content::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 3px; }

/* ─── TABS ───────────────────────────────────────────────────────────── */
.tab-section { display: none; }
.tab-section.active { display: block; }

/* ─── STATS ROW ──────────────────────────────────────────────────────── */
.stats-row { display: grid; grid-template-columns: repeat(3,1fr) 1.3fr; gap: 12px; margin-bottom: 20px; }
@media(max-width:900px){ .stats-row { grid-template-columns: 1fr 1fr; } }
.stat-card {
    background: var(--s1); border: 1px solid var(--border); border-radius: var(--radius);
    padding: 18px 20px; position: relative; overflow: hidden;
    transition: border-color .2s;
}
.stat-card:hover { border-color: var(--border2); }
.stat-card::before {
    content: ''; position: absolute; top: 0; left: 0; right: 0; height: 2px;
    background: var(--accent, var(--green)); opacity: .6;
}
.stat-card.orange { --accent: var(--orange); }
.stat-card.red    { --accent: var(--red); }
.stat-card.purple { --accent: var(--purple); }
.stat-ic  { font-size: 1.1rem; margin-bottom: 12px; opacity: .7; }
.stat-val { font-size: 1.8rem; font-weight: 800; line-height: 1; letter-spacing: -.03em; color: var(--accent, var(--green)); }
.stat-lbl { font-size: .68rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-top: 4px; }
.stat-sub { font-size: .75rem; color: var(--muted2); margin-top: 6px; }

/* ─── RESUMEN: dos columnas ──────────────────────────────────────────── */
.resumen-grid { display: grid; grid-template-columns: 1fr 1.6fr; gap: 16px; }
@media(max-width:900px){ .resumen-grid { grid-template-columns: 1fr; } }
.panel-card {
    background: var(--s1); border: 1px solid var(--border); border-radius: var(--radius);
    overflow: hidden;
}
.panel-card-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 14px 18px; border-bottom: 1px solid var(--border);
}
.panel-card-title { font-size: .75rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); }

/* Alert list (por cobrar) */
.alert-list { max-height: 320px; overflow-y: auto; }
.alert-item {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 18px; border-bottom: 1px solid var(--border);
    cursor: pointer; transition: background .1s;
}
.alert-item:hover { background: var(--s2); }
.alert-item:last-child { border-bottom: none; }
.alert-dot { width: 8px; height: 8px; border-radius: 50%; flex-shrink: 0; }
.alert-dot.red    { background: var(--red); box-shadow: 0 0 6px var(--red); }
.alert-dot.orange { background: var(--orange); box-shadow: 0 0 6px var(--orange); }
.alert-info { flex: 1; min-width: 0; }
.alert-nombre { font-size: .88rem; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.alert-dias  { font-size: .72rem; color: var(--muted); margin-top: 2px; }
.alert-monto { font-size: .85rem; font-weight: 800; color: var(--green); white-space: nowrap; }
.alert-btn-cobrar {
    padding: 5px 11px; border-radius: 7px; font-size: .72rem; font-weight: 700;
    background: rgba(76,217,100,.1); border: 1px solid rgba(76,217,100,.25); color: var(--green);
    cursor: pointer; white-space: nowrap; flex-shrink: 0; transition: background .15s;
}
.alert-btn-cobrar:hover { background: rgba(76,217,100,.2); }
.alert-empty { padding: 32px 18px; text-align: center; color: var(--muted); font-size: .85rem; }
.alert-empty i { display: block; font-size: 1.8rem; opacity: .2; margin-bottom: 8px; }

/* MRR Chart */
.chart-wrap { padding: 20px 18px 14px; }
.chart-bars { display: flex; align-items: flex-end; gap: 6px; height: 140px; }
.chart-col { flex: 1; display: flex; flex-direction: column; align-items: center; gap: 5px; }
.chart-bar-wrap { flex: 1; width: 100%; display: flex; align-items: flex-end; }
.chart-bar {
    width: 100%; border-radius: 5px 5px 0 0;
    background: linear-gradient(to top, rgba(76,217,100,.7), rgba(76,217,100,.25));
    border: 1px solid rgba(76,217,100,.3); border-bottom: none;
    min-height: 3px; transition: height .5s cubic-bezier(.25,.46,.45,.94);
    position: relative; cursor: default;
}
.chart-bar:hover { background: linear-gradient(to top, rgba(76,217,100,.9), rgba(76,217,100,.4)); }
.chart-bar[data-tip]:hover::after {
    content: attr(data-tip); position: absolute; bottom: calc(100% + 6px); left: 50%;
    transform: translateX(-50%); white-space: nowrap;
    background: var(--s3); border: 1px solid var(--border2); color: var(--text);
    font-size: .72rem; font-weight: 600; padding: 4px 8px; border-radius: 6px;
    pointer-events: none; z-index: 10;
}
.chart-label { font-size: .65rem; color: var(--muted); text-transform: uppercase; letter-spacing: .03em; }
.chart-total { text-align: right; font-size: .72rem; color: var(--muted); margin-top: 8px; }

/* ─── CLIENTES ───────────────────────────────────────────────────────── */
.toolbar {
    display: flex; align-items: center; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;
}
.search-wrap {
    display: flex; align-items: center; gap: 8px;
    background: var(--s1); border: 1px solid var(--border); border-radius: 10px;
    padding: 7px 12px; flex: 1; min-width: 200px; max-width: 320px;
    transition: border-color .15s;
}
.search-wrap:focus-within { border-color: var(--border2); }
.search-wrap i { color: var(--muted2); font-size: .85rem; }
.search-wrap input {
    background: none; border: none; color: var(--text); font-size: .88rem; outline: none; width: 100%;
}
.search-wrap input::placeholder { color: var(--muted2); }
.pills { display: flex; gap: 6px; flex-wrap: wrap; }
.pill {
    padding: 6px 13px; border-radius: 20px; font-size: .75rem; font-weight: 700;
    border: 1px solid var(--border); background: transparent; color: var(--muted);
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.pill.active { background: rgba(76,217,100,.1); border-color: rgba(76,217,100,.3); color: var(--green); }
.pill:hover:not(.active) { border-color: var(--border2); color: var(--text); }
.btn-primary {
    display: flex; align-items: center; gap: 7px; padding: 8px 16px;
    background: var(--green); color: #000; border: none; border-radius: 10px;
    font-size: .82rem; font-weight: 800; cursor: pointer; transition: background .15s;
    white-space: nowrap; margin-left: auto;
}
.btn-primary:hover { background: var(--green2); }

/* Table */
.table-wrap { overflow-x: auto; border-radius: var(--radius); border: 1px solid var(--border); }
table { width: 100%; border-collapse: collapse; }
thead th {
    text-align: left; padding: 10px 14px;
    font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em;
    color: var(--muted); background: var(--s1); border-bottom: 1px solid var(--border);
    white-space: nowrap; cursor: pointer; user-select: none;
}
thead th:hover { color: var(--text); }
thead th .sort-ic { margin-left: 4px; opacity: .3; }
thead th.sorted .sort-ic { opacity: 1; color: var(--green); }
tbody tr {
    border-bottom: 1px solid var(--border);
    cursor: pointer; transition: background .1s;
}
tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: var(--s2); }
tbody tr.selected { background: rgba(76,217,100,.04); }
tbody td { padding: 12px 14px; vertical-align: middle; }
.td-cliente { display: flex; align-items: center; gap: 10px; }
.avatar {
    width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
    background: rgba(76,217,100,.12); color: var(--green);
    font-weight: 800; font-size: .85rem;
    display: flex; align-items: center; justify-content: center;
}
.avatar.off { background: rgba(255,69,58,.1); color: var(--red); }
.cl-nombre { font-weight: 600; font-size: .88rem; white-space: nowrap; }
.cl-email { font-size: .72rem; color: var(--muted); white-space: nowrap; }
.badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px; border-radius: 20px; font-size: .68rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .04em; white-space: nowrap;
}
.b-activo    { background: rgba(76,217,100,.1);  color: var(--green);  border: 1px solid rgba(76,217,100,.25); }
.b-prueba    { background: rgba(10,132,255,.1);  color: var(--blue);   border: 1px solid rgba(10,132,255,.25); }
.b-vencido   { background: rgba(255,69,58,.1);   color: var(--red);    border: 1px solid rgba(255,69,58,.25); }
.b-cancelado { background: rgba(255,255,255,.05); color: var(--muted); border: 1px solid var(--border); }
.b-sin_plan  { background: rgba(255,255,255,.03); color: var(--muted2);border: 1px solid var(--border); }
.prox-ok   { color: var(--green);  font-weight: 600; font-size: .82rem; }
.prox-warn { color: var(--orange); font-weight: 700; font-size: .82rem; }
.prox-late { color: var(--red);    font-weight: 700; font-size: .82rem; }
.prox-null { color: var(--muted2); font-size: .82rem; }
.prox-sub  { font-size: .65rem; margin-top: 1px; display: block; opacity: .7; }
.btn-row {
    padding: 5px 10px; border-radius: 7px; font-size: .72rem; font-weight: 700;
    border: 1px solid var(--border); background: transparent; color: var(--muted);
    cursor: pointer; transition: all .15s; white-space: nowrap;
}
.btn-row:hover { border-color: var(--border2); color: var(--text); }
.btn-row.green { background: rgba(76,217,100,.08); border-color: rgba(76,217,100,.2); color: var(--green); }
.btn-row.green:hover { background: rgba(76,217,100,.16); }
.row-actions { display: flex; gap: 5px; align-items: center; }
.empty-state { padding: 60px 20px; text-align: center; color: var(--muted); }
.empty-state i { font-size: 2rem; opacity: .15; display: block; margin-bottom: 12px; }
.empty-state p { font-size: .88rem; }

/* ─── COBROS TAB ─────────────────────────────────────────────────────── */
.cobros-header { display: flex; align-items: center; gap: 12px; margin-bottom: 14px; flex-wrap: wrap; }
.month-select {
    background: var(--s1); border: 1px solid var(--border); color: var(--text);
    border-radius: 10px; padding: 7px 12px; font-size: .85rem; outline: none;
    transition: border-color .15s; cursor: pointer;
}
.month-select:focus { border-color: var(--border2); }
.cobros-total { font-size: .88rem; font-weight: 700; color: var(--green); margin-left: auto; }
.cobro-item {
    display: flex; align-items: center; gap: 14px;
    background: var(--s1); border: 1px solid var(--border); border-radius: 10px;
    padding: 12px 16px; margin-bottom: 8px; transition: border-color .15s;
}
.cobro-item:hover { border-color: var(--border2); }
.cobro-fecha { font-size: .78rem; color: var(--muted); white-space: nowrap; min-width: 72px; }
.cobro-cliente { flex: 1; min-width: 0; }
.cobro-cliente strong { display: block; font-size: .88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cobro-cliente span { font-size: .72rem; color: var(--muted); }
.cobro-monto { font-size: 1rem; font-weight: 800; color: var(--green); white-space: nowrap; }
.cobro-medio { font-size: .72rem; color: var(--muted); background: var(--s2); border-radius: 6px; padding: 2px 7px; white-space: nowrap; }
.cobro-del { background: none; border: none; color: var(--muted2); cursor: pointer; font-size: .82rem; padding: 4px; transition: color .15s; }
.cobro-del:hover { color: var(--red); }

/* ─── DETAIL SIDE PANEL ──────────────────────────────────────────────── */
.detail-panel {
    width: 0; overflow: hidden; background: var(--s1);
    border-left: 1px solid transparent;
    transition: width .25s cubic-bezier(.25,.46,.45,.94), border-color .25s;
    display: flex; flex-direction: column; flex-shrink: 0;
}
.detail-panel.open {
    width: 380px; border-color: var(--border); overflow: visible; overflow-y: auto;
}
.detail-panel::-webkit-scrollbar { width: 4px; }
.detail-panel::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }

.dp-head {
    padding: 20px; border-bottom: 1px solid var(--border);
    display: flex; align-items: flex-start; justify-content: space-between; gap: 12px;
    position: sticky; top: 0; background: var(--s1); z-index: 10;
}
.dp-avatar-wrap { display: flex; align-items: center; gap: 12px; }
.dp-avatar { width: 44px; height: 44px; border-radius: 50%; background: rgba(76,217,100,.14); color: var(--green); font-size: 1.1rem; font-weight: 800; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
.dp-avatar.off { background: rgba(255,69,58,.1); color: var(--red); }
.dp-nombre { font-size: 1rem; font-weight: 800; line-height: 1.2; }
.dp-email  { font-size: .75rem; color: var(--muted); margin-top: 3px; }
.dp-close  { background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1rem; padding: 4px; transition: color .15s; }
.dp-close:hover { color: var(--text); }

.dp-body { padding: 16px 20px; }
.dp-section { margin-bottom: 20px; }
.dp-section-title { font-size: .65rem; font-weight: 700; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); margin-bottom: 10px; padding-bottom: 6px; border-bottom: 1px solid var(--border); }

.info-row { display: flex; justify-content: space-between; align-items: center; padding: 7px 0; }
.info-row:not(:last-child) { border-bottom: 1px solid rgba(255,255,255,.04); }
.info-lbl { font-size: .75rem; color: var(--muted); }
.info-val { font-size: .82rem; font-weight: 600; text-align: right; }

.dp-actions { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
.dp-btn {
    padding: 9px 12px; border-radius: 9px; font-size: .78rem; font-weight: 700;
    border: 1px solid var(--border); background: var(--s2); color: var(--text);
    cursor: pointer; transition: all .15s; text-align: center;
    display: flex; align-items: center; justify-content: center; gap: 6px;
}
.dp-btn:hover { border-color: var(--border2); background: var(--s3); }
.dp-btn.green { background: rgba(76,217,100,.1); border-color: rgba(76,217,100,.25); color: var(--green); }
.dp-btn.green:hover { background: rgba(76,217,100,.18); }
.dp-btn.red { background: rgba(255,69,58,.08); border-color: rgba(255,69,58,.2); color: var(--red); }
.dp-btn.red:hover { background: rgba(255,69,58,.15); }
.dp-btn.full { grid-column: 1/-1; }
.dp-btn:disabled { opacity: .4; cursor: not-allowed; }

/* Notas */
.dp-notas {
    width: 100%; background: var(--s2); border: 1px solid var(--border); border-radius: 9px;
    color: var(--text); font-size: .82rem; padding: 10px 12px; outline: none; resize: vertical;
    min-height: 80px; font-family: inherit; transition: border-color .15s;
}
.dp-notas:focus { border-color: var(--border2); }
.notas-saved { font-size: .7rem; color: var(--green); opacity: 0; transition: opacity .3s; margin-top: 4px; }
.notas-saved.show { opacity: 1; }

/* Historial dentro del panel */
.hist-item {
    background: var(--s2); border-radius: 9px; padding: 10px 12px; margin-bottom: 6px;
    display: flex; align-items: center; gap: 10px;
}
.hist-info { flex: 1; min-width: 0; }
.hist-monto { font-size: .95rem; font-weight: 800; color: var(--green); white-space: nowrap; }
.hist-meta  { font-size: .7rem; color: var(--muted); margin-top: 1px; }
.hist-del   { background: none; border: none; color: var(--muted2); cursor: pointer; font-size: .8rem; padding: 3px; transition: color .15s; }
.hist-del:hover { color: var(--red); }
.hist-empty { text-align: center; padding: 20px; color: var(--muted); font-size: .82rem; }

/* ─── MODALES ─────────────────────────────────────────────────────────── */
.overlay {
    display: none; position: fixed; inset: 0; z-index: 500;
    background: rgba(0,0,0,.72); backdrop-filter: blur(8px);
    align-items: center; justify-content: center; padding: 20px;
}
.overlay.show { display: flex; }
.modal {
    background: var(--s2); border: 1px solid var(--border2); border-radius: 18px;
    width: 100%; max-width: 480px; padding: 24px;
    animation: pop .18s cubic-bezier(.25,.46,.45,.94);
    max-height: 90vh; overflow-y: auto;
}
.modal::-webkit-scrollbar { width: 4px; }
.modal::-webkit-scrollbar-thumb { background: var(--border2); border-radius: 2px; }
@keyframes pop { from { opacity:0; transform: scale(.96) translateY(8px); } to { opacity:1; transform: none; } }
.modal-title { font-size: 1rem; font-weight: 800; margin-bottom: 18px; display: flex; align-items: center; gap: 8px; }
.f-row { margin-bottom: 12px; }
.f-row label { display: block; font-size: .67rem; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: var(--muted); margin-bottom: 5px; }
.f-row input, .f-row select, .f-row textarea {
    width: 100%; background: var(--s3); border: 1px solid var(--border); border-radius: 9px;
    color: var(--text); font-size: .88rem; padding: 9px 11px; outline: none;
    transition: border-color .15s; font-family: inherit;
}
.f-row input:focus, .f-row select:focus, .f-row textarea:focus { border-color: var(--border2); }
.f-row select option { background: #1a1a22; }
.f-row textarea { min-height: 72px; resize: vertical; }
.f-row input[readonly] { opacity: .6; cursor: default; }
.f2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.modal-err { font-size: .78rem; color: var(--red); margin: 8px 0; display: none; min-height: 1em; }
.modal-err.show { display: block; }
.modal-btns { display: flex; gap: 8px; margin-top: 16px; }
.btn-ok {
    flex: 1; padding: 11px; background: var(--green); color: #000;
    border: none; border-radius: 10px; font-size: .88rem; font-weight: 800; cursor: pointer; transition: background .15s;
}
.btn-ok:hover { background: var(--green2); }
.btn-ok:disabled { opacity: .45; cursor: not-allowed; }
.btn-cancel {
    padding: 11px 18px; background: transparent; border: 1px solid var(--border);
    color: var(--muted); border-radius: 10px; font-size: .85rem; cursor: pointer; transition: all .15s;
}
.btn-cancel:hover { border-color: var(--border2); color: var(--text); }

/* Confirm dialog */
.confirm-dialog { max-width: 360px; }
.confirm-msg { font-size: .9rem; color: var(--muted); line-height: 1.5; margin-bottom: 20px; }
.confirm-btns { display: flex; gap: 8px; }
.btn-confirm-ok { flex:1; padding:10px; border:none; border-radius:9px; font-size:.85rem; font-weight:800; cursor:pointer; transition:background .15s; }
.btn-confirm-ok.danger  { background: var(--red);   color: #fff; }
.btn-confirm-ok.default { background: var(--green); color: #000; }
.btn-confirm-ok:hover { filter: brightness(1.1); }

/* Loading skeleton */
.skel-row { display: flex; gap: 14px; padding: 14px; border-bottom: 1px solid var(--border); }
.skel-avatar { width: 34px; height: 34px; border-radius: 50%; background: var(--s2); animation: pulse 1.5s ease-in-out infinite; flex-shrink: 0; }
.skel-lines { flex: 1; display: flex; flex-direction: column; gap: 6px; justify-content: center; }
.skel-line { height: 11px; border-radius: 5px; background: var(--s2); animation: pulse 1.5s ease-in-out infinite; }
@keyframes pulse { 0%,100%{ opacity:.4; } 50%{ opacity:.8; } }

/* ─── TOAST ──────────────────────────────────────────────────────────── */
.toast {
    position: fixed; bottom: 20px; left: 50%; transform: translateX(-50%) translateY(8px);
    background: rgba(16,16,24,.97); border: 1px solid var(--border2);
    border-radius: 12px; padding: 11px 20px;
    font-size: .82rem; font-weight: 600; color: var(--text);
    display: flex; align-items: center; gap: 8px; z-index: 900;
    opacity: 0; transition: opacity .22s, transform .22s;
    pointer-events: none; white-space: nowrap; max-width: calc(100vw - 40px);
}
.toast.show { opacity: 1; transform: translateX(-50%) translateY(0); }
.toast.ok    i { color: var(--green); }
.toast.err   i { color: var(--red); border-color: rgba(255,69,58,.3); }
.toast.info  i { color: var(--blue); }
</style>
</head>
<body>

<!-- TOPBAR -->
<header class="topbar">
    <div class="tb-brand">
        <img src="../../config/dist/img/loguito_lacanchita.WEBP" alt="">
        La Canchita
    </div>
    <div class="tb-sep"></div>
    <div class="tb-badge"><i class="fas fa-code"></i> Dev Panel</div>
    <nav class="tab-nav">
        <button class="tab-btn active" onclick="setTab('resumen',this)">Resumen</button>
        <button class="tab-btn"        onclick="setTab('clientes',this)">Clientes</button>
        <button class="tab-btn"        onclick="setTab('cobros',this)">Cobros</button>
    </nav>
    <div class="tb-right">
        <span class="admin-chip"><i class="fas fa-circle" style="color:var(--green);font-size:.45rem;margin-right:4px"></i><?= htmlspecialchars($admin_nombre) ?></span>
        <a href="../maquetaAdmin/Dashboard.php" class="btn-dash"><i class="fas fa-th-large"></i> Dashboard</a>
    </div>
</header>

<!-- LAYOUT -->
<div class="layout">

<!-- ═══ CONTENT ═══════════════════════════════════════════════════════════ -->
<div class="content" id="content">

<!-- ─── TAB: RESUMEN ─────────────────────────────────────────────────── -->
<section id="tab-resumen" class="tab-section active">

    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-ic">👥</div>
            <div class="stat-val" id="sTotal">–</div>
            <div class="stat-lbl">Clientes totales</div>
            <div class="stat-sub" id="sActivos">– activos</div>
        </div>
        <div class="stat-card purple">
            <div class="stat-ic">📈</div>
            <div class="stat-val" id="sMrr">–</div>
            <div class="stat-lbl">MRR estimado</div>
            <div class="stat-sub">ingresos mensuales recurrentes</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-ic">💰</div>
            <div class="stat-val" id="sCobrado">–</div>
            <div class="stat-lbl">Cobrado este mes</div>
        </div>
        <div class="stat-card red">
            <div class="stat-ic">⚡</div>
            <div class="stat-val" id="sPorCobrar">–</div>
            <div class="stat-lbl">Por cobrar (7 días)</div>
            <div class="stat-sub" id="sVencidos">– vencidos</div>
        </div>
    </div>

    <div class="resumen-grid">

        <!-- Alertas por cobrar -->
        <div class="panel-card">
            <div class="panel-card-head">
                <span class="panel-card-title"><i class="fas fa-bolt" style="color:var(--orange);margin-right:5px"></i>Acción requerida</span>
            </div>
            <div class="alert-list" id="alertList">
                <div class="alert-empty"><i class="fas fa-check-circle" style="color:var(--green);opacity:.4"></i>Cargando...</div>
            </div>
        </div>

        <!-- MRR Chart -->
        <div class="panel-card">
            <div class="panel-card-head">
                <span class="panel-card-title"><i class="fas fa-chart-bar" style="margin-right:5px"></i>Cobros últimos 6 meses</span>
                <span id="chartTotal" style="font-size:.75rem;font-weight:700;color:var(--green)"></span>
            </div>
            <div class="chart-wrap">
                <div class="chart-bars" id="chartBars">
                    <!-- JS rendered -->
                </div>
                <div class="chart-total" id="chartFooter"></div>
            </div>
        </div>

    </div>
</section>

<!-- ─── TAB: CLIENTES ────────────────────────────────────────────────── -->
<section id="tab-clientes" class="tab-section">
    <div class="toolbar">
        <div class="search-wrap">
            <i class="fas fa-search"></i>
            <input type="text" id="searchInput" placeholder="Buscar cliente..." oninput="filtrarTabla()">
        </div>
        <div class="pills">
            <button class="pill active" onclick="setFiltro('todos',this)">Todos</button>
            <button class="pill"        onclick="setFiltro('por_cobrar',this)">Por cobrar</button>
            <button class="pill"        onclick="setFiltro('activos',this)">Activos</button>
            <button class="pill"        onclick="setFiltro('prueba',this)">En prueba</button>
            <button class="pill"        onclick="setFiltro('vencidos',this)">Vencidos</button>
            <button class="pill"        onclick="setFiltro('cancelados',this)">Cancelados</button>
        </div>
        <button class="btn-primary" onclick="abrirModalPlan(null)"><i class="fas fa-plus"></i> Asignar plan</button>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th onclick="sortBy('nombre')">Cliente <i class="fas fa-sort sort-ic"></i></th>
                    <th>Plan</th>
                    <th onclick="sortBy('precio')">Precio <i class="fas fa-sort sort-ic"></i></th>
                    <th onclick="sortBy('prox')">Próximo cobro <i class="fas fa-sort sort-ic"></i></th>
                    <th>Estado</th>
                    <th>Predios</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaBody">
                <tr><td colspan="7"><?= str_repeat('<div class="skel-row"><div class="skel-avatar"></div><div class="skel-lines"><div class="skel-line" style="width:60%"></div><div class="skel-line" style="width:40%"></div></div></div>', 5) ?></td></tr>
            </tbody>
        </table>
    </div>
</section>

<!-- ─── TAB: COBROS ──────────────────────────────────────────────────── -->
<section id="tab-cobros" class="tab-section">
    <div class="cobros-header">
        <input type="month" class="month-select" id="mesFilter" onchange="cargarCobros()" value="<?= date('Y-m') ?>">
        <div class="search-wrap" style="max-width:220px">
            <i class="fas fa-search"></i>
            <input type="text" id="cobroSearch" placeholder="Filtrar cliente..." oninput="filtrarCobros()">
        </div>
        <span class="cobros-total" id="cobrosTotal"></span>
    </div>
    <div id="cobrosLista">
        <div class="empty-state"><i class="fas fa-circle-notch fa-spin"></i><p>Cargando cobros...</p></div>
    </div>
</section>

</div><!-- /content -->

<!-- ─── DETAIL PANEL ──────────────────────────────────────────────────── -->
<aside class="detail-panel" id="detailPanel">
    <div class="dp-head">
        <div class="dp-avatar-wrap">
            <div class="dp-avatar" id="dpAvatar">?</div>
            <div>
                <div class="dp-nombre" id="dpNombre">—</div>
                <div class="dp-email"  id="dpEmail">—</div>
            </div>
        </div>
        <button class="dp-close" onclick="cerrarDetalle()" title="Cerrar"><i class="fas fa-times"></i></button>
    </div>
    <div class="dp-body">

        <!-- Estado y acciones rápidas -->
        <div class="dp-section">
            <div class="dp-section-title">Acciones</div>
            <div class="dp-actions">
                <button class="dp-btn green" id="dpBtnCobrar" onclick="dpCobrar()"><i class="fas fa-dollar-sign"></i> Registrar cobro</button>
                <button class="dp-btn" id="dpBtnRecordatorio" onclick="dpRecordatorio()"><i class="fas fa-bell"></i> Recordatorio</button>
                <button class="dp-btn" onclick="dpEditarPlan()"><i class="fas fa-edit"></i> Editar plan</button>
                <button class="dp-btn red" id="dpBtnToggle" onclick="dpToggle()"><i class="fas fa-ban"></i> <span id="dpToggleLbl">Desactivar</span></button>
            </div>
        </div>

        <!-- Info del plan -->
        <div class="dp-section">
            <div class="dp-section-title">Suscripción</div>
            <div class="info-row"><span class="info-lbl">Plan</span><span class="info-val" id="dpPlan">—</span></div>
            <div class="info-row"><span class="info-lbl">Precio</span><span class="info-val" id="dpPrecio">—</span></div>
            <div class="info-row"><span class="info-lbl">Ciclo</span><span class="info-val" id="dpCiclo">—</span></div>
            <div class="info-row"><span class="info-lbl">Estado</span><span class="info-val" id="dpEstadoBadge">—</span></div>
            <div class="info-row"><span class="info-lbl">Próximo cobro</span><span class="info-val" id="dpProximo">—</span></div>
            <div class="info-row"><span class="info-lbl">Último cobro</span><span class="info-val" id="dpUltimo">—</span></div>
            <div class="info-row"><span class="info-lbl">Medio</span><span class="info-val" id="dpMedio">—</span></div>
            <div class="info-row"><span class="info-lbl">Total cobrado</span><span class="info-val" id="dpTotalCobrado">—</span></div>
        </div>

        <!-- Notas -->
        <div class="dp-section">
            <div class="dp-section-title">Notas internas</div>
            <textarea class="dp-notas" id="dpNotas" placeholder="Observaciones, acuerdos, descuentos..." oninput="scheduleSaveNotas()"></textarea>
            <div class="notas-saved" id="notasSaved"><i class="fas fa-check"></i> Guardado</div>
        </div>

        <!-- Historial -->
        <div class="dp-section">
            <div class="dp-section-title">Historial de cobros</div>
            <div id="dpHistorial"><div class="hist-empty">Cargando...</div></div>
        </div>

    </div>
</aside>

</div><!-- /layout -->

<!-- ─── MODALES ────────────────────────────────────────────────────────── -->

<!-- PLAN -->
<div class="overlay" id="overlayPlan">
<div class="modal">
    <div class="modal-title"><i class="fas fa-file-invoice-dollar" style="color:var(--green)"></i> <span id="planTitulo">Asignar plan</span></div>
    <input type="hidden" id="planUid">
    <div class="f-row" id="planClienteRow">
        <label>Cliente (Dueño)</label>
        <select id="planCliente"></select>
    </div>
    <div class="f2">
        <div class="f-row">
            <label>Nombre del plan</label>
            <input type="text" id="planNombre" placeholder="Ej: Básico, Pro, Premium">
        </div>
        <div class="f-row">
            <label>Estado</label>
            <select id="planEstado">
                <option value="activo">Activo</option>
                <option value="prueba">En prueba</option>
                <option value="vencido">Vencido</option>
                <option value="cancelado">Cancelado</option>
            </select>
        </div>
    </div>
    <div class="f2">
        <div class="f-row">
            <label>Precio ($)</label>
            <input type="number" id="planPrecio" placeholder="0" min="0" step="500">
        </div>
        <div class="f-row">
            <label>Ciclo</label>
            <select id="planCiclo">
                <option value="mensual">Mensual</option>
                <option value="trimestral">Trimestral</option>
                <option value="anual">Anual</option>
            </select>
        </div>
    </div>
    <div class="f2">
        <div class="f-row">
            <label>Próximo cobro</label>
            <input type="date" id="planProximo">
        </div>
        <div class="f-row">
            <label>Medio habitual</label>
            <select id="planMedio">
                <option value="transferencia">Transferencia</option>
                <option value="efectivo">Efectivo</option>
                <option value="mercadopago">Mercado Pago</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="otro">Otro</option>
            </select>
        </div>
    </div>
    <div class="f-row">
        <label>Notas</label>
        <textarea id="planNotas" placeholder="Descuentos, acuerdos especiales..."></textarea>
    </div>
    <div class="modal-err" id="planErr"></div>
    <div class="modal-btns">
        <button class="btn-cancel" onclick="cerrarOverlay('overlayPlan')">Cancelar</button>
        <button class="btn-ok" id="btnGuardarPlan" onclick="guardarPlan()"><i class="fas fa-save" style="margin-right:5px"></i>Guardar</button>
    </div>
</div>
</div>

<!-- COBRO -->
<div class="overlay" id="overlayCobro">
<div class="modal">
    <div class="modal-title"><i class="fas fa-dollar-sign" style="color:var(--green)"></i> Registrar cobro</div>
    <input type="hidden" id="cobroUid">
    <div class="f-row">
        <label>Cliente</label>
        <input type="text" id="cobroCliente" readonly>
    </div>
    <div class="f2">
        <div class="f-row">
            <label>Monto ($)</label>
            <input type="number" id="cobroMonto" min="1" step="100" placeholder="0">
        </div>
        <div class="f-row">
            <label>Fecha</label>
            <input type="date" id="cobroFecha">
        </div>
    </div>
    <div class="f2">
        <div class="f-row">
            <label>Período cubierto</label>
            <input type="month" id="cobroPeriodo">
        </div>
        <div class="f-row">
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
    <div class="f-row">
        <label>Referencia / notas</label>
        <input type="text" id="cobroNotas" placeholder="Comprobante, nro. de transferencia...">
    </div>
    <div class="modal-err" id="cobroErr"></div>
    <div class="modal-btns">
        <button class="btn-cancel" onclick="cerrarOverlay('overlayCobro')">Cancelar</button>
        <button class="btn-ok" id="btnGuardarCobro" onclick="guardarCobro()"><i class="fas fa-check" style="margin-right:5px"></i>Registrar</button>
    </div>
</div>
</div>

<!-- CONFIRM -->
<div class="overlay" id="overlayConfirm">
<div class="modal confirm-dialog">
    <div class="modal-title" id="confirmTitle">¿Confirmar acción?</div>
    <p class="confirm-msg" id="confirmMsg"></p>
    <div class="confirm-btns">
        <button class="btn-cancel" onclick="cerrarOverlay('overlayConfirm')">Cancelar</button>
        <button class="btn-confirm-ok default" id="confirmOkBtn">Confirmar</button>
    </div>
</div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"><i class="fas fa-check-circle"></i> <span id="toastMsg"></span></div>

<script>
/* ═══════════════════════════════════════════════════════════════════════
   STATE
═══════════════════════════════════════════════════════════════════════ */
const S = {
    tab:      'resumen',
    filtro:   'todos',
    clientes: [],
    filteredClientes: [],
    sortCol:  'prox',
    sortDir:  1,
    cobros:   [],
    selected: null,  // cliente actual en detail panel
};

const API = 'api/clientes.php';

/* ═══════════════════════════════════════════════════════════════════════
   UTILS
═══════════════════════════════════════════════════════════════════════ */
const fmt  = n  => '$' + Number(n).toLocaleString('es-AR', {maximumFractionDigits:0});
const esc  = s  => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
const today = () => new Date().toISOString().split('T')[0];
const fmtFecha = s => { if(!s)return '—'; const[y,m,d]=s.split('-'); return `${d}/${m}/${y}`; };
const cicloLbl = c => ({mensual:'/ mes',trimestral:'/ trim.',anual:'/ año'}[c]||'');

function proxInfo(prox) {
    if (!prox) return { html: '<span class="prox-null">Sin definir</span>', diff: Infinity };
    const [y,m,d] = prox.split('-').map(Number);
    const hoy = new Date(); hoy.setHours(0,0,0,0);
    const p   = new Date(y, m-1, d);
    const diff = Math.round((p-hoy)/86400000);
    const label = `${String(d).padStart(2,'0')}/${String(m).padStart(2,'0')}/${y}`;
    if (diff < 0)       return { html: `<span class="prox-late">${label}<span class="prox-sub">${Math.abs(diff)}d vencido</span></span>`, diff };
    if (diff === 0)     return { html: `<span class="prox-warn">${label}<span class="prox-sub">Hoy</span></span>`, diff };
    if (diff <= 7)      return { html: `<span class="prox-warn">${label}<span class="prox-sub">en ${diff} días</span></span>`, diff };
    return { html: `<span class="prox-ok">${label}</span>`, diff };
}

function estadoBadge(e) {
    const m = {activo:'b-activo Activo',prueba:'b-prueba En prueba',vencido:'b-vencido Vencido',cancelado:'b-cancelado Cancelado',sin_plan:'b-sin_plan Sin plan'};
    const [cls, lbl] = (m[e]||'b-sin_plan Sin plan').split(' ');
    return `<span class="badge ${cls}">${lbl}</span>`;
}

/* ═══════════════════════════════════════════════════════════════════════
   API CALLS
═══════════════════════════════════════════════════════════════════════ */
async function apiFetch(params) {
    try {
        const isPost = params.method === 'POST';
        const url = isPost ? API : `${API}?${new URLSearchParams(params.get||{})}`;
        const r = await fetch(url, isPost ? { method:'POST', body: params.body } : {});
        return await r.json();
    } catch { return { ok:false, msg:'Error de red' }; }
}

/* ═══════════════════════════════════════════════════════════════════════
   TABS
═══════════════════════════════════════════════════════════════════════ */
function setTab(tab, btn) {
    S.tab = tab;
    document.querySelectorAll('.tab-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + tab).classList.add('active');
    if (btn) btn.classList.add('active');
    if (tab === 'clientes' && !S.clientes.length) cargarClientes();
    if (tab === 'cobros') cargarCobros();
}

/* ═══════════════════════════════════════════════════════════════════════
   RESUMEN
═══════════════════════════════════════════════════════════════════════ */
async function cargarResumen() {
    // Auto-mark overdue (silently)
    fetch(API, { method:'POST', body: new URLSearchParams({action:'marcar_vencidos'}) });

    // Stats
    const j = await apiFetch({ get: {action:'stats'} });
    if (j.ok) {
        const d = j.data;
        document.getElementById('sTotal').textContent      = d.total_clientes;
        document.getElementById('sActivos').textContent    = `${d.activos} activos`;
        document.getElementById('sMrr').textContent        = fmt(d.mrr) + '/mes';
        document.getElementById('sCobrado').textContent    = fmt(d.cobrado_mes);
        document.getElementById('sPorCobrar').textContent  = d.por_cobrar;
        document.getElementById('sVencidos').textContent   = `${d.vencidos} vencidos`;
    }

    // Alert list (por cobrar urgente)
    const jc = await apiFetch({ get: {action:'listar', filtro:'por_cobrar'} });
    const list = document.getElementById('alertList');
    if (jc.ok && jc.data.length) {
        list.innerHTML = jc.data.slice(0,8).map(c => {
            const { diff } = proxInfo(c.PROXIMO_COBRO);
            const dotCls = diff < 0 ? 'red' : 'orange';
            const diasLabel = diff < 0 ? `${Math.abs(diff)}d vencido` : diff === 0 ? 'Hoy' : `en ${diff}d`;
            const cData = encodeURIComponent(JSON.stringify(c));
            return `<div class="alert-item" onclick="abrirDetalle(JSON.parse(decodeURIComponent('${cData}')))">
                <div class="alert-dot ${dotCls}"></div>
                <div class="alert-info">
                    <div class="alert-nombre">${esc(c.USUARIOS_NOMBRE+' '+c.USUARIOS_APELLIDO)}</div>
                    <div class="alert-dias">${diasLabel} · ${esc(c.PLAN_NOMBRE)}</div>
                </div>
                ${c.PLAN_PRECIO>0?`<span class="alert-monto">${fmt(c.PLAN_PRECIO)}</span>`:''}
                <button class="alert-btn-cobrar" onclick="event.stopPropagation();iniciarCobroRapido(event, JSON.parse(decodeURIComponent('${cData}')))"
                    title="Registrar cobro">Cobrar</button>
            </div>`;
        }).join('');
    } else {
        list.innerHTML = '<div class="alert-empty"><i class="fas fa-check-circle" style="color:var(--green);opacity:.5"></i>Todo al día — sin cobros urgentes</div>';
    }

    // Chart
    const jch = await apiFetch({ get: {action:'mrr_historico'} });
    if (jch.ok) renderChart(jch.data);
}

function iniciarCobroRapido(event, c) {
    event.stopPropagation();
    abrirModalCobro(c);
}

function renderChart(data) {
    const barsEl = document.getElementById('chartBars');
    const max = Math.max(...data.map(d => d.total), 1);
    const total = data.reduce((s,d) => s+d.total, 0);

    barsEl.innerHTML = data.map(d => {
        const pct = d.total / max;
        const h   = Math.max(pct * 120, d.total > 0 ? 4 : 1);
        return `<div class="chart-col">
            <div class="chart-bar-wrap">
                <div class="chart-bar" style="height:${h}px" data-tip="${d.label}: ${fmt(d.total)}"></div>
            </div>
            <div class="chart-label">${esc(d.label)}</div>
        </div>`;
    }).join('');

    document.getElementById('chartTotal').textContent = total > 0 ? fmt(total) + ' total' : '';
}

/* ═══════════════════════════════════════════════════════════════════════
   CLIENTES
═══════════════════════════════════════════════════════════════════════ */
async function cargarClientes() {
    document.getElementById('tablaBody').innerHTML =
        `<tr><td colspan="7">${Array(5).fill('<div class="skel-row"><div class="skel-avatar"></div><div class="skel-lines"><div class="skel-line" style="width:60%"></div><div class="skel-line" style="width:38%"></div></div></div>').join('')}</td></tr>`;

    const j = await apiFetch({ get: {action:'listar', filtro: S.filtro} });
    if (!j.ok) { renderTabla([]); toast(j.msg, 'err'); return; }
    S.clientes = j.data || [];
    aplicarFiltros();
}

function setFiltro(f, el) {
    S.filtro = f;
    document.querySelectorAll('.pills .pill').forEach(p => p.classList.remove('active'));
    el.classList.add('active');
    S.clientes = [];
    cargarClientes();
}

function filtrarTabla() {
    aplicarFiltros();
}

function aplicarFiltros() {
    const q = document.getElementById('searchInput').value.toLowerCase();
    S.filteredClientes = S.clientes.filter(c =>
        !q ||
        (c.USUARIOS_NOMBRE+' '+c.USUARIOS_APELLIDO).toLowerCase().includes(q) ||
        (c.USUARIOS_EMAIL||'').toLowerCase().includes(q)
    );
    sortClientes();
    renderTabla(S.filteredClientes);
}

function sortBy(col) {
    if (S.sortCol === col) S.sortDir *= -1; else { S.sortCol = col; S.sortDir = 1; }
    document.querySelectorAll('thead th').forEach(th => th.classList.remove('sorted'));
    sortClientes();
    renderTabla(S.filteredClientes);
}

function sortClientes() {
    S.filteredClientes.sort((a, b) => {
        let va, vb;
        if (S.sortCol === 'nombre') { va = (a.USUARIOS_NOMBRE||'').toLowerCase(); vb = (b.USUARIOS_NOMBRE||'').toLowerCase(); }
        else if (S.sortCol === 'precio') { va = parseFloat(a.PLAN_PRECIO||0); vb = parseFloat(b.PLAN_PRECIO||0); }
        else { va = a.PROXIMO_COBRO||'9999'; vb = b.PROXIMO_COBRO||'9999'; }
        return (va < vb ? -1 : va > vb ? 1 : 0) * S.sortDir;
    });
}

function renderTabla(clientes) {
    const tbody = document.getElementById('tablaBody');
    if (!clientes.length) {
        tbody.innerHTML = `<tr><td colspan="7"><div class="empty-state"><i class="fas fa-users"></i><p>Sin clientes en este filtro</p></div></td></tr>`;
        return;
    }
    // Store clients in window for access by index
    window._clientesRender = clientes;
    tbody.innerHTML = clientes.map((c, i) => {
        const inicial = (c.USUARIOS_NOMBRE||'?')[0].toUpperCase();
        const nombre  = esc(c.USUARIOS_NOMBRE+' '+c.USUARIOS_APELLIDO);
        const { html: proxHtml } = proxInfo(c.PROXIMO_COBRO);
        const avatarCls = c.CUENTA_ACTIVA==0 ? 'off' : '';
        const isSelected = S.selected?.USUARIOS_ID == c.USUARIOS_ID;

        return `<tr onclick="_rowClick(${i})" data-idx="${i}" ${isSelected?'class="selected"':''}>
            <td>
                <div class="td-cliente">
                    <div class="avatar ${avatarCls}">${inicial}</div>
                    <div>
                        <div class="cl-nombre">${nombre}${c.CUENTA_ACTIVA==0?' <span style="color:var(--red);font-size:.68rem">(bloqueado)</span>':''}</div>
                        <div class="cl-email">${esc(c.USUARIOS_EMAIL||'')}</div>
                    </div>
                </div>
            </td>
            <td style="font-size:.82rem">${esc(c.PLAN_NOMBRE)}</td>
            <td style="white-space:nowrap">
                ${c.PLAN_PRECIO>0
                    ? `<strong>${fmt(c.PLAN_PRECIO)}</strong> <span style="color:var(--muted);font-size:.72rem">${cicloLbl(c.PLAN_CICLO)}</span>`
                    : `<span style="color:var(--muted2)">—</span>`}
            </td>
            <td>${proxHtml}</td>
            <td>${estadoBadge(c.ESTADO)}</td>
            <td style="text-align:center;color:var(--muted)">${c.TOTAL_PREDIOS}</td>
            <td>
                <div class="row-actions" onclick="event.stopPropagation()">
                    <button class="btn-row green" onclick="abrirModalCobro(window._clientesRender[${i}])"><i class="fas fa-dollar-sign"></i></button>
                    <button class="btn-row"       onclick="abrirModalPlan(window._clientesRender[${i}])"><i class="fas fa-edit"></i></button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function _rowClick(i) {
    abrirDetalle(window._clientesRender[i]);
}

/* ═══════════════════════════════════════════════════════════════════════
   COBROS TAB
═══════════════════════════════════════════════════════════════════════ */
let _cobrosAll = [];
async function cargarCobros() {
    const mes = document.getElementById('mesFilter').value;
    document.getElementById('cobrosLista').innerHTML = '<div class="empty-state"><i class="fas fa-circle-notch fa-spin"></i><p>Cargando...</p></div>';
    const j = await apiFetch({ get: {action:'cobros_todos', mes} });
    if (!j.ok) { toast(j.msg,'err'); return; }
    _cobrosAll = j.data.cobros || [];
    document.getElementById('cobrosTotal').textContent = `Total: ${fmt(j.data.total)}`;
    filtrarCobros();
}

function filtrarCobros() {
    const q = document.getElementById('cobroSearch').value.toLowerCase();
    const filtered = _cobrosAll.filter(c =>
        !q || (c.USUARIOS_NOMBRE+' '+c.USUARIOS_APELLIDO).toLowerCase().includes(q)
    );
    const lista = document.getElementById('cobrosLista');
    if (!filtered.length) {
        lista.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>Sin cobros para este período</p></div>';
        return;
    }
    lista.innerHTML = filtered.map(c => `
        <div class="cobro-item">
            <span class="cobro-fecha">${fmtFecha(c.COBRO_FECHA)}</span>
            <div class="cobro-cliente">
                <strong>${esc(c.USUARIOS_NOMBRE+' '+c.USUARIOS_APELLIDO)}</strong>
                ${c.COBRO_PERIODO?`<span>Período ${esc(c.COBRO_PERIODO)}</span>`:''}
                ${c.COBRO_NOTAS?`<span>· ${esc(c.COBRO_NOTAS)}</span>`:''}
            </div>
            <span class="cobro-monto">${fmt(c.COBRO_MONTO)}</span>
            ${c.COBRO_MEDIO?`<span class="cobro-medio">${esc(c.COBRO_MEDIO)}</span>`:''}
            <button class="cobro-del" onclick="confirmar('¿Eliminar este cobro?','Eliminar',async()=>{await eliminarCobro(${c.COBRO_ID});cargarCobros();},'danger')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
        </div>
    `).join('');
}

/* ═══════════════════════════════════════════════════════════════════════
   DETAIL PANEL
═══════════════════════════════════════════════════════════════════════ */
function abrirDetalle(c) {
    S.selected = c;

    // Highlight row
    document.querySelectorAll('tbody tr').forEach(tr => tr.classList.remove('selected'));
    document.querySelectorAll('tbody tr').forEach(tr => {
        if (tr.dataset.idx !== undefined && window._clientesRender?.[tr.dataset.idx]?.USUARIOS_ID == c.USUARIOS_ID)
            tr.classList.add('selected');
    });

    const dp = document.getElementById('detailPanel');
    dp.classList.add('open');

    // Avatar
    const av = document.getElementById('dpAvatar');
    av.textContent = (c.USUARIOS_NOMBRE||'?')[0].toUpperCase();
    av.className   = 'dp-avatar' + (c.CUENTA_ACTIVA==0?' off':'');

    // Header
    document.getElementById('dpNombre').textContent = (c.USUARIOS_NOMBRE+' '+c.USUARIOS_APELLIDO).trim();
    document.getElementById('dpEmail').textContent  = c.USUARIOS_EMAIL || 'Sin email';

    // Toggle button
    const toggleLbl = document.getElementById('dpToggleLbl');
    const toggleBtn = document.getElementById('dpBtnToggle');
    if (c.CUENTA_ACTIVA==0) {
        toggleLbl.textContent = 'Activar cuenta';
        toggleBtn.classList.remove('red');
        toggleBtn.querySelector('i').className = 'fas fa-unlock';
    } else {
        toggleLbl.textContent = 'Desactivar';
        toggleBtn.classList.add('red');
        toggleBtn.querySelector('i').className = 'fas fa-ban';
    }

    // Plan info
    document.getElementById('dpPlan').textContent         = c.PLAN_NOMBRE || '—';
    document.getElementById('dpPrecio').textContent       = c.PLAN_PRECIO>0 ? fmt(c.PLAN_PRECIO)+' '+cicloLbl(c.PLAN_CICLO) : '—';
    document.getElementById('dpCiclo').textContent        = {mensual:'Mensual',trimestral:'Trimestral',anual:'Anual'}[c.PLAN_CICLO]||'—';
    document.getElementById('dpEstadoBadge').innerHTML    = estadoBadge(c.ESTADO);
    document.getElementById('dpProximo').innerHTML        = proxInfo(c.PROXIMO_COBRO).html;
    document.getElementById('dpUltimo').textContent       = c.ULTIMO_COBRO ? fmtFecha(c.ULTIMO_COBRO) : '—';
    document.getElementById('dpMedio').textContent        = c.MEDIO_COBRO !== '—' ? c.MEDIO_COBRO : '—';
    document.getElementById('dpTotalCobrado').textContent = fmt(c.TOTAL_COBRADO||0);

    // Notas
    document.getElementById('dpNotas').value = c.NOTAS || '';
    document.getElementById('notasSaved').classList.remove('show');

    // Historial
    cargarHistorialPanel(c.USUARIOS_ID);
}

function cerrarDetalle() {
    document.getElementById('detailPanel').classList.remove('open');
    document.querySelectorAll('tbody tr').forEach(tr => tr.classList.remove('selected'));
    S.selected = null;
}

async function cargarHistorialPanel(uid) {
    document.getElementById('dpHistorial').innerHTML = '<div class="hist-empty">Cargando...</div>';
    const j = await apiFetch({ get: {action:'historial', usuarios_id:uid} });
    const rows = j.data || [];
    if (!rows.length) {
        document.getElementById('dpHistorial').innerHTML = '<div class="hist-empty">Sin cobros registrados</div>';
        return;
    }
    document.getElementById('dpHistorial').innerHTML = rows.map(r => `
        <div class="hist-item">
            <div class="hist-info">
                <span class="hist-monto">${fmt(r.COBRO_MONTO)}</span>
                <div class="hist-meta">${fmtFecha(r.COBRO_FECHA)}${r.COBRO_PERIODO?' · '+esc(r.COBRO_PERIODO):''}${r.COBRO_MEDIO?' · '+esc(r.COBRO_MEDIO):''}${r.COBRO_NOTAS?'<br>'+esc(r.COBRO_NOTAS):''}</div>
            </div>
            <button class="hist-del" onclick="confirmar('¿Eliminar este cobro?','Eliminar',async()=>{await eliminarCobro(${r.COBRO_ID});if(S.selected)cargarHistorialPanel(S.selected.USUARIOS_ID);cargarResumen();},'danger')" title="Eliminar"><i class="fas fa-trash-alt"></i></button>
        </div>
    `).join('');
}

// Notas — debounced autosave
let _notasTimer = null;
function scheduleSaveNotas() {
    clearTimeout(_notasTimer);
    document.getElementById('notasSaved').classList.remove('show');
    _notasTimer = setTimeout(async () => {
        if (!S.selected) return;
        const notas = document.getElementById('dpNotas').value;
        const fd = new FormData();
        fd.append('action', 'guardar_notas');
        fd.append('usuarios_id', S.selected.USUARIOS_ID);
        fd.append('notas', notas);
        const j = await fetch(API, {method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
        if (j?.ok) {
            document.getElementById('notasSaved').classList.add('show');
            if (S.selected) S.selected.NOTAS = notas;
        }
    }, 900);
}

// Panel actions
function dpCobrar()     { if (S.selected) abrirModalCobro(S.selected); }
function dpEditarPlan() { if (S.selected) abrirModalPlan(S.selected); }

async function dpRecordatorio() {
    if (!S.selected) return;
    const btn = document.getElementById('dpBtnRecordatorio');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i> Enviando...';
    const fd = new FormData();
    fd.append('action', 'recordatorio');
    fd.append('usuarios_id', S.selected.USUARIOS_ID);
    const j = await fetch(API,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-bell"></i> Recordatorio';
    toast(j?.ok ? j.msg : (j?.msg||'Error'), j?.ok ? 'ok' : 'err');
}

async function dpToggle() {
    if (!S.selected) return;
    const nombre  = (S.selected.USUARIOS_NOMBRE+' '+S.selected.USUARIOS_APELLIDO).trim();
    const activo  = S.selected.CUENTA_ACTIVA;
    const accion  = activo ? 'desactivar' : 'activar';
    confirmar(
        `¿${accion.charAt(0).toUpperCase()+accion.slice(1)} la cuenta de ${nombre}?`,
        activo ? 'Desactivar' : 'Activar',
        async () => {
            const fd = new FormData();
            fd.append('action', 'toggle_cliente');
            fd.append('usuarios_id', S.selected.USUARIOS_ID);
            const j = await fetch(API,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
            if (!j?.ok) { toast(j?.msg||'Error','err'); return; }
            toast(j.msg);
            await cargarClientes();
            // Re-open with updated data
            const updated = S.clientes.find(c => c.USUARIOS_ID == S.selected.USUARIOS_ID);
            if (updated) abrirDetalle(updated);
        },
        activo ? 'danger' : 'default'
    );
}

/* ═══════════════════════════════════════════════════════════════════════
   MODAL PLAN
═══════════════════════════════════════════════════════════════════════ */
let _allClientes = null;
async function abrirModalPlan(c) {
    // Load all clients for dropdown if needed
    if (!_allClientes) {
        const j = await apiFetch({ get: {action:'listar', filtro:'todos'} });
        _allClientes = j.data || [];
    }
    const sel = document.getElementById('planCliente');
    sel.innerHTML = _allClientes.map(cl =>
        `<option value="${cl.USUARIOS_ID}" ${c?.USUARIOS_ID==cl.USUARIOS_ID?'selected':''}>${esc(cl.USUARIOS_NOMBRE+' '+cl.USUARIOS_APELLIDO)} — ${esc(cl.USUARIOS_EMAIL||'')}</option>`
    ).join('');

    const editing = !!(c?.SUSCRIPCION_ID);
    document.getElementById('planTitulo').textContent     = editing ? 'Editar plan' : 'Asignar plan';
    document.getElementById('planUid').value              = c?.USUARIOS_ID || '';
    document.getElementById('planClienteRow').style.display = editing ? 'none' : '';
    if (editing) {
        document.getElementById('planNombre').value  = c.PLAN_NOMBRE !== 'Sin plan' ? c.PLAN_NOMBRE : '';
        document.getElementById('planPrecio').value  = c.PLAN_PRECIO;
        document.getElementById('planCiclo').value   = c.PLAN_CICLO;
        document.getElementById('planProximo').value = c.PROXIMO_COBRO || '';
        document.getElementById('planEstado').value  = c.ESTADO !== 'sin_plan' ? c.ESTADO : 'activo';
        document.getElementById('planMedio').value   = c.MEDIO_COBRO !== '—' ? c.MEDIO_COBRO : 'transferencia';
        document.getElementById('planNotas').value   = c.NOTAS || '';
    } else {
        document.getElementById('planNombre').value  = 'Estándar';
        document.getElementById('planPrecio').value  = '';
        document.getElementById('planCiclo').value   = 'mensual';
        document.getElementById('planProximo').value = nextMonth();
        document.getElementById('planEstado').value  = 'activo';
        document.getElementById('planMedio').value   = 'transferencia';
        document.getElementById('planNotas').value   = '';
    }
    showModalErr('planErr', '');
    document.getElementById('overlayPlan').classList.add('show');
    setTimeout(() => document.getElementById('planNombre').focus(), 80);
}

async function guardarPlan() {
    const uid    = document.getElementById('planUid').value || document.getElementById('planCliente').value;
    const nombre = document.getElementById('planNombre').value.trim();
    const precio = document.getElementById('planPrecio').value;
    if (!uid)    { showModalErr('planErr','Seleccioná un cliente.'); return; }
    if (!nombre) { showModalErr('planErr','Ingresá el nombre del plan.'); return; }
    if (!precio || Number(precio) < 0) { showModalErr('planErr','Ingresá un precio válido.'); return; }

    const btn = setLoading('btnGuardarPlan', true);
    const fd  = new FormData();
    fd.append('action','upsert_plan'); fd.append('usuarios_id',uid);
    fd.append('plan_nombre',nombre);   fd.append('plan_precio',precio);
    fd.append('plan_ciclo', document.getElementById('planCiclo').value);
    fd.append('proximo_cobro', document.getElementById('planProximo').value);
    fd.append('estado',   document.getElementById('planEstado').value);
    fd.append('medio_cobro', document.getElementById('planMedio').value);
    fd.append('notas',    document.getElementById('planNotas').value);

    const j = await fetch(API,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
    setLoading('btnGuardarPlan', false, btn);
    if (!j?.ok) { showModalErr('planErr', j?.msg||'Error'); return; }
    cerrarOverlay('overlayPlan');
    toast('Plan guardado ✓');
    _allClientes = null;
    await cargarClientes();
    await cargarResumen();
    const updated = S.clientes.find(c => c.USUARIOS_ID == uid);
    if (updated && S.selected?.USUARIOS_ID == uid) abrirDetalle(updated);
}

/* ═══════════════════════════════════════════════════════════════════════
   MODAL COBRO
═══════════════════════════════════════════════════════════════════════ */
function abrirModalCobro(c) {
    document.getElementById('cobroUid').value     = c.USUARIOS_ID;
    document.getElementById('cobroCliente').value = (c.USUARIOS_NOMBRE+' '+c.USUARIOS_APELLIDO).trim();
    document.getElementById('cobroMonto').value   = c.PLAN_PRECIO > 0 ? c.PLAN_PRECIO : '';
    document.getElementById('cobroFecha').value   = today();
    document.getElementById('cobroPeriodo').value = today().substring(0,7);
    document.getElementById('cobroMedio').value   = c.MEDIO_COBRO !== '—' ? c.MEDIO_COBRO : 'transferencia';
    document.getElementById('cobroNotas').value   = '';
    showModalErr('cobroErr','');
    document.getElementById('overlayCobro').classList.add('show');
    setTimeout(() => document.getElementById('cobroMonto').focus(), 80);
}

async function guardarCobro() {
    const monto = parseFloat(document.getElementById('cobroMonto').value)||0;
    if (monto <= 0) { showModalErr('cobroErr','Ingresá un monto válido.'); return; }

    const btn = setLoading('btnGuardarCobro', true);
    const uid = document.getElementById('cobroUid').value;
    const fd  = new FormData();
    fd.append('action','registrar_cobro'); fd.append('usuarios_id',uid);
    fd.append('monto',monto); fd.append('fecha',document.getElementById('cobroFecha').value);
    fd.append('periodo',document.getElementById('cobroPeriodo').value);
    fd.append('medio',document.getElementById('cobroMedio').value);
    fd.append('notas',document.getElementById('cobroNotas').value);

    const j = await fetch(API,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
    setLoading('btnGuardarCobro', false, btn);
    if (!j?.ok) { showModalErr('cobroErr', j?.msg||'Error'); return; }
    cerrarOverlay('overlayCobro');
    toast('Cobro registrado ✓');
    await cargarClientes();
    await cargarResumen();
    if (S.selected?.USUARIOS_ID == uid) {
        const updated = S.clientes.find(c => c.USUARIOS_ID == uid);
        if (updated) abrirDetalle(updated);
    }
    if (S.tab === 'cobros') cargarCobros();
}

async function eliminarCobro(id) {
    const fd = new FormData();
    fd.append('action','eliminar_cobro'); fd.append('cobro_id',id);
    const j = await fetch(API,{method:'POST',body:fd}).then(r=>r.json()).catch(()=>null);
    if (!j?.ok) { toast(j?.msg||'Error','err'); return false; }
    toast('Cobro eliminado');
    return true;
}

/* ═══════════════════════════════════════════════════════════════════════
   CONFIRM DIALOG
═══════════════════════════════════════════════════════════════════════ */
function confirmar(msg, okLabel, onOk, type='default') {
    document.getElementById('confirmMsg').textContent  = msg;
    const btn = document.getElementById('confirmOkBtn');
    btn.textContent  = okLabel;
    btn.className    = `btn-confirm-ok ${type}`;
    btn.onclick      = async () => { cerrarOverlay('overlayConfirm'); await onOk(); };
    document.getElementById('overlayConfirm').classList.add('show');
}

/* ═══════════════════════════════════════════════════════════════════════
   HELPERS
═══════════════════════════════════════════════════════════════════════ */
function cerrarOverlay(id) { document.getElementById(id).classList.remove('show'); }

function showModalErr(id, msg) {
    const el = document.getElementById(id);
    el.textContent = msg;
    el.style.display = msg ? 'block' : 'none';
}

function setLoading(btnId, loading, saved) {
    const btn = document.getElementById(btnId);
    if (loading) {
        const orig = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-circle-notch fa-spin"></i>';
        return orig;
    } else {
        btn.disabled  = false;
        btn.innerHTML = saved;
    }
}

let _toastT = null;
function toast(msg, type='ok') {
    const el  = document.getElementById('toast');
    const ic  = { ok:'fa-check-circle', err:'fa-exclamation-circle', info:'fa-info-circle' }[type]||'fa-check-circle';
    el.querySelector('i').className = `fas ${ic}`;
    document.getElementById('toastMsg').textContent = msg;
    el.className = `toast ${type} show`;
    clearTimeout(_toastT);
    _toastT = setTimeout(() => el.classList.remove('show'), 3500);
}

function nextMonth() {
    const d = new Date(); d.setMonth(d.getMonth()+1);
    return d.toISOString().split('T')[0];
}

/* ═══════════════════════════════════════════════════════════════════════
   EVENTS
═══════════════════════════════════════════════════════════════════════ */
['overlayPlan','overlayCobro','overlayConfirm'].forEach(id => {
    document.getElementById(id).addEventListener('click', function(e) {
        if (e.target === this) cerrarOverlay(id);
    });
});

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        ['overlayPlan','overlayCobro','overlayConfirm'].forEach(cerrarOverlay);
        cerrarDetalle();
    }
    if ((e.ctrlKey||e.metaKey) && e.key === 'k') {
        e.preventDefault();
        setTab('clientes', document.querySelectorAll('.tab-btn')[1]);
        setTimeout(() => document.getElementById('searchInput').focus(), 100);
    }
});

/* ═══════════════════════════════════════════════════════════════════════
   INIT
═══════════════════════════════════════════════════════════════════════ */
cargarResumen();
</script>
</body>
</html>
