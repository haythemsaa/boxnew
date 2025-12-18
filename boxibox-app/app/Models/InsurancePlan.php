<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsurancePlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'name',
        'code',
        'description',
        'coverage_amount',
        'covered_risks',
        'exclusions',
        'pricing_type',
        'price_monthly',
        'price_yearly',
        'percentage_of_value',
        'price_per_sqm',
        'deductible',
        'deductible_percentage',
        'min_contract_months',
        'is_default',
        'is_active',
        'order',
    ];

    protected $casts = [
        'coverage_amount' => 'decimal:2',
        'covered_risks' => 'array',
        'exclusions' => 'array',
        'price_monthly' => 'decimal:2',
        'price_yearly' => 'decimal:2',
        'percentage_of_value' => 'decimal:2',
        'price_per_sqm' => 'decimal:2',
        'deductible' => 'decimal:2',
        'deductible_percentage' => 'decimal:2',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Get the provider that offers this plan.
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    /**
     * Get the policies for this plan.
     */
    public function policies(): HasMany
    {
        return $this->hasMany(InsurancePolicy::class, 'plan_id');
    }

    /**
     * Scope to active plans.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Calculate monthly premium based on pricing type and box size/value.
     */
    public function calculatePremium(float $boxSizeM2, ?float $declaredValue = null): float
    {
        return match ($this->pricing_type) {
            'fixed' => (float) $this->price_monthly,
            'percentage' => $declaredValue ? ($declaredValue * ($this->percentage_of_value / 100)) / 12 : (float) $this->price_monthly,
            'per_sqm' => $boxSizeM2 * (float) $this->price_per_sqm,
            default => (float) $this->price_monthly,
        };
    }
}
