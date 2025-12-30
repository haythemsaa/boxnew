<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Security Headers Middleware
 *
 * Adds security-related HTTP headers to all responses to protect against
 * common web vulnerabilities (XSS, clickjacking, MIME sniffing, etc.)
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Generate a nonce for this request (for inline scripts)
        $nonce = base64_encode(Str::random(32));
        $request->attributes->set('csp_nonce', $nonce);

        $response = $next($request);

        // Prevent clickjacking attacks
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME type sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection in older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        // Control referrer information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy (formerly Feature-Policy)
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(self), payment=(self)');

        // Content Security Policy - Stricter version with nonce support
        if (app()->environment('production')) {
            $csp = implode('; ', [
                "default-src 'self'",
                // Use nonce for inline scripts, keep unsafe-eval only for Vue.js runtime compiler
                // Note: unsafe-eval can be removed if using Vue runtime-only build
                "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' https://js.stripe.com https://cdn.jsdelivr.net",
                // Use nonce for inline styles where possible
                "style-src 'self' 'nonce-{$nonce}' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://fonts.bunny.net",
                "img-src 'self' data: https: blob:",
                "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com https://fonts.bunny.net data:",
                "connect-src 'self' https://api.stripe.com wss: https:",
                "frame-src 'self' https://js.stripe.com https://hooks.stripe.com",
                "object-src 'none'",
                "base-uri 'self'",
                "form-action 'self'",
                "frame-ancestors 'self'",
                "upgrade-insecure-requests",
                // Report CSP violations (configure endpoint as needed)
                // "report-uri /api/csp-report",
            ]);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // Strict Transport Security (HTTPS only)
        if ($request->secure() || app()->environment('production')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // Remove server identification headers
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }

    /**
     * Get the CSP nonce for the current request
     * Use this in Blade templates: <script nonce="{{ csp_nonce() }}">
     */
    public static function getNonce(): ?string
    {
        return request()->attributes->get('csp_nonce');
    }
}
