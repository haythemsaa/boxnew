<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTwoFactorAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // If user has 2FA enabled and hasn't confirmed it in this session,
        // redirect to 2FA verification page
        if ($user->two_factor_enabled && !session('2fa_verified')) {
            if ($request->path() !== 'auth/2fa/verify' && !str_starts_with($request->path(), 'auth/2fa/')) {
                return redirect()->route('auth.2fa.verify')->with('warning', 'Please verify with your 2FA method.');
            }
        }

        return $next($request);
    }
}
