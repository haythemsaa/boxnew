<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Lead;
use App\Models\Invoice;
use App\Models\SMSCampaign;
use App\Models\SMSLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SMSService
{
    protected string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', env('SMS_PROVIDER', 'twilio'));
        $this->config = $this->getProviderConfig();
    }

    /**
     * Send SMS via configured provider
     */
    public function send(string $to, string $message, array $options = []): array
    {
        // Normalize phone number
        $to = $this->normalizePhoneNumber($to);

        // Check if SMS is enabled
        if (!config('services.sms.enabled', false)) {
            Log::info('SMS disabled, would send:', ['to' => $to, 'message' => $message]);
            return ['status' => 'disabled', 'sid' => null];
        }

        // Send via provider
        try {
            $result = match ($this->provider) {
                'twilio' => $this->sendViaTwilio($to, $message, $options),
                'vonage' => $this->sendViaVonage($to, $message, $options),
                'aws-sns' => $this->sendViaAWS($to, $message, $options),
                default => $this->sendViaLog($to, $message, $options),
            };

            // Log SMS
            $this->logSMS($to, $message, $result['status'], $result['sid'] ?? null, $options);

            return $result;

        } catch (\Exception $e) {
            Log::error('SMS Send Error', [
                'provider' => $this->provider,
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    /**
     * Send via Twilio
     */
    protected function sendViaTwilio(string $to, string $message, array $options): array
    {
        $accountSid = $this->config['account_sid'] ?? '';
        $authToken = $this->config['auth_token'] ?? '';
        $from = $this->config['from'] ?? '';

        if (!$accountSid || !$authToken || !$from) {
            throw new \Exception('Twilio not configured');
        }

        $response = Http::asForm()
            ->withBasicAuth($accountSid, $authToken)
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $from,
                'To' => $to,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => 'sent',
                'sid' => $data['sid'] ?? null,
                'provider' => 'twilio',
            ];
        }

        throw new \Exception('Twilio API error: ' . $response->body());
    }

    /**
     * Send via Vonage (Nexmo)
     */
    protected function sendViaVonage(string $to, string $message, array $options): array
    {
        $apiKey = $this->config['api_key'] ?? '';
        $apiSecret = $this->config['api_secret'] ?? '';
        $from = $this->config['from'] ?? '';

        if (!$apiKey || !$apiSecret || !$from) {
            throw new \Exception('Vonage not configured');
        }

        $response = Http::asForm()->post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $from,
            'to' => $to,
            'text' => $message,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'status' => $data['messages'][0]['status'] == '0' ? 'sent' : 'failed',
                'sid' => $data['messages'][0]['message-id'] ?? null,
                'provider' => 'vonage',
            ];
        }

        throw new \Exception('Vonage API error: ' . $response->body());
    }

    /**
     * Send via AWS SNS
     */
    protected function sendViaAWS(string $to, string $message, array $options): array
    {
        // AWS SNS integration would go here
        // For now, fallback to log
        return $this->sendViaLog($to, $message, $options);
    }

    /**
     * Fallback: Log SMS instead of sending
     */
    protected function sendViaLog(string $to, string $message, array $options): array
    {
        Log::info('SMS (Log Mode)', [
            'to' => $to,
            'message' => $message,
            'options' => $options,
        ]);

        return ['status' => 'logged', 'sid' => uniqid('log_')];
    }

    /**
     * Send payment reminder SMS
     */
    public function sendPaymentReminder(Invoice $invoice, int $daysOverdue = 0): array
    {
        $customer = $invoice->customer;

        if (!$customer->phone) {
            return ['status' => 'no_phone'];
        }

        $message = match (true) {
            $daysOverdue === 0 => "Boxibox: Votre facture #{$invoice->number} de {$invoice->total_amount}€ arrive à échéance demain. Payez en ligne: " . route('portal.invoices.show', $invoice->id),
            $daysOverdue <= 3 => "Boxibox: Rappel - Votre facture #{$invoice->number} de {$invoice->total_amount}€ est impayée depuis {$daysOverdue} jour(s). Merci de régulariser: " . route('portal.invoices.show', $invoice->id),
            $daysOverdue <= 7 => "Boxibox: URGENT - Facture #{$invoice->number} ({$invoice->total_amount}€) impayée. Risque de blocage d'accès. Contactez-nous: 01 XX XX XX XX",
            default => "Boxibox: Votre accès sera suspendu si la facture #{$invoice->number} n'est pas payée. Appelez-nous: 01 XX XX XX XX",
        };

        return $this->send($customer->phone, $message, [
            'type' => 'payment_reminder',
            'invoice_id' => $invoice->id,
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Send contract expiration reminder
     */
    public function sendContractExpiring($contract, int $daysUntilExpiry): array
    {
        $customer = $contract->customer;

        if (!$customer->phone) {
            return ['status' => 'no_phone'];
        }

        $message = match (true) {
            $daysUntilExpiry >= 30 => "Boxibox: Votre contrat expire dans {$daysUntilExpiry} jours. Profitez de -10% si vous renouvelez maintenant! Répondez OUI pour prolonger.",
            $daysUntilExpiry >= 7 => "Boxibox: Plus que {$daysUntilExpiry} jours avant expiration. Renouvelez en ligne en 2 min: " . route('portal.contracts.renew', $contract->id),
            default => "Boxibox: URGENT - Votre contrat expire dans {$daysUntilExpiry} jours. Contactez-nous pour prolonger: 01 XX XX XX XX",
        };

        return $this->send($customer->phone, $message, [
            'type' => 'contract_expiring',
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Send promotional SMS
     */
    public function sendPromotion(Customer $customer, string $offerText, ?string $promoCode = null): array
    {
        if (!$customer->phone || !$customer->sms_consent) {
            return ['status' => 'no_consent'];
        }

        $message = "🎁 Boxibox: {$offerText}";
        if ($promoCode) {
            $message .= " Code: {$promoCode}";
        }
        $message .= " | STOP au 36180";

        return $this->send($customer->phone, $message, [
            'type' => 'promotion',
            'customer_id' => $customer->id,
            'promo_code' => $promoCode,
        ]);
    }

    /**
     * Send welcome SMS to new customer
     */
    public function sendWelcome(Customer $customer, string $accessCode): array
    {
        if (!$customer->phone) {
            return ['status' => 'no_phone'];
        }

        $message = "Bienvenue chez Boxibox! Votre code d'accès: {$accessCode}. Accès 24/7. Besoin d'aide? 01 XX XX XX XX";

        return $this->send($customer->phone, $message, [
            'type' => 'welcome',
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Send appointment reminder
     */
    public function sendAppointmentReminder($appointment): array
    {
        $customer = $appointment->customer ?? $appointment->lead;

        if (!$customer || !$customer->phone) {
            return ['status' => 'no_phone'];
        }

        $date = $appointment->scheduled_at->format('d/m à H\hi');
        $message = "Boxibox: Rappel de votre visite le {$date}. Adresse: {$appointment->site->address}. À bientôt!";

        return $this->send($customer->phone, $message, [
            'type' => 'appointment_reminder',
            'appointment_id' => $appointment->id,
        ]);
    }

    /**
     * Send OTP code
     */
    public function sendOTP(string $phone, string $code): array
    {
        $message = "Votre code Boxibox: {$code}. Valide 10 minutes. Ne le partagez pas.";

        return $this->send($phone, $message, [
            'type' => 'otp',
        ]);
    }

    /**
     * Bulk send SMS campaign
     */
    public function sendCampaign(SMSCampaign $campaign, array $recipients): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
            'skipped' => 0,
        ];

        foreach ($recipients as $recipient) {
            if (!$recipient->phone || !$recipient->sms_consent) {
                $results['skipped']++;
                continue;
            }

            $personalizedMessage = $this->personalize($campaign->message, $recipient);

            $result = $this->send($recipient->phone, $personalizedMessage, [
                'type' => 'campaign',
                'campaign_id' => $campaign->id,
                'customer_id' => $recipient->id,
            ]);

            if ($result['status'] === 'sent') {
                $results['sent']++;
            } else {
                $results['failed']++;
            }

            // Rate limiting: wait between sends
            usleep(100000); // 100ms delay
        }

        $campaign->update([
            'sent_count' => $results['sent'],
            'failed_count' => $results['failed'],
            'sent_at' => now(),
        ]);

        return $results;
    }

    /**
     * Personalize message with customer data
     */
    protected function personalize(string $message, $recipient): string
    {
        $replacements = [
            '{name}' => $recipient->first_name ?? $recipient->name ?? 'Client',
            '{first_name}' => $recipient->first_name ?? '',
            '{last_name}' => $recipient->last_name ?? '',
            '{email}' => $recipient->email ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }

    /**
     * Normalize phone number to E.164 format
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-digit characters
        $phone = preg_replace('/\D/', '', $phone);

        // Add country code if missing (default France +33)
        if (strlen($phone) === 10 && substr($phone, 0, 1) === '0') {
            $phone = '33' . substr($phone, 1);
        }

        // Add + prefix
        if (substr($phone, 0, 1) !== '+') {
            $phone = '+' . $phone;
        }

        return $phone;
    }

    /**
     * Log SMS
     */
    protected function logSMS(string $to, string $message, string $status, ?string $sid, array $metadata): void
    {
        SMSLog::create([
            'tenant_id' => $metadata['tenant_id'] ?? auth()->user()?->tenant_id,
            'to' => $to,
            'message' => $message,
            'status' => $status,
            'provider' => $this->provider,
            'provider_id' => $sid,
            'type' => $metadata['type'] ?? 'general',
            'metadata' => $metadata,
            'sent_at' => now(),
        ]);
    }

    /**
     * Get provider configuration
     */
    protected function getProviderConfig(): array
    {
        return match ($this->provider) {
            'twilio' => [
                'account_sid' => config('services.twilio.account_sid', env('TWILIO_ACCOUNT_SID')),
                'auth_token' => config('services.twilio.auth_token', env('TWILIO_AUTH_TOKEN')),
                'from' => config('services.twilio.from', env('TWILIO_FROM')),
            ],
            'vonage' => [
                'api_key' => config('services.vonage.key', env('VONAGE_KEY')),
                'api_secret' => config('services.vonage.secret', env('VONAGE_SECRET')),
                'from' => config('services.vonage.sms_from', env('VONAGE_SMS_FROM')),
            ],
            'aws-sns' => [
                'key' => config('services.aws.key', env('AWS_ACCESS_KEY_ID')),
                'secret' => config('services.aws.secret', env('AWS_SECRET_ACCESS_KEY')),
                'region' => config('services.aws.region', env('AWS_DEFAULT_REGION')),
            ],
            default => [],
        };
    }

    /**
     * Check if phone number is valid
     */
    public function isValidPhone(string $phone): bool
    {
        $normalized = $this->normalizePhoneNumber($phone);
        return preg_match('/^\+[1-9]\d{1,14}$/', $normalized);
    }

    /**
     * Get SMS cost estimate
     */
    public function estimateCost(int $count, string $country = 'FR'): float
    {
        // Average SMS cost per provider
        $costs = [
            'twilio' => ['FR' => 0.08, 'default' => 0.10],
            'vonage' => ['FR' => 0.07, 'default' => 0.09],
            'aws-sns' => ['FR' => 0.06, 'default' => 0.08],
        ];

        $cost = $costs[$this->provider][$country] ?? $costs[$this->provider]['default'] ?? 0.08;

        return round($count * $cost, 2);
    }
}
