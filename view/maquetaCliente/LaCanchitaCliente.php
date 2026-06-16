<?php
session_start();
require_once '../../config/dist/script/php/conn.php';
if (empty($_SESSION['usuario_id'])) { header('Location: ../../login.php'); exit; }

$uid     = (int)$_SESSION['usuario_id'];
$nombre  = $_SESSION['usuario_nombre']  ?? 'Usuario';
$inicial = strtoupper(substr($nombre, 0, 1));
$perfil  = (int)($_SESSION['usuario_perfil'] ?? 5);
$PWA_BASE = '../../';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>La Canchita</title>
<?php require_once '../../config/dist/script/php/pwa_head.php'; ?>
<link rel="stylesheet" href="../../config/pluggins/vendor/fontawesome-free/css/all.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

:root{
  --bg:    #09090f;
  --s1:    #101018;
  --s2:    #16161f;
  --s3:    #1d1d28;
  --bdr:   rgba(255,255,255,.08);
  --bdr2:  rgba(255,255,255,.13);
  --green: #4cd964;
  --blue:  #3b82f6;
  --orange:#ff9500;
  --red:   #e74c3c;
  --txt:   #f0f0f5;
  --muted: rgba(255,255,255,.42);
  --muted2:rgba(255,255,255,.2);
  --sb-w:  240px;
  --bn-h:  64px;
}

html,body{height:100%;background:var(--bg);color:var(--txt);
  font-family:'Segoe UI',system-ui,-apple-system,Arial,sans-serif;overflow:hidden}

/* ── Layout ─────────────────────────────────────────────────────────────── */
.app{display:flex;height:100vh;overflow:hidden}

/* ── Sidebar (desktop) ──────────────────────────────────────────────────── */
.sidebar{
  width:var(--sb-w);flex-shrink:0;
  background:rgba(9,9,15,.96);
  border-right:1px solid var(--bdr);
  display:flex;flex-direction:column;
  position:fixed;inset:0 auto 0 0;z-index:300;
  transition:transform .3s;
}
.sb-brand{
  padding:22px 20px 18px;
  border-bottom:1px solid var(--bdr);
  display:flex;align-items:center;gap:10px;
}
.sb-brand-name{font-size:17px;font-weight:800;color:var(--green);letter-spacing:-.3px}
.sb-brand-sub{font-size:10px;color:var(--muted);letter-spacing:.08em;text-transform:uppercase}

.sb-user{
  padding:14px 20px;border-bottom:1px solid var(--bdr);
  display:flex;align-items:center;gap:10px;
}
.avatar{
  width:38px;height:38px;border-radius:50%;
  background:linear-gradient(135deg,#4cd964,#34c759);
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:15px;color:#0d0d0d;flex-shrink:0;
}
.sb-user-name{font-size:13px;font-weight:600;line-height:1.3}
.sb-user-role{font-size:11px;color:var(--muted)}

.sb-nav{flex:1;padding:12px 0;overflow-y:auto}
.sb-item{
  display:flex;align-items:center;gap:11px;
  padding:11px 20px;font-size:14px;color:var(--muted);
  cursor:pointer;transition:all .15s;border-left:3px solid transparent;
  user-select:none;
}
.sb-item i{width:18px;text-align:center;font-size:15px}
.sb-item:hover{color:var(--txt);background:rgba(255,255,255,.04)}
.sb-item.active{color:var(--green);background:rgba(76,217,100,.07);border-left-color:var(--green)}

.sb-footer{
  padding:16px 20px;border-top:1px solid var(--bdr);
  display:flex;flex-direction:column;gap:8px;
}
.sb-logout{
  display:flex;align-items:center;gap:8px;
  font-size:13px;color:var(--muted);cursor:pointer;
  padding:8px 0;transition:color .15s;background:none;border:0;width:100%;text-align:left;
}
.sb-logout:hover{color:var(--red)}
<?php if($perfil<=4): ?>
.sb-admin-link{
  display:flex;align-items:center;gap:8px;
  font-size:12px;color:var(--muted2);cursor:pointer;
  padding:6px 0;transition:color .15s;text-decoration:none;
}
.sb-admin-link:hover{color:var(--txt)}
<?php endif; ?>

/* ── Main content ───────────────────────────────────────────────────────── */
.main{
  margin-left:var(--sb-w);flex:1;
  display:flex;flex-direction:column;height:100vh;overflow:hidden;
}

/* ── Topbar ─────────────────────────────────────────────────────────────── */
.topbar{
  height:56px;flex-shrink:0;
  display:flex;align-items:center;justify-content:space-between;
  padding:0 24px;border-bottom:1px solid var(--bdr);
  background:rgba(9,9,15,.8);backdrop-filter:blur(12px);
  position:sticky;top:0;z-index:100;
}
.topbar-title{font-size:16px;font-weight:700;letter-spacing:-.2px}
.topbar-search{
  display:flex;align-items:center;gap:10px;
  background:var(--s1);border:1px solid var(--bdr);border-radius:10px;
  padding:8px 14px;flex:1;max-width:320px;
  transition:border-color .2s;
}
.topbar-search:focus-within{border-color:rgba(76,217,100,.4)}
.topbar-search input{
  background:none;border:0;outline:0;color:var(--txt);font-size:14px;width:100%;
}
.topbar-search input::placeholder{color:var(--muted)}
.topbar-search i{color:var(--muted);font-size:13px;flex-shrink:0}
.topbar-right{display:flex;align-items:center;gap:12px}
.topbar-avatar{
  width:34px;height:34px;border-radius:50%;
  background:linear-gradient(135deg,#4cd964,#34c759);
  display:flex;align-items:center;justify-content:center;
  font-weight:700;font-size:13px;color:#0d0d0d;cursor:pointer;flex-shrink:0;
}

/* ── Content area ───────────────────────────────────────────────────────── */
.content{flex:1;overflow-y:auto;padding:24px}

/* ── Views ──────────────────────────────────────────────────────────────── */
.view{display:none}
.view.active{display:block}

/* ── Filter tabs ────────────────────────────────────────────────────────── */
.filter-tabs{
  display:flex;gap:6px;margin-bottom:20px;flex-wrap:wrap;
}
.filter-tab{
  padding:7px 16px;border-radius:20px;font-size:13px;font-weight:600;
  border:1px solid var(--bdr);background:var(--s1);color:var(--muted);
  cursor:pointer;transition:all .15s;user-select:none;
}
.filter-tab:hover{border-color:var(--bdr2);color:var(--txt)}
.filter-tab.active{background:rgba(76,217,100,.12);border-color:var(--green);color:var(--green)}

/* ── Predios grid ───────────────────────────────────────────────────────── */
.predios-grid{
  display:grid;
  grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
  gap:16px;
}
.predio-card{
  background:var(--s1);border:1px solid var(--bdr);border-radius:16px;
  overflow:hidden;cursor:pointer;transition:all .2s;
  animation:fadeUp .3s ease both;
}
.predio-card:hover{border-color:rgba(76,217,100,.3);transform:translateY(-2px);box-shadow:0 8px 28px rgba(0,0,0,.35)}
@keyframes fadeUp{from{opacity:0;transform:translateY(12px)}to{opacity:1;transform:translateY(0)}}

.predio-thumb{
  height:100px;background:linear-gradient(135deg,var(--s2),var(--s3));
  display:flex;align-items:center;justify-content:center;
  position:relative;
}
.predio-thumb i{font-size:36px;color:var(--green);opacity:.6}
.predio-thumb-badges{position:absolute;top:10px;right:10px;display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.thumb-badge{
  font-size:10px;font-weight:700;padding:3px 8px;border-radius:6px;
  background:rgba(0,0,0,.55);backdrop-filter:blur(6px);
}
.thumb-badge.green{color:var(--green)}
.thumb-badge.blue{color:#7dd3fc}

.predio-body{padding:14px 16px 0}
.predio-nombre{font-size:15px;font-weight:700;margin-bottom:4px}
.predio-loc{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:5px;margin-bottom:8px}
.predio-acts{display:flex;flex-wrap:wrap;gap:5px;margin-bottom:8px}
.act-tag{font-size:11px;padding:2px 8px;border-radius:5px;background:var(--s2);color:var(--muted2)}

.predio-footer{
  padding:12px 16px;display:flex;align-items:center;justify-content:space-between;
  border-top:1px solid var(--bdr);margin-top:8px;
}
.predio-tel{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:5px}
.btn-ver{
  padding:7px 14px;border-radius:8px;border:0;
  background:var(--green);color:#0d0d0d;font-size:12px;font-weight:700;
  cursor:pointer;transition:opacity .15s;display:flex;align-items:center;gap:5px;
}
.btn-ver:hover{opacity:.85}

/* ── Predio detalle ─────────────────────────────────────────────────────── */
.back-btn{
  display:inline-flex;align-items:center;gap:8px;
  font-size:14px;color:var(--muted);cursor:pointer;
  padding:8px 0;margin-bottom:20px;background:none;border:0;
  transition:color .15s;
}
.back-btn:hover{color:var(--txt)}

.predio-det-header{
  background:var(--s1);border:1px solid var(--bdr);border-radius:16px;
  padding:20px;margin-bottom:20px;
  display:flex;gap:16px;align-items:flex-start;
}
.predio-det-icon{
  width:56px;height:56px;border-radius:12px;
  background:var(--s2);display:flex;align-items:center;justify-content:center;
  font-size:24px;color:var(--green);flex-shrink:0;
}
.predio-det-info h2{font-size:18px;font-weight:800;margin-bottom:4px}
.predio-det-loc{font-size:13px;color:var(--muted);margin-bottom:8px;display:flex;align-items:center;gap:6px}
.predio-det-tags{display:flex;flex-wrap:wrap;gap:6px}
.det-tag{font-size:11px;padding:3px 10px;border-radius:6px;background:var(--s2);border:1px solid var(--bdr)}
.det-tag.green{color:var(--green);border-color:rgba(76,217,100,.25)}

.cancha-card{
  background:var(--s1);border:1px solid var(--bdr);border-radius:14px;
  padding:16px;margin-bottom:12px;animation:fadeUp .25s ease both;
}
.cancha-header{display:flex;align-items:center;gap:12px;margin-bottom:12px}
.cancha-icon{
  width:42px;height:42px;border-radius:10px;background:var(--s2);
  display:flex;align-items:center;justify-content:center;font-size:18px;color:var(--green);flex-shrink:0;
}
.cancha-info h3{font-size:14px;font-weight:700;margin-bottom:2px}
.cancha-info span{font-size:12px;color:var(--muted)}
.cancha-actions{margin-left:auto;flex-shrink:0}
.btn-reservar{
  padding:8px 16px;border-radius:8px;border:0;
  background:var(--green);color:#0d0d0d;font-size:13px;font-weight:700;
  cursor:pointer;transition:opacity .15s;
}
.btn-reservar:hover{opacity:.85}

.franjas-wrap{display:flex;flex-wrap:wrap;gap:8px}
.franja-item{
  background:var(--s2);border:1px solid var(--bdr);border-radius:8px;
  padding:8px 12px;font-size:12px;
}
.franja-hora{font-weight:700;margin-bottom:2px}
.franja-dias{font-size:11px;color:var(--muted);margin-bottom:2px}
.franja-precio{color:var(--green);font-weight:700}

/* ── Mis Reservas ───────────────────────────────────────────────────────── */
.reserva-card{
  background:var(--s1);border:1px solid var(--bdr);border-radius:14px;
  padding:16px;margin-bottom:12px;transition:border-color .2s;
  animation:fadeUp .25s ease both;
}
.reserva-card:hover{border-color:var(--bdr2)}
.rc-top{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;margin-bottom:10px}
.rc-cancha{font-size:14px;font-weight:700;margin-bottom:3px;display:flex;align-items:center;gap:6px}
.rc-predio{font-size:12px;color:var(--muted)}
.estado-badge{
  font-size:10px;font-weight:700;padding:4px 10px;border-radius:20px;
  white-space:nowrap;flex-shrink:0;
}
.estado-pendiente{background:rgba(255,149,0,.12);color:var(--orange);border:1px solid rgba(255,149,0,.25)}
.estado-confirmada{background:rgba(76,217,100,.12);color:var(--green);border:1px solid rgba(76,217,100,.25)}
.estado-cancelada{background:rgba(231,76,60,.1);color:var(--red);border:1px solid rgba(231,76,60,.2)}

.rc-meta{display:flex;flex-wrap:wrap;gap:10px;margin-bottom:12px}
.rc-meta span{font-size:12px;color:var(--muted);display:flex;align-items:center;gap:5px}
.rc-meta i{color:var(--green);width:12px}

.rc-footer{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.rc-precio{font-size:16px;font-weight:800;color:var(--txt)}
.rc-saldo{font-size:12px;color:var(--orange);margin-top:2px}
.btn-cancelar{
  padding:7px 14px;border-radius:8px;border:1px solid rgba(231,76,60,.3);
  background:rgba(231,76,60,.08);color:var(--red);font-size:12px;font-weight:600;
  cursor:pointer;transition:all .15s;
}
.btn-cancelar:hover{background:rgba(231,76,60,.15);border-color:var(--red)}

.btn-wsp{
  padding:7px 14px;border-radius:8px;border:1px solid rgba(37,211,102,.25);
  background:rgba(37,211,102,.08);color:#25D366;font-size:12px;font-weight:600;
  cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;
  transition:all .15s;
}
.btn-wsp:hover{background:rgba(37,211,102,.15)}

/* ── Mi Perfil ──────────────────────────────────────────────────────────── */
.perfil-wrap{max-width:560px}
.perfil-header{
  background:var(--s1);border:1px solid var(--bdr);border-radius:16px;
  padding:24px;margin-bottom:20px;
  display:flex;align-items:center;gap:16px;
}
.perfil-avatar{
  width:64px;height:64px;border-radius:50%;flex-shrink:0;
  background:linear-gradient(135deg,#4cd964,#34c759);
  display:flex;align-items:center;justify-content:center;
  font-size:26px;font-weight:800;color:#0d0d0d;
}
.perfil-header-info h2{font-size:17px;font-weight:700;margin-bottom:3px}
.perfil-header-info p{font-size:13px;color:var(--muted)}

.form-section{
  background:var(--s1);border:1px solid var(--bdr);border-radius:16px;
  padding:20px;margin-bottom:16px;
}
.form-section-title{font-size:13px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin-bottom:16px}
.field{margin-bottom:14px}
.field:last-child{margin-bottom:0}
.field label{display:block;font-size:12px;font-weight:600;color:var(--muted);margin-bottom:6px;text-transform:uppercase;letter-spacing:.04em}
.field input{
  width:100%;padding:11px 14px;background:var(--s2);border:1px solid var(--bdr);
  border-radius:10px;color:var(--txt);font-size:14px;outline:none;transition:border-color .2s;
}
.field input:focus{border-color:rgba(76,217,100,.45)}
.field input::placeholder{color:var(--muted2)}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:12px}

.btn-save{
  width:100%;padding:13px;border-radius:12px;border:0;
  background:var(--green);color:#0d0d0d;font-size:14px;font-weight:700;
  cursor:pointer;transition:opacity .15s;margin-top:4px;
}
.btn-save:hover{opacity:.88}
.btn-save:disabled{opacity:.4;cursor:not-allowed}

.perfil-msg{
  padding:10px 14px;border-radius:10px;font-size:13px;margin-top:12px;display:none;
}

/* ── Bottom nav (mobile) ────────────────────────────────────────────────── */
.bottom-nav{
  display:none;position:fixed;bottom:0;left:0;right:0;
  height:var(--bn-h);background:rgba(9,9,15,.96);
  border-top:1px solid var(--bdr);backdrop-filter:blur(12px);
  z-index:300;
}
.bn-items{display:flex;height:100%}
.bn-item{
  flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:4px;cursor:pointer;transition:color .15s;color:var(--muted);
  font-size:10px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;
  user-select:none;border:0;background:none;
}
.bn-item i{font-size:20px;transition:color .15s}
.bn-item.active{color:var(--green)}

/* ── Modal ──────────────────────────────────────────────────────────────── */
.modal-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.7);
  display:flex;align-items:flex-end;justify-content:center;
  z-index:500;opacity:0;pointer-events:none;transition:opacity .25s;
}
.modal-overlay.show{opacity:1;pointer-events:all}
.modal-box{
  background:var(--s1);border:1px solid var(--bdr);
  border-radius:20px 20px 0 0;
  width:100%;max-width:520px;max-height:92vh;overflow-y:auto;
  padding:20px;transform:translateY(20px);transition:transform .25s;
}
.modal-overlay.show .modal-box{transform:translateY(0)}
@media(min-width:768px){
  .modal-overlay{align-items:center}
  .modal-box{border-radius:20px;max-height:88vh}
}
.modal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px}
.modal-head h3{font-size:16px;font-weight:700;display:flex;align-items:center;gap:8px}
.modal-head h3 i{color:var(--green)}
.modal-close{background:var(--s2);border:0;color:var(--muted);width:32px;height:32px;border-radius:8px;cursor:pointer;font-size:14px;transition:color .15s}
.modal-close:hover{color:var(--txt)}

.modal-cancha-info{
  background:var(--s2);border-radius:10px;padding:12px 14px;margin-bottom:14px;
}
.modal-cancha-info strong{display:block;font-size:14px;font-weight:700;margin-bottom:2px}
.modal-cancha-info span{font-size:12px;color:var(--muted)}

.slots-label{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.06em;margin:14px 0 8px}
.slots-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(130px,1fr));gap:8px;margin-bottom:14px}
.slot-btn{
  padding:10px 8px;border-radius:10px;border:1px solid var(--bdr);
  background:var(--s2);color:var(--txt);font-size:13px;font-weight:600;
  cursor:pointer;text-align:center;transition:all .15s;display:block;width:100%;
}
.slot-btn:not([disabled]):hover{border-color:rgba(76,217,100,.4);background:rgba(76,217,100,.06)}
.slot-btn.selected{border-color:var(--green);background:rgba(76,217,100,.12);color:var(--green)}
.slot-btn[disabled]{opacity:.4;cursor:not-allowed}
.slot-price{display:block;font-size:11px;color:var(--muted);margin-top:2px;font-weight:400}
.slot-btn.selected .slot-price{color:rgba(76,217,100,.7)}
.slot-sena{display:block;font-size:10px;color:var(--orange);margin-top:1px}

.slots-empty{font-size:13px;color:var(--muted);text-align:center;padding:16px 0;grid-column:1/-1}

.resumen-box{
  background:var(--s2);border:1px solid var(--bdr);border-radius:10px;
  padding:12px 14px;margin-bottom:14px;font-size:13px;color:var(--muted);
  transition:all .2s;
}
.resumen-box.filled{border-color:rgba(76,217,100,.3);color:var(--txt)}

.modal-footer{display:flex;gap:10px}
.btn-modal-cancel{
  flex:1;padding:12px;border-radius:12px;border:1px solid var(--bdr);
  background:none;color:var(--muted);font-size:14px;cursor:pointer;transition:all .15s;
}
.btn-modal-cancel:hover{border-color:var(--bdr2);color:var(--txt)}
.btn-modal-confirm{
  flex:2;padding:12px;border-radius:12px;border:0;
  background:var(--green);color:#0d0d0d;font-size:14px;font-weight:700;
  cursor:pointer;transition:opacity .15s;
}
.btn-modal-confirm:disabled{opacity:.35;cursor:not-allowed}
.btn-modal-confirm:not(:disabled):hover{opacity:.88}

/* ── Calendar ───────────────────────────────────────────────────────────── */
.lc-cal{margin-bottom:4px}
.lc-cal-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:10px}
.lc-cal-title{font-size:14px;font-weight:700}
.lc-cal-nav{
  background:var(--s2);border:1px solid var(--bdr);color:var(--txt);
  width:30px;height:30px;border-radius:8px;cursor:pointer;font-size:12px;
  transition:all .15s;display:flex;align-items:center;justify-content:center;
}
.lc-cal-nav:hover:not([disabled]){border-color:var(--bdr2)}
.lc-cal-nav[disabled]{opacity:.3;cursor:not-allowed}
.lc-cal-week{display:grid;grid-template-columns:repeat(7,1fr);gap:2px;margin-bottom:4px}
.lc-cal-week span{text-align:center;font-size:11px;color:var(--muted);font-weight:600;padding:4px 0}
.lc-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:2px}
.lc-cal-day{
  aspect-ratio:1;border-radius:8px;border:0;background:none;color:var(--txt);
  font-size:13px;cursor:pointer;transition:all .15s;
  display:flex;align-items:center;justify-content:center;
}
.lc-cal-day:hover:not([disabled]){background:var(--s2)}
.lc-cal-day.today{background:var(--s2);color:var(--green);font-weight:700}
.lc-cal-day.selected{background:var(--green);color:#0d0d0d;font-weight:700}
.lc-cal-day[disabled]{opacity:.25;cursor:not-allowed}

/* ── Toast ──────────────────────────────────────────────────────────────── */
.toast{
  position:fixed;bottom:calc(var(--bn-h) + 12px);left:50%;transform:translateX(-50%) translateY(20px);
  background:rgba(22,22,31,.97);border:1px solid var(--bdr2);
  color:var(--txt);padding:12px 20px;border-radius:30px;font-size:14px;font-weight:500;
  z-index:999;opacity:0;pointer-events:none;transition:all .3s;white-space:nowrap;
  max-width:calc(100vw - 32px);text-align:center;
}
.toast.show{opacity:1;transform:translateX(-50%) translateY(0)}
.toast.ok{border-color:rgba(76,217,100,.35);color:var(--green)}
.toast.err{border-color:rgba(231,76,60,.35);color:var(--red)}

/* ── Confirm dialog ─────────────────────────────────────────────────────── */
.confirm-overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.65);
  display:flex;align-items:center;justify-content:center;
  z-index:600;opacity:0;pointer-events:none;transition:opacity .2s;padding:16px;
}
.confirm-overlay.show{opacity:1;pointer-events:all}
.confirm-box{
  background:var(--s1);border:1px solid var(--bdr);border-radius:16px;
  padding:24px;max-width:320px;width:100%;text-align:center;
}
.confirm-box h4{font-size:16px;font-weight:700;margin-bottom:8px}
.confirm-box p{font-size:14px;color:var(--muted);margin-bottom:20px;line-height:1.5}
.confirm-btns{display:flex;gap:10px}
.confirm-no{flex:1;padding:11px;border:1px solid var(--bdr);background:none;color:var(--muted);border-radius:10px;cursor:pointer;font-size:14px;transition:all .15s}
.confirm-no:hover{color:var(--txt);border-color:var(--bdr2)}
.confirm-yes{flex:1;padding:11px;border:0;background:var(--red);color:#fff;border-radius:10px;cursor:pointer;font-size:14px;font-weight:700;transition:opacity .15s}
.confirm-yes:hover{opacity:.85}

/* ── Empty / Skeleton ───────────────────────────────────────────────────── */
.empty-state{
  text-align:center;padding:48px 24px;
  display:flex;flex-direction:column;align-items:center;gap:12px;
}
.empty-state i{font-size:40px;color:var(--muted2)}
.empty-state h3{font-size:16px;font-weight:700}
.empty-state p{font-size:14px;color:var(--muted);max-width:280px;line-height:1.5}
.empty-state .btn-ver{margin-top:8px}

.skel{
  background:linear-gradient(90deg,var(--s1) 25%,var(--s2) 50%,var(--s1) 75%);
  background-size:200% 100%;animation:shimmer 1.4s infinite;border-radius:10px;
}
@keyframes shimmer{0%{background-position:200% 0}100%{background-position:-200% 0}}
.skel-card{height:180px;border-radius:14px;margin-bottom:12px}

/* ── Spinner ────────────────────────────────────────────────────────────── */
.spin{display:inline-block;width:16px;height:16px;border:2px solid rgba(0,0,0,.15);border-top-color:currentColor;border-radius:50%;animation:rotate .6s linear infinite;vertical-align:middle;margin-right:6px}
@keyframes rotate{to{transform:rotate(360deg)}}

/* ── Responsive ─────────────────────────────────────────────────────────── */
@media(max-width:767px){
  .sidebar{display:none}
  .main{margin-left:0}
  .bottom-nav{display:block}
  .content{padding:16px 16px calc(var(--bn-h) + 16px)}
  .topbar{padding:0 16px}
  .topbar-search{max-width:none;flex:1}
  .predios-grid{grid-template-columns:1fr}
  .field-row{grid-template-columns:1fr}
  .toast{bottom:calc(var(--bn-h) + 8px)}
}
@media(min-width:768px){
  .topbar-search-wrap{flex:1;display:flex;justify-content:center}
  .modal-overlay.show .modal-box{transform:translateY(0) scale(1)}
}
</style>
</head>
<body>
<div class="app">

  <!-- ── Sidebar (desktop) ───────────────────────────────────────────────── -->
  <aside class="sidebar">
    <div class="sb-brand">
      <div>
        <div class="sb-brand-name">La Canchita</div>
        <div class="sb-brand-sub">Reservas deportivas</div>
      </div>
    </div>
    <div class="sb-user">
      <div class="avatar"><?= $inicial ?></div>
      <div>
        <div class="sb-user-name"><?= htmlspecialchars($nombre) ?></div>
        <div class="sb-user-role">Cliente</div>
      </div>
    </div>
    <nav class="sb-nav">
      <div class="sb-item active" id="sb-predios" onclick="showView('predios')">
        <i class="fas fa-map-marker-alt"></i> Predios
      </div>
      <div class="sb-item" id="sb-reservas" onclick="showView('reservas')">
        <i class="fas fa-calendar-check"></i> Mis reservas
      </div>
      <div class="sb-item" id="sb-perfil" onclick="showView('perfil')">
        <i class="fas fa-user"></i> Mi perfil
      </div>
    </nav>
    <div class="sb-footer">
      <?php if($perfil<=4): ?>
      <a class="sb-admin-link" href="../maquetaAdmin/Dashboard.php">
        <i class="fas fa-cog"></i> Panel de administración
      </a>
      <?php endif; ?>
      <button class="sb-logout" onclick="location.href='../../logout.php'">
        <i class="fas fa-sign-out-alt"></i> Cerrar sesión
      </button>
    </div>
  </aside>

  <!-- ── Main ────────────────────────────────────────────────────────────── -->
  <div class="main">

    <!-- Topbar -->
    <div class="topbar">
      <div class="topbar-title" id="topbarTitle">Predios</div>
      <div class="topbar-search-wrap">
        <div class="topbar-search" id="searchWrap" style="display:flex">
          <i class="fas fa-search"></i>
          <input type="text" id="searchInput" placeholder="Buscar predio..." oninput="onSearch()">
        </div>
      </div>
      <div class="topbar-right">
        <div class="topbar-avatar" onclick="showView('perfil')"><?= $inicial ?></div>
      </div>
    </div>

    <!-- Content -->
    <div class="content">

      <!-- VIEW: Predios -->
      <div class="view active" id="view-predios">
        <div class="filter-tabs" id="activityFilters"></div>
        <div class="predios-grid" id="prediosGrid">
          <?php for($i=0;$i<4;$i++): ?>
          <div class="skel skel-card"></div>
          <?php endfor; ?>
        </div>
      </div>

      <!-- VIEW: Predio detalle -->
      <div class="view" id="view-predio">
        <button class="back-btn" onclick="goBackPredios()">
          <i class="fas fa-arrow-left"></i> Volver a predios
        </button>
        <div class="predio-det-header">
          <div class="predio-det-icon" id="detIcon"><i class="fas fa-map-marker-alt"></i></div>
          <div class="predio-det-info">
            <h2 id="detNombre">—</h2>
            <div class="predio-det-loc" id="detLoc"></div>
            <div class="predio-det-tags" id="detTags"></div>
          </div>
        </div>
        <div id="canchasContainer"></div>
      </div>

      <!-- VIEW: Mis reservas -->
      <div class="view" id="view-reservas">
        <div class="filter-tabs">
          <button class="filter-tab active" data-f="proximas" onclick="setResFilter('proximas',this)">Próximas</button>
          <button class="filter-tab" data-f="pasadas"  onclick="setResFilter('pasadas',this)">Pasadas</button>
          <button class="filter-tab" data-f="todas"    onclick="setResFilter('todas',this)">Todas</button>
        </div>
        <div id="reservasContainer"></div>
      </div>

      <!-- VIEW: Mi perfil -->
      <div class="view" id="view-perfil">
        <div class="perfil-wrap">
          <div class="perfil-header">
            <div class="perfil-avatar" id="perfilAvatar"><?= $inicial ?></div>
            <div class="perfil-header-info">
              <h2 id="perfilNombreDisplay"><?= htmlspecialchars($nombre) ?></h2>
              <p id="perfilEmailDisplay">—</p>
            </div>
          </div>
          <form onsubmit="submitPerfil(event)">
            <div class="form-section">
              <div class="form-section-title">Datos personales</div>
              <div class="field-row">
                <div class="field">
                  <label>Nombre</label>
                  <input type="text" name="nombre" id="pcNombre" placeholder="Tu nombre">
                </div>
                <div class="field">
                  <label>Apellido</label>
                  <input type="text" name="apellido" id="pcApellido" placeholder="Tu apellido">
                </div>
              </div>
              <div class="field-row">
                <div class="field">
                  <label>Email</label>
                  <input type="email" name="email" id="pcEmail" placeholder="tu@email.com">
                </div>
                <div class="field">
                  <label>Teléfono</label>
                  <input type="text" name="telefono" id="pcTel" placeholder="+54 11 ...">
                </div>
              </div>
            </div>
            <div class="form-section">
              <div class="form-section-title">Cambiar contraseña <span style="font-weight:400;text-transform:none;font-size:11px">(opcional)</span></div>
              <div class="field-row">
                <div class="field">
                  <label>Nueva contraseña</label>
                  <input type="password" name="password" id="pcPass" placeholder="Mínimo 6 caracteres">
                </div>
                <div class="field">
                  <label>Confirmar contraseña</label>
                  <input type="password" id="pcPass2" placeholder="Repetí la contraseña">
                </div>
              </div>
            </div>
            <button type="submit" class="btn-save" id="btnPcSubmit">
              <i class="fas fa-save" style="margin-right:6px"></i>Guardar cambios
            </button>
            <div class="perfil-msg" id="perfilMsg"></div>
          </form>
          <?php if($perfil<=4): ?>
          <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--bdr)">
            <a href="../maquetaAdmin/Dashboard.php" style="font-size:13px;color:var(--muted);text-decoration:none;display:flex;align-items:center;gap:8px">
              <i class="fas fa-cog"></i> Ir al panel de administración
            </a>
          </div>
          <?php endif; ?>
          <div style="margin-top:12px">
            <button onclick="location.href='../../logout.php'" style="font-size:13px;color:var(--muted);background:none;border:0;cursor:pointer;display:flex;align-items:center;gap:8px;padding:8px 0">
              <i class="fas fa-sign-out-alt"></i> Cerrar sesión
            </button>
          </div>
        </div>
      </div>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /app -->

<!-- Bottom nav (mobile) -->
<nav class="bottom-nav">
  <div class="bn-items">
    <button class="bn-item active" id="bn-predios" onclick="showView('predios')">
      <i class="fas fa-map-marker-alt"></i>Predios
    </button>
    <button class="bn-item" id="bn-reservas" onclick="showView('reservas')">
      <i class="fas fa-calendar-check"></i>Reservas
    </button>
    <button class="bn-item" id="bn-perfil" onclick="showView('perfil')">
      <i class="fas fa-user"></i>Perfil
    </button>
  </div>
</nav>

<!-- Modal reserva -->
<div class="modal-overlay" id="modalReserva">
  <div class="modal-box">
    <div class="modal-head">
      <h3><i class="fas fa-calendar-plus"></i>Reservar turno</h3>
      <button class="modal-close" onclick="cerrarModal()"><i class="fas fa-times"></i></button>
    </div>
    <div class="modal-cancha-info">
      <strong id="modalCanchaNombre">—</strong>
      <span id="modalCanchaDetalle">—</span>
    </div>
    <input type="hidden" id="modalFecha">
    <div id="calModalContainer"></div>
    <div class="slots-label"><i class="fas fa-clock" style="margin-right:5px"></i>Horarios disponibles</div>
    <div class="slots-grid" id="modalSlotsGrid">
      <span class="slots-empty">Elegí una fecha para ver los horarios.</span>
    </div>
    <div class="resumen-box" id="reservaResumen">Seleccioná un horario para continuar</div>
    <div class="modal-footer">
      <button class="btn-modal-cancel" onclick="cerrarModal()">Cancelar</button>
      <button class="btn-modal-confirm" id="btnConfirmarReserva" onclick="confirmarReserva()" disabled>
        Confirmar reserva
      </button>
    </div>
  </div>
</div>

<!-- Confirm dialog -->
<div class="confirm-overlay" id="confirmOverlay">
  <div class="confirm-box">
    <h4 id="confirmTitle">¿Cancelar reserva?</h4>
    <p id="confirmMsg">Esta acción no se puede deshacer.</p>
    <div class="confirm-btns">
      <button class="confirm-no" onclick="cerrarConfirm()">No, volver</button>
      <button class="confirm-yes" id="confirmYes">Sí, cancelar</button>
    </div>
  </div>
</div>

<!-- Toast -->
<div class="toast" id="toast"></div>

<script>
// ── Estado global ─────────────────────────────────────────────────────────
const S = {
  view: 'predios',
  predios: null,           // cache de predios
  predioActual: null,      // predio abierto
  canchaResId: null,       // cancha en modal
  franjaSeleccionada: null,
  reservasData: [],        // cache de mis reservas
  resFilter: 'proximas',   // filtro activo en reservas
  actFilter: null,         // filtro de actividad en predios
  searchQ: '',             // texto de búsqueda
};

// ── Utilidades ────────────────────────────────────────────────────────────
function esc(s){ if(!s) return ''; return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

function toast(msg, type='ok'){
  const t=document.getElementById('toast');
  t.textContent=msg; t.className='toast '+type+' show';
  clearTimeout(t._t); t._t=setTimeout(()=>t.className='toast',3500);
}

function confirmar(title, msg, onYes){
  document.getElementById('confirmTitle').textContent=title;
  document.getElementById('confirmMsg').textContent=msg;
  document.getElementById('confirmOverlay').classList.add('show');
  document.getElementById('confirmYes').onclick=()=>{ cerrarConfirm(); onYes(); };
}
function cerrarConfirm(){ document.getElementById('confirmOverlay').classList.remove('show'); }

// ── Navegación ────────────────────────────────────────────────────────────
const VIEWS = ['predios','predio','reservas','perfil'];
const TITLES = { predios:'Predios', predio:'Predio', reservas:'Mis reservas', perfil:'Mi perfil' };

function showView(name){
  VIEWS.forEach(v=>{
    const el=document.getElementById('view-'+v);
    if(el) el.classList.toggle('active', v===name);
  });
  // Topbar
  document.getElementById('topbarTitle').textContent = TITLES[name]||name;
  document.getElementById('searchWrap').style.display = name==='predios'?'flex':'none';

  // Sidebar active
  ['predios','reservas','perfil'].forEach(v=>{
    const sb=document.getElementById('sb-'+v);
    const bn=document.getElementById('bn-'+v);
    const active = v===name || (v==='predios' && name==='predio');
    if(sb) sb.classList.toggle('active', active);
    if(bn) bn.classList.toggle('active', active);
  });

  S.view=name;
  if(name==='reservas') loadReservas();
  if(name==='perfil')   loadPerfil();
}

function goBackPredios(){ showView('predios'); }

// ── Predios ───────────────────────────────────────────────────────────────
async function getPredios(){
  if(S.predios) return S.predios;
  const r=await fetch('api/predios.php?action=listar');
  const j=await r.json();
  if(j.ok) S.predios=j.data||[];
  return S.predios||[];
}

function onSearch(){ S.searchQ=document.getElementById('searchInput').value.toLowerCase(); renderPredios(); }

function setActFilter(act, btn){
  S.actFilter = S.actFilter===act ? null : act;
  document.querySelectorAll('#activityFilters .filter-tab').forEach(b=>b.classList.toggle('active', b.dataset.act===S.actFilter));
  renderPredios();
}

async function initPredios(){
  const data=await getPredios();
  // Construir filtros de actividad
  const acts=new Set();
  data.forEach(p=>{ if(p.ACTIVIDADES) p.ACTIVIDADES.split('||').forEach(a=>acts.add(a.trim())); });
  const filtersEl=document.getElementById('activityFilters');
  filtersEl.innerHTML=[...acts].slice(0,8).map(a=>
    `<button class="filter-tab" data-act="${esc(a)}" onclick="setActFilter('${esc(a)}',this)">${esc(a)}</button>`
  ).join('');
  renderPredios(data);
}

async function renderPredios(data){
  data=data||(await getPredios());
  const grid=document.getElementById('prediosGrid');

  let filtered=data;
  if(S.searchQ) filtered=filtered.filter(p=>
    (p.COMPLEJO_NOMBRE||'').toLowerCase().includes(S.searchQ)||
    (p.LOCALIDAD_NOMBRE||'').toLowerCase().includes(S.searchQ)||
    (p.PARTIDO_NOMBRE||'').toLowerCase().includes(S.searchQ)
  );
  if(S.actFilter) filtered=filtered.filter(p=>(p.ACTIVIDADES||'').includes(S.actFilter));

  if(!filtered.length){
    grid.innerHTML=`<div class="empty-state" style="grid-column:1/-1">
      <i class="fas fa-search"></i><h3>Sin resultados</h3>
      <p>No encontramos predios con ese criterio.</p>
      <button class="btn-ver" onclick="S.searchQ='';S.actFilter=null;document.getElementById('searchInput').value='';renderPredios()">Ver todos</button>
    </div>`; return;
  }

  grid.innerHTML=filtered.map((p,i)=>{
    const acts=p.ACTIVIDADES?p.ACTIVIDADES.split('||').map(a=>`<span class="act-tag">${esc(a.trim())}</span>`).join(''):'';
    const loc=[p.LOCALIDAD_NOMBRE,p.PARTIDO_NOMBRE].filter(Boolean).join(', ');
    const icon=p.TIPO_COMPLEJO_ICONO||'fa-map-marker-alt';
    return `<div class="predio-card" style="animation-delay:${i*.04}s" onclick="abrirPredio(${i})">
      <div class="predio-thumb">
        <i class="fas ${icon}"></i>
        <div class="predio-thumb-badges">
          ${p.TIPO_COMPLEJO_NOMBRE?`<span class="thumb-badge">${esc(p.TIPO_COMPLEJO_NOMBRE)}</span>`:''}
          <span class="thumb-badge green"><i class="fas fa-futbol"></i> ${p.TOTAL_CANCHAS}</span>
        </div>
      </div>
      <div class="predio-body">
        <div class="predio-nombre">${esc(p.COMPLEJO_NOMBRE)}</div>
        ${loc?`<div class="predio-loc"><i class="fas fa-map-pin" style="color:var(--green);font-size:10px"></i>${esc(loc)}</div>`:''}
        ${acts?`<div class="predio-acts">${acts}</div>`:''}
      </div>
      <div class="predio-footer">
        <span class="predio-tel">${p.COMPLEJO_TELEFONO?`<i class="fas fa-phone"></i>${esc(p.COMPLEJO_TELEFONO)}`:''}</span>
        <button class="btn-ver" onclick="event.stopPropagation();abrirPredio(${i})">
          <i class="fas fa-futbol"></i> Ver canchas
        </button>
      </div>
    </div>`;
  }).join('');
  // guardar filtered para acceder por índice
  window._filteredPredios=filtered;
}

async function abrirPredio(idx){
  const data=window._filteredPredios||S.predios||[];
  const p=data[idx]; if(!p) return;
  S.predioActual=p;

  const loc=[p.LOCALIDAD_NOMBRE,p.PARTIDO_NOMBRE,p.PROVINCIA_NOMBRE].filter(Boolean).join(', ');
  const icon=p.TIPO_COMPLEJO_ICONO||'fa-map-marker-alt';
  document.getElementById('detNombre').textContent=p.COMPLEJO_NOMBRE;
  document.getElementById('detIcon').innerHTML=`<i class="fas ${icon}"></i>`;
  document.getElementById('detLoc').innerHTML=loc?`<i class="fas fa-map-pin" style="color:var(--green);font-size:10px"></i>${esc(loc)}`:'';

  // Tags
  const tags=[];
  if(p.TIPO_COMPLEJO_NOMBRE) tags.push(`<span class="det-tag">${esc(p.TIPO_COMPLEJO_NOMBRE)}</span>`);
  tags.push(`<span class="det-tag green">${p.TOTAL_CANCHAS} cancha${p.TOTAL_CANCHAS!=1?'s':''}</span>`);
  if(p.ACTIVIDADES) p.ACTIVIDADES.split('||').forEach(a=>tags.push(`<span class="det-tag">${esc(a.trim())}</span>`));
  document.getElementById('detTags').innerHTML=tags.join('');

  showView('predio');

  // Skeleton canchas
  const cont=document.getElementById('canchasContainer');
  cont.innerHTML=[1,2].map(()=>'<div class="skel skel-card"></div>').join('');

  const r=await fetch(`api/predios.php?action=canchas&complejo_id=${p.COMPLEJO_ID}`);
  const j=await r.json();
  if(!j.ok||!j.data?.length){
    cont.innerHTML=`<div class="empty-state"><i class="fas fa-futbol"></i><h3>Sin canchas</h3><p>Este predio no tiene canchas configuradas todavía.</p></div>`; return;
  }
  S.predioActual.canchas=j.data;

  // WhatsApp
  let wspHtml='';
  if(p.COMPLEJO_TELEFONO){
    const tel=p.COMPLEJO_TELEFONO.replace(/\D/g,'');
    const msg=encodeURIComponent(`Hola! Quiero reservar en ${p.COMPLEJO_NOMBRE}`);
    wspHtml=`<a class="btn-wsp" href="https://wa.me/549${tel}?text=${msg}" target="_blank" rel="noopener">
      <i class="fab fa-whatsapp"></i>Consultar por WhatsApp</a>`;
  }
  if(wspHtml) document.getElementById('detTags').insertAdjacentHTML('afterend',`<div style="margin-top:10px">${wspHtml}</div>`);

  const DIAS=['','Lun','Mar','Mié','Jue','Vie','Sáb','Dom'];
  cont.innerHTML=j.data.map((c,i)=>{
    const icon2=(c.TIPO_CANCHA_ICONO||'fas fa-futbol');
    const franjas=c.franjas?.length
      ? c.franjas.map(f=>{
          const dias=(Array.isArray(f.DIAS)?f.DIAS:String(f.DIAS||'').split(',').map(Number))
                      .map(d=>DIAS[parseInt(d)]||'').filter(Boolean).join(' · ');
          const ini=(f.FRANJA_HORA_INICIO||'').substring(0,5);
          const fin=(f.FRANJA_HORA_FIN||'').substring(0,5);
          const precio=f.FRANJA_PRECIO?'$'+Number(f.FRANJA_PRECIO).toLocaleString('es-AR'):'—';
          return `<div class="franja-item">
            <div class="franja-hora">${ini} – ${fin}</div>
            <div class="franja-dias">${dias||'—'}</div>
            <div class="franja-precio">${precio}</div>
          </div>`;
        }).join('')
      : '<span style="font-size:13px;color:var(--muted)">Sin horarios configurados</span>';
    return `<div class="cancha-card" style="animation-delay:${i*.06}s">
      <div class="cancha-header">
        <div class="cancha-icon"><i class="${icon2}"></i></div>
        <div class="cancha-info">
          <h3>${esc(c.CANCHA_NOMBRE)}</h3>
          <span>${esc(c.TIPO_CANCHA_NOMBRE)}</span>
        </div>
        <div class="cancha-actions">
          <button class="btn-reservar" onclick="abrirReserva(${c.CANCHA_ID},${JSON.stringify(c.CANCHA_NOMBRE)},${JSON.stringify(c.TIPO_CANCHA_NOMBRE)})">
            <i class="fas fa-calendar-plus" style="margin-right:5px"></i>Reservar
          </button>
        </div>
      </div>
      <div class="franjas-wrap">${franjas}</div>
    </div>`;
  }).join('');
}

// ── Modal reserva ─────────────────────────────────────────────────────────
function abrirReserva(id, nombre, tipo){
  S.canchaResId=id; S.franjaSeleccionada=null;
  document.getElementById('modalCanchaNombre').textContent=nombre;
  document.getElementById('modalCanchaDetalle').textContent=tipo+(S.predioActual?' · '+S.predioActual.COMPLEJO_NOMBRE:'');

  const hoy=new Date().toISOString().split('T')[0];
  document.getElementById('modalFecha').value=hoy;
  if(!window._lcCal){
    window._lcCal=new CalendarioLC('calModalContainer',fecha=>{
      document.getElementById('modalFecha').value=fecha;
      actualizarSlots(fecha);
    });
  } else window._lcCal.setDate(hoy);
  actualizarSlots(hoy);
  document.getElementById('modalReserva').classList.add('show');
}

async function actualizarSlots(fechaStr){
  S.franjaSeleccionada=null; actualizarResumen();
  const grid=document.getElementById('modalSlotsGrid');
  grid.innerHTML=`<span class="slots-empty"><i class="fas fa-spinner fa-spin"></i> Verificando disponibilidad…</span>`;
  try{
    const r=await fetch(`api/reservas.php?action=disponibilidad&cancha_id=${S.canchaResId}&fecha=${fechaStr}`);
    const j=await r.json();
    if(!j.ok||!j.data?.length){ grid.innerHTML='<span class="slots-empty">No hay horarios para este día.</span>'; return; }

    const ahora=new Date();
    const esHoy=fechaStr===new Date().toISOString().split('T')[0];
    let slots=j.data;
    if(esHoy) slots=slots.filter(f=>{ const [h,m]=(f.FRANJA_HORA_FIN||'0:0').split(':').map(Number); const fin=new Date(); fin.setHours(h,m,0,0); return fin>ahora; });
    if(!slots.length){ grid.innerHTML=`<span class="slots-empty">${esHoy?'No quedan horarios para hoy. Probá mañana.':'Sin horarios disponibles.'}</span>`; return; }

    grid.innerHTML=slots.map(f=>{
      const ini=(f.FRANJA_HORA_INICIO||'--').substring(0,5);
      const fin=(f.FRANJA_HORA_FIN||'--').substring(0,5);
      const precio=f.FRANJA_PRECIO?'$'+Number(f.FRANJA_PRECIO).toLocaleString('es-AR'):'Sin precio';
      const sena=(f.FRANJA_SENA&&Number(f.FRANJA_SENA)>0)?`<span class="slot-sena">Seña $${Number(f.FRANJA_SENA).toLocaleString('es-AR')}</span>`:'';
      if(!f.disponible) return `<button class="slot-btn" disabled>
        <span style="text-decoration:line-through">${ini}–${fin}</span>
        <span class="slot-price"><i class="fas fa-lock" style="margin-right:2px"></i>${esc(f.motivo_no_disponible||'No disponible')}</span></button>`;
      return `<button class="slot-btn" data-id="${f.FRANJA_ID}" onclick="selSlot(this,${JSON.stringify(f)})">
        ${ini} – ${fin}<span class="slot-price">${precio}</span>${sena}</button>`;
    }).join('');
  }catch(e){ grid.innerHTML='<span class="slots-empty">Error al cargar. Intentá de nuevo.</span>'; }
}

function selSlot(btn,franja){
  document.querySelectorAll('#modalSlotsGrid .slot-btn').forEach(b=>b.classList.remove('selected'));
  btn.classList.add('selected'); S.franjaSeleccionada=franja; actualizarResumen();
}

function actualizarResumen(){
  const el=document.getElementById('reservaResumen');
  const btn=document.getElementById('btnConfirmarReserva');
  if(!S.franjaSeleccionada){ el.className='resumen-box'; el.textContent='Seleccioná un horario para continuar'; btn.disabled=true; return; }
  const fecha=document.getElementById('modalFecha').value;
  const fmtFecha=new Date(fecha+'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'short'});
  const ini=(S.franjaSeleccionada.FRANJA_HORA_INICIO||'').substring(0,5);
  const fin=(S.franjaSeleccionada.FRANJA_HORA_FIN||'').substring(0,5);
  const precio=S.franjaSeleccionada.FRANJA_PRECIO?'$'+Number(S.franjaSeleccionada.FRANJA_PRECIO).toLocaleString('es-AR'):'';
  el.className='resumen-box filled';
  el.innerHTML=`<strong style="text-transform:capitalize">${fmtFecha}</strong> &nbsp;·&nbsp; ${ini}–${fin} &nbsp;·&nbsp; <strong>${precio}</strong>`;
  btn.disabled=false;
}

function cerrarModal(){
  document.getElementById('modalReserva').classList.remove('show');
  S.canchaResId=null; S.franjaSeleccionada=null;
  const btn=document.getElementById('btnConfirmarReserva');
  btn.disabled=true; btn.innerHTML='Confirmar reserva';
}

async function confirmarReserva(){
  if(!S.franjaSeleccionada) return;
  const fecha=document.getElementById('modalFecha').value;
  const btn=document.getElementById('btnConfirmarReserva');
  btn.disabled=true; btn.innerHTML='<span class="spin"></span>Confirmando…';
  try{
    const fd=new FormData();
    fd.append('action','crear'); fd.append('cancha_id',S.canchaResId);
    fd.append('franja_id',S.franjaSeleccionada.FRANJA_ID); fd.append('fecha',fecha);
    const r=await fetch('api/reservas.php',{method:'POST',body:fd});
    const j=await r.json();
    if(j.ok){
      cerrarModal(); toast('✓ Reserva realizada con éxito','ok');
      S.reservasData=null; // invalidar cache
      if(S.view==='reservas') loadReservas();
    }else{
      toast(j.msg,'err');
      if(j.msg?.toLowerCase().includes('reserv')) actualizarSlots(fecha);
      btn.disabled=false; btn.innerHTML='Confirmar reserva';
    }
  }catch(e){ toast('Error de conexión. Intentá de nuevo.','err'); btn.disabled=false; btn.innerHTML='Confirmar reserva'; }
}
document.getElementById('modalReserva').addEventListener('click',e=>{ if(e.target===e.currentTarget) cerrarModal(); });

// ── Mis Reservas ──────────────────────────────────────────────────────────
async function loadReservas(){
  const cont=document.getElementById('reservasContainer');
  cont.innerHTML=[1,2,3].map(()=>'<div class="skel skel-card"></div>').join('');
  try{
    const r=await fetch('api/reservas.php?action=mis_reservas');
    const j=await r.json();
    S.reservasData=j.ok?j.data||[]:[];
    renderReservas();
  }catch(e){ cont.innerHTML='<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><h3>Error</h3><p>No se pudieron cargar tus reservas.</p></div>'; }
}

function setResFilter(f, btn){
  S.resFilter=f;
  document.querySelectorAll('[data-f]').forEach(b=>b.classList.toggle('active',b.dataset.f===f));
  renderReservas();
}

function renderReservas(){
  const cont=document.getElementById('reservasContainer');
  const hoy=new Date().toISOString().split('T')[0];
  let data=S.reservasData||[];

  if(S.resFilter==='proximas') data=data.filter(r=>r.RESERVA_FECHA>=hoy&&r.RESERVA_ESTADO!=='cancelada');
  else if(S.resFilter==='pasadas') data=data.filter(r=>r.RESERVA_FECHA<hoy);

  if(!data.length){
    const msgs={proximas:'No tenés reservas próximas.',pasadas:'No tenés reservas pasadas.',todas:'Todavía no hiciste ninguna reserva.'};
    cont.innerHTML=`<div class="empty-state"><i class="fas fa-calendar-times"></i><h3>Sin reservas</h3><p>${msgs[S.resFilter]||''}</p>
      <button class="btn-ver" onclick="showView('predios')"><i class="fas fa-search"></i>Buscar un predio</button></div>`;
    return;
  }

  const estadoLabel={pendiente:'⏳ Pendiente',confirmada:'✅ Confirmada',cancelada:'❌ Cancelada'};
  const badgeCls={pendiente:'estado-pendiente',confirmada:'estado-confirmada',cancelada:'estado-cancelada'};

  cont.innerHTML=data.map((res,i)=>{
    const estado=(res.RESERVA_ESTADO||'pendiente').toLowerCase();
    const fmtF=new Date(res.RESERVA_FECHA+'T00:00:00').toLocaleDateString('es-AR',{weekday:'long',day:'numeric',month:'short',year:'numeric'});
    const ini=(res.RESERVA_HORA_INICIO||'').substring(0,5);
    const fin=(res.RESERVA_HORA_FIN||'').substring(0,5);
    const precio=res.RESERVA_PRECIO?'$'+Number(res.RESERVA_PRECIO).toLocaleString('es-AR'):'—';
    const saldo=parseFloat(res.SALDO_PENDIENTE||0);
    const wspHtml=res.COMPLEJO_TELEFONO&&estado==='confirmada'
      ?`<a class="btn-wsp" href="https://wa.me/549${res.COMPLEJO_TELEFONO.replace(/\D/g,'')}?text=${encodeURIComponent(`Hola! Tengo una reserva en ${res.COMPLEJO_NOMBRE}`)}" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i>Contactar</a>`:'';
    return `<div class="reserva-card" style="animation-delay:${i*.04}s">
      <div class="rc-top">
        <div>
          <div class="rc-cancha"><i class="fas fa-futbol" style="color:var(--blue)"></i>${esc(res.TIPO_CANCHA_NOMBRE)} · ${esc(res.CANCHA_NOMBRE)}</div>
          <div class="rc-predio">${esc(res.COMPLEJO_NOMBRE)} — ${esc(res.COMPLEJO_DIRECCION||'')}</div>
        </div>
        <span class="estado-badge ${badgeCls[estado]||'estado-pendiente'}">${estadoLabel[estado]||estado}</span>
      </div>
      <div class="rc-meta">
        <span><i class="fas fa-calendar-alt"></i>${fmtF}</span>
        <span><i class="fas fa-clock"></i>${ini} – ${fin}</span>
        ${res.COMPLEJO_TELEFONO?`<span><i class="fas fa-phone"></i>${esc(res.COMPLEJO_TELEFONO)}</span>`:''}
      </div>
      <div class="rc-footer">
        <div>
          <div class="rc-precio">${precio}</div>
          ${saldo>0&&estado==='confirmada'?`<div class="rc-saldo">Pendiente: $${saldo.toLocaleString('es-AR')}</div>`:''}
        </div>
        <div style="display:flex;gap:8px;align-items:center">
          ${wspHtml}
          ${estado==='pendiente'?`<button class="btn-cancelar" onclick="cancelarReserva(${res.RESERVA_ID})"><i class="fas fa-times" style="margin-right:4px"></i>Cancelar</button>`:''}
        </div>
      </div>
    </div>`;
  }).join('');
}

async function cancelarReserva(id){
  confirmar('¿Cancelar esta reserva?','Esta acción no se puede deshacer.',async()=>{
    const fd=new FormData(); fd.append('action','cancelar'); fd.append('reserva_id',id);
    const r=await fetch('api/reservas.php',{method:'POST',body:fd});
    const j=await r.json();
    toast(j.ok?'Reserva cancelada.':j.msg, j.ok?'ok':'err');
    if(j.ok){ S.reservasData=null; loadReservas(); }
  });
}

// ── Mi Perfil ─────────────────────────────────────────────────────────────
async function loadPerfil(){
  const r=await fetch('api/perfil.php?action=get');
  const j=await r.json();
  if(!j.ok) return;
  const d=j.data;
  document.getElementById('pcNombre').value   = d.USUARIOS_NOMBRE   ||'';
  document.getElementById('pcApellido').value = d.USUARIOS_APELLIDO ||'';
  document.getElementById('pcEmail').value    = d.USUARIOS_EMAIL    ||'';
  document.getElementById('pcTel').value      = d.USUARIOS_TELEFONO ||'';
  document.getElementById('perfilNombreDisplay').textContent=(d.USUARIOS_NOMBRE+' '+d.USUARIOS_APELLIDO).trim();
  document.getElementById('perfilEmailDisplay').textContent=d.USUARIOS_EMAIL||'—';
  const ini=d.USUARIOS_NOMBRE?d.USUARIOS_NOMBRE[0].toUpperCase():'?';
  document.getElementById('perfilAvatar').textContent=ini;
  document.getElementById('pcPass').value=''; document.getElementById('pcPass2').value='';
  document.getElementById('perfilMsg').style.display='none';
}

async function submitPerfil(e){
  e.preventDefault();
  const pass=document.getElementById('pcPass').value;
  const pass2=document.getElementById('pcPass2').value;
  if(pass&&pass!==pass2){ toast('Las contraseñas no coinciden','err'); return; }
  const btn=document.getElementById('btnPcSubmit');
  btn.disabled=true; btn.innerHTML='<span class="spin"></span>Guardando…';
  const fd=new FormData(e.target); fd.append('action','update');
  const r=await fetch('api/perfil.php',{method:'POST',body:fd});
  const j=await r.json();
  btn.disabled=false; btn.innerHTML='<i class="fas fa-save" style="margin-right:6px"></i>Guardar cambios';
  const msg=document.getElementById('perfilMsg');
  msg.style.display='block';
  if(j.ok){
    toast('Perfil actualizado','ok');
    msg.style.cssText='display:block;background:rgba(76,217,100,.1);border:1px solid rgba(76,217,100,.25);color:#4cd964;padding:10px 14px;border-radius:10px;font-size:13px;margin-top:12px';
    msg.textContent='✓ '+j.msg;
    document.getElementById('pcPass').value=''; document.getElementById('pcPass2').value='';
    document.getElementById('perfilNombreDisplay').textContent=(document.getElementById('pcNombre').value+' '+document.getElementById('pcApellido').value).trim();
  }else{
    msg.style.cssText='display:block;background:rgba(231,76,60,.1);border:1px solid rgba(231,76,60,.25);color:#e74c3c;padding:10px 14px;border-radius:10px;font-size:13px;margin-top:12px';
    msg.textContent='✗ '+(j.msg||'Error al guardar.');
  }
}

// ── CalendarioLC ──────────────────────────────────────────────────────────
class CalendarioLC{
  constructor(containerId,onSelect){
    this.el=document.getElementById(containerId);
    this.onSelect=onSelect;
    const t=new Date(); t.setHours(0,0,0,0);
    this._today=t;
    this.selected=new Date(t);
    // año y mes como enteros — sin mutación de Date
    this.year=t.getFullYear();
    this.month=t.getMonth();
    this._render();
  }
  setDate(dateStr){
    const d=new Date(dateStr+'T00:00:00');
    this.selected=d; this.year=d.getFullYear(); this.month=d.getMonth();
    this._render();
  }
  prevMonth(){
    const ty=this._today.getFullYear(),tm=this._today.getMonth();
    if(this.year===ty&&this.month===tm) return;
    if(this.month===0){ this.year--; this.month=11; } else { this.month--; }
    this._render();
  }
  nextMonth(){
    if(this.month===11){ this.year++; this.month=0; } else { this.month++; }
    this._render();
  }
  pick(y,m,d){
    const date=new Date(y,m,d);
    if(date<this._today) return;
    this.selected=date;
    const yy=date.getFullYear(),mm=String(date.getMonth()+1).padStart(2,'0'),dd=String(date.getDate()).padStart(2,'0');
    this._render(); this.onSelect(`${yy}-${mm}-${dd}`);
  }
  _render(){
    const MESES=['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    const y=this.year,m=this.month;
    const offset=(new Date(y,m,1).getDay()+6)%7;
    const daysInM=new Date(y,m+1,0).getDate();
    const todayMs=this._today.getTime(),selMs=this.selected?this.selected.getTime():-1;
    const ty=this._today.getFullYear(),tm=this._today.getMonth();
    const canPrev=y>ty||(y===ty&&m>tm);
    let cells='<span></span>'.repeat(offset);
    for(let d=1;d<=daysInM;d++){
      const ms=new Date(y,m,d).getTime(),past=ms<todayMs;
      const cls=['lc-cal-day',ms===todayMs?'today':'',ms===selMs?'selected':''].filter(Boolean).join(' ');
      cells+=past?`<button class="${cls}" disabled>${d}</button>`:`<button class="${cls}" onclick="window._lcCal.pick(${y},${m},${d})">${d}</button>`;
    }
    this.el.innerHTML=`<div class="lc-cal">
      <div class="lc-cal-head">
        <button class="lc-cal-nav" onclick="window._lcCal.prevMonth()" ${canPrev?'':'disabled'}><i class="fas fa-chevron-left"></i></button>
        <span class="lc-cal-title">${MESES[m]} ${y}</span>
        <button class="lc-cal-nav" onclick="window._lcCal.nextMonth()"><i class="fas fa-chevron-right"></i></button>
      </div>
      <div class="lc-cal-week">${['L','M','X','J','V','S','D'].map(d=>`<span>${d}</span>`).join('')}</div>
      <div class="lc-cal-grid">${cells}</div>
    </div>`;
  }
}

// ── Init ──────────────────────────────────────────────────────────────────
document.addEventListener('keydown',e=>{ if(e.key==='Escape'){ cerrarModal(); cerrarConfirm(); } });
initPredios();

// ── PWA: botón para abrir en navegador cuando se usa como app instalada ──
if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
  const pwaBtn = document.createElement('a');
  pwaBtn.href = location.href;
  pwaBtn.target = '_blank';
  pwaBtn.rel = 'noopener';
  pwaBtn.title = 'Abrir en navegador';
  pwaBtn.style.cssText = 'position:fixed;bottom:calc(var(--bn-h) + 12px);right:14px;z-index:500;background:rgba(22,22,31,.95);color:var(--muted);border:1px solid var(--bdr);border-radius:10px;padding:7px 12px;font-size:0.72rem;font-weight:600;display:flex;align-items:center;gap:6px;text-decoration:none;backdrop-filter:blur(8px);';
  pwaBtn.innerHTML = '<i class="fas fa-external-link-alt"></i> Abrir en navegador';
  document.body.appendChild(pwaBtn);
}
</script>
</body>
</html>
