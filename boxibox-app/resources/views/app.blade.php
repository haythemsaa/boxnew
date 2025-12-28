<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Boxibox">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#4f46e5">
    <meta name="msapplication-tap-highlight" content="no">

    <title inertia>{{ config('app.name', 'Boxibox') }}</title>

    <!-- PWA Manifest -->
    <link rel="manifest" href="/manifest.json">

    <!-- Favicon & App Icons -->
    <link rel="icon" type="image/png" sizes="32x32" href="/images/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/images/icons/icon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/images/icons/icon-180x180.png">
    <link rel="apple-touch-icon" sizes="152x152" href="/images/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="144x144" href="/images/icons/icon-144x144.png">
    <link rel="apple-touch-icon" sizes="120x120" href="/images/icons/icon-120x120.png">
    <link rel="apple-touch-icon" sizes="114x114" href="/images/icons/icon-114x114.png">
    <link rel="apple-touch-icon" sizes="76x76" href="/images/icons/icon-76x76.png">
    <link rel="apple-touch-icon" sizes="72x72" href="/images/icons/icon-72x72.png">
    <link rel="apple-touch-icon" sizes="60x60" href="/images/icons/icon-60x60.png">
    <link rel="apple-touch-icon" sizes="57x57" href="/images/icons/icon-57x57.png">

    <!-- Splash Screens for iOS -->
    <link rel="apple-touch-startup-image" href="/images/splash/splash-640x1136.png" media="(device-width: 320px) and (device-height: 568px)">
    <link rel="apple-touch-startup-image" href="/images/splash/splash-750x1334.png" media="(device-width: 375px) and (device-height: 667px)">
    <link rel="apple-touch-startup-image" href="/images/splash/splash-1242x2208.png" media="(device-width: 414px) and (device-height: 736px)">
    <link rel="apple-touch-startup-image" href="/images/splash/splash-1125x2436.png" media="(device-width: 375px) and (device-height: 812px)">
    <link rel="apple-touch-startup-image" href="/images/splash/splash-1536x2048.png" media="(min-device-width: 768px) and (max-device-width: 1024px)">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead

    <style>
        /* PWA Safe Area Support */
        :root {
            --sat: env(safe-area-inset-top);
            --sar: env(safe-area-inset-right);
            --sab: env(safe-area-inset-bottom);
            --sal: env(safe-area-inset-left);
        }

        /* Prevent pull-to-refresh on mobile */
        body {
            overscroll-behavior-y: contain;
        }

        /* iOS bounce fix */
        html, body {
            height: 100%;
            overflow: hidden;
        }

        #app {
            height: 100%;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        /* Hide scrollbar on mobile */
        @media (max-width: 768px) {
            ::-webkit-scrollbar {
                display: none;
            }
        }
    </style>
</head>
<body class="antialiased">
    @inertia

    <!-- Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/sw.js')
                    .then(function(registration) {
                        console.log('SW registered:', registration.scope);

                        // Check for updates
                        registration.addEventListener('updatefound', () => {
                            const newWorker = registration.installing;
                            newWorker.addEventListener('statechange', () => {
                                if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                    // New content available
                                    if (confirm('Nouvelle version disponible. Recharger la page?')) {
                                        newWorker.postMessage({ action: 'skipWaiting' });
                                        window.location.reload();
                                    }
                                }
                            });
                        });
                    })
                    .catch(function(error) {
                        console.log('SW registration failed:', error);
                    });
            });
        }

        // Push notification permission request
        function requestNotificationPermission() {
            if ('Notification' in window && 'serviceWorker' in navigator) {
                Notification.requestPermission().then(function(permission) {
                    if (permission === 'granted') {
                        console.log('Notification permission granted');
                        subscribeToPush();
                    }
                });
            }
        }

        async function subscribeToPush() {
            try {
                const registration = await navigator.serviceWorker.ready;
                const subscription = await registration.pushManager.subscribe({
                    userVisibleOnly: true,
                    applicationServerKey: urlBase64ToUint8Array('{{ config('services.push.public_key', '') }}')
                });

                // Send subscription to server
                await fetch('/api/push-subscription', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    },
                    body: JSON.stringify(subscription)
                });
            } catch (error) {
                console.log('Push subscription failed:', error);
            }
        }

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

        // Install prompt handling
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            // Show custom install button/banner if needed
            window.dispatchEvent(new CustomEvent('pwa-installable'));
        });

        window.addEventListener('appinstalled', () => {
            console.log('PWA was installed');
            deferredPrompt = null;
        });

        // Expose install function globally
        window.installPWA = async () => {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log('Install prompt outcome:', outcome);
                deferredPrompt = null;
            }
        };
    </script>
</body>
</html>
