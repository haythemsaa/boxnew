<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\TwoFactorAuthService;
use App\Services\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class TwoFactorAuthController extends Controller
{
    protected TwoFactorAuthService $twoFactorService;
    protected SecurityAuditService $auditService;

    public function __construct(
        TwoFactorAuthService $twoFactorService,
        SecurityAuditService $auditService
    ) {
        $this->twoFactorService = $twoFactorService;
        $this->auditService = $auditService;
    }

    /**
     * Show 2FA setup page.
     */
    public function setup(): Response
    {
        $user = auth()->user();

        // If 2FA is already enabled, redirect to settings
        if ($user->two_factor_enabled) {
            return redirect()->route('auth.2fa.settings');
        }

        $secret = $this->twoFactorService->generateSecret($user);
        $qrCode = $this->twoFactorService->getQrCode($user, $secret);

        return Inertia::render('Auth/TwoFactorSetup', [
            'qrCode' => $qrCode,
            'secret' => $secret,
        ]);
    }

    /**
     * Confirm 2FA setup with OTP code.
     */
    public function confirm(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $user = auth()->user();

        if ($this->twoFactorService->enable($user, $request->code)) {
            $this->auditService->logSecurityEvent(
                'two_factor_enabled',
                'authentication',
                'success',
                ['method' => 'google_authenticator']
            );

            return redirect()->route('auth.2fa.backup-codes')
                ->with('success', '2FA has been enabled successfully.');
        }

        return back()->with('error', 'Invalid verification code. Please try again.');
    }

    /**
     * Show backup codes page.
     */
    public function backupCodes(): Response
    {
        $user = auth()->user();

        return Inertia::render('Auth/BackupCodes', [
            'backupCodes' => $user->two_factor_backup_codes,
        ]);
    }

    /**
     * Verify 2FA code during login.
     */
    public function verifyLogin(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $user = auth()->user();
        $code = $request->code;

        // Check if it's a regular OTP or backup code
        $isValid = false;

        // Try OTP first (6 digits)
        if (strlen($code) === 6 && ctype_digit($code)) {
            $isValid = $this->twoFactorService->verify($user, $code);
        } else {
            // Try backup code
            $isValid = $this->twoFactorService->verifyBackupCode($user, $code);
        }

        if ($isValid) {
            session(['2fa_verified' => true]);
            $this->auditService->logSecurityEvent(
                'login_2fa_verified',
                'authentication',
                'success'
            );

            return redirect()->intended(route('dashboard'))
                ->with('success', '2FA verification successful.');
        }

        return back()->with('error', 'Invalid code. Please try again.');
    }

    /**
     * Show 2FA settings page.
     */
    public function settings(): Response
    {
        $user = auth()->user();

        return Inertia::render('Auth/TwoFactorSettings', [
            'twoFactorEnabled' => $user->two_factor_enabled,
            'backupCodesCount' => $this->twoFactorService->getRemainingBackupCodes($user),
            'confirmedAt' => $user->two_factor_confirmed_at,
        ]);
    }

    /**
     * Disable 2FA.
     */
    public function disable(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = auth()->user();
        $this->twoFactorService->disable($user);

        $this->auditService->logSecurityEvent(
            'two_factor_disabled',
            'authentication',
            'success'
        );

        return redirect()->route('auth.2fa.settings')
            ->with('success', '2FA has been disabled.');
    }

    /**
     * Regenerate backup codes.
     */
    public function regenerateBackupCodes(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|current_password',
        ]);

        $user = auth()->user();
        $backupCodes = $this->twoFactorService->generateBackupCodes();
        $user->update(['two_factor_backup_codes' => $backupCodes]);

        $this->auditService->logSecurityEvent(
            'backup_codes_regenerated',
            'authentication',
            'success'
        );

        return redirect()->route('auth.2fa.backup-codes')
            ->with('success', 'Backup codes have been regenerated. Store them in a safe place.');
    }
}
