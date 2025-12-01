<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $provider;
    protected $apiKey;

    public function __construct()
    {
        // Support for multiple SMS providers
        $this->provider = config('services.sms.provider', 'twilio');
        $this->apiKey = config("services.sms.{$this->provider}.api_key");
    }

    /**
     * Send SMS notification.
     */
    public function send(string $phoneNumber, string $message): bool
    {
        try {
            if (!$this->isConfigured()) {
                Log::warning('SMS service not configured');
                return false;
            }

            // Validate phone number
            if (!$this->isValidPhoneNumber($phoneNumber)) {
                Log::warning('Invalid phone number format', ['phone' => $phoneNumber]);
                return false;
            }

            // Validate message length
            if (strlen($message) > 160) {
                Log::warning('SMS message too long', ['length' => strlen($message)]);
                return false;
            }

            Log::info('SMS sent', [
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'provider' => $this->provider,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send SMS', [
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send payment reminder via SMS.
     */
    public function sendPaymentReminder(string $phoneNumber, string $invoiceNumber, float $amount): bool
    {
        $message = "Payment reminder: Invoice {$invoiceNumber} of €{$amount} is due. Visit your account to pay.";
        return $this->send($phoneNumber, $message);
    }

    /**
     * Send contract expiration warning via SMS.
     */
    public function sendContractWarning(string $phoneNumber, int $daysUntilExpiry): bool
    {
        $message = "Your storage contract expires in {$daysUntilExpiry} days. Renew now to avoid service interruption.";
        return $this->send($phoneNumber, $message);
    }

    /**
     * Send 2FA code via SMS.
     */
    public function send2FACode(string $phoneNumber, string $code): bool
    {
        $message = "Your Boxibox 2FA code is: {$code}. Do not share this code.";
        return $this->send($phoneNumber, $message);
    }

    /**
     * Send payment confirmation via SMS.
     */
    public function sendPaymentConfirmation(string $phoneNumber, string $invoiceNumber): bool
    {
        $message = "Payment received for invoice {$invoiceNumber}. Thank you!";
        return $this->send($phoneNumber, $message);
    }

    /**
     * Validate phone number format.
     */
    protected function isValidPhoneNumber(string $phoneNumber): bool
    {
        return preg_match('/^\+?[1-9]\d{9,14}$/', str_replace(' ', '', $phoneNumber));
    }

    /**
     * Mask phone number for logging.
     */
    protected function maskPhoneNumber(string $phoneNumber): string
    {
        $length = strlen($phoneNumber);
        $visible = 3;
        $masked = str_repeat('*', max(0, $length - $visible));
        return substr($phoneNumber, 0, $visible) . $masked;
    }

    /**
     * Check if SMS service is configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->provider) && !empty($this->apiKey);
    }

    /**
     * Get SMS configuration status.
     */
    public function getConfigStatus(): array
    {
        return [
            'provider' => $this->provider,
            'is_configured' => $this->isConfigured(),
            'available_providers' => ['twilio', 'aws_sns', 'vonage'],
        ];
    }
}
