/* La Canchita — Service Worker
 * Estrategia pensada para una app PHP con sesión:
 *  - APIs y peticiones con ?action= → SOLO red (nunca se cachean: datos de sesión).
 *  - Navegaciones (páginas .php) → red primero, si no hay conexión cae a offline.html.
 *  - Assets estáticos (css/js/img/fonts) → cache primero, se revalidan en segundo plano.
 * Subí CACHE_VERSION cada vez que cambies assets para forzar la actualización.
 */
const CACHE_VERSION = 'lacanchita-v1';
const STATIC_CACHE  = `${CACHE_VERSION}-static`;
const RUNTIME_CACHE = `${CACHE_VERSION}-runtime`;

// App shell mínimo que sí queremos disponible offline.
const PRECACHE_URLS = [
  './offline.html',
  './manifest.webmanifest',
  './config/dist/img/pwa/icon-192.png',
  './config/dist/img/pwa/icon-512.png',
  './config/dist/img/loguito_lacanchita.webp',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
      .catch(() => self.skipWaiting()) // si algún asset falla, no bloquea la instalación
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys.filter((k) => !k.startsWith(CACHE_VERSION)).map((k) => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ¿Es un asset estático cacheable?
function isStaticAsset(url) {
  return /\.(?:css|js|png|jpe?g|webp|gif|svg|ico|woff2?|ttf|eot)$/i.test(url.pathname);
}

// ¿Es una petición de datos sensibles? (API o acción dinámica)
function isApiRequest(url) {
  return url.pathname.includes('/api/') || url.searchParams.has('action');
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (req.method !== 'GET') return;                 // POST/PUT/etc → siempre a la red
  const url = new URL(req.url);
  if (url.origin !== self.location.origin) return;  // recursos externos → red directa

  // 1) APIs / datos de sesión: nunca cachear.
  if (isApiRequest(url)) {
    event.respondWith(fetch(req));
    return;
  }

  // 2) Assets estáticos: cache primero + revalidación en segundo plano.
  if (isStaticAsset(url)) {
    event.respondWith(
      caches.open(RUNTIME_CACHE).then((cache) =>
        cache.match(req).then((cached) => {
          const network = fetch(req)
            .then((res) => {
              if (res && res.status === 200) cache.put(req, res.clone());
              return res;
            })
            .catch(() => cached);
          return cached || network;
        })
      )
    );
    return;
  }

  // 3) Navegaciones (páginas): red primero, fallback a offline.html.
  if (req.mode === 'navigate') {
    event.respondWith(
      fetch(req).catch(() => caches.match('./offline.html'))
    );
    return;
  }

  // 4) Resto: intentar red, fallback a cache si existe.
  event.respondWith(fetch(req).catch(() => caches.match(req)));
});
