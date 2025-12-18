<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compress API Response Middleware
 *
 * Compresses JSON responses using gzip when the client supports it.
 * This reduces bandwidth usage and improves API response times.
 *
 * Note: In production, consider using nginx/Apache gzip compression
 * instead for better performance. This middleware is a fallback.
 */
class CompressResponse
{
    /**
     * Minimum response size to compress (in bytes).
     * Responses smaller than this won't be compressed as the
     * overhead isn't worth it.
     */
    protected int $minSize = 1024; // 1KB

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Only compress if client accepts gzip
        if (!$this->clientAcceptsGzip($request)) {
            return $response;
        }

        // Only compress JSON responses
        if (!$this->isJsonResponse($response)) {
            return $response;
        }

        // Check if response is large enough to benefit from compression
        $content = $response->getContent();
        if (strlen($content) < $this->minSize) {
            return $response;
        }

        // Don't compress if already compressed
        if ($response->headers->has('Content-Encoding')) {
            return $response;
        }

        // Compress the content
        $compressed = gzencode($content, 6);

        if ($compressed === false) {
            return $response;
        }

        // Only use compressed version if it's actually smaller
        if (strlen($compressed) >= strlen($content)) {
            return $response;
        }

        $response->setContent($compressed);
        $response->headers->set('Content-Encoding', 'gzip');
        $response->headers->set('Content-Length', strlen($compressed));
        $response->headers->set('Vary', 'Accept-Encoding');

        return $response;
    }

    /**
     * Check if the client accepts gzip encoding.
     */
    protected function clientAcceptsGzip(Request $request): bool
    {
        $acceptEncoding = $request->header('Accept-Encoding', '');

        return str_contains($acceptEncoding, 'gzip');
    }

    /**
     * Check if the response is JSON.
     */
    protected function isJsonResponse(Response $response): bool
    {
        if ($response instanceof JsonResponse) {
            return true;
        }

        $contentType = $response->headers->get('Content-Type', '');

        return str_contains($contentType, 'application/json');
    }
}
