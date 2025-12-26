<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleReserveSettings extends Model
{
    protected $table = 'google_reserve_settings';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'is_enabled',
        'merchant_id',
        'api_key',
        'available_days',
        'opening_time',
        'closing_time',
        'slot_duration_minutes',
        'buffer_time_minutes',
        'max_advance_days',
        'min_advance_hours',
        'auto_confirm',
        'send_reminders',
        'reminder_hours_before',
        'cancellation_policy',
        'metadata',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'available_days' => 'array',
        'slot_duration_minutes' => 'integer',
        'buffer_time_minutes' => 'integer',
        'max_advance_days' => 'integer',
        'min_advance_hours' => 'integer',
        'auto_confirm' => 'boolean',
        'send_reminders' => 'boolean',
        'reminder_hours_before' => 'integer',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function isConfigured(): bool
    {
        return $this->is_enabled && !empty($this->merchant_id);
    }
}
