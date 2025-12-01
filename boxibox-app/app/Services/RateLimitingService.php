<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitingService
{
    /**
     * Define rate limit for login attempts.
     * 5 attempts per 15 minutes per IP
     */
    public function checkLoginLimit(string $ipAddress): bool
    {
        $key = "login_attempts:{$ipAddress}";
        $maxAttempts = 5;
        $decayMinutes = 15;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        return true;
    }

    /**
     * Increment login attempts for IP.
     */
    public function incrementLoginAttempts(string $ipAddress): void
    {
        $key = "login_attempts:{$ipAddress}";
        $decayMinutes = 15;

        RateLimiter::hit($key, $decayMinutes * 60);
    }

    /**
     * Clear login attempts for IP.
     */
    public function clearLoginAttempts(string $ipAddress): void
    {
        $key = "login_attempts:{$ipAddress}";
        RateLimiter::clear($key);
    }

    /**
     * Get remaining attempts for IP.
     */
    public function getRemainingAttempts(string $ipAddress): int
    {
        $key = "login_attempts:{$ipAddress}";
        $maxAttempts = 5;

        return max(0, $maxAttempts - RateLimiter::attempts($key));
    }

    /**
     * Get seconds until retry is allowed.
     */
    public function getSecondsUntilRetry(string $ipAddress): int
    {
        $key = "login_attempts:{$ipAddress}";

        $availableIn = RateLimiter::availableIn($key);
        return $availableIn > 0 ? $availableIn : 0;
    }

    /**
     * Rate limit API endpoint.
     * Default: 60 requests per minute per IP
     */
    public function checkApiLimit(string $endpoint, ?string $identifier = null): bool
    {
        $ipAddress = $identifier ?? request()->ip();
        $key = "api:{$endpoint}:{$ipAddress}";
        $maxAttempts = 60;
        $decayMinutes = 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        return true;
    }

    /**
     * Rate limit export operations.
     * Default: 5 exports per hour per user
     */
    public function checkExportLimit(int $userId): bool
    {
        $key = "export:{$userId}";
        $maxAttempts = 5;
        $decayMinutes = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        return true;
    }

    /**
     * Rate limit password reset requests.
     * Default: 3 requests per hour per email
     */
    public function checkPasswordResetLimit(string $email): bool
    {
        $key = "password_reset:{$email}";
        $maxAttempts = 3;
        $decayMinutes = 60;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            return false;
        }

        RateLimiter::hit($key, $decayMinutes * 60);
        return true;
    }

    /**
     * Get throttle message.
     */
    public function getThrottleMessage(string $ipAddress): string
    {
        $remainingSeconds = $this->getSecondsUntilRetry($ipAddress);
        $minutes = ceil($remainingSeconds / 60);

        return "Too many login attempts. Please try again in {$minutes} minute" . ($minutes !== 1 ? 's' : '') . '.';
    }
}
