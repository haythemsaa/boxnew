<?php

namespace App\Services;

use App\Models\TenantEmailProvider;
use App\Models\EmailTracking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Service d'envoi d'email utilisant les providers configurés par le tenant
 *
 * Ce service :
 * 1. Récupère le provider par défaut du tenant
 * 2. Envoie l'email via ce provider
 * 3. Crée un tracking pour suivre ouvertures/clics
 * 4. Incrémente les compteurs d'utilisation
 */
class TenantEmailService
{
    protected EmailTrackingService $trackingService;

    public function __construct(EmailTrackingService $trackingService)
    {
        $this->trackingService = $trackingService;
    }

    /**
     * Envoyer un email via le provider du tenant
     */
    public function send(
        int $tenantId,
        string $toEmail,
        string $subject,
        string $htmlContent,
        string $emailType = 'notification',
        string $recipientType = 'customer',
        ?int $recipientId = null,
        array $options = []
    ): ?EmailTracking {
        // Récupérer le provider par défaut du tenant
        $provider = TenantEmailProvider::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('is_default', 'desc')
            ->first();

        if (!$provider) {
            Log::warning('No verified email provider for tenant', ['tenant_id' => $tenantId]);

            // Fallback : utiliser le mailer Laravel par défaut
            return $this->sendViaDefaultMailer($tenantId, $toEmail, $subject, $htmlContent, $emailType, $recipientType, $recipientId);
        }

        // Vérifier les limites
        if (!$provider->canSend()) {
            Log::warning('Email provider limit reached', [
                'provider_id' => $provider->id,
                'daily' => $provider->emails_sent_today,
                'monthly' => $provider->emails_sent_month,
            ]);
            return null;
        }

        // Créer le tracking
        $tracking = $this->trackingService->createTracking(
            $tenantId,
            $toEmail,
            $recipientType,
            $recipientId,
            $emailType,
            $subject,
            $provider->provider
        );

        // Ajouter le pixel de tracking
        $htmlContent = $this->addTrackingPixel($htmlContent, $tracking->tracking_id);

        // Remplacer les liens par des liens trackés
        if ($options['track_links'] ?? true) {
            $htmlContent = $this->trackingService->wrapLinksWithTracking($htmlContent, $tracking->id);
        }

        // Envoyer via le provider
        $result = $this->sendViaProvider($provider, $toEmail, $subject, $htmlContent, $options);

        if ($result['success']) {
            // Mettre à jour le tracking avec le message ID du provider
            $tracking->update([
                'provider_message_id' => $result['message_id'] ?? null,
                'status' => 'sent',
            ]);

            // Incrémenter les compteurs
            $provider->incrementSentCount();

            Log::info('Email sent successfully', [
                'tracking_id' => $tracking->tracking_id,
                'provider' => $provider->provider,
                'to' => $toEmail,
            ]);
        } else {
            $tracking->update([
                'status' => 'failed',
                'metadata' => array_merge($tracking->metadata ?? [], ['error' => $result['error']]),
            ]);

            Log::error('Email send failed', [
                'provider' => $provider->provider,
                'error' => $result['error'],
            ]);
        }

        return $tracking;
    }

    /**
     * Envoyer via le provider configuré
     */
    protected function sendViaProvider(TenantEmailProvider $provider, string $toEmail, string $subject, string $htmlContent, array $options = []): array
    {
        $config = $provider->decrypted_config;

        try {
            return match ($provider->provider) {
                'mailgun' => $this->sendViaMailgun($provider, $config, $toEmail, $subject, $htmlContent, $options),
                'sendinblue' => $this->sendViaSendinblue($provider, $config, $toEmail, $subject, $htmlContent, $options),
                'postmark' => $this->sendViaPostmark($provider, $config, $toEmail, $subject, $htmlContent, $options),
                'sendgrid' => $this->sendViaSendgrid($provider, $config, $toEmail, $subject, $htmlContent, $options),
                'smtp' => $this->sendViaSmtp($provider, $config, $toEmail, $subject, $htmlContent, $options),
                default => ['success' => false, 'error' => 'Provider non supporté: ' . $provider->provider],
            };
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function sendViaMailgun(TenantEmailProvider $provider, array $config, string $toEmail, string $subject, string $htmlContent, array $options): array
    {
        $domain = $config['domain'] ?? '';
        $apiKey = $config['api_key'] ?? '';
        $region = $config['region'] ?? 'us';

        $baseUrl = $region === 'eu' ? 'https://api.eu.mailgun.net' : 'https://api.mailgun.net';

        $payload = [
            'from' => "{$provider->from_name} <{$provider->from_email}>",
            'to' => $toEmail,
            'subject' => $subject,
            'html' => $htmlContent,
        ];

        if ($provider->reply_to_email) {
            $payload['h:Reply-To'] = $provider->reply_to_email;
        }

        // Attachments
        if (!empty($options['attachments'])) {
            // Note: Pour les attachments, il faudrait utiliser multipart/form-data
        }

        $response = Http::withBasicAuth('api', $apiKey)
            ->asForm()
            ->post("{$baseUrl}/v3/{$domain}/messages", $payload);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('id')];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendViaSendinblue(TenantEmailProvider $provider, array $config, string $toEmail, string $subject, string $htmlContent, array $options): array
    {
        $apiKey = $config['api_key'] ?? '';

        $payload = [
            'sender' => [
                'name' => $provider->from_name,
                'email' => $provider->from_email,
            ],
            'to' => [['email' => $toEmail]],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        if ($provider->reply_to_email) {
            $payload['replyTo'] = ['email' => $provider->reply_to_email];
        }

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', $payload);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('messageId')];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendViaPostmark(TenantEmailProvider $provider, array $config, string $toEmail, string $subject, string $htmlContent, array $options): array
    {
        $serverToken = $config['server_token'] ?? '';

        $payload = [
            'From' => "{$provider->from_name} <{$provider->from_email}>",
            'To' => $toEmail,
            'Subject' => $subject,
            'HtmlBody' => $htmlContent,
            'MessageStream' => 'outbound',
        ];

        if ($provider->reply_to_email) {
            $payload['ReplyTo'] = $provider->reply_to_email;
        }

        $response = Http::withHeaders([
            'X-Postmark-Server-Token' => $serverToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.postmarkapp.com/email', $payload);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('MessageID')];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendViaSendgrid(TenantEmailProvider $provider, array $config, string $toEmail, string $subject, string $htmlContent, array $options): array
    {
        $apiKey = $config['api_key'] ?? '';

        $payload = [
            'personalizations' => [[
                'to' => [['email' => $toEmail]],
            ]],
            'from' => [
                'email' => $provider->from_email,
                'name' => $provider->from_name,
            ],
            'subject' => $subject,
            'content' => [
                ['type' => 'text/html', 'value' => $htmlContent],
            ],
        ];

        if ($provider->reply_to_email) {
            $payload['reply_to'] = ['email' => $provider->reply_to_email];
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.sendgrid.com/v3/mail/send', $payload);

        if ($response->successful() || $response->status() === 202) {
            // SendGrid retourne 202 sans body en cas de succès
            return ['success' => true];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendViaSmtp(TenantEmailProvider $provider, array $config, string $toEmail, string $subject, string $htmlContent, array $options): array
    {
        try {
            // Configuration dynamique du mailer
            config([
                'mail.mailers.tenant_smtp' => [
                    'transport' => 'smtp',
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'encryption' => $config['encryption'] === 'none' ? null : $config['encryption'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                ],
            ]);

            Mail::mailer('tenant_smtp')->send([], [], function ($message) use ($provider, $toEmail, $subject, $htmlContent) {
                $message->from($provider->from_email, $provider->from_name)
                    ->to($toEmail)
                    ->subject($subject)
                    ->html($htmlContent);

                if ($provider->reply_to_email) {
                    $message->replyTo($provider->reply_to_email);
                }
            });

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Fallback : envoyer via le mailer Laravel par défaut
     */
    protected function sendViaDefaultMailer(int $tenantId, string $toEmail, string $subject, string $htmlContent, string $emailType, string $recipientType, ?int $recipientId): ?EmailTracking
    {
        try {
            // Créer le tracking
            $tracking = $this->trackingService->createTracking(
                $tenantId,
                $toEmail,
                $recipientType,
                $recipientId,
                $emailType,
                $subject,
                'laravel_default'
            );

            // Ajouter le pixel de tracking
            $htmlContent = $this->addTrackingPixel($htmlContent, $tracking->tracking_id);

            Mail::send([], [], function ($message) use ($toEmail, $subject, $htmlContent) {
                $message->to($toEmail)
                    ->subject($subject)
                    ->html($htmlContent);
            });

            return $tracking;
        } catch (\Exception $e) {
            Log::error('Default mailer failed', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Ajouter le pixel de tracking au HTML
     */
    protected function addTrackingPixel(string $html, string $trackingId): string
    {
        $pixelHtml = $this->trackingService->getTrackingPixelHtml($trackingId);

        // Insérer juste avant </body> si présent, sinon à la fin
        if (stripos($html, '</body>') !== false) {
            return str_ireplace('</body>', $pixelHtml . '</body>', $html);
        }

        return $html . $pixelHtml;
    }

    /**
     * Obtenir les statistiques d'envoi du tenant
     */
    public function getTenantStats(int $tenantId): array
    {
        $emailProviders = TenantEmailProvider::where('tenant_id', $tenantId)->get();

        $totalSentToday = $emailProviders->sum('emails_sent_today');
        $totalSentMonth = $emailProviders->sum('emails_sent_month');

        $trackingStats = EmailTracking::where('tenant_id', $tenantId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened,
                SUM(CASE WHEN first_clicked_at IS NOT NULL THEN 1 ELSE 0 END) as total_clicked,
                SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END) as total_bounced
            ')
            ->first();

        return [
            'providers_count' => $emailProviders->count(),
            'active_providers' => $emailProviders->where('is_active', true)->count(),
            'verified_providers' => $emailProviders->where('is_verified', true)->count(),
            'total_sent_today' => $totalSentToday,
            'total_sent_month' => $totalSentMonth,
            'last_30_days' => [
                'sent' => $trackingStats->total_sent ?? 0,
                'opened' => $trackingStats->total_opened ?? 0,
                'clicked' => $trackingStats->total_clicked ?? 0,
                'bounced' => $trackingStats->total_bounced ?? 0,
                'open_rate' => $trackingStats->total_sent > 0
                    ? round(($trackingStats->total_opened / $trackingStats->total_sent) * 100, 1)
                    : 0,
                'click_rate' => $trackingStats->total_opened > 0
                    ? round(($trackingStats->total_clicked / $trackingStats->total_opened) * 100, 1)
                    : 0,
            ],
        ];
    }
}
