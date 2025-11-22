<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdvancedAnalyticsService
{
    /**
     * Get comprehensive occupancy analytics
     */
    public function getOccupancyAnalytics($tenantId, $siteId = null): array
    {
        $query = Box::where('tenant_id', $tenantId);

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $boxes = $query->get();
        $totalBoxes = $boxes->count();
        $occupiedBoxes = $boxes->where('status', 'occupied')->count();
        $availableBoxes = $boxes->where('status', 'available')->count();
        $reservedBoxes = $boxes->where('status', 'reserved')->count();
        $maintenanceBoxes = $boxes->where('status', 'maintenance')->count();

        $occupancyRate = $totalBoxes > 0 ? ($occupiedBoxes / $totalBoxes) : 0;

        // Occupancy by type
        $byType = $boxes->groupBy('type')->map(function ($typeBoxes, $type) {
            $total = $typeBoxes->count();
            $occupied = $typeBoxes->where('status', 'occupied')->count();

            return [
                'total' => $total,
                'occupied' => $occupied,
                'available' => $typeBoxes->where('status', 'available')->count(),
                'occupancy_rate' => $total > 0 ? ($occupied / $total) * 100 : 0,
            ];
        });

        // Occupancy by size
        $bySize = $boxes->groupBy('size')->map(function ($sizeBoxes, $size) {
            $total = $sizeBoxes->count();
            $occupied = $sizeBoxes->where('status', 'occupied')->count();

            return [
                'total' => $total,
                'occupied' => $occupied,
                'occupancy_rate' => $total > 0 ? ($occupied / $total) * 100 : 0,
            ];
        });

        // 12-month trend
        $monthlyTrend = $this->getOccupancyTrend($tenantId, $siteId, 12);

        return [
            'current' => [
                'total_boxes' => $totalBoxes,
                'occupied' => $occupiedBoxes,
                'available' => $availableBoxes,
                'reserved' => $reservedBoxes,
                'maintenance' => $maintenanceBoxes,
                'occupancy_rate' => $occupancyRate * 100,
                'occupancy_status' => $this->getOccupancyStatus($occupancyRate),
            ],
            'by_type' => $byType,
            'by_size' => $bySize,
            'trend' => $monthlyTrend,
            'move_ins_this_month' => $this->getMoveInsCount($tenantId, $siteId, 'month'),
            'move_outs_this_month' => $this->getMoveOutsCount($tenantId, $siteId, 'month'),
        ];
    }

    /**
     * Get revenue analytics
     */
    public function getRevenueAnalytics($tenantId, $siteId = null, $period = 'month'): array
    {
        $startDate = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        $endDate = now();

        // Active contracts
        $activeContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->get();

        $mrr = $activeContracts->sum('monthly_amount');
        $arr = $mrr * 12;

        // Invoices
        $invoices = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->when($siteId, fn($q) => $q->whereHas('contract', fn($q2) => $q2->where('site_id', $siteId)))
            ->get();

        $totalRevenue = $invoices->where('status', 'paid')->sum('total_amount');
        $pendingRevenue = $invoices->where('status', 'pending')->sum('total_amount');
        $overdueRevenue = $invoices->where('status', 'overdue')->sum('total_amount');

        // Revenue Per Available Foot/Unit (RevPAF/RevPAU)
        $totalBoxes = Box::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->count();

        $totalSquareMeters = Box::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->sum('square_meters');

        $revPAU = $totalBoxes > 0 ? $mrr / $totalBoxes : 0;
        $revPAF = $totalSquareMeters > 0 ? $mrr / $totalSquareMeters : 0;

        // ARPU (Average Revenue Per Unit/Customer)
        $totalCustomers = $activeContracts->unique('customer_id')->count();
        $arpu = $totalCustomers > 0 ? $mrr / $totalCustomers : 0;

        // Revenue breakdown
        $byType = $invoices->where('status', 'paid')->groupBy(function ($invoice) {
            // Simplified - in production would check invoice items
            return 'rent'; // or 'products', 'services', 'penalties'
        })->map->sum('total_amount');

        // 12-month revenue trend
        $revenueTrend = $this->getRevenueTrend($tenantId, $siteId, 12);

        return [
            'recurring' => [
                'mrr' => $mrr,
                'arr' => $arr,
                'active_contracts' => $activeContracts->count(),
            ],
            'current_period' => [
                'total_revenue' => $totalRevenue,
                'pending_revenue' => $pendingRevenue,
                'overdue_revenue' => $overdueRevenue,
                'collected_rate' => ($totalRevenue + $pendingRevenue + $overdueRevenue) > 0
                    ? ($totalRevenue / ($totalRevenue + $pendingRevenue + $overdueRevenue)) * 100
                    : 0,
            ],
            'metrics' => [
                'revpau' => $revPAU,
                'revpaf' => $revPAF,
                'arpu' => $arpu,
            ],
            'breakdown' => $byType,
            'trend' => $revenueTrend,
        ];
    }

    /**
     * Get marketing and sales analytics
     */
    public function getMarketingAnalytics($tenantId, $period = 'month'): array
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'quarter' => now()->startOfQuarter(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth(),
        };

        // Leads (if Lead model exists)
        $leadsQuery = \App\Models\Lead::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, now()]);

        $totalLeads = $leadsQuery->count();
        $convertedLeads = $leadsQuery->where('status', 'converted')->count();
        $leadConversionRate = $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;

        // New customers
        $newCustomers = Customer::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, now()])
            ->count();

        // CAC (Customer Acquisition Cost) - simplified
        $marketingSpend = 0; // Would come from marketing expense tracking
        $cac = $newCustomers > 0 ? $marketingSpend / $newCustomers : 0;

        // LTV (Lifetime Value)
        $avgContractValue = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->avg('monthly_amount');

        $avgContractDuration = 12; // Simplified - could calculate from historical data
        $ltv = $avgContractValue * $avgContractDuration;

        // LTV/CAC ratio
        $ltvCacRatio = $cac > 0 ? $ltv / $cac : 0;

        return [
            'leads' => [
                'total' => $totalLeads,
                'converted' => $convertedLeads,
                'conversion_rate' => $leadConversionRate,
            ],
            'customers' => [
                'new' => $newCustomers,
                'cac' => $cac,
            ],
            'value' => [
                'ltv' => $ltv,
                'ltv_cac_ratio' => $ltvCacRatio,
                'avg_contract_value' => $avgContractValue,
            ],
        ];
    }

    /**
     * Get operational analytics
     */
    public function getOperationalAnalytics($tenantId, $siteId = null): array
    {
        // Costs per unit
        $activeBoxes = Box::where('tenant_id', $tenantId)
            ->where('status', 'occupied')
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->count();

        // Simplified costs - in production would track actual expenses
        $totalExpenses = 10000; // Monthly operational expenses
        $costPerUnit = $activeBoxes > 0 ? $totalExpenses / $activeBoxes : 0;

        // Revenue
        $revenue = $this->getRevenueAnalytics($tenantId, $siteId, 'month')['recurring']['mrr'];

        // Expense ratio (industry standard: 25-40%)
        $expenseRatio = $revenue > 0 ? ($totalExpenses / $revenue) * 100 : 0;

        // NOI (Net Operating Income)
        $noi = $revenue - $totalExpenses;

        // Staff productivity (simplified)
        $staffCount = 5; // Would come from HR system
        $revenuePerStaff = $staffCount > 0 ? $revenue / $staffCount : 0;

        return [
            'costs' => [
                'total_expenses' => $totalExpenses,
                'cost_per_unit' => $costPerUnit,
                'expense_ratio' => $expenseRatio,
            ],
            'profitability' => [
                'noi' => $noi,
                'noi_margin' => $revenue > 0 ? ($noi / $revenue) * 100 : 0,
            ],
            'efficiency' => [
                'staff_count' => $staffCount,
                'revenue_per_staff' => $revenuePerStaff,
                'units_per_staff' => $staffCount > 0 ? $activeBoxes / $staffCount : 0,
            ],
        ];
    }

    /**
     * Get occupancy trend over time
     */
    protected function getOccupancyTrend($tenantId, $siteId, $months): array
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            // Simplified - in production would use snapshot data
            $boxes = Box::where('tenant_id', $tenantId)
                ->when($siteId, fn($q) => $q->where('site_id', $siteId))
                ->get();

            $total = $boxes->count();
            $occupied = $boxes->where('status', 'occupied')->count();

            $trend[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('M Y'),
                'occupancy_rate' => $total > 0 ? ($occupied / $total) * 100 : 0,
                'total_boxes' => $total,
                'occupied_boxes' => $occupied,
            ];
        }

        return $trend;
    }

    /**
     * Get revenue trend over time
     */
    protected function getRevenueTrend($tenantId, $siteId, $months): array
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthStart = $date->copy()->startOfMonth();
            $monthEnd = $date->copy()->endOfMonth();

            $revenue = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereBetween('created_at', [$monthStart, $monthEnd])
                ->when($siteId, fn($q) => $q->whereHas('contract', fn($q2) => $q2->where('site_id', $siteId)))
                ->sum('total_amount');

            $trend[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $date->format('M Y'),
                'revenue' => $revenue,
            ];
        }

        return $trend;
    }

    protected function getMoveInsCount($tenantId, $siteId, $period): int
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        return Contract::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->whereBetween('start_date', [$startDate, now()])
            ->count();
    }

    protected function getMoveOutsCount($tenantId, $siteId, $period): int
    {
        $startDate = match($period) {
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfMonth(),
        };

        return Contract::where('tenant_id', $tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'terminated')
            ->whereBetween('end_date', [$startDate, now()])
            ->count();
    }

    protected function getOccupancyStatus(float $rate): string
    {
        if ($rate < 0.70) return 'low';
        if ($rate < 0.85) return 'medium';
        if ($rate < 0.95) return 'good';
        return 'excellent';
    }
}
