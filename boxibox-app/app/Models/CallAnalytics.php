<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CallAnalytics extends Model
{
    protected $table = 'call_analytics';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'date',
        'source',
        'total_calls',
        'answered_calls',
        'missed_calls',
        'voicemail_calls',
        'avg_duration',
        'avg_wait_time',
        'conversions',
        'conversion_value',
        'unique_callers',
        'repeat_callers',
        'after_hours_calls',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'total_calls' => 'integer',
        'answered_calls' => 'integer',
        'missed_calls' => 'integer',
        'voicemail_calls' => 'integer',
        'avg_duration' => 'integer',
        'avg_wait_time' => 'integer',
        'conversions' => 'integer',
        'conversion_value' => 'decimal:2',
        'unique_callers' => 'integer',
        'repeat_callers' => 'integer',
        'after_hours_calls' => 'integer',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
