<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorAuthService
{
    protected Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * Generate 2FA secret for user.
     */
    public function generateSecret(User $user): string
    {
        $secret = $this->google2fa->generateSecretKey();
        $user->update(['two_factor_secret' => $secret]);

        return $secret;
    }

    /**
     * Get QR code for 2FA setup.
     */
    public function getQrCode(User $user, string $secret): string
    {
        $companyName = config('app.name');
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            $companyName,
            $user->email,
            $secret
        );

        return $qrCodeUrl;
    }

    /**
     * Verify OTP code against secret.
     */
    public function verify(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            return false;
        }

        return $this->google2fa->verifyKey($user->two_factor_secret, $code, 2);
    }

    /**
     * Enable 2FA for user.
     */
    public function enable(User $user, string $code): bool
    {
        if (!$this->verify($user, $code)) {
            return false;
        }

        // Generate backup codes
        $backupCodes = $this->generateBackupCodes();

        $user->update([
            'two_factor_enabled' => true,
            'two_factor_backup_codes' => $backupCodes,
            'two_factor_confirmed_at' => now(),
        ]);

        return true;
    }

    /**
     * Disable 2FA for user.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_backup_codes' => null,
            'two_factor_confirmed_at' => null,
        ]);
    }

    /**
     * Generate backup codes for account recovery.
     */
    public function generateBackupCodes(): array
    {
        $codes = [];

        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)));
        }

        return $codes;
    }

    /**
     * Verify and consume backup code.
     */
    public function verifyBackupCode(User $user, string $code): bool
    {
        if (!$user->two_factor_backup_codes) {
            return false;
        }

        $codes = $user->two_factor_backup_codes;
        $code = strtoupper($code);

        if (!in_array($code, $codes)) {
            return false;
        }

        // Remove used backup code
        $codes = array_filter($codes, fn($c) => $c !== $code);
        $user->update(['two_factor_backup_codes' => array_values($codes)]);

        return true;
    }

    /**
     * Get remaining backup codes count.
     */
    public function getRemainingBackupCodes(User $user): int
    {
        if (!$user->two_factor_backup_codes) {
            return 0;
        }

        return count($user->two_factor_backup_codes);
    }

    /**
     * Record successful login.
     */
    public function recordLogin(User $user, ?string $ipAddress = null): void
    {
        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ipAddress ?? request()->ip(),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ]);
    }

    /**
     * Record failed login attempt.
     */
    public function recordFailedLogin(User $user): void
    {
        $attempts = $user->failed_login_attempts + 1;

        $data = [
            'failed_login_attempts' => $attempts,
        ];

        // Lock account after 5 failed attempts for 15 minutes
        if ($attempts >= 5) {
            $data['locked_until'] = now()->addMinutes(15);
        }

        $user->update($data);
    }

    /**
     * Check if account is locked.
     */
    public function isAccountLocked(User $user): bool
    {
        if (!$user->locked_until) {
            return false;
        }

        if ($user->locked_until->isPast()) {
            $user->update(['locked_until' => null, 'failed_login_attempts' => 0]);
            return false;
        }

        return true;
    }

    /**
     * Get minutes until account unlock.
     */
    public function getMinutesUntilUnlock(User $user): int
    {
        if (!$user->locked_until) {
            return 0;
        }

        return (int) $user->locked_until->diffInMinutes(now());
    }
}
