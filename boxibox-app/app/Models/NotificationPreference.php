<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'email_enabled',
        'sms_enabled',
        'push_enabled',
        // Invoice notifications
        'invoice_created_email',
        'invoice_created_sms',
        'invoice_created_push',
        // Payment notifications
        'payment_received_email',
        'payment_received_sms',
        'payment_received_push',
        // Payment reminders
        'payment_reminder_email',
        'payment_reminder_sms',
        'payment_reminder_push',
        // Contract notifications
        'contract_expiring_email',
        'contract_expiring_sms',
        'contract_expiring_push',
        // Access alerts
        'access_alert_email',
        'access_alert_sms',
        'access_alert_push',
        // IoT alerts
        'iot_alert_email',
        'iot_alert_sms',
        'iot_alert_push',
        // Booking notifications
        'booking_confirmed_email',
        'booking_confirmed_sms',
        'booking_confirmed_push',
        // Welcome
        'welcome_email',
        'welcome_sms',
        'welcome_push',
    ];

    protected $casts = [
        'email_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
        'push_enabled' => 'boolean',
        'invoice_created_email' => 'boolean',
        'invoice_created_sms' => 'boolean',
        'invoice_created_push' => 'boolean',
        'payment_received_email' => 'boolean',
        'payment_received_sms' => 'boolean',
        'payment_received_push' => 'boolean',
        'payment_reminder_email' => 'boolean',
        'payment_reminder_sms' => 'boolean',
        'payment_reminder_push' => 'boolean',
        'contract_expiring_email' => 'boolean',
        'contract_expiring_sms' => 'boolean',
        'contract_expiring_push' => 'boolean',
        'access_alert_email' => 'boolean',
        'access_alert_sms' => 'boolean',
        'access_alert_push' => 'boolean',
        'iot_alert_email' => 'boolean',
        'iot_alert_sms' => 'boolean',
        'iot_alert_push' => 'boolean',
        'booking_confirmed_email' => 'boolean',
        'booking_confirmed_sms' => 'boolean',
        'booking_confirmed_push' => 'boolean',
        'welcome_email' => 'boolean',
        'welcome_sms' => 'boolean',
        'welcome_push' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if a specific notification type should be sent via a channel
     */
    public function shouldNotify(string $type, string $channel): bool
    {
        $fieldName = "{$type}_{$channel}";

        // Check if channel is globally enabled
        $channelEnabled = $this->{"{$channel}_enabled"} ?? false;
        if (!$channelEnabled) {
            return false;
        }

        // Check if this specific type is enabled for this channel
        return $this->{$fieldName} ?? false;
    }
}
