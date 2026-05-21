const CACHE_NAME = 'spliqo-v2';
const OFFLINE_URL = '/offline.html';

// Install: pre-cache only the offline fallback page
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL))
    );
    self.skipWaiting();
});

// Activate: wipe ALL old caches so stale entries never cause offline flash
self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.map((k) => caches.delete(k)))
                .then(() => caches.open(CACHE_NAME).then((cache) => cache.add(OFFLINE_URL)))
        )
    );
    self.clients.claim();
});

// Fetch: pure network-first — NEVER cache dynamic pages
// Only serve offline.html when the device has no internet at all
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;
    if (!event.request.url.startsWith(self.location.origin)) return;

    // Navigation requests only get the offline fallback when truly offline
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request).catch(async () => {
                // Only show offline page when there is no network connectivity
                if (!navigator.onLine) {
                    return caches.match(OFFLINE_URL);
                }
                // Server is up but returned an error — let the browser show it normally
                return fetch(event.request);
            })
        );
        return;
    }

    // Static assets: network only, no caching
    event.respondWith(fetch(event.request).catch(() => new Response('', { status: 503 })));
});
