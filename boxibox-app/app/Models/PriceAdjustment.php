<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceAdjustment extends Model
{
    protected $fillable = [
        'tenant_id',
        'box_id',
        'old_price',
        'new_price',
        'adjustment_percentage',
        'trigger',
        'trigger_details',
        'auto_applied',
        'approved_by',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'adjustment_percentage' => 'decimal:2',
        'trigger_details' => 'array',
        'auto_applied' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function scopeAutomatic($query)
    {
        return $query->where('auto_applied', true);
    }

    public function scopeManual($query)
    {
        return $query->where('auto_applied', false);
    }

    public function scopeByTrigger($query, string $trigger)
    {
        return $query->where('trigger', $trigger);
    }

    public function getIsIncreaseAttribute(): bool
    {
        return $this->new_price > $this->old_price;
    }

    public function getFormattedChangeAttribute(): string
    {
        $sign = $this->is_increase ? '+' : '';
        return $sign . number_format($this->adjustment_percentage, 1) . '%';
    }

    public static function getTriggers(): array
    {
        return [
            'occupancy' => 'Taux d\'occupation',
            'demand' => 'Demande',
            'competitor' => 'Prix concurrent',
            'seasonal' => 'Saisonnalité',
            'manual' => 'Manuel',
            'ml' => 'Algorithme ML',
            'ab_test' => 'Test A/B',
        ];
    }
}
