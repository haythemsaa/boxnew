<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\IpWhitelistingService;

class EnforceIpWhitelist
{
    protected IpWhitelistingService $ipWhitelistService;

    public function __construct(IpWhitelistingService $ipWhitelistService)
    {
        $this->ipWhitelistService = $ipWhitelistService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->whitelisted_ips) {
            return $next($request);
        }

        $currentIp = $this->ipWhitelistService->getClientIp();

        // Check if current IP is whitelisted
        if (!$this->ipWhitelistService->isIpWhitelisted($user, $currentIp)) {
            return response('Unauthorized IP address', 403);
        }

        return $next($request);
    }
}
