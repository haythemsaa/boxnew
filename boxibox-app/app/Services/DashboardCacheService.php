<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Prospect;
use App\Models\Signature;
use App\Models\Site;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard Cache Service
 *
 * Provides cached access to dashboard statistics to reduce database load.
 * Cache is automatically invalidated when relevant models are updated.
 */
class DashboardCacheService
{
    /**
     * Cache TTL in seconds (5 minutes for real-time data).
     */
    protected int $ttl = 300;

    /**
     * Cache TTL for less volatile data (30 minutes).
     */
    protected int $longTtl = 1800;

    /**
     * Get all dashboard stats with caching.
     */
    public function getStats(int $tenantId): array
    {
        $cacheKey = "dashboard_stats_{$tenantId}";

        return Cache::remember($cacheKey, $this->ttl, function () use ($tenantId) {
            return $this->calculateStats($tenantId);
        });
    }

    /**
     * Get revenue trend with longer cache.
     */
    public function getRevenueTrend(int $tenantId, int $months = 6): array
    {
        $cacheKey = "dashboard_revenue_trend_{$tenantId}_{$months}";

        return Cache::remember($cacheKey, $this->longTtl, function () use ($tenantId, $months) {
            return $this->calculateRevenueTrend($tenantId, $months);
        });
    }

    /**
     * Get occupancy by site with caching.
     */
    public function getOccupancyBySite(int $tenantId): array
    {
        $cacheKey = "dashboard_occupancy_{$tenantId}";

        return Cache::remember($cacheKey, $this->ttl, function () use ($tenantId) {
            return Site::where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->withCount('boxes')
                ->withCount(['boxes as occupied_count' => fn($q) => $q->where('status', 'occupied')])
                ->get()
                ->map(fn($site) => [
                    'id' => $site->id,
                    'name' => $site->name,
                    'total' => $site->boxes_count,
                    'occupied' => $site->occupied_count,
                    'rate' => $site->boxes_count > 0
                        ? round(($site->occupied_count / $site->boxes_count) * 100, 1)
                        : 0,
                ])
                ->toArray();
        });
    }

    /**
     * Invalidate all dashboard caches for a tenant.
     */
    public function invalidate(int $tenantId): void
    {
        Cache::forget("dashboard_stats_{$tenantId}");
        Cache::forget("dashboard_revenue_trend_{$tenantId}_6");
        Cache::forget("dashboard_revenue_trend_{$tenantId}_12");
        Cache::forget("dashboard_occupancy_{$tenantId}");
    }

    /**
     * Invalidate specific cache type.
     */
    public function invalidateStats(int $tenantId): void
    {
        Cache::forget("dashboard_stats_{$tenantId}");
    }

    /**
     * Calculate all dashboard statistics.
     */
    protected function calculateStats(int $tenantId): array
    {
        $monthStart = now()->startOfMonth();
        $lastMonthStart = now()->subMonth()->startOfMonth();
        $lastMonthEnd = now()->subMonth()->endOfMonth();

        // Site stats
        $siteStats = Site::where('tenant_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total_sites,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_sites
            ")
            ->first();

        // Box stats
        $boxStats = Box::where('tenant_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total_boxes,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available_boxes,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied_boxes,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance_boxes,
                SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved_boxes
            ")
            ->first();

        // Customer stats
        $customerStats = Customer::where('tenant_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total_customers,
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_customers,
                SUM(CASE WHEN type = 'individual' THEN 1 ELSE 0 END) as individual_customers,
                SUM(CASE WHEN type = 'company' THEN 1 ELSE 0 END) as company_customers,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as new_customers_this_month
            ", [$monthStart])
            ->first();

        // Contract stats
        $contractStats = Contract::where('tenant_id', $tenantId)
            ->selectRaw("
                SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_contracts,
                SUM(CASE WHEN status = 'active' AND end_date BETWEEN ? AND ? THEN 1 ELSE 0 END) as expiring_soon,
                SUM(CASE WHEN status = 'active' THEN monthly_price ELSE 0 END) as monthly_revenue,
                SUM(CASE WHEN status = 'terminated' AND actual_end_date >= ? THEN 1 ELSE 0 END) as terminated_this_month,
                AVG(CASE WHEN status = 'active' AND start_date IS NOT NULL THEN DATEDIFF(NOW(), start_date) / 30 ELSE NULL END) as avg_duration_months
            ", [now(), now()->addDays(30), $monthStart])
            ->first();

        // Invoice stats
        $invoiceStats = Invoice::where('tenant_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total_invoices,
                SUM(CASE WHEN status IN ('sent', 'overdue') THEN 1 ELSE 0 END) as pending_invoices,
                SUM(CASE WHEN status = 'overdue' THEN 1 ELSE 0 END) as overdue_invoices,
                SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_invoices,
                SUM(CASE WHEN status = 'overdue' THEN total ELSE 0 END) as overdue_amount
            ")
            ->first();

        // Payment stats
        $paymentStats = Payment::where('tenant_id', $tenantId)
            ->selectRaw("
                SUM(CASE WHEN status = 'completed' AND type = 'payment' THEN 1 ELSE 0 END) as total_payments,
                SUM(CASE WHEN status = 'completed' AND type = 'payment' THEN amount ELSE 0 END) as total_collected,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_payments,
                SUM(CASE WHEN status = 'completed' AND type = 'payment' AND paid_at BETWEEN ? AND ? THEN amount ELSE 0 END) as last_month_revenue
            ", [$lastMonthStart, $lastMonthEnd])
            ->first();

        // Prospect stats
        $prospectStats = Prospect::where('tenant_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total_prospects,
                SUM(CASE WHEN status = 'hot' THEN 1 ELSE 0 END) as hot_prospects,
                SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as prospects_this_month,
                SUM(CASE WHEN status = 'converted' AND converted_at >= ? THEN 1 ELSE 0 END) as converted_this_month,
                SUM(CASE WHEN created_at < ? THEN 1 ELSE 0 END) as total_before_month
            ", [$monthStart, $monthStart, $monthStart])
            ->first();

        // Signature stats
        $signatureStats = Signature::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'sent', 'viewed'])
            ->count();

        // Build stats array
        $totalBoxes = $boxStats->total_boxes ?? 0;
        $occupiedBoxes = $boxStats->occupied_boxes ?? 0;
        $activeContracts = $contractStats->active_contracts ?? 0;
        $monthlyRevenue = $contractStats->monthly_revenue ?? 0;
        $terminatedThisMonth = $contractStats->terminated_this_month ?? 0;
        $convertedThisMonth = $prospectStats->converted_this_month ?? 0;
        $totalProspectsMonthBefore = $prospectStats->total_before_month ?? 0;

        return [
            // Sites
            'total_sites' => $siteStats->total_sites ?? 0,
            'active_sites' => $siteStats->active_sites ?? 0,

            // Boxes
            'total_boxes' => $totalBoxes,
            'available_boxes' => $boxStats->available_boxes ?? 0,
            'occupied_boxes' => $occupiedBoxes,
            'maintenance_boxes' => $boxStats->maintenance_boxes ?? 0,
            'reserved_boxes' => $boxStats->reserved_boxes ?? 0,

            // Customers
            'total_customers' => $customerStats->total_customers ?? 0,
            'active_customers' => $customerStats->active_customers ?? 0,
            'individual_customers' => $customerStats->individual_customers ?? 0,
            'company_customers' => $customerStats->company_customers ?? 0,
            'new_customers_this_month' => $customerStats->new_customers_this_month ?? 0,

            // Contracts
            'active_contracts' => $activeContracts,
            'expiring_soon' => $contractStats->expiring_soon ?? 0,
            'monthly_revenue' => $monthlyRevenue,
            'annual_revenue_projection' => $monthlyRevenue * 12,
            'avg_contract_duration' => round($contractStats->avg_duration_months ?? 0, 1),

            // Invoices
            'total_invoices' => $invoiceStats->total_invoices ?? 0,
            'pending_invoices' => $invoiceStats->pending_invoices ?? 0,
            'overdue_invoices' => $invoiceStats->overdue_invoices ?? 0,
            'paid_invoices' => $invoiceStats->paid_invoices ?? 0,
            'overdue_amount' => $invoiceStats->overdue_amount ?? 0,

            // Payments
            'total_payments' => $paymentStats->total_payments ?? 0,
            'total_collected' => $paymentStats->total_collected ?? 0,
            'pending_payments' => $paymentStats->pending_payments ?? 0,
            'last_month_revenue' => $paymentStats->last_month_revenue ?? 0,

            // Prospects
            'total_prospects' => $prospectStats->total_prospects ?? 0,
            'hot_prospects' => $prospectStats->hot_prospects ?? 0,
            'prospects_this_month' => $prospectStats->prospects_this_month ?? 0,

            // Signatures
            'pending_signatures' => $signatureStats,

            // Calculated metrics
            'occupation_rate' => $totalBoxes > 0
                ? round(($occupiedBoxes / $totalBoxes) * 100, 2)
                : 0,
            'pending_actions' => ($contractStats->expiring_soon ?? 0) + ($invoiceStats->overdue_invoices ?? 0),
            'revpau' => $totalBoxes > 0
                ? round($monthlyRevenue / $totalBoxes, 2)
                : 0,
            'average_contract_value' => $activeContracts > 0
                ? round($monthlyRevenue / $activeContracts, 2)
                : 0,
            'churn_rate' => $activeContracts > 0
                ? round(($terminatedThisMonth / ($activeContracts + $terminatedThisMonth)) * 100, 2)
                : 0,
            'cac' => 0,
            'conversion_rate' => $totalProspectsMonthBefore > 0
                ? round(($convertedThisMonth / $totalProspectsMonthBefore) * 100, 1)
                : 0,

            // Timestamps
            'cached_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Calculate revenue trend for last N months.
     */
    protected function calculateRevenueTrend(int $tenantId, int $months): array
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $revenue = Payment::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->where('type', 'payment')
                ->whereBetween('paid_at', [$monthStart, $monthEnd])
                ->sum('amount');

            $trend[] = [
                'month' => $date->format('M'),
                'year' => $date->format('Y'),
                'full_month' => $date->format('M Y'),
                'revenue' => round($revenue, 2),
            ];
        }

        return $trend;
    }
}
