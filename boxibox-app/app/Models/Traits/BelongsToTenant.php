<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Trait for models that belong to a tenant
 * Automatically applies TenantScope and sets tenant_id on creation
 */
trait BelongsToTenant
{
    /**
     * Boot the trait
     */
    protected static function bootBelongsToTenant(): void
    {
        // Apply global scope for automatic tenant filtering
        static::addGlobalScope(new TenantScope());

        // Automatically set tenant_id when creating a new model
        static::creating(function ($model) {
            if (empty($model->tenant_id) && Auth::check() && Auth::user()->tenant_id) {
                $model->tenant_id = Auth::user()->tenant_id;
            }
        });
    }

    /**
     * Get the tenant that owns this model
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Scope to query without tenant restriction (use with caution!)
     */
    public function scopeWithoutTenantScope($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }
}
