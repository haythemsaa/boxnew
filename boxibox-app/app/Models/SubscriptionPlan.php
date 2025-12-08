<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubscriptionPlan extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'badge_color',
        'monthly_price',
        'yearly_price',
        'yearly_discount',
        'max_sites',
        'max_boxes',
        'max_users',
        'max_customers',
        'includes_support',
        'support_level',
        'included_modules',
        'features',
        'is_popular',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
        'yearly_discount' => 'decimal:2',
        'included_modules' => 'array',
        'features' => 'array',
        'includes_support' => 'boolean',
        'is_popular' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Abonnements utilisant ce plan
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id');
    }

    /**
     * Tenants abonnés à ce plan
     */
    public function tenants(): HasMany
    {
        return $this->hasMany(Tenant::class, 'current_plan_id');
    }

    /**
     * Obtenir les modules inclus
     */
    public function getIncludedModulesListAttribute()
    {
        if (!$this->included_modules) {
            return collect();
        }

        return Module::whereIn('id', $this->included_modules)->get();
    }

    /**
     * Vérifier si un module est inclus
     */
    public function includesModule(int $moduleId): bool
    {
        return in_array($moduleId, $this->included_modules ?? []);
    }

    /**
     * Scope pour les plans actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /**
     * Calculer le prix avec réduction annuelle
     */
    public function getYearlyPriceWithDiscountAttribute(): float
    {
        $monthlyTotal = $this->monthly_price * 12;
        return $monthlyTotal * (1 - ($this->yearly_discount / 100));
    }

    /**
     * Obtenir l'économie annuelle
     */
    public function getYearlySavingsAttribute(): float
    {
        return ($this->monthly_price * 12) - $this->yearly_price;
    }

    /**
     * Prix formatés
     */
    public function getFormattedMonthlyPriceAttribute(): string
    {
        return number_format($this->monthly_price, 0, ',', ' ') . ' €';
    }

    public function getFormattedYearlyPriceAttribute(): string
    {
        return number_format($this->yearly_price, 0, ',', ' ') . ' €';
    }

    /**
     * Limites formatées
     */
    public function getLimitsAttribute(): array
    {
        return [
            'sites' => $this->max_sites ?? '∞',
            'boxes' => $this->max_boxes ?? '∞',
            'users' => $this->max_users ?? '∞',
            'customers' => $this->max_customers ?? '∞',
        ];
    }
}
