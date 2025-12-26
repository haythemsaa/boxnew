<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\ContractAddon;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ContractAddonService
{
    /**
     * Add an addon to a contract.
     */
    public function addAddon(Contract $contract, Product $product, array $options = []): ContractAddon
    {
        // Vérifier que le produit est récurrent
        if (!$product->isRecurring()) {
            throw new \Exception('Seuls les produits récurrents peuvent être ajoutés comme add-ons.');
        }

        // Vérifier si l'addon existe déjà
        $existingAddon = $contract->addons()
            ->where('product_id', $product->id)
            ->active()
            ->first();

        if ($existingAddon) {
            throw new \Exception('Ce produit est déjà ajouté à ce contrat.');
        }

        return ContractAddon::create([
            'contract_id' => $contract->id,
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_sku' => $product->sku,
            'quantity' => $options['quantity'] ?? 1,
            'unit_price' => $options['unit_price'] ?? $product->price,
            'tax_rate' => $options['tax_rate'] ?? $product->tax_rate ?? 20,
            'billing_period' => $options['billing_period'] ?? $product->billing_period ?? 'monthly',
            'start_date' => $options['start_date'] ?? now()->toDateString(),
            'end_date' => $options['end_date'] ?? null,
            'notes' => $options['notes'] ?? null,
        ]);
    }

    /**
     * Get addons due for billing.
     */
    public function getAddonsDueBilling(?int $tenantId = null): Collection
    {
        $query = ContractAddon::query()
            ->with(['contract', 'product'])
            ->active()
            ->where('next_billing_date', '<=', today());

        if ($tenantId) {
            $query->whereHas('contract', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        return $query->get();
    }

    /**
     * Get invoice items for a contract's addons.
     */
    public function getInvoiceItemsForContract(Contract $contract): array
    {
        $items = [];

        $activeAddons = $contract->addons()
            ->active()
            ->where('next_billing_date', '<=', today())
            ->get();

        foreach ($activeAddons as $addon) {
            $items[] = $addon->toInvoiceItem();

            // Avancer la date de prochaine facturation
            $addon->advanceNextBillingDate();
        }

        return $items;
    }

    /**
     * Calculate total monthly recurring for a contract.
     */
    public function calculateMonthlyRecurring(Contract $contract): float
    {
        return $contract->addons()
            ->active()
            ->get()
            ->sum('monthly_amount');
    }

    /**
     * Pause all addons for a contract.
     */
    public function pauseAllAddons(Contract $contract): int
    {
        $count = 0;

        foreach ($contract->addons()->active()->get() as $addon) {
            $addon->pause();
            $count++;
        }

        return $count;
    }

    /**
     * Resume all addons for a contract.
     */
    public function resumeAllAddons(Contract $contract): int
    {
        $count = 0;

        foreach ($contract->addons()->paused()->get() as $addon) {
            $addon->resume();
            $count++;
        }

        return $count;
    }

    /**
     * Cancel all addons for a contract.
     */
    public function cancelAllAddons(Contract $contract, ?string $reason = null): int
    {
        $count = 0;

        foreach ($contract->addons()->whereIn('status', ['active', 'paused'])->get() as $addon) {
            $addon->cancel($reason);
            $count++;
        }

        return $count;
    }

    /**
     * Check for expired addons and update status.
     */
    public function processExpiredAddons(?int $tenantId = null): int
    {
        $query = ContractAddon::query()
            ->active()
            ->whereNotNull('end_date')
            ->where('end_date', '<', today());

        if ($tenantId) {
            $query->whereHas('contract', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            });
        }

        $count = 0;

        foreach ($query->get() as $addon) {
            $addon->update(['status' => 'expired']);
            $count++;
        }

        return $count;
    }

    /**
     * Get addon statistics for a tenant.
     */
    public function getStatistics(int $tenantId): array
    {
        $addons = ContractAddon::query()
            ->whereHas('contract', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->get();

        $active = $addons->where('status', 'active');

        return [
            'total_addons' => $addons->count(),
            'active_addons' => $active->count(),
            'paused_addons' => $addons->where('status', 'paused')->count(),
            'cancelled_addons' => $addons->where('status', 'cancelled')->count(),
            'monthly_recurring_revenue' => $active->sum('monthly_amount'),
            'unique_products' => $active->pluck('product_id')->unique()->count(),
        ];
    }

    /**
     * Get most popular addon products.
     */
    public function getPopularAddons(int $tenantId, int $limit = 10): Collection
    {
        return ContractAddon::query()
            ->whereHas('contract', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId);
            })
            ->active()
            ->selectRaw('product_id, product_name, COUNT(*) as usage_count, SUM(unit_price * quantity) as total_revenue')
            ->groupBy('product_id', 'product_name')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }
}
