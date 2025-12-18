<?php

namespace App\Services;

use App\Models\SmsTracking;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Service pour le tracking et l'envoi de SMS
 *
 * COMMENT CA MARCHE :
 *
 * 1. ENVOI SMS
 *    - On utilise un provider (Twilio, Vonage, OVH SMS, etc.)
 *    - Le provider retourne un message_id
 *
 * 2. DELIVERY REPORT (livraison)
 *    - Le provider envoie un webhook quand le SMS est livré/échoué
 *    - On met à jour le statut
 *
 * 3. RÉPONSE SMS (inbound)
 *    - Si le client répond au SMS, le provider envoie un webhook
 *    - On enregistre la réponse
 *    - On met à jour les métadonnées du lead pour l'AI Scoring
 *
 * PROVIDERS SUPPORTÉS :
 * - Twilio (mondial)
 * - Vonage/Nexmo (mondial)
 * - OVH SMS (France)
 * - Plivo (mondial)
 */
class SmsTrackingService
{
    protected ?string $provider;
    protected array $config;

    public function __construct()
    {
        $this->provider = config('services.sms.provider', 'twilio');
        $this->config = config('services.sms', []);
    }

    /**
     * Envoyer un SMS avec tracking
     */
    public function send(
        int $tenantId,
        string $recipientPhone,
        string $message,
        string $smsType = 'notification',
        string $recipientType = 'lead',
        ?int $recipientId = null,
        array $metadata = []
    ): ?SmsTracking {
        // Normaliser le numéro de téléphone
        $phone = $this->normalizePhone($recipientPhone);

        if (!$phone) {
            Log::error('Invalid phone number', ['phone' => $recipientPhone]);
            return null;
        }

        // Créer l'enregistrement de tracking
        $tracking = SmsTracking::create([
            'tenant_id' => $tenantId,
            'tracking_id' => Str::uuid()->toString(),
            'recipient_phone' => $phone,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'sms_type' => $smsType,
            'message' => $message,
            'status' => 'pending',
            'provider' => $this->provider,
            'metadata' => $metadata,
        ]);

        // Envoyer via le provider
        $result = $this->sendViaProvider($phone, $message);

        if ($result['success']) {
            $tracking->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider_message_id' => $result['message_id'] ?? null,
                'cost' => $result['cost'] ?? null,
            ]);
        } else {
            $tracking->markAsFailed($result['error'] ?? 'Unknown error');
        }

        return $tracking;
    }

    /**
     * Envoyer via le provider configuré
     */
    protected function sendViaProvider(string $phone, string $message): array
    {
        try {
            return match ($this->provider) {
                'twilio' => $this->sendViaTwilio($phone, $message),
                'vonage', 'nexmo' => $this->sendViaVonage($phone, $message),
                'ovh' => $this->sendViaOvh($phone, $message),
                'plivo' => $this->sendViaPlivo($phone, $message),
                'log', 'debug' => $this->sendViaLog($phone, $message),
                default => ['success' => false, 'error' => 'Unknown provider: ' . $this->provider],
            };
        } catch (\Exception $e) {
            Log::error('SMS send failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Envoyer via Twilio
     */
    protected function sendViaTwilio(string $phone, string $message): array
    {
        $accountSid = $this->config['twilio']['account_sid'] ?? env('TWILIO_ACCOUNT_SID');
        $authToken = $this->config['twilio']['auth_token'] ?? env('TWILIO_AUTH_TOKEN');
        $fromNumber = $this->config['twilio']['from'] ?? env('TWILIO_FROM_NUMBER');

        if (!$accountSid || !$authToken || !$fromNumber) {
            return ['success' => false, 'error' => 'Twilio not configured'];
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
                'cost' => $data['price'] ?? null,
            ];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    /**
     * Envoyer via Vonage (ex-Nexmo)
     */
    protected function sendViaVonage(string $phone, string $message): array
    {
        $apiKey = $this->config['vonage']['api_key'] ?? env('VONAGE_API_KEY');
        $apiSecret = $this->config['vonage']['api_secret'] ?? env('VONAGE_API_SECRET');
        $fromName = $this->config['vonage']['from'] ?? env('VONAGE_FROM', 'BoxiBox');

        if (!$apiKey || !$apiSecret) {
            return ['success' => false, 'error' => 'Vonage not configured'];
        }

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $fromName,
            'to' => $phone,
            'text' => $message,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $messageData = $data['messages'][0] ?? [];

            if (($messageData['status'] ?? '1') === '0') {
                return [
                    'success' => true,
                    'message_id' => $messageData['message-id'],
                    'cost' => $messageData['message-price'] ?? null,
                ];
            }

            return ['success' => false, 'error' => $messageData['error-text'] ?? 'Unknown error'];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    /**
     * Envoyer via OVH SMS (France)
     */
    protected function sendViaOvh(string $phone, string $message): array
    {
        $appKey = $this->config['ovh']['app_key'] ?? env('OVH_APP_KEY');
        $appSecret = $this->config['ovh']['app_secret'] ?? env('OVH_APP_SECRET');
        $consumerKey = $this->config['ovh']['consumer_key'] ?? env('OVH_CONSUMER_KEY');
        $serviceName = $this->config['ovh']['service_name'] ?? env('OVH_SMS_SERVICE');
        $sender = $this->config['ovh']['sender'] ?? env('OVH_SMS_SENDER', 'BoxiBox');

        if (!$appKey || !$appSecret || !$consumerKey || !$serviceName) {
            return ['success' => false, 'error' => 'OVH SMS not configured'];
        }

        // OVH requires signed requests - simplified example
        // In production, use the official OVH PHP SDK
        return ['success' => false, 'error' => 'OVH requires SDK integration'];
    }

    /**
     * Envoyer via Plivo
     */
    protected function sendViaPlivo(string $phone, string $message): array
    {
        $authId = $this->config['plivo']['auth_id'] ?? env('PLIVO_AUTH_ID');
        $authToken = $this->config['plivo']['auth_token'] ?? env('PLIVO_AUTH_TOKEN');
        $fromNumber = $this->config['plivo']['from'] ?? env('PLIVO_FROM_NUMBER');

        if (!$authId || !$authToken || !$fromNumber) {
            return ['success' => false, 'error' => 'Plivo not configured'];
        }

        $response = Http::withBasicAuth($authId, $authToken)
            ->post("https://api.plivo.com/v1/Account/{$authId}/Message/", [
                'src' => $fromNumber,
                'dst' => $phone,
                'text' => $message,
            ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'message_id' => $data['message_uuid'][0] ?? null,
            ];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    /**
     * Mode debug - log seulement (pour dev)
     */
    protected function sendViaLog(string $phone, string $message): array
    {
        Log::info('SMS (DEBUG MODE)', [
            'to' => $phone,
            'message' => $message,
        ]);

        return [
            'success' => true,
            'message_id' => 'debug-' . Str::uuid()->toString(),
        ];
    }

    /**
     * Gérer un webhook de provider SMS
     */
    public function handleProviderWebhook(string $provider, array $payload): bool
    {
        try {
            return match ($provider) {
                'twilio' => $this->handleTwilioWebhook($payload),
                'vonage', 'nexmo' => $this->handleVonageWebhook($payload),
                default => false,
            };
        } catch (\Exception $e) {
            Log::error('SMS webhook handling failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Webhook Twilio - Delivery Report & Inbound SMS
     */
    protected function handleTwilioWebhook(array $payload): bool
    {
        $messageSid = $payload['MessageSid'] ?? $payload['SmsSid'] ?? null;
        $status = $payload['MessageStatus'] ?? $payload['SmsStatus'] ?? null;

        // Inbound SMS (réponse)
        if (isset($payload['Body']) && isset($payload['From'])) {
            return $this->handleInboundSms(
                $payload['From'],
                $payload['Body'],
                $messageSid
            );
        }

        // Delivery report
        if (!$messageSid || !$status) {
            return false;
        }

        $tracking = SmsTracking::where('provider_message_id', $messageSid)->first();

        if (!$tracking) {
            return false;
        }

        switch ($status) {
            case 'delivered':
                $tracking->markAsDelivered();
                break;
            case 'failed':
            case 'undelivered':
                $errorCode = $payload['ErrorCode'] ?? 'Unknown';
                $tracking->markAsFailed("Error code: {$errorCode}");
                break;
        }

        return true;
    }

    /**
     * Webhook Vonage - Delivery Report & Inbound SMS
     */
    protected function handleVonageWebhook(array $payload): bool
    {
        // Inbound SMS
        if (isset($payload['text']) && isset($payload['msisdn'])) {
            return $this->handleInboundSms(
                $payload['msisdn'],
                $payload['text'],
                $payload['messageId'] ?? null
            );
        }

        // Delivery report
        $messageId = $payload['messageId'] ?? $payload['message-id'] ?? null;
        $status = $payload['status'] ?? null;

        if (!$messageId || !$status) {
            return false;
        }

        $tracking = SmsTracking::where('provider_message_id', $messageId)->first();

        if (!$tracking) {
            return false;
        }

        switch ($status) {
            case 'delivered':
                $tracking->markAsDelivered();
                break;
            case 'failed':
            case 'rejected':
            case 'expired':
                $errorText = $payload['err-code'] ?? 'Unknown error';
                $tracking->markAsFailed($errorText);
                break;
        }

        return true;
    }

    /**
     * Gérer un SMS entrant (réponse du client)
     */
    protected function handleInboundSms(string $from, string $message, ?string $messageId): bool
    {
        $phone = $this->normalizePhone($from);

        // Trouver le dernier SMS envoyé à ce numéro
        $tracking = SmsTracking::where('recipient_phone', $phone)
            ->where('status', '!=', 'failed')
            ->orderBy('sent_at', 'desc')
            ->first();

        if ($tracking) {
            $tracking->markAsReplied($message);

            Log::info('SMS reply received', [
                'from' => $phone,
                'message' => $message,
                'original_tracking_id' => $tracking->tracking_id,
            ]);

            return true;
        }

        // SMS entrant sans tracking associé - log quand même
        Log::info('Inbound SMS without tracking', [
            'from' => $phone,
            'message' => $message,
        ]);

        return false;
    }

    /**
     * Normaliser un numéro de téléphone au format E.164
     */
    protected function normalizePhone(string $phone): ?string
    {
        // Supprimer tout sauf les chiffres et le +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Si commence par 0 (format français), convertir en +33
        if (preg_match('/^0[67]/', $phone)) {
            $phone = '+33' . substr($phone, 1);
        }

        // Si pas de +, supposer français
        if (!str_starts_with($phone, '+')) {
            if (strlen($phone) === 9 && preg_match('/^[67]/', $phone)) {
                $phone = '+33' . $phone;
            } elseif (strlen($phone) === 10) {
                $phone = '+33' . substr($phone, 1);
            }
        }

        // Valider le format E.164
        if (!preg_match('/^\+[1-9]\d{6,14}$/', $phone)) {
            return null;
        }

        return $phone;
    }

    /**
     * Obtenir les statistiques SMS pour un lead/prospect
     */
    public function getRecipientStats(string $type, int $id): array
    {
        $stats = SmsTracking::forRecipient($type, $id)
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) as total_delivered,
                SUM(CASE WHEN replied_at IS NOT NULL THEN 1 ELSE 0 END) as total_replied,
                SUM(CASE WHEN failed_at IS NOT NULL THEN 1 ELSE 0 END) as total_failed,
                SUM(COALESCE(cost, 0)) as total_cost
            ')
            ->first();

        return [
            'total_sent' => $stats->total_sent ?? 0,
            'total_delivered' => $stats->total_delivered ?? 0,
            'total_replied' => $stats->total_replied ?? 0,
            'total_failed' => $stats->total_failed ?? 0,
            'total_cost' => $stats->total_cost ?? 0,
            'delivery_rate' => $stats->total_sent > 0
                ? round(($stats->total_delivered / $stats->total_sent) * 100, 1)
                : 0,
            'reply_rate' => $stats->total_delivered > 0
                ? round(($stats->total_replied / $stats->total_delivered) * 100, 1)
                : 0,
        ];
    }
}
