<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Module extends Model
{
    protected $fillable = [
        'code',
        'name',
        'description',
        'icon',
        'color',
        'category',
        'monthly_price',
        'yearly_price',
        'features',
        'routes',
        'dependencies',
        'is_core',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'features' => 'array',
        'routes' => 'array',
        'dependencies' => 'array',
        'is_core' => 'boolean',
        'is_active' => 'boolean',
        'monthly_price' => 'decimal:2',
        'yearly_price' => 'decimal:2',
    ];

    /**
     * Modules activés par les tenants
     */
    public function tenantModules(): HasMany
    {
        return $this->hasMany(TenantModule::class);
    }

    /**
     * Tenants utilisant ce module
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_modules')
            ->withPivot(['status', 'trial_ends_at', 'starts_at', 'ends_at', 'is_demo'])
            ->withTimestamps();
    }

    /**
     * Plans incluant ce module
     */
    public function plans(): BelongsToMany
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'plan_modules');
    }

    /**
     * Scope pour les modules actifs
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope pour les modules core
     */
    public function scopeCore($query)
    {
        return $query->where('is_core', true);
    }

    /**
     * Scope par catégorie
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Vérifier si un tenant a accès à ce module
     */
    public function isEnabledForTenant(int $tenantId): bool
    {
        // Module core = toujours activé
        if ($this->is_core) {
            return true;
        }

        // Vérifier dans tenant_modules
        return $this->tenantModules()
            ->where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            })
            ->exists();
    }

    /**
     * Obtenir le prix formaté
     */
    public function getFormattedMonthlyPriceAttribute(): string
    {
        return number_format($this->monthly_price, 2, ',', ' ') . ' €/mois';
    }

    public function getFormattedYearlyPriceAttribute(): string
    {
        return number_format($this->yearly_price, 2, ',', ' ') . ' €/an';
    }

    /**
     * Catégories disponibles
     */
    public static function getCategories(): array
    {
        return [
            'core' => 'Fonctionnalités de base',
            'marketing' => 'Marketing & CRM',
            'operations' => 'Opérations',
            'integrations' => 'Intégrations',
            'premium' => 'Premium',
            'analytics' => 'Analytics & IA',
        ];
    }
}
