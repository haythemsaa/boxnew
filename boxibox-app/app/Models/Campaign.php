<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'type',
        'status',
        'target_segment',
        'content',
        'scheduled_at',
        'started_at',
        'completed_at',
        'total_recipients',
        'sent_count',
        'delivered_count',
        'opened_count',
        'clicked_count',
        'converted_count',
        'conversion_rate',
    ];

    protected $casts = [
        'target_segment' => 'array',
        'content' => 'array',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'conversion_rate' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());
    }

    public function calculateConversionRate(): void
    {
        if ($this->sent_count > 0) {
            $this->conversion_rate = ($this->converted_count / $this->sent_count) * 100;
            $this->save();
        }
    }

    public function getOpenRateAttribute(): float
    {
        return $this->delivered_count > 0
            ? ($this->opened_count / $this->delivered_count) * 100
            : 0;
    }

    public function getClickRateAttribute(): float
    {
        return $this->opened_count > 0
            ? ($this->clicked_count / $this->opened_count) * 100
            : 0;
    }
}
