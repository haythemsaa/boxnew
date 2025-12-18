<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ScheduledReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'report_type',
        'custom_report_id',
        'frequency',
        'day_of_week',
        'day_of_month',
        'time',
        'recipients',
        'format',
        'filters',
        'is_active',
        'last_sent_at',
        'next_send_at',
        'send_count',
    ];

    protected $casts = [
        'custom_report_id' => 'integer',
        'day_of_week' => 'integer',
        'day_of_month' => 'integer',
        'recipients' => 'array',
        'filters' => 'array',
        'is_active' => 'boolean',
        'last_sent_at' => 'datetime',
        'next_send_at' => 'datetime',
        'send_count' => 'integer',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customReport(): BelongsTo
    {
        return $this->belongsTo(CustomReport::class);
    }

    public function history(): HasMany
    {
        return $this->hasMany(ReportHistory::class, 'scheduled_report_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_run_at', '<=', now());
    }

    public function calculateNextRunAt(): void
    {
        $next = match ($this->frequency) {
            'daily' => now()->addDay()->setTimeFromTimeString($this->time),
            'weekly' => now()->next($this->day_of_week)->setTimeFromTimeString($this->time),
            'monthly' => now()->addMonth()->setDay($this->day_of_month)->setTimeFromTimeString($this->time),
            default => now()->addDay(),
        };
        $this->next_run_at = $next;
    }

    public function getFrequencyLabelAttribute(): string
    {
        return match ($this->frequency) {
            'daily' => 'Quotidien',
            'weekly' => 'Hebdomadaire',
            'monthly' => 'Mensuel',
            default => $this->frequency,
        };
    }
}
