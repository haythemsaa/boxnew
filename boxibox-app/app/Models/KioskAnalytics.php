<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KioskAnalytics extends Model
{
    protected $table = 'kiosk_analytics';

    protected $fillable = [
        'tenant_id',
        'kiosk_id',
        'date',
        'total_sessions',
        'unique_users',
        'new_rentals',
        'payments_processed',
        'access_codes_generated',
        'avg_session_duration',
        'completion_rate',
        'abandonment_rate',
        'revenue',
        'peak_hour',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'total_sessions' => 'integer',
        'unique_users' => 'integer',
        'new_rentals' => 'integer',
        'payments_processed' => 'integer',
        'access_codes_generated' => 'integer',
        'avg_session_duration' => 'integer',
        'completion_rate' => 'decimal:2',
        'abandonment_rate' => 'decimal:2',
        'revenue' => 'decimal:2',
        'peak_hour' => 'integer',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function kiosk(): BelongsTo
    {
        return $this->belongsTo(KioskDevice::class, 'kiosk_id');
    }
}
