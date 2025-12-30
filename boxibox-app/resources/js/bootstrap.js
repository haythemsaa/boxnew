import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Laravel Echo with Reverb WebSocket
 *
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel.
 *
 * Only initializes if VITE_REVERB_APP_KEY is configured.
 */

const reverbAppKey = import.meta.env.VITE_REVERB_APP_KEY;

// Promise that resolves when Echo is ready
window.echoReady = new Promise((resolve) => {
    if (!reverbAppKey) {
        resolve(null);
        return;
    }

    Promise.all([
        import('laravel-echo'),
        import('pusher-js')
    ]).then(([{ default: Echo }, { default: Pusher }]) => {
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbAppKey,
            wsHost: import.meta.env.VITE_REVERB_HOST || window.location.hostname,
            wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
            wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/broadcasting/auth',
        });

        console.log('Echo initialized with Reverb');
        resolve(window.Echo);
    }).catch(err => {
        console.error('Failed to initialize Echo:', err);
        resolve(null);
    });
});
