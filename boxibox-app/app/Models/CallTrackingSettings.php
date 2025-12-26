<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallTrackingSettings extends Model
{
    protected $table = 'call_tracking_settings';

    protected $fillable = [
        'tenant_id',
        'is_enabled',
        'provider',
        'api_key',
        'api_secret',
        'account_sid',
        'record_calls',
        'recording_retention_days',
        'transcribe_calls',
        'notify_missed_calls',
        'notify_after_hours',
        'notification_email',
        'notification_phone',
        'business_hours',
        'timezone',
        'enable_voicemail',
        'voicemail_greeting',
        'enable_sms_autoresponse',
        'sms_autoresponse_message',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'record_calls' => 'boolean',
        'transcribe_calls' => 'boolean',
        'notify_missed_calls' => 'boolean',
        'notify_after_hours' => 'boolean',
        'enable_voicemail' => 'boolean',
        'enable_sms_autoresponse' => 'boolean',
        'business_hours' => 'array',
        'recording_retention_days' => 'integer',
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
        'account_sid',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isConfigured(): bool
    {
        return $this->is_enabled && !empty($this->api_key);
    }

    public function isWithinBusinessHours(): bool
    {
        $now = now($this->timezone ?? 'Europe/Paris');
        $dayName = strtolower($now->format('l'));

        if (!isset($this->business_hours[$dayName])) {
            return false;
        }

        $hours = $this->business_hours[$dayName];
        if (empty($hours) || count($hours) < 2) {
            return false;
        }

        $openTime = $now->copy()->setTimeFromTimeString($hours[0]);
        $closeTime = $now->copy()->setTimeFromTimeString($hours[1]);

        return $now->between($openTime, $closeTime);
    }
}
