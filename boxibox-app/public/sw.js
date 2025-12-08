// Boxibox Service Worker
const CACHE_NAME = 'boxibox-v2';
const OFFLINE_URL = '/offline.html';

// Assets to cache on install
const PRECACHE_ASSETS = [
    '/',
    '/mobile',
    '/offline.html',
    '/manifest.json',
    '/build/assets/app.css',
    '/build/assets/app.js',
];

// Install event - precache essential assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Precaching assets...');
                return cache.addAll(PRECACHE_ASSETS);
            })
            .then(() => {
                self.skipWaiting();
            })
    );
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
    console.log('[SW] Activating service worker...');
    event.waitUntil(
        caches.keys()
            .then((cacheNames) => {
                return Promise.all(
                    cacheNames
                        .filter((name) => name !== CACHE_NAME)
                        .map((name) => {
                            console.log('[SW] Deleting old cache:', name);
                            return caches.delete(name);
                        })
                );
            })
            .then(() => {
                self.clients.claim();
            })
    );
});

// Fetch event - network first, fallback to cache
self.addEventListener('fetch', (event) => {
    const { request } = event;
    const url = new URL(request.url);

    // Skip non-GET requests
    if (request.method !== 'GET') {
        return;
    }

    // Skip cross-origin requests
    if (url.origin !== location.origin) {
        return;
    }

    // API requests - network only with offline handling
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/mobile/')) {
        event.respondWith(
            fetch(request)
                .catch(() => {
                    // Return cached version for navigation requests
                    if (request.mode === 'navigate') {
                        return caches.match(OFFLINE_URL);
                    }
                    return new Response(
                        JSON.stringify({ error: 'Offline', message: 'Vous etes hors ligne' }),
                        { headers: { 'Content-Type': 'application/json' } }
                    );
                })
        );
        return;
    }

    // Static assets - cache first
    if (url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/images/') ||
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js')) {
        event.respondWith(
            caches.match(request)
                .then((cachedResponse) => {
                    if (cachedResponse) {
                        return cachedResponse;
                    }
                    return fetch(request).then((response) => {
                        if (response.ok) {
                            const responseClone = response.clone();
                            caches.open(CACHE_NAME).then((cache) => {
                                cache.put(request, responseClone);
                            });
                        }
                        return response;
                    });
                })
        );
        return;
    }

    // Navigation requests - network first
    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request)
                .then((response) => {
                    // Cache successful navigation responses
                    if (response.ok && url.pathname.startsWith('/mobile')) {
                        const responseClone = response.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(request, responseClone);
                        });
                    }
                    return response;
                })
                .catch(() => {
                    return caches.match(request)
                        .then((cachedResponse) => {
                            if (cachedResponse) {
                                return cachedResponse;
                            }
                            return caches.match(OFFLINE_URL);
                        });
                })
        );
        return;
    }

    // Default - network first
    event.respondWith(
        fetch(request)
            .then((response) => {
                if (response.ok) {
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then((cache) => {
                        cache.put(request, responseClone);
                    });
                }
                return response;
            })
            .catch(() => {
                return caches.match(request);
            })
    );
});

// Push notification event
self.addEventListener('push', (event) => {
    console.log('[SW] Push received:', event);

    let data = { title: 'Boxibox', body: 'Nouvelle notification', icon: '/images/icons/icon-192x192.png' };

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data.body = event.data.text();
        }
    }

    const options = {
        body: data.body,
        icon: data.icon || '/images/icons/icon-192x192.png',
        badge: '/images/icons/badge-72x72.png',
        vibrate: [100, 50, 100],
        data: {
            dateOfArrival: Date.now(),
            url: data.url || '/mobile',
        },
        actions: data.actions || [
            { action: 'open', title: 'Ouvrir' },
            { action: 'close', title: 'Fermer' },
        ],
    };

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event);

    event.notification.close();

    if (event.action === 'close') {
        return;
    }

    const urlToOpen = event.notification.data?.url || '/mobile';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if app is already open
                for (const client of clientList) {
                    if (client.url.includes('/mobile') && 'focus' in client) {
                        client.navigate(urlToOpen);
                        return client.focus();
                    }
                }
                // Open new window
                if (clients.openWindow) {
                    return clients.openWindow(urlToOpen);
                }
            })
    );
});

// Background sync event
self.addEventListener('sync', (event) => {
    console.log('[SW] Background sync:', event.tag);

    if (event.tag === 'sync-payments') {
        event.waitUntil(syncPayments());
    }
});

async function syncPayments() {
    // Sync pending payments when back online
    const cache = await caches.open(CACHE_NAME);
    const pendingPayments = await cache.match('/pending-payments');

    if (pendingPayments) {
        const payments = await pendingPayments.json();
        for (const payment of payments) {
            try {
                await fetch('/mobile/pay', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payment),
                });
            } catch (error) {
                console.error('[SW] Failed to sync payment:', error);
            }
        }
        await cache.delete('/pending-payments');
    }
}

// Message event - communication with main app
self.addEventListener('message', (event) => {
    console.log('[SW] Message received:', event.data);

    if (event.data.action === 'skipWaiting') {
        self.skipWaiting();
    }
});

console.log('[SW] Service worker loaded');
