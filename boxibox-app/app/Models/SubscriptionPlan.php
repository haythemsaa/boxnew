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
        // Quotas Email & SMS
        'emails_per_month',
        'sms_per_month',
        'email_tracking_enabled',
        'custom_email_provider_allowed',
        'custom_sms_provider_allowed',
        'api_access',
        'whitelabel',
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
        // Email & SMS casts
        'emails_per_month' => 'integer',
        'sms_per_month' => 'integer',
        'email_tracking_enabled' => 'boolean',
        'custom_email_provider_allowed' => 'boolean',
        'custom_sms_provider_allowed' => 'boolean',
        'api_access' => 'boolean',
        'whitelabel' => 'boolean',
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

    /**
     * Quotas email/SMS formatés
     */
    public function getQuotasAttribute(): array
    {
        return [
            'emails' => $this->emails_per_month === 0 ? 'Illimité' : number_format($this->emails_per_month, 0, '', ' '),
            'sms' => $this->sms_per_month === 0 ? 'Illimité' : number_format($this->sms_per_month, 0, '', ' '),
        ];
    }

    /**
     * Vérifier si emails illimités
     */
    public function getHasUnlimitedEmailsAttribute(): bool
    {
        return $this->emails_per_month === 0 || $this->emails_per_month === null;
    }

    /**
     * Vérifier si SMS illimités
     */
    public function getHasUnlimitedSmsAttribute(): bool
    {
        return $this->sms_per_month === 0 || $this->sms_per_month === null;
    }

    /**
     * Obtenir le plan par défaut
     */
    public static function getDefault(): ?self
    {
        return static::where('code', 'starter')->first()
            ?? static::active()->first();
    }
}
