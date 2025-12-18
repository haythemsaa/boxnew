<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Validate External API Key Middleware
 *
 * Validates API keys for external integrations with:
 * - Key validation and expiry check
 * - IP whitelist validation
 * - Per-key rate limiting
 * - Usage tracking
 * - Structured logging for security auditing
 */
class ValidateExternalApiKey
{
    /**
     * Rate limit: requests per minute per API key.
     */
    protected int $rateLimit = 60;

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $requiredPermission = null): Response
    {
        $apiKey = $this->extractApiKey($request);

        if (!$apiKey) {
            return $this->unauthorized('API key is required', 'missing_key');
        }

        // Find API key in database (with caching)
        $key = $this->findApiKey($apiKey);

        if (!$key) {
            $this->logFailedAttempt($request, 'invalid_key', $apiKey);
            return $this->unauthorized('Invalid API key', 'invalid_key');
        }

        // Check if key is active
        if (!$key->is_active) {
            $this->logFailedAttempt($request, 'inactive_key', $apiKey, $key->id);
            return $this->unauthorized('API key is inactive', 'inactive_key');
        }

        // Check if key has expired
        if ($key->isExpired()) {
            $this->logFailedAttempt($request, 'expired_key', $apiKey, $key->id);
            return $this->unauthorized('API key has expired', 'expired_key');
        }

        // Validate IP whitelist
        $clientIp = $request->ip();
        if (!$key->isAllowedFrom($clientIp)) {
            $this->logFailedAttempt($request, 'ip_not_whitelisted', $apiKey, $key->id, $clientIp);
            return $this->forbidden('IP address not whitelisted', 'ip_not_whitelisted');
        }

        // Check rate limit
        if (!$this->checkRateLimit($key)) {
            $this->logFailedAttempt($request, 'rate_limit_exceeded', $apiKey, $key->id);
            return $this->tooManyRequests($key);
        }

        // Check required permission
        if ($requiredPermission && !$key->hasPermission($requiredPermission)) {
            $this->logFailedAttempt($request, 'insufficient_permissions', $apiKey, $key->id);
            return $this->forbidden("Missing required permission: {$requiredPermission}", 'insufficient_permissions');
        }

        // Record usage (async to not slow down request)
        $this->recordUsage($key, $request);

        // Add API key info to request for use in controllers
        $request->attributes->set('api_key', $key);
        $request->attributes->set('api_key_tenant_id', $key->tenant_id);

        return $next($request);
    }

    /**
     * Extract API key from request.
     */
    protected function extractApiKey(Request $request): ?string
    {
        // Try Authorization header first (preferred)
        $authHeader = $request->header('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Try X-API-Key header
        if ($apiKey = $request->header('X-API-Key')) {
            return $apiKey;
        }

        // Try query parameter as fallback (less secure, but convenient for testing)
        return $request->query('api_key');
    }

    /**
     * Find API key with caching.
     */
    protected function findApiKey(string $key): ?ApiKey
    {
        // Cache for 5 minutes to reduce DB queries
        $cacheKey = 'api_key:' . hash('sha256', $key);

        return Cache::remember($cacheKey, 300, function () use ($key) {
            return ApiKey::where('key', $key)->first();
        });
    }

    /**
     * Check rate limit for API key.
     */
    protected function checkRateLimit(ApiKey $key): bool
    {
        $cacheKey = "api_rate_limit:{$key->id}";
        $requests = Cache::get($cacheKey, 0);

        if ($requests >= $this->rateLimit) {
            return false;
        }

        Cache::put($cacheKey, $requests + 1, 60);
        return true;
    }

    /**
     * Get remaining rate limit.
     */
    protected function getRemainingRateLimit(ApiKey $key): int
    {
        $cacheKey = "api_rate_limit:{$key->id}";
        $requests = Cache::get($cacheKey, 0);
        return max(0, $this->rateLimit - $requests);
    }

    /**
     * Record API key usage.
     */
    protected function recordUsage(ApiKey $key, Request $request): void
    {
        // Record usage in background to not slow down response
        dispatch(function () use ($key, $request) {
            $key->recordUsage();

            Log::channel('api')->info('API Request', [
                'api_key_id' => $key->id,
                'tenant_id' => $key->tenant_id,
                'endpoint' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
            ]);
        })->afterResponse();
    }

    /**
     * Log failed authentication attempt.
     */
    protected function logFailedAttempt(
        Request $request,
        string $reason,
        string $apiKey,
        int $keyId = null,
        string $ip = null
    ): void {
        Log::channel('security')->warning('API Authentication Failed', [
            'reason' => $reason,
            'api_key_id' => $keyId,
            'api_key_prefix' => substr($apiKey, 0, 12) . '...',
            'ip' => $ip ?? $request->ip(),
            'endpoint' => $request->path(),
            'method' => $request->method(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    /**
     * Return unauthorized response.
     */
    protected function unauthorized(string $message, string $code): Response
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], 401);
    }

    /**
     * Return forbidden response.
     */
    protected function forbidden(string $message, string $code): Response
    {
        return response()->json([
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ], 403);
    }

    /**
     * Return too many requests response.
     */
    protected function tooManyRequests(ApiKey $key): Response
    {
        return response()->json([
            'error' => [
                'code' => 'rate_limit_exceeded',
                'message' => 'Rate limit exceeded. Please wait before making more requests.',
                'retry_after' => 60,
            ],
        ], 429)->withHeaders([
            'X-RateLimit-Limit' => $this->rateLimit,
            'X-RateLimit-Remaining' => 0,
            'Retry-After' => 60,
        ]);
    }
}
