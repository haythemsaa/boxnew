<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'description',
        'type',
        'priority',
        'is_active',
        'conditions',
        'adjustment_type',
        'adjustment_value',
        'min_price',
        'max_price',
        'valid_from',
        'valid_until',
        'stackable',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'stackable' => 'boolean',
        'priority' => 'integer',
        'adjustment_value' => 'decimal:2',
        'min_price' => 'decimal:2',
        'max_price' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('valid_from')
                    ->orWhereDate('valid_from', '<=', now());
            })
            ->where(function($q) {
                $q->whereNull('valid_until')
                    ->orWhereDate('valid_until', '>=', now());
            });
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Accessors
    public function getIsValidAttribute(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->valid_from && $now->isBefore($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->isAfter($this->valid_until)) {
            return false;
        }

        return true;
    }

    // Helper Methods
    public function apply(float $basePrice): float
    {
        if (!$this->is_valid) {
            return $basePrice;
        }

        $adjustedPrice = $basePrice;

        if ($this->adjustment_type === 'percentage') {
            $adjustedPrice += ($basePrice * ($this->adjustment_value / 100));
        } else {
            $adjustedPrice += $this->adjustment_value;
        }

        // Apply min/max constraints
        if ($this->min_price && $adjustedPrice < $this->min_price) {
            $adjustedPrice = $this->min_price;
        }

        if ($this->max_price && $adjustedPrice > $this->max_price) {
            $adjustedPrice = $this->max_price;
        }

        return $adjustedPrice;
    }

    public function matchesConditions(array $boxAttributes): bool
    {
        foreach ($this->conditions as $key => $value) {
            if (!isset($boxAttributes[$key]) || $boxAttributes[$key] != $value) {
                return false;
            }
        }

        return true;
    }
}
