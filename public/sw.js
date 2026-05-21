// Service worker — offline shell for /admin/quick-add
const CACHE = 'joreption-shell-v1';
const SHELL_URLS = [
    '/images/logo.jpg',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE).then((c) => c.addAll(SHELL_URLS)).then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        ).then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // Network-first for HTML / Livewire / API
    if (request.headers.get('accept')?.includes('text/html')
        || url.pathname.startsWith('/livewire/')
        || url.pathname.startsWith('/api/')) {
        event.respondWith(
            fetch(request).catch(() => caches.match(request))
        );
        return;
    }

    // Cache-first for assets (images, css, js, manifest, fonts)
    if (/\.(css|js|jpg|jpeg|png|gif|webp|svg|woff2?|ttf|ico|webmanifest)$/i.test(url.pathname)
        || url.hostname.endsWith('bunny.net')) {
        event.respondWith(
            caches.match(request).then((cached) => {
                const fetchPromise = fetch(request).then((res) => {
                    if (res.ok && url.origin === self.location.origin) {
                        const clone = res.clone();
                        caches.open(CACHE).then((c) => c.put(request, clone));
                    }
                    return res;
                }).catch(() => cached);
                return cached || fetchPromise;
            })
        );
    }
});
