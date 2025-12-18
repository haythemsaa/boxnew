<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTwoFactorAuth
{
    /**
     * Roles that MUST have 2FA enabled (security requirement).
     */
    protected array $mandatoryRoles = [
        'superadmin',
        'super-admin',
        'admin',
        'tenant-admin',
    ];

    /**
     * Routes that are always allowed (2FA setup/verification flow).
     */
    protected array $allowedRoutes = [
        'auth/2fa',
        'auth/logout',
        'profile/2fa',
        'settings/2fa',
    ];

    /**
     * Handle an incoming request.
     *
     * Security policy:
     * 1. Admins/SuperAdmins MUST have 2FA enabled
     * 2. If 2FA is enabled, must verify on each session
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // Check if route is in allowed list (2FA setup flow)
        if ($this->isAllowedRoute($request->path())) {
            return $next($request);
        }

        // Check if user MUST have 2FA (admin roles)
        if ($this->mustHaveTwoFactor($user)) {
            // Admin without 2FA enabled - force setup
            if (!$user->two_factor_enabled) {
                return $this->redirectToTwoFactorSetup($request);
            }
        }

        // If user has 2FA enabled, verify session
        if ($user->two_factor_enabled && !session('2fa_verified')) {
            return $this->redirectToTwoFactorVerify($request);
        }

        return $next($request);
    }

    /**
     * Check if the current route is allowed without 2FA.
     */
    protected function isAllowedRoute(string $path): bool
    {
        foreach ($this->allowedRoutes as $allowed) {
            if (str_starts_with($path, $allowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if user's role requires mandatory 2FA.
     */
    protected function mustHaveTwoFactor($user): bool
    {
        // Check Spatie roles
        if (method_exists($user, 'hasAnyRole')) {
            if ($user->hasAnyRole($this->mandatoryRoles)) {
                return true;
            }
        }

        // Check legacy role column
        if (!empty($user->role) && in_array(strtolower($user->role), $this->mandatoryRoles)) {
            return true;
        }

        // SuperAdmin check (tenant_id = null usually indicates superadmin)
        if ($user->tenant_id === null && $user->hasRole('superadmin')) {
            return true;
        }

        return false;
    }

    /**
     * Redirect to 2FA setup page.
     */
    protected function redirectToTwoFactorSetup(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => '2FA required',
                'message' => 'Two-factor authentication must be enabled for admin accounts.',
                'redirect' => route('profile.2fa.setup'),
            ], 403);
        }

        return redirect()
            ->route('profile.2fa.setup')
            ->with('warning', 'L\'authentification à deux facteurs est obligatoire pour les comptes administrateurs. Veuillez la configurer maintenant.');
    }

    /**
     * Redirect to 2FA verification page.
     */
    protected function redirectToTwoFactorVerify(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => '2FA verification required',
                'message' => 'Please verify your identity with two-factor authentication.',
                'redirect' => route('auth.2fa.verify'),
            ], 403);
        }

        return redirect()
            ->route('auth.2fa.verify')
            ->with('warning', 'Veuillez confirmer votre identité avec votre code 2FA.');
    }
}
