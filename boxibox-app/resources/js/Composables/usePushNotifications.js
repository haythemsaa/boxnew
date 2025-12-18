import { ref, computed, onMounted } from 'vue';

// Push notification state
const isSupported = ref(false);
const isSubscribed = ref(false);
const permission = ref('default');
const subscription = ref(null);
const loading = ref(false);
const error = ref(null);

// VAPID public key (will be fetched from server)
let vapidPublicKey = null;

/**
 * Composable for managing Push Notifications
 */
export function usePushNotifications() {
    // Check if push notifications are supported
    const checkSupport = () => {
        isSupported.value = 'serviceWorker' in navigator &&
                           'PushManager' in window &&
                           'Notification' in window;
        return isSupported.value;
    };

    // Get current permission status
    const checkPermission = () => {
        if ('Notification' in window) {
            permission.value = Notification.permission;
        }
        return permission.value;
    };

    // Check if user is already subscribed
    const checkSubscription = async () => {
        if (!isSupported.value) return false;

        try {
            const registration = await navigator.serviceWorker.ready;
            const existingSubscription = await registration.pushManager.getSubscription();

            if (existingSubscription) {
                subscription.value = existingSubscription;
                isSubscribed.value = true;
                return true;
            }
        } catch (e) {
            console.error('Error checking subscription:', e);
        }

        isSubscribed.value = false;
        return false;
    };

    // Fetch VAPID public key from server
    const fetchVapidKey = async () => {
        if (vapidPublicKey) return vapidPublicKey;

        try {
            const response = await fetch('/api/v1/push/public-key');
            const data = await response.json();
            vapidPublicKey = data.publicKey;
            return vapidPublicKey;
        } catch (e) {
            console.error('Error fetching VAPID key:', e);
            return null;
        }
    };

    // Request notification permission
    const requestPermission = async () => {
        if (!isSupported.value) {
            error.value = 'Les notifications ne sont pas supportees sur ce navigateur';
            return false;
        }

        try {
            const result = await Notification.requestPermission();
            permission.value = result;
            return result === 'granted';
        } catch (e) {
            console.error('Error requesting permission:', e);
            error.value = 'Erreur lors de la demande de permission';
            return false;
        }
    };

    // Subscribe to push notifications
    const subscribe = async () => {
        if (!isSupported.value) {
            error.value = 'Les notifications ne sont pas supportees';
            return false;
        }

        loading.value = true;
        error.value = null;

        try {
            // Request permission if needed
            if (permission.value !== 'granted') {
                const granted = await requestPermission();
                if (!granted) {
                    error.value = 'Permission refusee';
                    loading.value = false;
                    return false;
                }
            }

            // Get VAPID key
            const publicKey = await fetchVapidKey();
            if (!publicKey) {
                error.value = 'Cle VAPID non disponible';
                loading.value = false;
                return false;
            }

            // Get service worker registration
            const registration = await navigator.serviceWorker.ready;

            // Subscribe to push
            const pushSubscription = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(publicKey),
            });

            subscription.value = pushSubscription;

            // Send subscription to server
            const response = await fetch('/api/v1/push/subscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({
                    endpoint: pushSubscription.endpoint,
                    keys: {
                        p256dh: arrayBufferToBase64(pushSubscription.getKey('p256dh')),
                        auth: arrayBufferToBase64(pushSubscription.getKey('auth')),
                    },
                    contentEncoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
                    deviceType: getDeviceType(),
                    browser: getBrowserName(),
                    os: getOSName(),
                }),
            });

            if (!response.ok) {
                throw new Error('Erreur serveur');
            }

            isSubscribed.value = true;
            loading.value = false;
            return true;
        } catch (e) {
            console.error('Error subscribing:', e);
            error.value = 'Erreur lors de l\'abonnement';
            loading.value = false;
            return false;
        }
    };

    // Unsubscribe from push notifications
    const unsubscribe = async () => {
        if (!subscription.value) return false;

        loading.value = true;
        error.value = null;

        try {
            // Unsubscribe from push manager
            await subscription.value.unsubscribe();

            // Notify server
            await fetch('/api/v1/push/unsubscribe', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({
                    endpoint: subscription.value.endpoint,
                }),
            });

            subscription.value = null;
            isSubscribed.value = false;
            loading.value = false;
            return true;
        } catch (e) {
            console.error('Error unsubscribing:', e);
            error.value = 'Erreur lors du desabonnement';
            loading.value = false;
            return false;
        }
    };

    // Send test notification
    const sendTest = async () => {
        try {
            const response = await fetch('/api/v1/push/test', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
            });

            return response.ok;
        } catch (e) {
            console.error('Error sending test:', e);
            return false;
        }
    };

    // Initialize on mount
    const initialize = async () => {
        checkSupport();
        checkPermission();
        if (isSupported.value) {
            await checkSubscription();
        }
    };

    // Computed states
    const canSubscribe = computed(() => {
        return isSupported.value && permission.value !== 'denied' && !isSubscribed.value;
    });

    const isDenied = computed(() => {
        return permission.value === 'denied';
    });

    return {
        // State
        isSupported,
        isSubscribed,
        permission,
        subscription,
        loading,
        error,

        // Computed
        canSubscribe,
        isDenied,

        // Methods
        initialize,
        checkSupport,
        checkPermission,
        checkSubscription,
        requestPermission,
        subscribe,
        unsubscribe,
        sendTest,
    };
}

// Helper functions
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - base64String.length % 4) % 4);
    const base64 = (base64String + padding)
        .replace(/-/g, '+')
        .replace(/_/g, '/');

    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);

    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

function arrayBufferToBase64(buffer) {
    const bytes = new Uint8Array(buffer);
    let binary = '';
    for (let i = 0; i < bytes.byteLength; i++) {
        binary += String.fromCharCode(bytes[i]);
    }
    return window.btoa(binary);
}

function getDeviceType() {
    const ua = navigator.userAgent;
    if (/tablet|ipad|playbook|silk/i.test(ua)) return 'tablet';
    if (/Mobile|Android|iP(hone|od)|IEMobile|BlackBerry|Kindle|Silk-Accelerated/i.test(ua)) return 'mobile';
    return 'web';
}

function getBrowserName() {
    const ua = navigator.userAgent;
    if (ua.includes('Firefox')) return 'Firefox';
    if (ua.includes('Chrome')) return 'Chrome';
    if (ua.includes('Safari')) return 'Safari';
    if (ua.includes('Edge')) return 'Edge';
    if (ua.includes('Opera')) return 'Opera';
    return 'Unknown';
}

function getOSName() {
    const ua = navigator.userAgent;
    if (ua.includes('Windows')) return 'Windows';
    if (ua.includes('Mac')) return 'macOS';
    if (ua.includes('Linux')) return 'Linux';
    if (ua.includes('Android')) return 'Android';
    if (ua.includes('iOS') || ua.includes('iPhone') || ua.includes('iPad')) return 'iOS';
    return 'Unknown';
}
