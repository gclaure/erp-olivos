const CACHE_NAME = 'olivos-pwa-v6';
const OFFLINE_URL = '/offline.html';

const PRECACHE_ASSETS = [
    OFFLINE_URL,
    '/favicon.ico',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/icon-maskable-192.png',
    '/icons/icon-maskable-512.png',
    '/img/logo-light.png',
    '/img/logo-dark.png'
];

// 1. Install: Precachear recursos esenciales y saltar espera
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(PRECACHE_ASSETS);
        }).then(() => self.skipWaiting())
    );
});

// 2. Activate: Limpiar versiones antiguas de caché y reclamar clientes
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// 3. Fetch: Estrategias selectivas por tipo de petición
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Solo interceptar peticiones GET bajo HTTP/HTTPS
    if (request.method !== 'GET' || !url.protocol.startsWith('http')) {
        return;
    }

    // Bypass para WebSockets, LiveReload de Vite en desarrollo y CSRF/auth endpoints
    if (url.pathname.includes('/reverb') || url.pathname.includes('/ws') || url.port === '5173') {
        return;
    }

    // A. ASSETS ESTÁTICOS DE VITE Y FUENTES (Cache First con revalidación)
    const isViteAsset = url.pathname.startsWith('/build/assets/');
    const isFont = url.hostname.includes('fonts.bunny.net') || 
                   url.hostname.includes('fonts.googleapis.com') || 
                   url.hostname.includes('fonts.gstatic.com');
    const isStaticImage = url.pathname.startsWith('/img/') || 
                          url.pathname.startsWith('/images/') || 
                          url.pathname.includes('favicon');

    if (isViteAsset || isFont || isStaticImage) {
        event.respondWith(
            caches.match(request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(request, responseToCache);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    // Si falla el recurso estático y está en caché, nada que hacer
                });
            })
        );
        return;
    }

    // B. NAVEGACIONES Y PETICIONES DE PÁGINA (Network First con fallback a offline.html)
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(async () => {
                const cache = await caches.open(CACHE_NAME);
                const offlineResponse = await cache.match(OFFLINE_URL);
                return offlineResponse || new Response('Sin conexión a internet', {
                    status: 503,
                    headers: { 'Content-Type': 'text/plain' }
                });
            })
        );
        return;
    }

    // C. OTRAS PETICIONES GET (Network First)
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
