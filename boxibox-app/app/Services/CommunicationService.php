<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantUsage;
use App\Models\TenantCredit;
use App\Models\TenantEmailProvider;
use App\Models\TenantSmsProvider;
use App\Models\EmailTracking;
use App\Models\SmsTracking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Service Hybride d'Envoi Email & SMS
 *
 * LOGIQUE :
 * 1. Vérifie le quota du plan
 * 2. Si quota épuisé, vérifie les crédits achetés
 * 3. Si crédits épuisés, utilise le provider perso du tenant (si autorisé)
 * 4. Sinon, bloque l'envoi
 *
 * PROVIDERS MUTUALISÉS (config dans .env) :
 * - Email : Mailgun, SendGrid, ou SMTP
 * - SMS : Twilio, Vonage
 */
class CommunicationService
{
    protected EmailTrackingService $emailTracking;

    public function __construct(EmailTrackingService $emailTracking)
    {
        $this->emailTracking = $emailTracking;
    }

    /**
     * ==========================================
     * ENVOI EMAIL
     * ==========================================
     */
    public function sendEmail(
        int $tenantId,
        string $toEmail,
        string $subject,
        string $htmlContent,
        string $emailType = 'notification',
        string $recipientType = 'customer',
        ?int $recipientId = null,
        array $options = []
    ): array {
        $tenant = Tenant::with('subscriptionPlan')->find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'error' => 'Tenant non trouvé'];
        }

        // 1. Vérifier le quota et déterminer la source
        $source = $this->determineEmailSource($tenant);

        if (!$source['can_send']) {
            return [
                'success' => false,
                'error' => $source['reason'],
                'quota_exceeded' => true,
            ];
        }

        // 2. Créer le tracking
        $tracking = $this->emailTracking->createTracking(
            $tenantId,
            $toEmail,
            $recipientType,
            $recipientId,
            $emailType,
            $subject,
            $source['provider']
        );

        // 3. Ajouter pixel de tracking
        $htmlContent = $this->addTrackingPixel($htmlContent, $tracking->tracking_id);

        // 4. Tracker les liens si activé
        if ($options['track_links'] ?? true) {
            $htmlContent = $this->emailTracking->wrapLinksWithTracking($htmlContent, $tracking->id);
        }

        // 5. Envoyer selon la source
        $result = match ($source['type']) {
            'plan_quota' => $this->sendViaSharedProvider($tenant, $toEmail, $subject, $htmlContent, 'email'),
            'credits' => $this->sendViaSharedProvider($tenant, $toEmail, $subject, $htmlContent, 'email'),
            'custom_provider' => $this->sendViaCustomEmailProvider($source['provider_model'], $toEmail, $subject, $htmlContent),
            default => ['success' => false, 'error' => 'Source inconnue'],
        };

        // 6. Mettre à jour tracking et compteurs
        if ($result['success']) {
            $tracking->update([
                'provider_message_id' => $result['message_id'] ?? null,
                'status' => 'sent',
            ]);

            $this->incrementEmailUsage($tenant, $source['type'] === 'credits');

            if ($source['type'] === 'credits' && $source['credit']) {
                $source['credit']->useCredits(1);
            }
        } else {
            $tracking->update([
                'status' => 'failed',
                'metadata' => ['error' => $result['error']],
            ]);
        }

        return array_merge($result, [
            'tracking_id' => $tracking->tracking_id,
            'source' => $source['type'],
        ]);
    }

    /**
     * Déterminer la source d'envoi pour email
     */
    protected function determineEmailSource(Tenant $tenant): array
    {
        $plan = $tenant->subscriptionPlan;
        $usage = TenantUsage::currentMonth($tenant->id);

        // 1. Quota du plan non atteint ?
        if ($plan && ($plan->has_unlimited_emails || $usage->emails_sent < $plan->emails_per_month)) {
            return [
                'can_send' => true,
                'type' => 'plan_quota',
                'provider' => 'shared_' . config('services.email.shared_provider', 'mailgun'),
                'remaining' => $plan->has_unlimited_emails ? PHP_INT_MAX : ($plan->emails_per_month - $usage->emails_sent),
            ];
        }

        // 2. Crédits disponibles ?
        $credit = TenantCredit::where('tenant_id', $tenant->id)
            ->email()
            ->active()
            ->orderBy('expires_at')
            ->first();

        if ($credit && $credit->credits_remaining > 0) {
            return [
                'can_send' => true,
                'type' => 'credits',
                'provider' => 'shared_' . config('services.email.shared_provider', 'mailgun'),
                'remaining' => $credit->credits_remaining,
                'credit' => $credit,
            ];
        }

        // 3. Provider personnalisé autorisé ?
        if ($plan && $plan->custom_email_provider_allowed) {
            $customProvider = TenantEmailProvider::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->orderBy('is_default', 'desc')
                ->first();

            if ($customProvider) {
                return [
                    'can_send' => true,
                    'type' => 'custom_provider',
                    'provider' => $customProvider->provider,
                    'provider_model' => $customProvider,
                    'remaining' => PHP_INT_MAX,
                ];
            }
        }

        // 4. Bloqué
        return [
            'can_send' => false,
            'type' => 'blocked',
            'reason' => 'Quota email épuisé. Achetez des crédits ou passez à un plan supérieur.',
        ];
    }

    /**
     * ==========================================
     * ENVOI SMS
     * ==========================================
     */
    public function sendSms(
        int $tenantId,
        string $toPhone,
        string $message,
        string $smsType = 'notification',
        string $recipientType = 'customer',
        ?int $recipientId = null,
        array $metadata = []
    ): array {
        $tenant = Tenant::with('subscriptionPlan')->find($tenantId);

        if (!$tenant) {
            return ['success' => false, 'error' => 'Tenant non trouvé'];
        }

        // Normaliser le numéro
        $phone = $this->normalizePhone($toPhone);
        if (!$phone) {
            return ['success' => false, 'error' => 'Numéro de téléphone invalide'];
        }

        // 1. Vérifier le quota
        $source = $this->determineSmsSource($tenant);

        if (!$source['can_send']) {
            return [
                'success' => false,
                'error' => $source['reason'],
                'quota_exceeded' => true,
            ];
        }

        // 2. Créer le tracking
        $tracking = SmsTracking::create([
            'tenant_id' => $tenantId,
            'tracking_id' => Str::uuid()->toString(),
            'recipient_phone' => $phone,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'sms_type' => $smsType,
            'message' => $message,
            'status' => 'pending',
            'provider' => $source['provider'],
            'metadata' => $metadata,
        ]);

        // 3. Envoyer selon la source
        $result = match ($source['type']) {
            'plan_quota' => $this->sendViaSharedProvider($tenant, $phone, $message, null, 'sms'),
            'credits' => $this->sendViaSharedProvider($tenant, $phone, $message, null, 'sms'),
            'custom_provider' => $this->sendViaCustomSmsProvider($source['provider_model'], $phone, $message),
            default => ['success' => false, 'error' => 'Source inconnue'],
        };

        // 4. Mettre à jour
        if ($result['success']) {
            $tracking->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider_message_id' => $result['message_id'] ?? null,
                'cost' => $result['cost'] ?? null,
            ]);

            $this->incrementSmsUsage($tenant, $source['type'] === 'credits');

            if ($source['type'] === 'credits' && $source['credit']) {
                $source['credit']->useCredits(1);
            }
        } else {
            $tracking->markAsFailed($result['error'] ?? 'Erreur inconnue');
        }

        return array_merge($result, [
            'tracking_id' => $tracking->tracking_id,
            'source' => $source['type'],
        ]);
    }

    /**
     * Déterminer la source d'envoi pour SMS
     */
    protected function determineSmsSource(Tenant $tenant): array
    {
        $plan = $tenant->subscriptionPlan;
        $usage = TenantUsage::currentMonth($tenant->id);

        // 1. Quota du plan
        if ($plan && $plan->sms_per_month > 0 && $usage->sms_sent < $plan->sms_per_month) {
            return [
                'can_send' => true,
                'type' => 'plan_quota',
                'provider' => 'shared_' . config('services.sms.shared_provider', 'twilio'),
                'remaining' => $plan->sms_per_month - $usage->sms_sent,
            ];
        }

        // 2. Crédits
        $credit = TenantCredit::where('tenant_id', $tenant->id)
            ->sms()
            ->active()
            ->orderBy('expires_at')
            ->first();

        if ($credit && $credit->credits_remaining > 0) {
            return [
                'can_send' => true,
                'type' => 'credits',
                'provider' => 'shared_' . config('services.sms.shared_provider', 'twilio'),
                'remaining' => $credit->credits_remaining,
                'credit' => $credit,
            ];
        }

        // 3. Provider perso
        if ($plan && $plan->custom_sms_provider_allowed) {
            $customProvider = TenantSmsProvider::where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('is_verified', true)
                ->orderBy('is_default', 'desc')
                ->first();

            if ($customProvider) {
                return [
                    'can_send' => true,
                    'type' => 'custom_provider',
                    'provider' => $customProvider->provider,
                    'provider_model' => $customProvider,
                    'remaining' => PHP_INT_MAX,
                ];
            }
        }

        return [
            'can_send' => false,
            'type' => 'blocked',
            'reason' => 'Quota SMS épuisé. Achetez des crédits ou passez à un plan supérieur.',
        ];
    }

    /**
     * ==========================================
     * PROVIDERS MUTUALISÉS
     * ==========================================
     */
    protected function sendViaSharedProvider(Tenant $tenant, string $to, string $content, ?string $html, string $type): array
    {
        if ($type === 'email') {
            return $this->sendSharedEmail($tenant, $to, $content, $html);
        }

        return $this->sendSharedSms($tenant, $to, $content);
    }

    /**
     * Envoyer email via provider mutualisé
     */
    protected function sendSharedEmail(Tenant $tenant, string $toEmail, string $subject, string $htmlContent): array
    {
        $provider = config('services.email.shared_provider', 'mailgun');

        try {
            return match ($provider) {
                'mailgun' => $this->sendSharedMailgun($tenant, $toEmail, $subject, $htmlContent),
                'sendgrid' => $this->sendSharedSendgrid($tenant, $toEmail, $subject, $htmlContent),
                'smtp' => $this->sendSharedSmtp($tenant, $toEmail, $subject, $htmlContent),
                default => ['success' => false, 'error' => "Provider mutualisé non supporté: {$provider}"],
            };
        } catch (\Exception $e) {
            Log::error('Shared email send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function sendSharedMailgun(Tenant $tenant, string $toEmail, string $subject, string $htmlContent): array
    {
        $apiKey = config('services.mailgun.secret');
        $domain = config('services.mailgun.domain');
        $region = config('services.mailgun.region', 'us');

        if (!$apiKey || !$domain) {
            return ['success' => false, 'error' => 'Mailgun non configuré'];
        }

        $baseUrl = $region === 'eu' ? 'https://api.eu.mailgun.net' : 'https://api.mailgun.net';

        // Utiliser le nom du tenant comme expéditeur
        $fromName = $tenant->name ?? 'BoxiBox';
        $fromEmail = config('services.mailgun.from', 'noreply@' . $domain);

        $response = Http::withBasicAuth('api', $apiKey)
            ->asForm()
            ->post("{$baseUrl}/v3/{$domain}/messages", [
                'from' => "{$fromName} <{$fromEmail}>",
                'to' => $toEmail,
                'subject' => $subject,
                'html' => $htmlContent,
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('id')];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendSharedSendgrid(Tenant $tenant, string $toEmail, string $subject, string $htmlContent): array
    {
        $apiKey = config('services.sendgrid.api_key');

        if (!$apiKey) {
            return ['success' => false, 'error' => 'SendGrid non configuré'];
        }

        $fromName = $tenant->name ?? 'BoxiBox';
        $fromEmail = config('services.sendgrid.from', 'noreply@boxibox.fr');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.sendgrid.com/v3/mail/send', [
            'personalizations' => [['to' => [['email' => $toEmail]]]],
            'from' => ['email' => $fromEmail, 'name' => $fromName],
            'subject' => $subject,
            'content' => [['type' => 'text/html', 'value' => $htmlContent]],
        ]);

        if ($response->successful() || $response->status() === 202) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendSharedSmtp(Tenant $tenant, string $toEmail, string $subject, string $htmlContent): array
    {
        try {
            $fromName = $tenant->name ?? 'BoxiBox';

            Mail::send([], [], function ($message) use ($toEmail, $subject, $htmlContent, $fromName) {
                $message->to($toEmail)
                    ->subject($subject)
                    ->html($htmlContent);
            });

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envoyer SMS via provider mutualisé
     */
    protected function sendSharedSms(Tenant $tenant, string $phone, string $message): array
    {
        $provider = config('services.sms.shared_provider', 'twilio');

        try {
            return match ($provider) {
                'twilio' => $this->sendSharedTwilio($phone, $message),
                'vonage' => $this->sendSharedVonage($tenant, $phone, $message),
                'log' => $this->sendSharedLog($phone, $message),
                default => ['success' => false, 'error' => "Provider SMS mutualisé non supporté: {$provider}"],
            };
        } catch (\Exception $e) {
            Log::error('Shared SMS send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function sendSharedTwilio(string $phone, string $message): array
    {
        $accountSid = config('services.twilio.sid');
        $authToken = config('services.twilio.token');
        $fromNumber = config('services.twilio.from');

        if (!$accountSid || !$authToken || !$fromNumber) {
            return ['success' => false, 'error' => 'Twilio non configuré'];
        }

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $fromNumber,
                'To' => $phone,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'message_id' => $data['sid'],
                'cost' => isset($data['price']) ? abs(floatval($data['price'])) : null,
            ];
        }

        return ['success' => false, 'error' => $response->json('message') ?? $response->body()];
    }

    protected function sendSharedVonage(Tenant $tenant, string $phone, string $message): array
    {
        $apiKey = config('services.vonage.key');
        $apiSecret = config('services.vonage.secret');
        $from = $tenant->name ?? config('services.vonage.from', 'BoxiBox');

        if (!$apiKey || !$apiSecret) {
            return ['success' => false, 'error' => 'Vonage non configuré'];
        }

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => substr($from, 0, 11), // Max 11 chars
            'to' => preg_replace('/[^0-9]/', '', $phone),
            'text' => $message,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $msgData = $data['messages'][0] ?? [];

            if (($msgData['status'] ?? '1') === '0') {
                return [
                    'success' => true,
                    'message_id' => $msgData['message-id'],
                    'cost' => isset($msgData['message-price']) ? floatval($msgData['message-price']) : null,
                ];
            }

            return ['success' => false, 'error' => $msgData['error-text'] ?? 'Erreur Vonage'];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendSharedLog(string $phone, string $message): array
    {
        Log::info('SMS (DEBUG)', ['to' => $phone, 'message' => $message]);
        return ['success' => true, 'message_id' => 'debug-' . Str::uuid()];
    }

    /**
     * ==========================================
     * PROVIDERS PERSONNALISÉS
     * ==========================================
     */
    protected function sendViaCustomEmailProvider(TenantEmailProvider $provider, string $toEmail, string $subject, string $htmlContent): array
    {
        $config = $provider->decrypted_config;

        try {
            return match ($provider->provider) {
                'mailgun' => $this->sendCustomMailgun($provider, $config, $toEmail, $subject, $htmlContent),
                'sendinblue' => $this->sendCustomSendinblue($provider, $config, $toEmail, $subject, $htmlContent),
                'sendgrid' => $this->sendCustomSendgrid($provider, $config, $toEmail, $subject, $htmlContent),
                'postmark' => $this->sendCustomPostmark($provider, $config, $toEmail, $subject, $htmlContent),
                default => ['success' => false, 'error' => 'Provider non supporté'],
            };
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function sendViaCustomSmsProvider(TenantSmsProvider $provider, string $phone, string $message): array
    {
        $config = $provider->decrypted_config;

        try {
            return match ($provider->provider) {
                'twilio' => $this->sendCustomTwilio($provider, $config, $phone, $message),
                'vonage' => $this->sendCustomVonage($provider, $config, $phone, $message),
                'plivo' => $this->sendCustomPlivo($provider, $config, $phone, $message),
                default => ['success' => false, 'error' => 'Provider non supporté'],
            };
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Les méthodes sendCustom* sont similaires à celles de TenantEmailService/TenantSmsService
    // ... (implémentation simplifiée pour garder le code concis)

    protected function sendCustomMailgun($provider, $config, $toEmail, $subject, $htmlContent): array
    {
        $domain = $config['domain'] ?? '';
        $apiKey = $config['api_key'] ?? '';
        $region = $config['region'] ?? 'us';
        $baseUrl = $region === 'eu' ? 'https://api.eu.mailgun.net' : 'https://api.mailgun.net';

        $response = Http::withBasicAuth('api', $apiKey)->asForm()
            ->post("{$baseUrl}/v3/{$domain}/messages", [
                'from' => "{$provider->from_name} <{$provider->from_email}>",
                'to' => $toEmail,
                'subject' => $subject,
                'html' => $htmlContent,
            ]);

        return $response->successful()
            ? ['success' => true, 'message_id' => $response->json('id')]
            : ['success' => false, 'error' => $response->body()];
    }

    protected function sendCustomSendinblue($provider, $config, $toEmail, $subject, $htmlContent): array
    {
        $response = Http::withHeaders(['api-key' => $config['api_key'] ?? ''])
            ->post('https://api.brevo.com/v3/smtp/email', [
                'sender' => ['name' => $provider->from_name, 'email' => $provider->from_email],
                'to' => [['email' => $toEmail]],
                'subject' => $subject,
                'htmlContent' => $htmlContent,
            ]);

        return $response->successful()
            ? ['success' => true, 'message_id' => $response->json('messageId')]
            : ['success' => false, 'error' => $response->body()];
    }

    protected function sendCustomSendgrid($provider, $config, $toEmail, $subject, $htmlContent): array
    {
        $response = Http::withHeaders(['Authorization' => 'Bearer ' . ($config['api_key'] ?? '')])
            ->post('https://api.sendgrid.com/v3/mail/send', [
                'personalizations' => [['to' => [['email' => $toEmail]]]],
                'from' => ['email' => $provider->from_email, 'name' => $provider->from_name],
                'subject' => $subject,
                'content' => [['type' => 'text/html', 'value' => $htmlContent]],
            ]);

        return ($response->successful() || $response->status() === 202)
            ? ['success' => true]
            : ['success' => false, 'error' => $response->body()];
    }

    protected function sendCustomPostmark($provider, $config, $toEmail, $subject, $htmlContent): array
    {
        $response = Http::withHeaders(['X-Postmark-Server-Token' => $config['server_token'] ?? ''])
            ->post('https://api.postmarkapp.com/email', [
                'From' => "{$provider->from_name} <{$provider->from_email}>",
                'To' => $toEmail,
                'Subject' => $subject,
                'HtmlBody' => $htmlContent,
            ]);

        return $response->successful()
            ? ['success' => true, 'message_id' => $response->json('MessageID')]
            : ['success' => false, 'error' => $response->body()];
    }

    protected function sendCustomTwilio($provider, $config, $phone, $message): array
    {
        $accountSid = $config['account_sid'] ?? '';
        $response = Http::withBasicAuth($accountSid, $config['auth_token'] ?? '')
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $provider->sender_id,
                'To' => $phone,
                'Body' => $message,
            ]);

        return $response->successful()
            ? ['success' => true, 'message_id' => $response->json('sid')]
            : ['success' => false, 'error' => $response->json('message') ?? $response->body()];
    }

    protected function sendCustomVonage($provider, $config, $phone, $message): array
    {
        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $config['api_key'] ?? '',
            'api_secret' => $config['api_secret'] ?? '',
            'from' => $provider->sender_id,
            'to' => preg_replace('/[^0-9]/', '', $phone),
            'text' => $message,
        ]);

        if ($response->successful()) {
            $msgData = $response->json()['messages'][0] ?? [];
            return ($msgData['status'] ?? '1') === '0'
                ? ['success' => true, 'message_id' => $msgData['message-id']]
                : ['success' => false, 'error' => $msgData['error-text'] ?? 'Erreur'];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendCustomPlivo($provider, $config, $phone, $message): array
    {
        $authId = $config['auth_id'] ?? '';
        $response = Http::withBasicAuth($authId, $config['auth_token'] ?? '')
            ->post("https://api.plivo.com/v1/Account/{$authId}/Message/", [
                'src' => $provider->sender_id,
                'dst' => preg_replace('/[^0-9]/', '', $phone),
                'text' => $message,
            ]);

        return $response->successful()
            ? ['success' => true, 'message_id' => $response->json('message_uuid')[0] ?? null]
            : ['success' => false, 'error' => $response->body()];
    }

    /**
     * ==========================================
     * HELPERS
     * ==========================================
     */
    protected function incrementEmailUsage(Tenant $tenant, bool $fromCredits = false): void
    {
        $usage = TenantUsage::currentMonth($tenant->id);
        $usage->incrementEmails(1, $fromCredits);
        $tenant->increment('emails_sent_this_month');
    }

    protected function incrementSmsUsage(Tenant $tenant, bool $fromCredits = false): void
    {
        $usage = TenantUsage::currentMonth($tenant->id);
        $usage->incrementSms(1, $fromCredits);
        $tenant->increment('sms_sent_this_month');
    }

    protected function addTrackingPixel(string $html, string $trackingId): string
    {
        $pixelHtml = $this->emailTracking->getTrackingPixelHtml($trackingId);
        return stripos($html, '</body>') !== false
            ? str_ireplace('</body>', $pixelHtml . '</body>', $html)
            : $html . $pixelHtml;
    }

    protected function normalizePhone(string $phone): ?string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        if (preg_match('/^0[67]/', $phone)) {
            $phone = '+33' . substr($phone, 1);
        }

        if (!str_starts_with($phone, '+')) {
            if (strlen($phone) === 9 && preg_match('/^[67]/', $phone)) {
                $phone = '+33' . $phone;
            } elseif (strlen($phone) === 10) {
                $phone = '+33' . substr($phone, 1);
            }
        }

        return preg_match('/^\+[1-9]\d{6,14}$/', $phone) ? $phone : null;
    }

    /**
     * Obtenir les statistiques de quota pour un tenant
     */
    public function getQuotaStatus(int $tenantId): array
    {
        $tenant = Tenant::with('subscriptionPlan')->find($tenantId);
        $plan = $tenant?->subscriptionPlan;
        $usage = TenantUsage::currentMonth($tenantId);

        $emailCredits = TenantCredit::where('tenant_id', $tenantId)->email()->active()->sum('credits_remaining');
        $smsCredits = TenantCredit::where('tenant_id', $tenantId)->sms()->active()->sum('credits_remaining');

        return [
            'plan' => $plan ? [
                'name' => $plan->name,
                'emails_per_month' => $plan->emails_per_month,
                'sms_per_month' => $plan->sms_per_month,
                'custom_email_allowed' => $plan->custom_email_provider_allowed,
                'custom_sms_allowed' => $plan->custom_sms_provider_allowed,
            ] : null,
            'usage' => [
                'emails_sent' => $usage->emails_sent,
                'emails_quota' => $usage->emails_quota,
                'emails_remaining' => $usage->emails_remaining,
                'emails_percent' => $usage->email_usage_percent,
                'sms_sent' => $usage->sms_sent,
                'sms_quota' => $usage->sms_quota,
                'sms_remaining' => $usage->sms_remaining,
                'sms_percent' => $usage->sms_usage_percent,
            ],
            'credits' => [
                'emails' => $emailCredits,
                'sms' => $smsCredits,
            ],
            'period' => [
                'start' => $usage->period_start->format('Y-m-d'),
                'end' => $usage->period_end->format('Y-m-d'),
            ],
        ];
    }
}
