<?php

namespace App\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Mail\Message;

class EmailService
{
    /**
     * Send email notification (legacy signature for backward compatibility).
     */
    public function send(string|array $to, string $subject = '', string $view = '', array $data = []): bool
    {
        // Handle new array-based calling convention from CRMService
        if (is_array($to)) {
            return $this->sendFromArray($to);
        }

        try {
            // Verify email configuration
            if (!$this->isConfigured()) {
                Log::warning('Email service not configured');
                return false;
            }

            // Check if view exists, otherwise use raw HTML
            $viewExists = view()->exists($view);

            Mail::send([], [], function (Message $message) use ($to, $subject, $view, $data, $viewExists) {
                $message->to($to)
                    ->subject($subject);

                if ($viewExists) {
                    $html = view($view, $data)->render();
                } else {
                    // Use body from data or generate simple HTML
                    $html = $this->generateHtmlEmail($data['body'] ?? '', $subject);
                }

                $message->html($html);
            });

            Log::info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
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
     * Send email from array config (used by CRMService auto follow-up).
     */
    protected function sendFromArray(array $config): bool
    {
        $to = $config['to'] ?? null;
        $subject = $config['subject'] ?? 'Notification';
        $body = $config['body'] ?? '';
        $tenantId = $config['tenant_id'] ?? null;

        if (!$to) {
            Log::warning('Email send failed: no recipient');
            return false;
        }

        try {
            if (!$this->isConfigured()) {
                Log::warning('Email service not configured', ['to' => $to]);
                return false;
            }

            $html = $this->generateHtmlEmail($body, $subject);

            Mail::send([], [], function (Message $message) use ($to, $subject, $html) {
                $message->to($to)
                    ->subject($subject)
                    ->html($html);
            });

            // Log the sent email for tracking
            $this->logSentEmail($config);

            Log::info('Email sent successfully', [
                'to' => $to,
                'subject' => $subject,
                'type' => $config['type'] ?? 'general',
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
     * Generate a simple HTML email template.
     */
    protected function generateHtmlEmail(string $body, string $subject): string
    {
        $body = nl2br(e($body));

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$subject}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #2563eb; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background: #f9fafb; padding: 30px; border: 1px solid #e5e7eb; border-top: none; }
        .footer { background: #f3f4f6; padding: 15px; text-align: center; font-size: 12px; color: #6b7280; border-radius: 0 0 8px 8px; }
        a { color: #2563eb; }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0; font-size: 24px;">BoxiBox</h1>
    </div>
    <div class="content">
        {$body}
    </div>
    <div class="footer">
        <p>Cet email a été envoyé automatiquement par BoxiBox.</p>
        <p>© 2024 BoxiBox - Tous droits réservés</p>
    </div>
</body>
</html>
HTML;
    }

    /**
     * Log sent email for tracking and analytics.
     */
    protected function logSentEmail(array $config): void
    {
        try {
            // Create email log if table exists
            if (\Illuminate\Support\Facades\Schema::hasTable('email_logs')) {
                \Illuminate\Support\Facades\DB::table('email_logs')->insert([
                    'tenant_id' => $config['tenant_id'] ?? null,
                    'to_email' => $config['to'],
                    'subject' => $config['subject'] ?? '',
                    'type' => $config['type'] ?? 'general',
                    'lead_id' => $config['lead_id'] ?? null,
                    'prospect_id' => $config['prospect_id'] ?? null,
                    'customer_id' => $config['customer_id'] ?? null,
                    'status' => 'sent',
                    'sent_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            // Silent fail for logging
            Log::debug('Email log insert failed', ['error' => $e->getMessage()]);
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
