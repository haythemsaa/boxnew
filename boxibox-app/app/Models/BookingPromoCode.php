<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPromoCode extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'code',
        'name',
        'description',
        'discount_type',
        'discount_value',
        'min_rental_amount',
        'min_rental_months',
        'max_uses',
        'uses_count',
        'valid_from',
        'valid_until',
        'is_active',
        'first_time_only',
        'applicable_box_types',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_rental_amount' => 'decimal:2',
        'valid_from' => 'date',
        'valid_until' => 'date',
        'is_active' => 'boolean',
        'first_time_only' => 'boolean',
        'applicable_box_types' => 'array',
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

    // Helpers
    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->max_uses && $this->uses_count >= $this->max_uses) {
            return false;
        }

        $now = now()->startOfDay();

        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $monthlyPrice, int $rentalMonths = 1): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->min_rental_amount && $monthlyPrice < $this->min_rental_amount) {
            return 0;
        }

        if ($this->min_rental_months && $rentalMonths < $this->min_rental_months) {
            return 0;
        }

        return match ($this->discount_type) {
            'percentage' => $monthlyPrice * ($this->discount_value / 100),
            'fixed' => min($this->discount_value, $monthlyPrice),
            'free_months' => $monthlyPrice * min($this->discount_value, $rentalMonths),
            default => 0,
        };
    }

    public function incrementUses(): void
    {
        $this->increment('uses_count');
    }

    public function getDiscountLabelAttribute(): string
    {
        return match ($this->discount_type) {
            'percentage' => "{$this->discount_value}%",
            'fixed' => number_format($this->discount_value, 2) . ' €',
            'free_months' => "{$this->discount_value} mois gratuit(s)",
            default => '',
        };
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        $now = now()->startOfDay();

        return $query->active()
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('uses_count', '<', 'max_uses');
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_from')
                    ->orWhere('valid_from', '<=', $now);
            })
            ->where(function ($q) use ($now) {
                $q->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', $now);
            });
    }

    public static function findValidCode(string $code, int $tenantId, ?int $siteId = null): ?self
    {
        return static::where('code', strtoupper($code))
            ->where('tenant_id', $tenantId)
            ->where(function ($q) use ($siteId) {
                $q->whereNull('site_id');
                if ($siteId) {
                    $q->orWhere('site_id', $siteId);
                }
            })
            ->valid()
            ->first();
    }
}
