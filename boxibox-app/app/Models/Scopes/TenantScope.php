<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

/**
 * Global scope to automatically filter queries by tenant_id
 * Prevents data leakage between tenants in multi-tenant application
 */
class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Only apply if user is authenticated and has a tenant_id
        if (Auth::check() && Auth::user()->tenant_id) {
            $builder->where($model->getTable() . '.tenant_id', Auth::user()->tenant_id);
        }
    }
}
