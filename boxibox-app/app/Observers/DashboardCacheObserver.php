<?php

namespace App\Observers;

use App\Services\DashboardCacheService;
use Illuminate\Database\Eloquent\Model;

/**
 * Dashboard Cache Observer
 *
 * Automatically invalidates dashboard cache when relevant models change.
 * Attach this observer to: Contract, Invoice, Payment, Customer, Box, Site
 */
class DashboardCacheObserver
{
    public function __construct(
        protected DashboardCacheService $cacheService
    ) {}

    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Handle the "deleted" event.
     */
    public function deleted(Model $model): void
    {
        $this->invalidateCache($model);
    }

    /**
     * Invalidate dashboard cache for the model's tenant.
     */
    protected function invalidateCache(Model $model): void
    {
        $tenantId = $this->getTenantId($model);

        if ($tenantId) {
            $this->cacheService->invalidateStats($tenantId);
        }
    }

    /**
     * Get tenant ID from model.
     */
    protected function getTenantId(Model $model): ?int
    {
        // Direct tenant_id
        if (isset($model->tenant_id)) {
            return $model->tenant_id;
        }

        // Through relationship (e.g., Box -> Site -> Tenant)
        if (method_exists($model, 'site') && $model->site) {
            return $model->site->tenant_id;
        }

        return null;
    }
}
