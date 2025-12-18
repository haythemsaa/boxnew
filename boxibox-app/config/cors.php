<?php

/**
 * Parse allowed origins from environment variable
 */
$parseAllowedOrigins = function (): array {
    $origins = env('CORS_ALLOWED_ORIGINS', '');

    // Development defaults
    if (env('APP_ENV') === 'local' || env('APP_DEBUG') === true) {
        $defaultOrigins = [
            'http://localhost:5173',
            'http://localhost:8000',
            'http://127.0.0.1:8000',
            'http://localhost:3000',
            env('APP_URL', 'http://localhost'),
        ];

        if (empty($origins)) {
            return $defaultOrigins;
        }

        return array_unique(array_merge(
            $defaultOrigins,
            array_filter(array_map('trim', explode(',', $origins)))
        ));
    }

    // Production: MUST configure CORS_ALLOWED_ORIGINS
    if (empty($origins)) {
        // Fallback to APP_URL only if no CORS configured
        $appUrl = env('APP_URL', '');
        return $appUrl ? [$appUrl] : [];
    }

    return array_filter(array_map('trim', explode(',', $origins)));
};

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    /*
     * Paths that should respond to CORS preflight requests.
     */
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
        'book/api/*',
        'widget/*',
    ],

    /*
     * Allowed HTTP methods.
     * In production, restrict to only methods you actually use.
     */
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
     * Allowed origins.
     * SECURITY: Never use '*' in production!
     *
     * Configure via CORS_ALLOWED_ORIGINS environment variable:
     * - Development: http://localhost:5173,http://localhost:8000,http://127.0.0.1:8000
     * - Production: https://app.boxibox.com,https://booking.boxibox.com,https://widget.boxibox.com
     */
    'allowed_origins' => $parseAllowedOrigins(),

    /*
     * Allowed origins patterns (regex).
     * Useful for multi-tenant subdomains.
     */
    'allowed_origins_patterns' => array_filter([
        // Allow all subdomains of the configured app domain in production
        env('APP_ENV') === 'production'
            ? 'https?://[a-z0-9\-]+\.' . preg_quote(parse_url(env('APP_URL', 'boxibox.com'), PHP_URL_HOST) ?? 'boxibox.com', '/')
            : null,
    ]),

    /*
     * Allowed request headers.
     * Restrict to headers you actually need.
     */
    'allowed_headers' => [
        'Content-Type',
        'Authorization',
        'X-Requested-With',
        'X-XSRF-TOKEN',
        'X-API-Key',
        'Accept',
        'Accept-Language',
        'Origin',
    ],

    /*
     * Headers exposed to the browser.
     */
    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
        'Content-Disposition',
    ],

    /*
     * Max age for preflight request caching (in seconds).
     */
    'max_age' => 86400, // 24 hours

    /*
     * Whether to allow credentials (cookies, authorization headers).
     */
    'supports_credentials' => true,

];
