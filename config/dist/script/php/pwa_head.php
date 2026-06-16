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
  var PWA_BASE = <?= json_encode($PWA_BASE) ?>;

  // Registro del service worker
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register(PWA_BASE + 'sw.js', { scope: PWA_BASE })
        .catch(function (err) { console.warn('SW no registrado:', err); });
    });
  }

  // Botón flotante "Instalar app" (solo aparece si el navegador lo permite)
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
      'position:fixed', 'right:16px', 'bottom:16px', 'z-index:99999',
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

  // Si ya está instalada, ocultar el botón.
  window.addEventListener('appinstalled', function () {
    var b = document.getElementById('pwaInstallBtn');
    if (b) b.remove();
  });
})();
</script>
<!-- /PWA -->
