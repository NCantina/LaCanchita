<?php
/**
 * Meta tags PWA + registro del service worker.
 * Incluir dentro del <head> de cada página.
 *
 * Antes de incluirlo, definí la base relativa hacia la raíz del proyecto:
 *   - Páginas en la raíz:            $PWA_BASE = './';
 *   - Páginas en view/maquetaX/:     $PWA_BASE = '../../';
 * Si no se define, asume './'.
 */
$PWA_BASE = $PWA_BASE ?? './';
$b = htmlspecialchars($PWA_BASE, ENT_QUOTES);
?>
<!-- PWA -->
<link rel="manifest" href="<?= $b ?>manifest.webmanifest">
<meta name="theme-color" content="#0d0d0d">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="La Canchita">
<link rel="apple-touch-icon" href="<?= $b ?>config/dist/img/pwa/apple-touch-icon.png">
<link rel="icon" type="image/png" sizes="192x192" href="<?= $b ?>config/dist/img/pwa/icon-192.png">
<script>
(function () {
  var PWA_BASE   = <?= json_encode($PWA_BASE) ?>;
  var PUSH_API   = PWA_BASE + 'api/push_subscribe.php';
  var IS_LOGGED  = <?= json_encode(!empty($_SESSION['usuario_id'])) ?>;

  var swReg = null;

  // ── Registro del service worker ──────────────────────────────────────────
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(PWA_BASE + 'sw.js', { scope: PWA_BASE })
        .then(function (reg) {
          swReg = reg;
          if (IS_LOGGED) initPush(reg);
        })
        .catch(function (err) { console.warn('SW no registrado:', err); });
    });
  }

  // ── Push notifications ───────────────────────────────────────────────────
  function urlBase64ToUint8Array(base64String) {
    var padding = '='.repeat((4 - base64String.length % 4) % 4);
    var base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    var raw     = atob(base64);
    var arr     = new Uint8Array(raw.length);
    for (var i = 0; i < raw.length; i++) arr[i] = raw.charCodeAt(i);
    return arr;
  }

  function initPush(reg) {
    if (!('PushManager' in window)) return;
    if (Notification.permission === 'denied') return;

    // Si ya tiene suscripción activa, la re-enviamos al server por si expiró.
    reg.pushManager.getSubscription().then(function (sub) {
      if (sub) { sendSubToServer(sub); return; }
      // Sin suscripción previa: pedimos permiso y suscribimos.
      if (Notification.permission === 'granted') {
        subscribePush(reg);
      } else {
        // Mostramos el pedido de permisos solo al hacer click (menos intrusivo).
        document.addEventListener('click', function onFirstClick() {
          document.removeEventListener('click', onFirstClick);
          Notification.requestPermission().then(function (perm) {
            if (perm === 'granted') subscribePush(reg);
          });
        }, { once: true });
      }
    });
  }

  function subscribePush(reg) {
    fetch(PUSH_API + '?action=vapid_public')
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        return reg.pushManager.subscribe({
          userVisibleOnly:      true,
          applicationServerKey: urlBase64ToUint8Array(j.key),
        });
      })
      .then(function (sub) { if (sub) sendSubToServer(sub); })
      .catch(function () {});
  }

  function sendSubToServer(sub) {
    var json = sub.toJSON();
    fetch(PUSH_API + '?action=subscribe', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json' },
      body:    JSON.stringify({ endpoint: json.endpoint, keys: json.keys }),
    }).catch(function () {});
  }

  // ── Botón flotante "Instalar app" ────────────────────────────────────────
  var deferredPrompt = null;
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    deferredPrompt = e;
    showInstallButton();
  });

  function showInstallButton() {
    if (document.getElementById('pwaInstallBtn')) return;
    var btn = document.createElement('button');
    btn.id = 'pwaInstallBtn';
    btn.type = 'button';
    btn.innerHTML = '⬇️ Instalar app';
    btn.style.cssText = [
      'position:fixed', 'right:16px', 'bottom:72px', 'z-index:99999',
      'background:#4cd964', 'color:#0d0d0d', 'border:0', 'border-radius:30px',
      'padding:12px 20px', 'font:600 14px/1 Segoe UI,system-ui,Arial,sans-serif',
      'box-shadow:0 6px 20px rgba(0,0,0,.35)', 'cursor:pointer'
    ].join(';');
    btn.addEventListener('click', function () {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      deferredPrompt.userChoice.finally(function () {
        deferredPrompt = null;
        btn.remove();
      });
    });
    document.body.appendChild(btn);
  }

  window.addEventListener('appinstalled', function () {
    var b = document.getElementById('pwaInstallBtn');
    if (b) b.remove();
  });
})();
</script>
<!-- /PWA -->
