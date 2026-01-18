// Service Worker per San Vincenzo Calcio PWA
const CACHE_NAME = 'san-vincenzo-calcio-v1';
const OFFLINE_URL = '/offline.html';

// Risorse da mettere in cache all'installazione
const STATIC_CACHE_URLS = [
  '/',
  '/admin',
  '/offline.html',
  '/favicon.ico',
];

// Strategia: Cache First per asset statici, Network First per API
self.addEventListener('install', (event) => {
  console.log('[Service Worker] Installazione...');
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => {
        console.log('[Service Worker] Cache aperta');
        return cache.addAll(STATIC_CACHE_URLS);
      })
      .catch((error) => {
        console.error('[Service Worker] Errore durante la cache:', error);
      })
  );
  self.skipWaiting();
});

// Attivazione: pulizia cache vecchie
self.addEventListener('activate', (event) => {
  console.log('[Service Worker] Attivazione...');
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((cacheName) => cacheName !== CACHE_NAME)
          .map((cacheName) => {
            console.log('[Service Worker] Rimozione cache vecchia:', cacheName);
            return caches.delete(cacheName);
          })
      );
    })
  );
  return self.clients.claim();
});

// Fetch: gestione richieste
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignora richieste non GET
  if (request.method !== 'GET') {
    return;
  }

  // Ignora richieste a domini esterni (API, CDN, ecc.)
  if (url.origin !== location.origin) {
    return;
  }

  event.respondWith(
    caches.match(request)
      .then((cachedResponse) => {
        // Se c'è in cache, restituisci
        if (cachedResponse) {
          return cachedResponse;
        }

        // Altrimenti, fai richiesta di rete
        return fetch(request)
          .then((response) => {
            // Se la risposta è valida, mettila in cache
            if (response && response.status === 200) {
              const responseToCache = response.clone();
              caches.open(CACHE_NAME).then((cache) => {
                cache.put(request, responseToCache);
              });
            }
            return response;
          })
          .catch(() => {
            // Se siamo offline e la richiesta è una pagina, mostra offline.html
            if (request.mode === 'navigate') {
              return caches.match(OFFLINE_URL);
            }
            // Per altre richieste, restituisci una risposta vuota
            return new Response('Offline', {
              status: 503,
              statusText: 'Service Unavailable',
              headers: new Headers({
                'Content-Type': 'text/plain',
              }),
            });
          });
      })
  );
});

// Messaggio per aggiornamento cache
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
});
