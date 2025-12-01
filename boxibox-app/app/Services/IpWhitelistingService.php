<?php

namespace App\Services;

use App\Models\User;

class IpWhitelistingService
{
    /**
     * Add IP to user's whitelist.
     */
    public function addIpToWhitelist(User $user, string $ipAddress): void
    {
        $whitelistedIps = $user->whitelisted_ips ?? [];

        // Add IP if not already in list
        if (!in_array($ipAddress, $whitelistedIps)) {
            $whitelistedIps[] = $ipAddress;
            $user->update(['whitelisted_ips' => $whitelistedIps]);
        }
    }

    /**
     * Remove IP from user's whitelist.
     */
    public function removeIpFromWhitelist(User $user, string $ipAddress): void
    {
        $whitelistedIps = $user->whitelisted_ips ?? [];

        $whitelistedIps = array_filter($whitelistedIps, fn($ip) => $ip !== $ipAddress);
        $user->update(['whitelisted_ips' => array_values($whitelistedIps)]);
    }

    /**
     * Check if IP is whitelisted for user.
     */
    public function isIpWhitelisted(User $user, string $ipAddress): bool
    {
        // If no whitelist is set, all IPs are allowed
        if (empty($user->whitelisted_ips)) {
            return true;
        }

        return in_array($ipAddress, $user->whitelisted_ips);
    }

    /**
     * Enable IP whitelisting for user.
     */
    public function enableWhitelisting(User $user, string $currentIp): void
    {
        $whitelistedIps = [$currentIp];
        $user->update(['whitelisted_ips' => $whitelistedIps]);
    }

    /**
     * Disable IP whitelisting for user.
     */
    public function disableWhitelisting(User $user): void
    {
        $user->update(['whitelisted_ips' => null]);
    }

    /**
     * Get whitelisted IPs for user.
     */
    public function getWhitelistedIps(User $user): array
    {
        return $user->whitelisted_ips ?? [];
    }

    /**
     * Get IP address from request.
     */
    public function getClientIp(): string
    {
        return request()->ip() ?? '0.0.0.0';
    }
}
