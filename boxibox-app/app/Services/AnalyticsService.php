<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    protected int $tenantId;

    public function __construct(int $tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * Get comprehensive dashboard KPIs
     */
    public function getDashboardKPIs(): array
    {
        return [
            'occupancy' => $this->getOccupancyMetrics(),
            'revenue' => $this->getRevenueMetrics(),
            'customers' => $this->getCustomerMetrics(),
            'contracts' => $this->getContractMetrics(),
            'trends' => $this->getTrendMetrics(),
        ];
    }

    /**
     * Calculate occupancy metrics
     */
    public function getOccupancyMetrics(): array
    {
        $totalBoxes = Box::where('tenant_id', $this->tenantId)->count();
        $occupiedBoxes = Box::where('tenant_id', $this->tenantId)
            ->where('status', 'occupied')
            ->count();
        $availableBoxes = Box::where('tenant_id', $this->tenantId)
            ->where('status', 'available')
            ->count();

        $occupancyRate = $totalBoxes > 0 ? ($occupiedBoxes / $totalBoxes) * 100 : 0;

        return [
            'total_units' => $totalBoxes,
            'occupied_units' => $occupiedBoxes,
            'available_units' => $availableBoxes,
            'occupancy_rate' => round($occupancyRate, 2),
            'occupancy_percentage' => round($occupancyRate, 0),
        ];
    }

    /**
     * Calculate revenue metrics
     */
    public function getRevenueMetrics(): array
    {
        // Monthly Recurring Revenue (MRR)
        $mrr = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->sum('monthly_price');

        // Annual Recurring Revenue (ARR)
        $arr = $mrr * 12;

        // Total collected this month
        $monthlyCollected = Payment::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        // Total collected this year
        $yearlyCollected = Payment::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        // Average Revenue Per Unit (ARPU)
        $activeContracts = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->count();
        $arpu = $activeContracts > 0 ? $mrr / $activeContracts : 0;

        // Outstanding invoices
        $outstanding = Invoice::where('tenant_id', $this->tenantId)
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('balance');

        return [
            'mrr' => round($mrr, 2),
            'arr' => round($arr, 2),
            'monthly_collected' => round($monthlyCollected, 2),
            'yearly_collected' => round($yearlyCollected, 2),
            'arpu' => round($arpu, 2),
            'outstanding_balance' => round($outstanding, 2),
        ];
    }

    /**
     * Calculate customer metrics
     */
    public function getCustomerMetrics(): array
    {
        $totalCustomers = Customer::where('tenant_id', $this->tenantId)->count();
        $activeCustomers = Customer::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->count();

        // New customers this month
        $newThisMonth = Customer::where('tenant_id', $this->tenantId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        // Customer lifetime value (average) - using parameterized query to prevent SQL injection
        $avgLifetimeValue = DB::table(DB::raw('(SELECT customer_id, SUM(amount) as customer_total FROM payments WHERE tenant_id = ? AND status = ? GROUP BY customer_id) as customer_totals'))
            ->setBindings([$this->tenantId, 'completed'])
            ->selectRaw('AVG(customer_total) as avg_ltv')
            ->value('avg_ltv') ?? 0;

        return [
            'total_customers' => $totalCustomers,
            'active_customers' => $activeCustomers,
            'new_this_month' => $newThisMonth,
            'avg_lifetime_value' => round($avgLifetimeValue, 2),
        ];
    }

    /**
     * Calculate contract metrics
     */
    public function getContractMetrics(): array
    {
        $activeContracts = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->count();

        $expiringContracts = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->whereNotNull('end_date')
            ->where('end_date', '<=', now()->addDays(30))
            ->count();

        // Move-ins this month
        $moveInsThisMonth = Contract::where('tenant_id', $this->tenantId)
            ->whereMonth('start_date', now()->month)
            ->whereYear('start_date', now()->year)
            ->count();

        // Move-outs this month
        $moveOutsThisMonth = Contract::where('tenant_id', $this->tenantId)
            ->whereIn('status', ['expired', 'cancelled'])
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();

        // Average contract duration (in days)
        $avgDuration = Contract::where('tenant_id', $this->tenantId)
            ->whereNotNull('end_date')
            ->selectRaw('AVG(DATEDIFF(end_date, start_date)) as avg_days')
            ->value('avg_days') ?? 0;

        return [
            'active_contracts' => $activeContracts,
            'expiring_soon' => $expiringContracts,
            'move_ins_this_month' => $moveInsThisMonth,
            'move_outs_this_month' => $moveOutsThisMonth,
            'avg_contract_duration_days' => round($avgDuration, 0),
        ];
    }

    /**
     * Get trend data for charts (last 12 months)
     */
    public function getTrendMetrics(): array
    {
        $months = [];
        $revenue = [];
        $moveIns = [];
        $moveOuts = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $months[] = $date->format('M Y');

            // Revenue collected that month
            $revenue[] = Payment::where('tenant_id', $this->tenantId)
                ->where('status', 'completed')
                ->whereMonth('paid_at', $date->month)
                ->whereYear('paid_at', $date->year)
                ->sum('amount');

            // Move-ins that month
            $moveIns[] = Contract::where('tenant_id', $this->tenantId)
                ->whereMonth('start_date', $date->month)
                ->whereYear('start_date', $date->year)
                ->count();

            // Move-outs that month
            $moveOuts[] = Contract::where('tenant_id', $this->tenantId)
                ->whereIn('status', ['expired', 'cancelled'])
                ->whereMonth('updated_at', $date->month)
                ->whereYear('updated_at', $date->year)
                ->count();
        }

        return [
            'months' => $months,
            'revenue' => $revenue,
            'move_ins' => $moveIns,
            'move_outs' => $moveOuts,
        ];
    }

    /**
     * Get site-specific metrics
     */
    public function getSiteMetrics(int $siteId): array
    {
        $totalBoxes = Box::where('tenant_id', $this->tenantId)
            ->where('site_id', $siteId)
            ->count();

        $occupiedBoxes = Box::where('tenant_id', $this->tenantId)
            ->where('site_id', $siteId)
            ->where('status', 'occupied')
            ->count();

        $occupancyRate = $totalBoxes > 0 ? ($occupiedBoxes / $totalBoxes) * 100 : 0;

        $siteRevenue = Contract::where('tenant_id', $this->tenantId)
            ->where('site_id', $siteId)
            ->where('status', 'active')
            ->sum('monthly_price');

        return [
            'total_units' => $totalBoxes,
            'occupied_units' => $occupiedBoxes,
            'occupancy_rate' => round($occupancyRate, 2),
            'monthly_revenue' => round($siteRevenue, 2),
        ];
    }
}
