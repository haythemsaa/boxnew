<?php

namespace App\Services;

use App\Models\TenantSmsProvider;
use App\Models\SmsTracking;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Service d'envoi de SMS utilisant les providers configurés par le tenant
 *
 * Ce service :
 * 1. Récupère le provider par défaut du tenant
 * 2. Envoie le SMS via ce provider
 * 3. Crée un tracking pour suivre livraison/réponses
 * 4. Incrémente les compteurs d'utilisation
 */
class TenantSmsService
{
    /**
     * Envoyer un SMS via le provider du tenant
     */
    public function send(
        int $tenantId,
        string $toPhone,
        string $message,
        string $smsType = 'notification',
        string $recipientType = 'customer',
        ?int $recipientId = null,
        array $metadata = []
    ): ?SmsTracking {
        // Normaliser le numéro de téléphone
        $phone = $this->normalizePhone($toPhone);

        if (!$phone) {
            Log::error('Invalid phone number', ['phone' => $toPhone]);
            return null;
        }

        // Récupérer le provider par défaut du tenant
        $provider = TenantSmsProvider::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('is_verified', true)
            ->orderBy('is_default', 'desc')
            ->first();

        if (!$provider) {
            Log::warning('No verified SMS provider for tenant', ['tenant_id' => $tenantId]);
            return null;
        }

        // Vérifier les limites
        if (!$provider->canSend()) {
            Log::warning('SMS provider limit reached', [
                'provider_id' => $provider->id,
                'daily' => $provider->sms_sent_today,
                'monthly' => $provider->sms_sent_month,
            ]);
            return null;
        }

        // Créer le tracking
        $tracking = SmsTracking::create([
            'tenant_id' => $tenantId,
            'tracking_id' => Str::uuid()->toString(),
            'recipient_phone' => $phone,
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'sms_type' => $smsType,
            'message' => $message,
            'status' => 'pending',
            'provider' => $provider->provider,
            'metadata' => $metadata,
        ]);

        // Envoyer via le provider
        $result = $this->sendViaProvider($provider, $phone, $message);

        if ($result['success']) {
            $tracking->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider_message_id' => $result['message_id'] ?? null,
                'cost' => $result['cost'] ?? null,
            ]);

            // Incrémenter les compteurs
            $provider->incrementSentCount();

            // Déduire du solde si applicable
            if (isset($result['cost']) && $provider->balance !== null) {
                $provider->deductFromBalance($result['cost']);
            }

            Log::info('SMS sent successfully', [
                'tracking_id' => $tracking->tracking_id,
                'provider' => $provider->provider,
                'to' => $phone,
            ]);
        } else {
            $tracking->markAsFailed($result['error'] ?? 'Unknown error');

            Log::error('SMS send failed', [
                'provider' => $provider->provider,
                'error' => $result['error'],
            ]);
        }

        return $tracking;
    }

    /**
     * Envoyer via le provider configuré
     */
    protected function sendViaProvider(TenantSmsProvider $provider, string $phone, string $message): array
    {
        $config = $provider->decrypted_config;

        try {
            return match ($provider->provider) {
                'twilio' => $this->sendViaTwilio($provider, $config, $phone, $message),
                'vonage' => $this->sendViaVonage($provider, $config, $phone, $message),
                'plivo' => $this->sendViaPlivo($provider, $config, $phone, $message),
                'messagebird' => $this->sendViaMessagebird($provider, $config, $phone, $message),
                'ovh' => $this->sendViaOvh($provider, $config, $phone, $message),
                'clicksend' => $this->sendViaClicksend($provider, $config, $phone, $message),
                default => ['success' => false, 'error' => 'Provider non supporté: ' . $provider->provider],
            };
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    protected function sendViaTwilio(TenantSmsProvider $provider, array $config, string $phone, string $message): array
    {
        $accountSid = $config['account_sid'] ?? '';
        $authToken = $config['auth_token'] ?? '';

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $provider->sender_id,
                'To' => $phone,
                'Body' => $message,
                // Configurer le webhook pour les status updates
                'StatusCallback' => $provider->webhook_url,
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

    protected function sendViaVonage(TenantSmsProvider $provider, array $config, string $phone, string $message): array
    {
        $apiKey = $config['api_key'] ?? '';
        $apiSecret = $config['api_secret'] ?? '';

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $provider->sender_id,
            'to' => preg_replace('/[^0-9]/', '', $phone),
            'text' => $message,
            'callback' => $provider->webhook_url,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $messageData = $data['messages'][0] ?? [];

            if (($messageData['status'] ?? '1') === '0') {
                return [
                    'success' => true,
                    'message_id' => $messageData['message-id'],
                    'cost' => isset($messageData['message-price']) ? floatval($messageData['message-price']) : null,
                ];
            }

            return ['success' => false, 'error' => $messageData['error-text'] ?? 'Unknown error'];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function sendViaPlivo(TenantSmsProvider $provider, array $config, string $phone, string $message): array
    {
        $authId = $config['auth_id'] ?? '';
        $authToken = $config['auth_token'] ?? '';

        $response = Http::withBasicAuth($authId, $authToken)
            ->post("https://api.plivo.com/v1/Account/{$authId}/Message/", [
                'src' => $provider->sender_id,
                'dst' => preg_replace('/[^0-9]/', '', $phone),
                'text' => $message,
                'url' => $provider->webhook_url,
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

    protected function sendViaMessagebird(TenantSmsProvider $provider, array $config, string $phone, string $message): array
    {
        $apiKey = $config['api_key'] ?? '';

        $response = Http::withHeaders([
            'Authorization' => 'AccessKey ' . $apiKey,
        ])->post('https://rest.messagebird.com/messages', [
            'originator' => $provider->sender_id,
            'recipients' => [preg_replace('/[^0-9]/', '', $phone)],
            'body' => $message,
            'reportUrl' => $provider->webhook_url,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'message_id' => $data['id'],
            ];
        }

        return ['success' => false, 'error' => $response->json('errors')[0]['description'] ?? $response->body()];
    }

    protected function sendViaOvh(TenantSmsProvider $provider, array $config, string $phone, string $message): array
    {
        // OVH nécessite des signatures de requête complexes
        // Pour une implémentation complète, utiliser le SDK OVH
        // Ici une version simplifiée qui ne fonctionnera pas en production

        return ['success' => false, 'error' => 'OVH nécessite le SDK officiel. Installez ovh/ovh via composer.'];
    }

    protected function sendViaClicksend(TenantSmsProvider $provider, array $config, string $phone, string $message): array
    {
        $username = $config['username'] ?? '';
        $apiKey = $config['api_key'] ?? '';

        $response = Http::withBasicAuth($username, $apiKey)
            ->post('https://rest.clicksend.com/v3/sms/send', [
                'messages' => [[
                    'source' => 'boxibox',
                    'from' => $provider->sender_id,
                    'body' => $message,
                    'to' => $phone,
                    'custom_string' => $provider->webhook_url,
                ]],
            ]);

        if ($response->successful()) {
            $data = $response->json();
            $messageData = $data['data']['messages'][0] ?? [];

            if (($messageData['status'] ?? '') === 'SUCCESS') {
                return [
                    'success' => true,
                    'message_id' => $messageData['message_id'] ?? null,
                    'cost' => isset($messageData['message_price']) ? floatval($messageData['message_price']) : null,
                ];
            }

            return ['success' => false, 'error' => $messageData['status'] ?? 'Unknown error'];
        }

        return ['success' => false, 'error' => $response->body()];
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
     * Gérer un webhook de provider SMS
     */
    public function handleWebhook(string $provider, array $payload): bool
    {
        try {
            return match ($provider) {
                'twilio' => $this->handleTwilioWebhook($payload),
                'vonage' => $this->handleVonageWebhook($payload),
                'plivo' => $this->handlePlivoWebhook($payload),
                'messagebird' => $this->handleMessagebirdWebhook($payload),
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

    protected function handleTwilioWebhook(array $payload): bool
    {
        $messageSid = $payload['MessageSid'] ?? $payload['SmsSid'] ?? null;
        $status = $payload['MessageStatus'] ?? $payload['SmsStatus'] ?? null;

        // SMS entrant (réponse)
        if (isset($payload['Body']) && isset($payload['From'])) {
            return $this->handleInboundSms($payload['From'], $payload['Body'], $messageSid);
        }

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

    protected function handleVonageWebhook(array $payload): bool
    {
        // SMS entrant
        if (isset($payload['text']) && isset($payload['msisdn'])) {
            return $this->handleInboundSms($payload['msisdn'], $payload['text'], $payload['messageId'] ?? null);
        }

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
                $tracking->markAsFailed($payload['err-code'] ?? 'Unknown error');
                break;
        }

        return true;
    }

    protected function handlePlivoWebhook(array $payload): bool
    {
        $messageUuid = $payload['MessageUUID'] ?? null;
        $status = $payload['Status'] ?? null;

        if (!$messageUuid || !$status) {
            return false;
        }

        $tracking = SmsTracking::where('provider_message_id', $messageUuid)->first();
        if (!$tracking) {
            return false;
        }

        switch (strtolower($status)) {
            case 'delivered':
                $tracking->markAsDelivered();
                break;
            case 'failed':
            case 'undelivered':
                $tracking->markAsFailed($payload['ErrorCode'] ?? 'Unknown error');
                break;
        }

        return true;
    }

    protected function handleMessagebirdWebhook(array $payload): bool
    {
        $messageId = $payload['id'] ?? null;
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
            case 'delivery_failed':
                $tracking->markAsFailed($payload['statusReason'] ?? 'Delivery failed');
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

        Log::info('Inbound SMS without tracking', [
            'from' => $phone,
            'message' => $message,
        ]);

        return false;
    }

    /**
     * Obtenir les statistiques SMS du tenant
     */
    public function getTenantStats(int $tenantId): array
    {
        $smsProviders = TenantSmsProvider::where('tenant_id', $tenantId)->get();

        $totalSentToday = $smsProviders->sum('sms_sent_today');
        $totalSentMonth = $smsProviders->sum('sms_sent_month');

        $trackingStats = SmsTracking::where('tenant_id', $tenantId)
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->selectRaw('
                COUNT(*) as total_sent,
                SUM(CASE WHEN delivered_at IS NOT NULL THEN 1 ELSE 0 END) as total_delivered,
                SUM(CASE WHEN replied_at IS NOT NULL THEN 1 ELSE 0 END) as total_replied,
                SUM(CASE WHEN failed_at IS NOT NULL THEN 1 ELSE 0 END) as total_failed,
                SUM(COALESCE(cost, 0)) as total_cost
            ')
            ->first();

        return [
            'providers_count' => $smsProviders->count(),
            'active_providers' => $smsProviders->where('is_active', true)->count(),
            'verified_providers' => $smsProviders->where('is_verified', true)->count(),
            'total_sent_today' => $totalSentToday,
            'total_sent_month' => $totalSentMonth,
            'last_30_days' => [
                'sent' => $trackingStats->total_sent ?? 0,
                'delivered' => $trackingStats->total_delivered ?? 0,
                'replied' => $trackingStats->total_replied ?? 0,
                'failed' => $trackingStats->total_failed ?? 0,
                'cost' => $trackingStats->total_cost ?? 0,
                'delivery_rate' => $trackingStats->total_sent > 0
                    ? round(($trackingStats->total_delivered / $trackingStats->total_sent) * 100, 1)
                    : 0,
                'reply_rate' => $trackingStats->total_delivered > 0
                    ? round(($trackingStats->total_replied / $trackingStats->total_delivered) * 100, 1)
                    : 0,
            ],
        ];
    }
}
