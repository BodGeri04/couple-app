// Bump VERSION whenever you change this file, so phones pick up the new one.
const VERSION = 'v2';
const STATIC_CACHE = `static-${VERSION}`;
const OFFLINE_URL = '/offline.html';

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE).then((cache) => cache.addAll([OFFLINE_URL, '/icons/icon-192.png'])),
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((key) => key !== STATIC_CACHE).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;

    // Page loads: always the network (private content is never cached); offline page as fallback.
    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Hashed build files and icons: cache-first for fast startup.
    if (url.pathname.startsWith('/build/assets/') || url.pathname.startsWith('/icons/')) {
        event.respondWith(
            caches.match(request).then(
                (cached) =>
                    cached ||
                    fetch(request).then((response) => {
                        if (response.ok) {
                            const copy = response.clone();
                            caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
                        }
                        return response;
                    }),
            ),
        );
    }
    // Everything else (page data, snap images, API calls) is never touched.
});

// ---------- Web Push ----------
self.addEventListener('push', (event) => {
    let data = {};

    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { body: event.data ? event.data.text() : '' };
    }

    // iPhones require every push to show a notification, so we always do.
    event.waitUntil(
        self.registration.showNotification(data.title || 'Aplikáció', {
            body: data.body || '',
            icon: '/icons/icon-192.png',
            tag: data.tag || undefined,
            data: { url: data.url || '/dashboard' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const target = new URL(event.notification.data?.url || '/dashboard', self.location.origin).href;

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            const existing = windows.find((client) => client.url.startsWith(self.location.origin));

            if (existing && 'navigate' in existing) {
                return existing
                    .focus()
                    .then(() => existing.navigate(target))
                    .catch(() => self.clients.openWindow(target));
            }

            return self.clients.openWindow(target);
        }),
    );
});