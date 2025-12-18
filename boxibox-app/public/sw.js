// Boxibox Service Worker
const CACHE_NAME = 'boxibox-v4';
const OFFLINE_URL = '/offline.html';

// Assets to cache on install - only static files that don't change
const PRECACHE_ASSETS = [
    '/offline.html',
    '/manifest.json',
];

// Install event - precache essential assets
self.addEventListener('install', (event) => {
    console.log('[SW] Installing service worker...');
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => {
                console.log('[SW] Precaching assets...');
                // Use addAll with error handling for each asset
                return Promise.allSettled(
                    PRECACHE_ASSETS.map(url =>
                        cache.add(url).catch(err => {
                            console.warn('[SW] Failed to cache:', url, err);
                        })
                    )
                );
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
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/mobile/') || url.pathname.startsWith('/portal/')) {
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
                    // Cache successful navigation responses for portal and mobile
                    if (response.ok && (url.pathname.startsWith('/mobile') || url.pathname.startsWith('/portal'))) {
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

    // Build notification options based on type
    const options = {
        body: data.body,
        icon: data.icon || '/images/icons/icon-192x192.png',
        badge: data.badge || '/images/icons/badge-72x72.png',
        vibrate: getVibrationPattern(data.type),
        tag: data.tag || 'boxibox-notification',
        renotify: true,
        requireInteraction: data.requireInteraction || false,
        silent: data.silent || false,
        data: {
            dateOfArrival: Date.now(),
            url: data.url || '/tenant/dashboard',
            type: data.type || 'general',
            notificationId: data.notificationId,
        },
        actions: data.actions || getDefaultActions(data.type),
    };

    // Add image if provided
    if (data.image) {
        options.image = data.image;
    }

    event.waitUntil(
        self.registration.showNotification(data.title, options)
    );
});

// Get vibration pattern based on notification type
function getVibrationPattern(type) {
    const patterns = {
        alert: [200, 100, 200, 100, 200],
        payment: [100, 50, 100],
        contract: [100, 50, 100],
        iot: [300, 100, 300],
        system: [100],
        marketing: [50],
    };
    return patterns[type] || [100, 50, 100];
}

// Get default actions based on notification type
function getDefaultActions(type) {
    const actionSets = {
        payment: [
            { action: 'view', title: 'Voir facture' },
            { action: 'dismiss', title: 'Plus tard' },
        ],
        contract: [
            { action: 'view', title: 'Voir contrat' },
            { action: 'dismiss', title: 'Ignorer' },
        ],
        booking: [
            { action: 'view', title: 'Voir reservation' },
            { action: 'confirm', title: 'Confirmer' },
        ],
        iot: [
            { action: 'view', title: 'Voir alerte' },
            { action: 'acknowledge', title: 'Vu' },
        ],
    };
    return actionSets[type] || [
        { action: 'open', title: 'Ouvrir' },
        { action: 'close', title: 'Fermer' },
    ];
}

// Notification click event
self.addEventListener('notificationclick', (event) => {
    console.log('[SW] Notification clicked:', event);

    event.notification.close();

    const action = event.action;
    const notificationData = event.notification.data || {};

    // Handle dismiss/close actions
    if (action === 'close' || action === 'dismiss') {
        return;
    }

    // Track click for analytics
    if (notificationData.notificationId) {
        trackNotificationClick(notificationData.notificationId);
    }

    // Determine URL based on action
    let urlToOpen = notificationData.url || '/tenant/dashboard';

    // Handle specific actions
    if (action === 'view' || action === 'open') {
        urlToOpen = notificationData.url;
    } else if (action === 'confirm' && notificationData.type === 'booking') {
        urlToOpen = notificationData.url + '?action=confirm';
    } else if (action === 'acknowledge' && notificationData.type === 'iot') {
        urlToOpen = '/tenant/iot/alerts?acknowledge=' + notificationData.notificationId;
    }

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true })
            .then((clientList) => {
                // Check if app is already open
                for (const client of clientList) {
                    if ((client.url.includes('/tenant') || client.url.includes('/mobile')) && 'focus' in client) {
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

// Track notification click for analytics
async function trackNotificationClick(notificationId) {
    try {
        await fetch('/api/v1/push/track-click', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ notification_id: notificationId }),
        });
    } catch (e) {
        console.log('[SW] Failed to track click:', e);
    }
}

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
