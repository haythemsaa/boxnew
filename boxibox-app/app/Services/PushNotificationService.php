<?php

namespace App\Services;

use App\Models\PushSubscription;
use App\Models\PushNotificationLog;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class PushNotificationService
{
    /**
     * VAPID keys for Web Push
     */
    private string $publicKey;
    private string $privateKey;
    private string $subject;

    public function __construct()
    {
        $this->publicKey = config('services.webpush.public_key', '');
        $this->privateKey = config('services.webpush.private_key', '');
        $this->subject = config('services.webpush.subject', 'mailto:contact@boxibox.fr');
    }

    /**
     * Subscribe a user to push notifications.
     */
    public function subscribe(User $user, array $subscriptionData): PushSubscription
    {
        // Check if subscription already exists
        $existing = PushSubscription::where('endpoint', $subscriptionData['endpoint'])->first();

        if ($existing) {
            $existing->update([
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id,
                'p256dh_key' => $subscriptionData['keys']['p256dh'],
                'auth_token' => $subscriptionData['keys']['auth'],
                'content_encoding' => $subscriptionData['contentEncoding'] ?? 'aesgcm',
                'device_type' => $subscriptionData['deviceType'] ?? null,
                'browser' => $subscriptionData['browser'] ?? null,
                'os' => $subscriptionData['os'] ?? null,
                'is_active' => true,
            ]);
            return $existing;
        }

        return PushSubscription::create([
            'user_id' => $user->id,
            'tenant_id' => $user->tenant_id,
            'endpoint' => $subscriptionData['endpoint'],
            'p256dh_key' => $subscriptionData['keys']['p256dh'],
            'auth_token' => $subscriptionData['keys']['auth'],
            'content_encoding' => $subscriptionData['contentEncoding'] ?? 'aesgcm',
            'device_type' => $subscriptionData['deviceType'] ?? null,
            'browser' => $subscriptionData['browser'] ?? null,
            'os' => $subscriptionData['os'] ?? null,
            'is_active' => true,
        ]);
    }

    /**
     * Unsubscribe from push notifications.
     */
    public function unsubscribe(string $endpoint): bool
    {
        $subscription = PushSubscription::where('endpoint', $endpoint)->first();

        if ($subscription) {
            $subscription->deactivate();
            return true;
        }

        return false;
    }

    /**
     * Send notification to a specific user.
     */
    public function sendToUser(User $user, array $notification): int
    {
        $subscriptions = PushSubscription::where('user_id', $user->id)
            ->active()
            ->get();

        return $this->sendToSubscriptions($subscriptions, $notification, $user->tenant_id, $user->id);
    }

    /**
     * Send notification to all users of a tenant.
     */
    public function sendToTenant(Tenant $tenant, array $notification): int
    {
        $subscriptions = PushSubscription::where('tenant_id', $tenant->id)
            ->active()
            ->get();

        return $this->sendToSubscriptions($subscriptions, $notification, $tenant->id);
    }

    /**
     * Send notification to specific role within a tenant.
     */
    public function sendToRole(Tenant $tenant, string $role, array $notification): int
    {
        $userIds = User::where('tenant_id', $tenant->id)
            ->role($role)
            ->pluck('id');

        $subscriptions = PushSubscription::whereIn('user_id', $userIds)
            ->active()
            ->get();

        return $this->sendToSubscriptions($subscriptions, $notification, $tenant->id);
    }

    /**
     * Send notification to all subscriptions.
     */
    public function broadcast(array $notification): int
    {
        $subscriptions = PushSubscription::active()->get();
        return $this->sendToSubscriptions($subscriptions, $notification);
    }

    /**
     * Send notification to a collection of subscriptions.
     */
    protected function sendToSubscriptions(Collection $subscriptions, array $notification, ?int $tenantId = null, ?int $userId = null): int
    {
        if ($subscriptions->isEmpty()) {
            return 0;
        }

        // Log the notification
        $log = PushNotificationLog::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $notification['type'] ?? PushNotificationLog::TYPE_SYSTEM,
            'title' => $notification['title'],
            'body' => $notification['body'],
            'data' => $notification['data'] ?? null,
            'status' => PushNotificationLog::STATUS_PENDING,
            'recipients_count' => $subscriptions->count(),
        ]);

        $deliveredCount = 0;
        $failedEndpoints = [];

        foreach ($subscriptions as $subscription) {
            try {
                $success = $this->sendPushMessage($subscription, $notification);

                if ($success) {
                    $deliveredCount++;
                    $subscription->markAsUsed();
                } else {
                    $failedEndpoints[] = $subscription->endpoint;
                }
            } catch (\Exception $e) {
                Log::error('Push notification failed', [
                    'endpoint' => $subscription->endpoint,
                    'error' => $e->getMessage(),
                ]);
                $failedEndpoints[] = $subscription->endpoint;

                // Deactivate subscription if it's invalid
                if ($this->isInvalidSubscriptionError($e)) {
                    $subscription->deactivate();
                }
            }
        }

        // Update log
        if ($deliveredCount > 0) {
            $log->markAsSent($deliveredCount);
        } else {
            $log->markAsFailed();
        }

        return $deliveredCount;
    }

    /**
     * Send a push message to a single subscription.
     */
    protected function sendPushMessage(PushSubscription $subscription, array $notification): bool
    {
        // If VAPID keys are not configured, simulate success in development
        if (empty($this->publicKey) || empty($this->privateKey)) {
            Log::info('Push notification simulated (VAPID keys not configured)', [
                'endpoint' => substr($subscription->endpoint, 0, 50) . '...',
                'notification' => $notification,
            ]);
            return true;
        }

        $payload = json_encode([
            'title' => $notification['title'],
            'body' => $notification['body'],
            'icon' => $notification['icon'] ?? '/images/icons/icon-192x192.png',
            'badge' => $notification['badge'] ?? '/images/icons/badge-72x72.png',
            'url' => $notification['url'] ?? '/tenant/dashboard',
            'actions' => $notification['actions'] ?? [
                ['action' => 'open', 'title' => 'Ouvrir'],
                ['action' => 'close', 'title' => 'Fermer'],
            ],
            'data' => $notification['data'] ?? [],
            'tag' => $notification['tag'] ?? null,
            'requireInteraction' => $notification['requireInteraction'] ?? false,
        ]);

        // Use Web Push library if available
        if (class_exists('\Minishlink\WebPush\WebPush')) {
            return $this->sendWithWebPushLibrary($subscription, $payload);
        }

        // Fallback to cURL implementation
        return $this->sendWithCurl($subscription, $payload);
    }

    /**
     * Send using Minishlink WebPush library.
     */
    protected function sendWithWebPushLibrary(PushSubscription $subscription, string $payload): bool
    {
        $webPush = new \Minishlink\WebPush\WebPush([
            'VAPID' => [
                'subject' => $this->subject,
                'publicKey' => $this->publicKey,
                'privateKey' => $this->privateKey,
            ],
        ]);

        $pushSubscription = \Minishlink\WebPush\Subscription::create($subscription->toWebPushSubscription());

        $report = $webPush->sendOneNotification($pushSubscription, $payload);

        return $report->isSuccess();
    }

    /**
     * Send using cURL (fallback).
     */
    protected function sendWithCurl(PushSubscription $subscription, string $payload): bool
    {
        // This is a simplified implementation
        // For production, use the WebPush library

        $headers = [
            'Content-Type: application/octet-stream',
            'Content-Encoding: aesgcm',
            'TTL: 86400',
        ];

        $ch = curl_init($subscription->endpoint);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * Check if error indicates an invalid subscription.
     */
    protected function isInvalidSubscriptionError(\Exception $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, '410') ||
               str_contains($message, '404') ||
               str_contains($message, 'expired') ||
               str_contains($message, 'invalid');
    }

    /**
     * Get VAPID public key for client.
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Create notification for payment reminder.
     */
    public static function paymentReminderNotification(string $customerName, float $amount, string $dueDate): array
    {
        return [
            'type' => PushNotificationLog::TYPE_PAYMENT,
            'title' => 'Rappel de paiement',
            'body' => "Facture de {$customerName} ({$amount}€) arrive a echeance le {$dueDate}",
            'icon' => '/images/icons/payment-icon.png',
            'url' => '/tenant/invoices?status=pending',
            'tag' => 'payment-reminder',
        ];
    }

    /**
     * Create notification for contract expiry.
     */
    public static function contractExpiryNotification(string $customerName, string $boxName, int $daysLeft): array
    {
        return [
            'type' => PushNotificationLog::TYPE_CONTRACT,
            'title' => 'Contrat expirant',
            'body' => "Le contrat de {$customerName} ({$boxName}) expire dans {$daysLeft} jours",
            'icon' => '/images/icons/contract-icon.png',
            'url' => '/tenant/contracts?filter=expiring',
            'tag' => 'contract-expiry',
        ];
    }

    /**
     * Create notification for new booking.
     */
    public static function newBookingNotification(string $customerName, string $boxType): array
    {
        return [
            'type' => PushNotificationLog::TYPE_ALERT,
            'title' => 'Nouvelle reservation !',
            'body' => "{$customerName} a reserve une box {$boxType}",
            'icon' => '/images/icons/booking-icon.png',
            'url' => '/tenant/bookings',
            'tag' => 'new-booking',
            'requireInteraction' => true,
        ];
    }

    /**
     * Create notification for IoT alert.
     */
    public static function iotAlertNotification(string $boxName, string $alertType, string $value): array
    {
        return [
            'type' => PushNotificationLog::TYPE_IOT,
            'title' => 'Alerte capteur IoT',
            'body' => "Box {$boxName}: {$alertType} - {$value}",
            'icon' => '/images/icons/iot-icon.png',
            'url' => '/tenant/iot/alerts',
            'tag' => 'iot-alert',
            'requireInteraction' => true,
        ];
    }

    /**
     * Create notification for overdue invoice.
     */
    public static function overdueInvoiceNotification(string $customerName, float $amount, int $daysOverdue): array
    {
        return [
            'type' => PushNotificationLog::TYPE_PAYMENT,
            'title' => 'Facture en retard',
            'body' => "Facture de {$customerName} ({$amount}€) en retard de {$daysOverdue} jours",
            'icon' => '/images/icons/warning-icon.png',
            'url' => '/tenant/invoices?status=overdue',
            'tag' => 'overdue-invoice',
            'requireInteraction' => true,
        ];
    }
}
