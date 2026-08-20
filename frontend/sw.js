/* =========================================================
   FRESH SCENTS — Service Worker
   Inasaidia tovuti kusakinishwa kama App (PWA) kwenye simu,
   na kuhifadhi baadhi ya faili kwa matumizi ya haraka zaidi.
========================================================= */

const CACHE_NAME = 'fresh-scents-v1';
const CORE_ASSETS = [
  'index.html',
  'css/style.css',
  'js/api.js',
  'js/main.js',
  'images/logo.jpeg',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(CORE_ASSETS)).catch(() => {})
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((names) =>
      Promise.all(names.filter((n) => n !== CACHE_NAME).map((n) => caches.delete(n)))
    )
  );
  self.clients.claim();
});

// Mtandao kwanza (network-first) kwa maombi ya API/data, kache kwa faili tuli (static)
self.addEventListener('fetch', (event) => {
  const url = new URL(event.request.url);

  // Usiingilie maombi ya backend/api - hayo yanahitaji data mpya kila wakati
  if (url.pathname.includes('/backend/')) return;
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then((cached) => {
      const networkFetch = fetch(event.request)
        .then((response) => {
          if (response && response.status === 200) {
            const clone = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, clone));
          }
          return response;
        })
        .catch(() => cached);
      return cached || networkFetch;
    })
  );
});
