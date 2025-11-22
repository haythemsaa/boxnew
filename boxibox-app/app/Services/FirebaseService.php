<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\PushNotificationToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseService
{
    private string $fcmUrl = 'https://fcm.googleapis.com/fcm/send';
    private ?string $serverKey;

    public function __construct()
    {
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * Send notification to a specific customer
     */
    public function sendToCustomer(Customer $customer, array $notification, ?array $data = null): array
    {
        if (!$this->serverKey) {
            Log::warning('Firebase server key not configured');
            return [
                'success' => false,
                'message' => 'Firebase not configured',
                'sent' => 0,
            ];
        }

        // Get all active tokens for the customer
        $tokens = PushNotificationToken::forCustomer($customer->id)
            ->active()
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return [
                'success' => false,
                'message' => 'No active tokens found',
                'sent' => 0,
            ];
        }

        return $this->sendToTokens($tokens, $notification, $data);
    }

    /**
     * Send notification to multiple tokens
     */
    public function sendToTokens(array $tokens, array $notification, ?array $data = null): array
    {
        if (!$this->serverKey) {
            return [
                'success' => false,
                'message' => 'Firebase not configured',
                'sent' => 0,
            ];
        }

        $payload = [
            'registration_ids' => $tokens,
            'notification' => [
                'title' => $notification['title'] ?? 'BoxiBox',
                'body' => $notification['body'] ?? '',
                'sound' => 'default',
                'badge' => $notification['badge'] ?? 1,
            ],
            'priority' => 'high',
        ];

        // Add custom data if provided
        if ($data) {
            $payload['data'] = $data;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'key=' . $this->serverKey,
                'Content-Type' => 'application/json',
            ])->post($this->fcmUrl, $payload);

            $result = $response->json();

            if ($response->successful()) {
                $successCount = $result['success'] ?? 0;
                $failureCount = $result['failure'] ?? 0;

                // Handle invalid tokens
                if (!empty($result['results'])) {
                    $this->handleInvalidTokens($tokens, $result['results']);
                }

                return [
                    'success' => true,
                    'message' => "Sent to {$successCount} device(s)",
                    'sent' => $successCount,
                    'failed' => $failureCount,
                    'details' => $result,
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to send notification',
                'error' => $result,
                'sent' => 0,
            ];
        } catch (\Exception $e) {
            Log::error('Firebase notification error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
                'sent' => 0,
            ];
        }
    }

    /**
     * Send notification to a single token
     */
    public function sendToToken(string $token, array $notification, ?array $data = null): array
    {
        return $this->sendToTokens([$token], $notification, $data);
    }

    /**
     * Handle invalid tokens by deactivating them
     */
    private function handleInvalidTokens(array $tokens, array $results): void
    {
        foreach ($results as $index => $result) {
            if (isset($result['error'])) {
                $error = $result['error'];
                $token = $tokens[$index] ?? null;

                // Deactivate token if it's invalid or not registered
                if (in_array($error, ['InvalidRegistration', 'NotRegistered', 'MismatchSenderId'])) {
                    if ($token) {
                        PushNotificationToken::where('token', $token)
                            ->update(['is_active' => false]);

                        Log::info("Deactivated invalid token: {$token}");
                    }
                }
            }
        }
    }

    /**
     * Send payment reminder notification
     */
    public function sendPaymentReminder(Customer $customer, array $invoice): array
    {
        // Check notification preferences
        $preferences = $customer->notification_preferences ?? [];
        if (isset($preferences['payment_reminders']) && !$preferences['payment_reminders']) {
            return [
                'success' => false,
                'message' => 'Payment reminders disabled',
                'sent' => 0,
            ];
        }

        $notification = [
            'title' => 'Rappel de paiement',
            'body' => "Votre facture #{$invoice['number']} de {$invoice['amount']}€ est due le {$invoice['due_date']}",
        ];

        $data = [
            'type' => 'payment_reminder',
            'invoice_id' => (string)$invoice['id'],
            'screen' => 'InvoiceDetails',
        ];

        return $this->sendToCustomer($customer, $notification, $data);
    }

    /**
     * Send contract expiration notification
     */
    public function sendContractExpiration(Customer $customer, array $contract, int $daysUntilExpiry): array
    {
        $preferences = $customer->notification_preferences ?? [];
        if (isset($preferences['contract_updates']) && !$preferences['contract_updates']) {
            return [
                'success' => false,
                'message' => 'Contract updates disabled',
                'sent' => 0,
            ];
        }

        $notification = [
            'title' => 'Contrat arrivant à expiration',
            'body' => "Votre contrat pour le box {$contract['box_name']} expire dans {$daysUntilExpiry} jours",
        ];

        $data = [
            'type' => 'contract_expiration',
            'contract_id' => (string)$contract['id'],
            'screen' => 'ContractDetails',
        ];

        return $this->sendToCustomer($customer, $notification, $data);
    }

    /**
     * Send promotion notification
     */
    public function sendPromotion(Customer $customer, array $promotion): array
    {
        $preferences = $customer->notification_preferences ?? [];
        if (isset($preferences['promotions']) && !$preferences['promotions']) {
            return [
                'success' => false,
                'message' => 'Promotions disabled',
                'sent' => 0,
            ];
        }

        $notification = [
            'title' => $promotion['title'] ?? 'Promotion BoxiBox',
            'body' => $promotion['description'] ?? 'Profitez d\'une offre spéciale !',
        ];

        $data = [
            'type' => 'promotion',
            'promotion_id' => (string)($promotion['id'] ?? ''),
            'screen' => 'Promotions',
        ];

        return $this->sendToCustomer($customer, $notification, $data);
    }

    /**
     * Send maintenance alert
     */
    public function sendMaintenanceAlert(Customer $customer, string $message): array
    {
        $preferences = $customer->notification_preferences ?? [];
        if (isset($preferences['maintenance_alerts']) && !$preferences['maintenance_alerts']) {
            return [
                'success' => false,
                'message' => 'Maintenance alerts disabled',
                'sent' => 0,
            ];
        }

        $notification = [
            'title' => 'Alerte de maintenance',
            'body' => $message,
        ];

        $data = [
            'type' => 'maintenance',
            'screen' => 'Issues',
        ];

        return $this->sendToCustomer($customer, $notification, $data);
    }
}
