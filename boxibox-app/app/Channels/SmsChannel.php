<?php

namespace App\Channels;

use App\Services\TenantSmsService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Canal SMS personnalisé pour Laravel Notifications
 *
 * Utilise le TenantSmsService pour envoyer des SMS via le provider
 * configuré par le tenant (Twilio, Vonage, etc.)
 */
class SmsChannel
{
    public function __construct(protected TenantSmsService $smsService)
    {
    }

    /**
     * Envoyer la notification via SMS
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        // Vérifier que la notification implémente toSms
        if (!method_exists($notification, 'toSms')) {
            Log::warning('Notification does not implement toSms method', [
                'notification' => get_class($notification),
            ]);
            return;
        }

        // Récupérer le message SMS
        $message = $notification->toSms($notifiable);

        if (!$message) {
            return;
        }

        // Récupérer le numéro de téléphone
        $phone = $this->getPhoneNumber($notifiable);

        if (!$phone) {
            Log::warning('Cannot send SMS: no phone number', [
                'notifiable' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
            ]);
            return;
        }

        // Récupérer le tenant_id
        $tenantId = $this->getTenantId($notifiable, $notification);

        if (!$tenantId) {
            Log::warning('Cannot send SMS: no tenant_id', [
                'notifiable' => get_class($notifiable),
            ]);
            return;
        }

        // Récupérer les métadonnées
        $smsType = method_exists($notification, 'getSmsType')
            ? $notification->getSmsType()
            : 'notification';

        $metadata = method_exists($notification, 'getSmsMetadata')
            ? $notification->getSmsMetadata()
            : [];

        // Envoyer le SMS
        $tracking = $this->smsService->send(
            tenantId: $tenantId,
            toPhone: $phone,
            message: $message,
            smsType: $smsType,
            recipientType: $this->getRecipientType($notifiable),
            recipientId: $notifiable->id ?? null,
            metadata: $metadata
        );

        if ($tracking) {
            Log::info('SMS notification sent', [
                'tracking_id' => $tracking->tracking_id,
                'notification' => get_class($notification),
                'phone' => substr($phone, 0, 6) . '****',
            ]);
        }
    }

    /**
     * Récupérer le numéro de téléphone du notifiable
     */
    protected function getPhoneNumber(mixed $notifiable): ?string
    {
        // Méthode préférée: routeNotificationForSms
        if (method_exists($notifiable, 'routeNotificationForSms')) {
            return $notifiable->routeNotificationForSms();
        }

        // Fallback: propriété mobile ou phone
        return $notifiable->mobile ?? $notifiable->phone ?? null;
    }

    /**
     * Récupérer le tenant_id
     */
    protected function getTenantId(mixed $notifiable, Notification $notification): ?int
    {
        // Depuis la notification si disponible
        if (property_exists($notification, 'tenantId')) {
            return $notification->tenantId;
        }

        // Depuis le notifiable
        return $notifiable->tenant_id ?? null;
    }

    /**
     * Déterminer le type de destinataire
     */
    protected function getRecipientType(mixed $notifiable): string
    {
        $class = get_class($notifiable);

        return match (true) {
            str_contains($class, 'Customer') => 'customer',
            str_contains($class, 'Lead') => 'lead',
            str_contains($class, 'Prospect') => 'prospect',
            str_contains($class, 'User') => 'user',
            default => 'other',
        };
    }
}
