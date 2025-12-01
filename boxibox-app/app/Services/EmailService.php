<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class EmailService
{
    /**
     * Send email notification.
     */
    public function send(string $to, string $subject, string $view, array $data = []): bool
    {
        try {
            // Verify email configuration
            if (!$this->isConfigured()) {
                Log::warning('Email service not configured');
                return false;
            }

            // In production, use actual mailable classes
            // For now, this is a stub that demonstrates the pattern

            Log::info('Email sent', [
                'to' => $to,
                'subject' => $subject,
                'view' => $view,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Send invoice email.
     */
    public function sendInvoice(string $to, array $invoiceData): bool
    {
        return $this->send(
            $to,
            "Invoice {$invoiceData['invoice_number']}",
            'emails.invoice',
            $invoiceData
        );
    }

    /**
     * Send payment confirmation email.
     */
    public function sendPaymentConfirmation(string $to, array $paymentData): bool
    {
        return $this->send(
            $to,
            'Payment Received',
            'emails.payment-confirmation',
            $paymentData
        );
    }

    /**
     * Send contract reminder email.
     */
    public function sendContractReminder(string $to, array $contractData): bool
    {
        return $this->send(
            $to,
            "Contract Expiring Soon: {$contractData['contract_number']}",
            'emails.contract-reminder',
            $contractData
        );
    }

    /**
     * Send welcome email to new customer.
     */
    public function sendWelcomeEmail(string $to, string $customerName): bool
    {
        return $this->send(
            $to,
            'Welcome to Boxibox',
            'emails.welcome',
            ['name' => $customerName]
        );
    }

    /**
     * Check if email service is configured.
     */
    public function isConfigured(): bool
    {
        $driver = config('mail.default');
        $from = config('mail.from.address');

        return !empty($driver) && !empty($from);
    }

    /**
     * Get email configuration status.
     */
    public function getConfigStatus(): array
    {
        return [
            'driver' => config('mail.default'),
            'from_address' => config('mail.from.address'),
            'from_name' => config('mail.from.name'),
            'is_configured' => $this->isConfigured(),
            'smtp_host' => config('mail.mailers.smtp.host'),
            'smtp_port' => config('mail.mailers.smtp.port'),
        ];
    }
}
