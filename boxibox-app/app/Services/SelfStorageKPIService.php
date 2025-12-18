<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Site;
use App\Models\Customer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * Service de calcul des KPIs specifiques au Self-Storage
 *
 * Metriques cles:
 * - NOI (Net Operating Income)
 * - Economic Occupancy
 * - Physical Occupancy
 * - RevPASF (Revenue per Available Square Foot)
 * - Street Rate vs Actual Rate
 * - Move-in/Move-out Rates
 * - Customer Lifetime Value
 * - Churn Rate
 * - Rent Roll Growth
 * - Delinquency Rate
 */
class SelfStorageKPIService
{
    protected ?int $tenantId = null;
    protected int $cacheMinutes = 30;

    /**
     * Définir le tenant ID pour les requêtes
     */
    public function setTenantId(int $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Obtenir tous les KPIs pour un tenant
     */
    public function getAllKPIs(int $tenantId, ?int $siteId = null): array
    {
        $this->tenantId = $tenantId;
        $cacheKey = "kpi:all:{$tenantId}:" . ($siteId ?? 'all');

        return Cache::remember($cacheKey, $this->cacheMinutes * 60, function () use ($siteId) {
            return [
                'occupancy' => $this->getOccupancyMetrics(null, $siteId),
                'revenue' => $this->getRevenueMetrics(null, $siteId),
                'operations' => $this->getOperationsMetrics(null, $siteId),
                'customer' => $this->getCustomerMetrics(null, $siteId),
                'financial' => $this->getFinancialMetrics(null, $siteId),
                'trends' => $this->getTrendMetrics(null, $siteId),
            ];
        });
    }

    /**
     * Metriques d'occupation
     */
    public function getOccupancyMetrics(?int $tenantId = null, ?int $siteId = null): array
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
        }

        if ($this->tenantId === null) {
            return $this->getEmptyOccupancyMetrics();
        }

        $boxQuery = Box::where('tenant_id', $this->tenantId);
        if ($siteId) {
            $boxQuery->where('site_id', $siteId);
        }

        // Physical Occupancy (by units)
        $totalBoxes = $boxQuery->count();
        $occupiedBoxes = (clone $boxQuery)->where('status', 'occupied')->count();
        $availableBoxes = $totalBoxes - $occupiedBoxes;
        $physicalOccupancy = $totalBoxes > 0 ? round(($occupiedBoxes / $totalBoxes) * 100, 2) : 0;

        // Square Footage Metrics (calculated from length * width since size_m2 is not a DB column)
        $totalSqft = $boxQuery->selectRaw('SUM(length * width) as total')->value('total') ?? 0;
        $occupiedSqft = (clone $boxQuery)->where('status', 'occupied')->selectRaw('SUM(length * width) as total')->value('total') ?? 0;
        $sqftOccupancy = $totalSqft > 0 ? round(($occupiedSqft / $totalSqft) * 100, 2) : 0;

        // Economic Occupancy (actual revenue / potential revenue)
        $actualRevenue = $this->getActualMonthlyRevenue($siteId);
        $potentialRevenue = $this->getPotentialMonthlyRevenue($siteId);
        $economicOccupancy = $potentialRevenue > 0 ? round(($actualRevenue / $potentialRevenue) * 100, 2) : 0;

        // By size category
        $occupancyBySize = $this->getOccupancyBySize($siteId);

        return [
            'physical_occupancy' => $physicalOccupancy,
            'economic_occupancy' => $economicOccupancy,
            'sqft_occupancy' => $sqftOccupancy,
            'total_units' => $totalBoxes,
            'occupied_units' => $occupiedBoxes,
            'available_units' => $availableBoxes,
            'total_sqft' => round($totalSqft, 2),
            'occupied_sqft' => round($occupiedSqft, 2),
            'available_sqft' => round($totalSqft - $occupiedSqft, 2),
            'by_size' => $occupancyBySize,
        ];
    }

    /**
     * Retourne des métriques d'occupation vides
     */
    protected function getEmptyOccupancyMetrics(): array
    {
        return [
            'physical_occupancy' => 0,
            'economic_occupancy' => 0,
            'sqft_occupancy' => 0,
            'total_units' => 0,
            'occupied_units' => 0,
            'available_units' => 0,
            'total_sqft' => 0,
            'occupied_sqft' => 0,
            'available_sqft' => 0,
            'by_size' => [],
        ];
    }

    /**
     * Metriques de revenus
     */
    public function getRevenueMetrics(?int $tenantId = null, ?int $siteId = null): array
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
        }

        if ($this->tenantId === null) {
            return $this->getEmptyRevenueMetrics();
        }
        $actualRevenue = $this->getActualMonthlyRevenue($siteId);
        $potentialRevenue = $this->getPotentialMonthlyRevenue($siteId);
        $totalSqft = $this->getTotalSqft($siteId);
        $occupiedSqft = $this->getOccupiedSqft($siteId);

        // RevPAB (Revenue per Available Box)
        $totalBoxes = Box::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->count();
        $revPAB = $totalBoxes > 0 ? round($actualRevenue / $totalBoxes, 2) : 0;

        // RevPOR (Revenue per Occupied Room/Box)
        $occupiedBoxes = Box::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'occupied')
            ->count();
        $revPOR = $occupiedBoxes > 0 ? round($actualRevenue / $occupiedBoxes, 2) : 0;

        // RevPASF (Revenue per Available Square Foot)
        $revPASF = $totalSqft > 0 ? round($actualRevenue / $totalSqft, 2) : 0;

        // RevPOSF (Revenue per Occupied Square Foot)
        $revPOSF = $occupiedSqft > 0 ? round($actualRevenue / $occupiedSqft, 2) : 0;

        // Street Rate vs Actual Rate
        $streetRate = $this->getAverageStreetRate($siteId);
        $actualRate = $occupiedSqft > 0 ? round($actualRevenue / $occupiedSqft, 2) : 0;
        $rateVariance = $streetRate > 0 ? round((($actualRate - $streetRate) / $streetRate) * 100, 2) : 0;

        // MRR (Monthly Recurring Revenue)
        $mrr = $this->getMRR($siteId);

        // ARR (Annual Recurring Revenue)
        $arr = $mrr * 12;

        return [
            'actual_monthly_revenue' => round($actualRevenue, 2),
            'potential_monthly_revenue' => round($potentialRevenue, 2),
            'revenue_loss' => round($potentialRevenue - $actualRevenue, 2),
            'mrr' => round($mrr, 2),
            'arr' => round($arr, 2),
            'noi' => round($actualRevenue * 0.65, 2), // NOI = Revenue - OpEx (35%)
            'revpab' => $revPAB,
            'revpor' => $revPOR,
            'revpasf' => $revPASF,
            'revposf' => $revPOSF,
            'street_rate' => round($streetRate, 2),
            'actual_rate' => $actualRate,
            'rate_variance_percent' => $rateVariance,
        ];
    }

    /**
     * Retourne des métriques de revenus vides
     */
    protected function getEmptyRevenueMetrics(): array
    {
        return [
            'actual_monthly_revenue' => 0,
            'potential_monthly_revenue' => 0,
            'revenue_loss' => 0,
            'mrr' => 0,
            'arr' => 0,
            'noi' => 0,
            'revpab' => 0,
            'revpor' => 0,
            'revpasf' => 0,
            'revposf' => 0,
            'street_rate' => 0,
            'actual_rate' => 0,
            'rate_variance_percent' => 0,
        ];
    }

    /**
     * Metriques operationnelles
     */
    public function getOperationsMetrics(?int $tenantId = null, ?int $siteId = null): array
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
        }

        if ($this->tenantId === null) {
            return $this->getEmptyOperationsMetrics();
        }

        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth = $now->copy()->subMonth()->endOfMonth();

        // Move-ins this month
        $moveInsThisMonth = Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->whereBetween('start_date', [$startOfMonth, $now])
            ->count();

        // Move-ins last month
        $moveInsLastMonth = Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->whereBetween('start_date', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        // Move-outs this month
        $moveOutsThisMonth = Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'terminated')
            ->whereBetween('end_date', [$startOfMonth, $now])
            ->count();

        // Move-outs last month
        $moveOutsLastMonth = Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'terminated')
            ->whereBetween('end_date', [$startOfLastMonth, $endOfLastMonth])
            ->count();

        // Net move activity
        $netMoveActivity = $moveInsThisMonth - $moveOutsThisMonth;

        // Average length of stay (active contracts)
        $avgLengthOfStay = Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'active')
            ->selectRaw('AVG(DATEDIFF(IFNULL(end_date, NOW()), start_date)) as avg_days')
            ->value('avg_days');
        $avgLengthOfStayMonths = round(($avgLengthOfStay ?? 0) / 30, 1);

        // Churn rate (monthly)
        $activeContractsStartOfMonth = Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('start_date', '<', $startOfMonth)
            ->whereRaw('(end_date IS NULL OR end_date >= ?)', [$startOfMonth])
            ->count();
        $churnRate = $activeContractsStartOfMonth > 0
            ? round(($moveOutsThisMonth / $activeContractsStartOfMonth) * 100, 2)
            : 0;

        // Conversion rate (prospects to contracts this month)
        $prospectsLastMonth = DB::table('prospects')
            ->where('tenant_id', $this->tenantId)
            ->whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])
            ->count();
        $conversionRate = $prospectsLastMonth > 0
            ? round(($moveInsThisMonth / $prospectsLastMonth) * 100, 2)
            : 0;

        // Calculate move-in/move-out rates (per occupied units)
        $occupiedBoxes = Box::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'occupied')
            ->count();
        $moveInRate = $occupiedBoxes > 0 ? round(($moveInsThisMonth / $occupiedBoxes) * 100, 2) : 0;
        $moveOutRate = $occupiedBoxes > 0 ? round(($moveOutsThisMonth / $occupiedBoxes) * 100, 2) : 0;

        return [
            'move_ins_this_month' => $moveInsThisMonth,
            'move_ins_last_month' => $moveInsLastMonth,
            'move_outs_this_month' => $moveOutsThisMonth,
            'move_outs_last_month' => $moveOutsLastMonth,
            'net_move_activity' => $netMoveActivity,
            'move_in_rate' => $moveInRate,
            'move_out_rate' => $moveOutRate,
            'avg_length_of_stay' => $avgLengthOfStayMonths,
            'churn_rate' => $churnRate,
            'annual_churn_rate' => round($churnRate * 12, 2),
            'conversion_rate' => $conversionRate,
        ];
    }

    /**
     * Retourne des métriques opérationnelles vides
     */
    protected function getEmptyOperationsMetrics(): array
    {
        return [
            'move_ins_this_month' => 0,
            'move_ins_last_month' => 0,
            'move_outs_this_month' => 0,
            'move_outs_last_month' => 0,
            'net_move_activity' => 0,
            'move_in_rate' => 0,
            'move_out_rate' => 0,
            'avg_length_of_stay' => 0,
            'churn_rate' => 0,
            'annual_churn_rate' => 0,
            'conversion_rate' => 0,
        ];
    }

    /**
     * Metriques clients
     */
    public function getCustomerMetrics(?int $tenantId = null, ?int $siteId = null): array
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
        }

        if ($this->tenantId === null) {
            return $this->getEmptyCustomerMetrics();
        }

        // Total active customers
        $activeCustomers = Customer::where('tenant_id', $this->tenantId)
            ->whereHas('contracts', function ($q) use ($siteId) {
                $q->where('status', 'active');
                if ($siteId) {
                    $q->where('site_id', $siteId);
                }
            })
            ->count();

        // Average revenue per customer
        $avgRevenuePerCustomer = Payment::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->avg('amount') ?? 0;

        // Customer Lifetime Value
        $avgLifetimeMonths = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->selectRaw('AVG(DATEDIFF(IFNULL(end_date, NOW()), start_date) / 30) as avg_months')
            ->value('avg_months') ?? 12;
        $avgMonthlyValue = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->avg('monthly_price') ?? 0;
        $clv = round($avgMonthlyValue * $avgLifetimeMonths, 2);

        // Customer acquisition cost (simplified - would need marketing spend data)
        // For now, estimate based on industry average
        $estimatedCAC = 100; // placeholder

        // CLV to CAC ratio
        $clvCacRatio = $estimatedCAC > 0 ? round($clv / $estimatedCAC, 2) : 0;

        // Customers by type
        $customersByType = Customer::where('tenant_id', $this->tenantId)
            ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
            ->selectRaw("type, COUNT(*) as count")
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();

        return [
            'active_customers' => $activeCustomers,
            'arpu' => round($avgRevenuePerCustomer, 2),
            'clv' => $clv,
            'avg_stay_months' => round($avgLifetimeMonths, 1),
            'estimated_cac' => $estimatedCAC,
            'clv_cac_ratio' => $clvCacRatio,
            'customers_by_type' => $customersByType,
        ];
    }

    /**
     * Retourne des métriques clients vides
     */
    protected function getEmptyCustomerMetrics(): array
    {
        return [
            'active_customers' => 0,
            'arpu' => 0,
            'clv' => 0,
            'avg_stay_months' => 0,
            'estimated_cac' => 100,
            'clv_cac_ratio' => 0,
            'customers_by_type' => [],
        ];
    }

    /**
     * Metriques financieres
     */
    public function getFinancialMetrics(?int $tenantId = null, ?int $siteId = null): array
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
        }

        if ($this->tenantId === null) {
            return $this->getEmptyFinancialMetrics();
        }

        $now = now();

        // Delinquency metrics
        $overdueInvoices = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'overdue')
            ->when($siteId, function ($q) use ($siteId) {
                $q->whereHas('contract', fn($c) => $c->where('site_id', $siteId));
            });

        $totalOverdue = $overdueInvoices->sum('total');
        $overdueCount = $overdueInvoices->count();

        // Delinquency by age
        $delinquencyByAge = [
            '1_30' => Invoice::where('tenant_id', $this->tenantId)
                ->where('status', 'overdue')
                ->whereRaw('DATEDIFF(NOW(), due_date) BETWEEN 1 AND 30')
                ->sum('total'),
            '31_60' => Invoice::where('tenant_id', $this->tenantId)
                ->where('status', 'overdue')
                ->whereRaw('DATEDIFF(NOW(), due_date) BETWEEN 31 AND 60')
                ->sum('total'),
            '61_90' => Invoice::where('tenant_id', $this->tenantId)
                ->where('status', 'overdue')
                ->whereRaw('DATEDIFF(NOW(), due_date) BETWEEN 61 AND 90')
                ->sum('total'),
            '90_plus' => Invoice::where('tenant_id', $this->tenantId)
                ->where('status', 'overdue')
                ->whereRaw('DATEDIFF(NOW(), due_date) > 90')
                ->sum('total'),
        ];

        // Delinquency rate
        $totalBilled = Invoice::where('tenant_id', $this->tenantId)
            ->whereMonth('invoice_date', $now->month)
            ->whereYear('invoice_date', $now->year)
            ->sum('total');
        $delinquencyRate = $totalBilled > 0 ? round(($totalOverdue / $totalBilled) * 100, 2) : 0;

        // Collection rate (use paid_at instead of paid_at)
        $totalPaid = Payment::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->whereMonth('paid_at', $now->month)
            ->whereYear('paid_at', $now->year)
            ->sum('amount');
        $collectionRate = $totalBilled > 0 ? round(($totalPaid / $totalBilled) * 100, 2) : 0;

        // NOI (Net Operating Income) - simplified
        $grossRevenue = $totalPaid;
        $estimatedOpex = $grossRevenue * 0.35; // Industry average 35% of revenue
        $noi = $grossRevenue - $estimatedOpex;
        $noiMargin = $grossRevenue > 0 ? round(($noi / $grossRevenue) * 100, 2) : 0;

        // Cap Rate (would need property value - using estimate)
        $annualNOI = $noi * 12;
        $estimatedPropertyValue = $annualNOI / 0.06; // 6% cap rate estimate
        $capRate = $estimatedPropertyValue > 0 ? round(($annualNOI / $estimatedPropertyValue) * 100, 2) : 6;

        // DSO (Days Sales Outstanding)
        $avgDaysOverdue = Invoice::where('tenant_id', $this->tenantId)
            ->where('status', 'overdue')
            ->selectRaw('AVG(DATEDIFF(NOW(), due_date)) as avg_days')
            ->value('avg_days') ?? 0;

        return [
            'total_delinquent' => round($totalOverdue, 2),
            'overdue_count' => $overdueCount,
            'delinquency_rate' => $delinquencyRate,
            'collection_rate' => $collectionRate,
            'delinquency_buckets' => [
                '1-30' => $delinquencyByAge['1_30'],
                '31-60' => $delinquencyByAge['31_60'],
                '61-90' => $delinquencyByAge['61_90'],
                '90+' => $delinquencyByAge['90_plus'],
            ],
            'gross_revenue' => round($grossRevenue, 2),
            'estimated_opex' => round($estimatedOpex, 2),
            'noi' => round($noi, 2),
            'noi_margin' => $noiMargin,
            'annual_noi' => round($annualNOI, 2),
            'estimated_cap_rate' => $capRate,
            'dso' => round($avgDaysOverdue, 1),
        ];
    }

    /**
     * Retourne des métriques financières vides
     */
    protected function getEmptyFinancialMetrics(): array
    {
        return [
            'total_delinquent' => 0,
            'overdue_count' => 0,
            'delinquency_rate' => 0,
            'collection_rate' => 0,
            'delinquency_buckets' => [
                '1-30' => 0,
                '31-60' => 0,
                '61-90' => 0,
                '90+' => 0,
            ],
            'gross_revenue' => 0,
            'estimated_opex' => 0,
            'noi' => 0,
            'noi_margin' => 0,
            'annual_noi' => 0,
            'estimated_cap_rate' => 6,
            'dso' => 0,
        ];
    }

    /**
     * Tendances
     */
    public function getTrendMetrics(?int $tenantId = null, ?int $siteId = null): array
    {
        if ($tenantId !== null) {
            $this->tenantId = $tenantId;
        }

        if ($this->tenantId === null) {
            return $this->getEmptyTrendMetrics();
        }

        $trends = [];

        // 12-month revenue trend
        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $month = $date->format('Y-m');
            $monthLabel = $date->format('M Y');

            $revenue = Payment::where('tenant_id', $this->tenantId)
                ->where('status', 'completed')
                ->whereMonth('paid_at', $date->month)
                ->whereYear('paid_at', $date->year)
                ->sum('amount');

            $occupancy = $this->getHistoricalOccupancy($date, $siteId);

            $trends[] = [
                'month' => $month,
                'label' => $monthLabel,
                'revenue' => round($revenue, 2),
                'occupancy' => $occupancy,
            ];
        }

        // YoY comparison
        $thisYearRevenue = Payment::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->whereYear('paid_at', now()->year)
            ->sum('amount');

        $lastYearRevenue = Payment::where('tenant_id', $this->tenantId)
            ->where('status', 'completed')
            ->whereYear('paid_at', now()->year - 1)
            ->sum('amount');

        $yoyGrowth = $lastYearRevenue > 0
            ? round((($thisYearRevenue - $lastYearRevenue) / $lastYearRevenue) * 100, 2)
            : 0;

        // Year-over-year occupancy comparison
        $currentOccupancy = $this->getOccupancyMetrics(null, $siteId)['physical_occupancy'] ?? 0;
        $lastYearOccupancy = $this->getHistoricalOccupancy(now()->subYear(), $siteId);
        $occupancyChange = $currentOccupancy - $lastYearOccupancy;

        // Year-over-year customer comparison
        $currentCustomers = Customer::where('tenant_id', $this->tenantId)
            ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
            ->count();
        $lastYearCustomers = Contract::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->where('start_date', '<', now()->subYear())
            ->count();
        $customerGrowth = $lastYearCustomers > 0
            ? round((($currentCustomers - $lastYearCustomers) / $lastYearCustomers) * 100, 2)
            : 0;

        return [
            'monthly' => $trends,
            'ytd_revenue' => round($thisYearRevenue, 2),
            'prev_year_revenue' => round($lastYearRevenue, 2),
            'yoy' => [
                'revenue_growth' => $yoyGrowth,
                'occupancy_change' => round($occupancyChange, 1),
                'customer_growth' => $customerGrowth,
            ],
        ];
    }

    /**
     * Retourne des métriques de tendances vides
     */
    protected function getEmptyTrendMetrics(): array
    {
        return [
            'monthly' => [],
            'ytd_revenue' => 0,
            'prev_year_revenue' => 0,
            'yoy' => [
                'revenue_growth' => 0,
                'occupancy_change' => 0,
                'customer_growth' => 0,
            ],
        ];
    }

    // ============================================
    // Helper Methods
    // ============================================

    protected function getActualMonthlyRevenue(?int $siteId): float
    {
        return Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'active')
            ->sum('monthly_price');
    }

    protected function getPotentialMonthlyRevenue(?int $siteId): float
    {
        return Box::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->sum('base_price');
    }

    protected function getMRR(?int $siteId): float
    {
        return Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'active')
            ->sum('monthly_price');
    }

    protected function getTotalSqft(?int $siteId): float
    {
        return Box::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->selectRaw('SUM(length * width) as total')
            ->value('total') ?? 0;
    }

    protected function getOccupiedSqft(?int $siteId): float
    {
        return Box::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('status', 'occupied')
            ->selectRaw('SUM(length * width) as total')
            ->value('total') ?? 0;
    }

    protected function getAverageStreetRate(?int $siteId): float
    {
        $totalSqft = $this->getTotalSqft($siteId);
        $potentialRevenue = $this->getPotentialMonthlyRevenue($siteId);

        return $totalSqft > 0 ? $potentialRevenue / $totalSqft : 0;
    }

    protected function getOccupancyBySize(?int $siteId): array
    {
        $sizeCategories = [
            'xs' => [0, 3],
            'small' => [3, 6],
            'medium' => [6, 12],
            'large' => [12, 20],
            'xl' => [20, 999],
        ];

        $result = [];

        foreach ($sizeCategories as $category => $range) {
            $total = Box::where('tenant_id', $this->tenantId)
                ->when($siteId, fn($q) => $q->where('site_id', $siteId))
                ->whereRaw('(length * width) BETWEEN ? AND ?', $range)
                ->count();

            $occupied = Box::where('tenant_id', $this->tenantId)
                ->when($siteId, fn($q) => $q->where('site_id', $siteId))
                ->whereRaw('(length * width) BETWEEN ? AND ?', $range)
                ->where('status', 'occupied')
                ->count();

            $result[$category] = [
                'total' => $total,
                'occupied' => $occupied,
                'available' => $total - $occupied,
                'rate' => $total > 0 ? round(($occupied / $total) * 100, 1) : 0,
            ];
        }

        return $result;
    }

    protected function getHistoricalOccupancy(Carbon $date, ?int $siteId): float
    {
        $totalBoxes = Box::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->count();

        $activeContracts = Contract::where('tenant_id', $this->tenantId)
            ->when($siteId, fn($q) => $q->where('site_id', $siteId))
            ->where('start_date', '<=', $date->endOfMonth())
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date->startOfMonth());
            })
            ->count();

        return $totalBoxes > 0 ? round(($activeContracts / $totalBoxes) * 100, 1) : 0;
    }

    /**
     * Invalider le cache pour un tenant
     */
    public function invalidateCache(int $tenantId): void
    {
        $cacheKey = "kpi:all:{$tenantId}:*";
        Cache::forget("kpi:all:{$tenantId}:all");

        // Invalider aussi par site
        $sites = Site::where('tenant_id', $tenantId)->pluck('id');
        foreach ($sites as $siteId) {
            Cache::forget("kpi:all:{$tenantId}:{$siteId}");
        }
    }
}
