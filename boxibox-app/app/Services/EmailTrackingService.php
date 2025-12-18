<?php

namespace App\Services;

use App\Models\EmailTracking;
use App\Models\EmailLinkTracking;
use App\Models\Lead;
use App\Models\Prospect;
use App\Models\Customer;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

/**
 * Service pour le tracking des emails (ouvertures, clics, etc.)
 *
 * COMMENT CA MARCHE :
 *
 * 1. PIXEL DE TRACKING (ouverture)
 *    - On insère une image invisible (1x1 pixel) dans l'email
 *    - Quand l'email est ouvert, le navigateur charge l'image
 *    - Notre serveur enregistre l'ouverture
 *
 * 2. LIENS TRACKÉS (clics)
 *    - On remplace tous les liens par des liens passant par notre serveur
 *    - Exemple: https://monsite.com/tarifs
 *      devient: https://boxibox.com/track/click/UUID123
 *    - Quand le clic arrive, on enregistre puis redirige vers l'URL originale
 *
 * 3. WEBHOOKS (providers externes)
 *    - Mailgun, Sendinblue, etc. envoient des webhooks pour:
 *      - delivered (email arrivé)
 *      - bounced (email rejeté)
 *      - complained (spam)
 */
class EmailTrackingService
{
    /**
     * Créer un nouvel enregistrement de tracking pour un email
     */
    public function createTracking(
        int $tenantId,
        string $recipientEmail,
        string $recipientType,
        ?int $recipientId,
        string $emailType,
        string $subject,
        ?string $provider = null,
        ?string $providerMessageId = null
    ): EmailTracking {
        return EmailTracking::create([
            'tenant_id' => $tenantId,
            'tracking_id' => Str::uuid()->toString(),
            'recipient_email' => $recipientEmail,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'email_type' => $emailType,
            'subject' => $subject,
            'status' => 'sent',
            'sent_at' => now(),
            'provider' => $provider,
            'provider_message_id' => $providerMessageId,
        ]);
    }

    /**
     * Générer l'URL du pixel de tracking (image 1x1)
     * À insérer dans le HTML de l'email
     */
    public function getTrackingPixelUrl(string $trackingId): string
    {
        return URL::to("/api/track/email/open/{$trackingId}");
    }

    /**
     * Générer le HTML du pixel de tracking
     */
    public function getTrackingPixelHtml(string $trackingId): string
    {
        $url = $this->getTrackingPixelUrl($trackingId);
        return '<img src="' . $url . '" width="1" height="1" style="display:none;" alt="" />';
    }

    /**
     * Créer un lien tracké
     */
    public function createTrackedLink(
        int $emailTrackingId,
        string $originalUrl,
        ?string $linkName = null
    ): EmailLinkTracking {
        $link = EmailLinkTracking::create([
            'email_tracking_id' => $emailTrackingId,
            'link_id' => Str::uuid()->toString(),
            'original_url' => $originalUrl,
            'link_name' => $linkName,
        ]);

        return $link;
    }

    /**
     * Générer l'URL trackée pour un lien
     */
    public function getTrackedLinkUrl(string $linkId): string
    {
        return URL::to("/api/track/email/click/{$linkId}");
    }

    /**
     * Remplacer tous les liens dans le HTML par des liens trackés
     */
    public function wrapLinksWithTracking(string $html, int $emailTrackingId): string
    {
        // Pattern pour trouver tous les liens href
        $pattern = '/href=["\']([^"\']+)["\']/i';

        return preg_replace_callback($pattern, function ($matches) use ($emailTrackingId) {
            $originalUrl = $matches[1];

            // Ne pas tracker certains liens
            if ($this->shouldSkipLink($originalUrl)) {
                return $matches[0];
            }

            // Créer le lien tracké
            $link = $this->createTrackedLink($emailTrackingId, $originalUrl);
            $trackedUrl = $this->getTrackedLinkUrl($link->link_id);

            return 'href="' . $trackedUrl . '"';
        }, $html);
    }

    /**
     * Vérifier si un lien doit être ignoré du tracking
     */
    protected function shouldSkipLink(string $url): bool
    {
        $skipPatterns = [
            'mailto:',
            'tel:',
            '#',
            'javascript:',
            'unsubscribe',
            'track/email', // Ne pas re-tracker nos propres liens
        ];

        foreach ($skipPatterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Enregistrer une ouverture d'email
     */
    public function recordOpen(string $trackingId): bool
    {
        $tracking = EmailTracking::where('tracking_id', $trackingId)->first();

        if (!$tracking) {
            Log::warning('Email tracking not found', ['tracking_id' => $trackingId]);
            return false;
        }

        $tracking->markAsOpened();

        Log::info('Email opened', [
            'tracking_id' => $trackingId,
            'recipient' => $tracking->recipient_email,
            'open_count' => $tracking->open_count,
        ]);

        return true;
    }

    /**
     * Enregistrer un clic sur un lien
     */
    public function recordClick(string $linkId): ?string
    {
        $link = EmailLinkTracking::where('link_id', $linkId)->first();

        if (!$link) {
            Log::warning('Email link tracking not found', ['link_id' => $linkId]);
            return null;
        }

        $link->recordClick([
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        Log::info('Email link clicked', [
            'link_id' => $linkId,
            'original_url' => $link->original_url,
            'click_count' => $link->click_count,
        ]);

        return $link->original_url;
    }

    /**
     * Gérer un webhook de provider email (Mailgun, Sendinblue, etc.)
     */
    public function handleProviderWebhook(string $provider, array $payload): bool
    {
        try {
            return match ($provider) {
                'mailgun' => $this->handleMailgunWebhook($payload),
                'sendinblue', 'brevo' => $this->handleSendinblueWebhook($payload),
                'postmark' => $this->handlePostmarkWebhook($payload),
                'ses', 'amazon_ses' => $this->handleSesWebhook($payload),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('Email webhook handling failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Webhook Mailgun
     */
    protected function handleMailgunWebhook(array $payload): bool
    {
        $eventData = $payload['event-data'] ?? $payload;
        $event = $eventData['event'] ?? null;
        $messageId = $eventData['message']['headers']['message-id'] ?? null;

        if (!$messageId) {
            return false;
        }

        $tracking = EmailTracking::where('provider_message_id', $messageId)->first();

        if (!$tracking) {
            return false;
        }

        switch ($event) {
            case 'delivered':
                $tracking->update(['delivered_at' => now(), 'status' => 'delivered']);
                break;
            case 'opened':
                $tracking->markAsOpened();
                break;
            case 'clicked':
                $url = $eventData['url'] ?? '';
                $tracking->markAsClicked($url);
                break;
            case 'failed':
            case 'bounced':
                $severity = $eventData['severity'] ?? 'permanent';
                $tracking->markAsBounced($severity === 'permanent' ? 'hard' : 'soft');
                break;
            case 'complained':
                $tracking->markAsComplained();
                break;
            case 'unsubscribed':
                $tracking->markAsUnsubscribed();
                break;
        }

        return true;
    }

    /**
     * Webhook Sendinblue/Brevo
     */
    protected function handleSendinblueWebhook(array $payload): bool
    {
        $event = $payload['event'] ?? null;
        $messageId = $payload['message-id'] ?? $payload['messageId'] ?? null;

        if (!$messageId) {
            return false;
        }

        $tracking = EmailTracking::where('provider_message_id', $messageId)->first();

        if (!$tracking) {
            return false;
        }

        switch ($event) {
            case 'delivered':
                $tracking->update(['delivered_at' => now(), 'status' => 'delivered']);
                break;
            case 'opened':
            case 'unique_opened':
                $tracking->markAsOpened();
                break;
            case 'click':
                $url = $payload['link'] ?? '';
                $tracking->markAsClicked($url);
                break;
            case 'hard_bounce':
                $tracking->markAsBounced('hard');
                break;
            case 'soft_bounce':
                $tracking->markAsBounced('soft');
                break;
            case 'spam':
            case 'complaint':
                $tracking->markAsComplained();
                break;
            case 'unsubscribed':
                $tracking->markAsUnsubscribed();
                break;
        }

        return true;
    }

    /**
     * Webhook Postmark
     */
    protected function handlePostmarkWebhook(array $payload): bool
    {
        $recordType = $payload['RecordType'] ?? null;
        $messageId = $payload['MessageID'] ?? null;

        if (!$messageId) {
            return false;
        }

        $tracking = EmailTracking::where('provider_message_id', $messageId)->first();

        if (!$tracking) {
            return false;
        }

        switch ($recordType) {
            case 'Delivery':
                $tracking->update(['delivered_at' => now(), 'status' => 'delivered']);
                break;
            case 'Open':
                $tracking->markAsOpened();
                break;
            case 'Click':
                $url = $payload['OriginalLink'] ?? '';
                $tracking->markAsClicked($url);
                break;
            case 'Bounce':
                $type = ($payload['Type'] ?? '') === 'HardBounce' ? 'hard' : 'soft';
                $tracking->markAsBounced($type);
                break;
            case 'SpamComplaint':
                $tracking->markAsComplained();
                break;
        }

        return true;
    }

    /**
     * Webhook Amazon SES
     */
    protected function handleSesWebhook(array $payload): bool
    {
        // SES envoie via SNS, décodage du message
        $message = json_decode($payload['Message'] ?? '{}', true);
        $notificationType = $message['notificationType'] ?? $payload['notificationType'] ?? null;
        $mail = $message['mail'] ?? [];
        $messageId = $mail['messageId'] ?? null;

        if (!$messageId) {
            return false;
        }

        $tracking = EmailTracking::where('provider_message_id', $messageId)->first();

        if (!$tracking) {
            return false;
        }

        switch ($notificationType) {
            case 'Delivery':
                $tracking->update(['delivered_at' => now(), 'status' => 'delivered']);
                break;
            case 'Bounce':
                $bounceType = $message['bounce']['bounceType'] ?? 'Permanent';
                $tracking->markAsBounced($bounceType === 'Permanent' ? 'hard' : 'soft');
                break;
            case 'Complaint':
                $tracking->markAsComplained();
                break;
        }

        return true;
    }

    /**
     * Obtenir les statistiques d'email pour un lead/prospect
     */
    public function getRecipientStats(string $type, int $id): array
    {
        $stats = EmailTracking::forRecipient($type, $id)
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN opened_at IS NOT NULL THEN 1 ELSE 0 END) as total_opened,
                SUM(CASE WHEN first_clicked_at IS NOT NULL THEN 1 ELSE 0 END) as total_clicked,
                SUM(CASE WHEN bounced_at IS NOT NULL THEN 1 ELSE 0 END) as total_bounced,
                SUM(open_count) as total_opens,
                SUM(click_count) as total_clicks
            ')
            ->first();

        return [
            'total_sent' => $stats->total_sent ?? 0,
            'total_opened' => $stats->total_opened ?? 0,
            'total_clicked' => $stats->total_clicked ?? 0,
            'total_bounced' => $stats->total_bounced ?? 0,
            'total_opens' => $stats->total_opens ?? 0,
            'total_clicks' => $stats->total_clicks ?? 0,
            'open_rate' => $stats->total_sent > 0
                ? round(($stats->total_opened / $stats->total_sent) * 100, 1)
                : 0,
            'click_rate' => $stats->total_opened > 0
                ? round(($stats->total_clicked / $stats->total_opened) * 100, 1)
                : 0,
        ];
    }
}
