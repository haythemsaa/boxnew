<?php

namespace App\Services;

use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Box;
use App\Models\Lead;
use App\Models\Prospect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class RevenueForecastingService
{
    /**
     * Get comprehensive revenue forecast
     */
    public function getForecast(int $tenantId, int $months = 12): array
    {
        $cacheKey = "revenue_forecast_{$tenantId}_{$months}_" . now()->format('Y-m-d');

        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $months) {
            return [
                'current_mrr' => $this->calculateCurrentMRR($tenantId),
                'forecast' => $this->generateMonthlyForecast($tenantId, $months),
                'scenarios' => $this->generateScenarios($tenantId, $months),
                'breakdown' => $this->getRevenueBreakdown($tenantId),
                'growth_metrics' => $this->calculateGrowthMetrics($tenantId),
                'revenue_at_risk' => $this->calculateRevenueAtRisk($tenantId),
                'pipeline_revenue' => $this->calculatePipelineRevenue($tenantId),
                'historical' => $this->getHistoricalRevenue($tenantId, 12),
                'seasonality' => $this->analyzeSeasonality($tenantId),
                'confidence' => $this->calculateForecastConfidence($tenantId),
            ];
        });
    }

    /**
     * Calculate current MRR (Monthly Recurring Revenue)
     */
    public function calculateCurrentMRR(int $tenantId): array
    {
        $activeContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('box')
            ->get();

        $baseMRR = $activeContracts->sum('monthly_price');

        // Add insurance revenue
        $insuranceMRR = $activeContracts
            ->where('has_insurance', true)
            ->sum('insurance_monthly_price');

        // Add other add-ons
        $addonsMRR = $activeContracts->sum('addons_monthly_total');

        $totalMRR = $baseMRR + $insuranceMRR + $addonsMRR;

        return [
            'total' => round($totalMRR, 2),
            'base_rent' => round($baseMRR, 2),
            'insurance' => round($insuranceMRR, 2),
            'addons' => round($addonsMRR, 2),
            'contract_count' => $activeContracts->count(),
            'average_contract_value' => $activeContracts->count() > 0
                ? round($totalMRR / $activeContracts->count(), 2)
                : 0,
        ];
    }

    /**
     * Generate monthly revenue forecast
     */
    public function generateMonthlyForecast(int $tenantId, int $months): array
    {
        $forecast = [];
        $currentMRR = $this->calculateCurrentMRR($tenantId)['total'];

        // Get historical growth rate
        $growthRate = $this->calculateHistoricalGrowthRate($tenantId);

        // Get churn rate
        $churnRate = $this->calculateMonthlyChurnRate($tenantId);

        // Get expected new revenue
        $expectedNewRevenue = $this->calculateExpectedNewRevenue($tenantId);

        // Seasonality factors
        $seasonality = $this->getSeasonalityFactors($tenantId);

        $runningMRR = $currentMRR;

        for ($i = 0; $i < $months; $i++) {
            $date = now()->addMonths($i);
            $monthKey = $date->format('Y-m');
            $monthNum = (int) $date->format('n');

            // Apply seasonality
            $seasonalFactor = $seasonality[$monthNum] ?? 1.0;

            // Calculate expected churn
            $churnLoss = $runningMRR * ($churnRate / 100) * $seasonalFactor;

            // Calculate expected new revenue (contracts ending this month that might renew)
            $renewalRevenue = $this->getExpectedRenewals($tenantId, $date);

            // Calculate new customer revenue
            $newRevenue = $expectedNewRevenue * $seasonalFactor;

            // Expansion revenue (upgrades, addons)
            $expansionRevenue = $runningMRR * 0.02; // Assume 2% expansion

            // Calculate new MRR
            $netChange = $newRevenue + $expansionRevenue + $renewalRevenue - $churnLoss;
            $runningMRR = max(0, $runningMRR + $netChange);

            // Confidence decreases for further months
            $confidence = max(50, 95 - ($i * 3));

            // Calculate bounds
            $variance = $runningMRR * (0.05 + ($i * 0.02));
            $lowerBound = $runningMRR - $variance;
            $upperBound = $runningMRR + $variance;

            $forecast[] = [
                'month' => $monthKey,
                'month_label' => $date->translatedFormat('M Y'),
                'predicted_mrr' => round($runningMRR, 2),
                'lower_bound' => round(max(0, $lowerBound), 2),
                'upper_bound' => round($upperBound, 2),
                'confidence' => $confidence,
                'breakdown' => [
                    'starting_mrr' => round($runningMRR - $netChange, 2),
                    'new_revenue' => round($newRevenue, 2),
                    'expansion_revenue' => round($expansionRevenue, 2),
                    'renewal_revenue' => round($renewalRevenue, 2),
                    'churn_loss' => round($churnLoss, 2),
                    'net_change' => round($netChange, 2),
                ],
                'annual_projection' => round($runningMRR * 12, 2),
            ];
        }

        return $forecast;
    }

    /**
     * Generate best/worst/expected scenarios
     */
    public function generateScenarios(int $tenantId, int $months): array
    {
        $currentMRR = $this->calculateCurrentMRR($tenantId)['total'];
        $baseGrowthRate = $this->calculateHistoricalGrowthRate($tenantId);
        $baseChurnRate = $this->calculateMonthlyChurnRate($tenantId);

        $scenarios = [
            'optimistic' => [
                'name' => 'Optimiste',
                'growth_rate' => $baseGrowthRate * 1.5,
                'churn_rate' => $baseChurnRate * 0.7,
                'description' => 'Croissance forte, churn reduit',
                'color' => 'emerald',
            ],
            'expected' => [
                'name' => 'Attendu',
                'growth_rate' => $baseGrowthRate,
                'churn_rate' => $baseChurnRate,
                'description' => 'Tendances actuelles maintenues',
                'color' => 'blue',
            ],
            'pessimistic' => [
                'name' => 'Pessimiste',
                'growth_rate' => $baseGrowthRate * 0.5,
                'churn_rate' => $baseChurnRate * 1.5,
                'description' => 'Croissance faible, churn eleve',
                'color' => 'red',
            ],
        ];

        foreach ($scenarios as $key => &$scenario) {
            $mrr = $currentMRR;
            $projection = [];

            for ($i = 0; $i < $months; $i++) {
                $date = now()->addMonths($i);
                $growth = $mrr * ($scenario['growth_rate'] / 100);
                $churn = $mrr * ($scenario['churn_rate'] / 100);
                $mrr = max(0, $mrr + $growth - $churn);

                $projection[] = [
                    'month' => $date->format('Y-m'),
                    'mrr' => round($mrr, 2),
                ];
            }

            $scenario['projection'] = $projection;
            $scenario['final_mrr'] = round($mrr, 2);
            $scenario['total_revenue'] = round(collect($projection)->sum('mrr'), 2);
            $scenario['growth_percent'] = round((($mrr - $currentMRR) / max(1, $currentMRR)) * 100, 1);
        }

        return $scenarios;
    }

    /**
     * Get revenue breakdown by category
     */
    public function getRevenueBreakdown(int $tenantId): array
    {
        $contracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('box')
            ->get();

        // By box size
        $bySize = $contracts->groupBy(function ($c) {
            $size = $c->box?->size_m2 ?? 0;
            if ($size <= 3) return 'XS (1-3m2)';
            if ($size <= 6) return 'S (4-6m2)';
            if ($size <= 10) return 'M (7-10m2)';
            if ($size <= 20) return 'L (11-20m2)';
            return 'XL (20m2+)';
        })->map(fn($group) => [
            'count' => $group->count(),
            'revenue' => round($group->sum('monthly_price'), 2),
            'avg_price' => round($group->avg('monthly_price'), 2),
        ]);

        // By customer type
        $byCustomerType = $contracts->groupBy(function ($c) {
            return $c->customer?->type ?? 'individual';
        })->map(fn($group) => [
            'count' => $group->count(),
            'revenue' => round($group->sum('monthly_price'), 2),
        ]);

        // By site
        $bySite = $contracts->groupBy(function ($c) {
            return $c->box?->site?->name ?? 'Unknown';
        })->map(fn($group) => [
            'count' => $group->count(),
            'revenue' => round($group->sum('monthly_price'), 2),
        ]);

        // By contract duration
        $byDuration = $contracts->groupBy(function ($c) {
            $months = $c->start_date && $c->end_date
                ? $c->start_date->diffInMonths($c->end_date)
                : 1;
            if ($months <= 1) return 'Mensuel';
            if ($months <= 6) return '3-6 mois';
            if ($months <= 12) return '12 mois';
            return 'Long terme';
        })->map(fn($group) => [
            'count' => $group->count(),
            'revenue' => round($group->sum('monthly_price'), 2),
        ]);

        return [
            'by_size' => $bySize->toArray(),
            'by_customer_type' => $byCustomerType->toArray(),
            'by_site' => $bySite->toArray(),
            'by_duration' => $byDuration->toArray(),
        ];
    }

    /**
     * Calculate growth metrics
     */
    public function calculateGrowthMetrics(int $tenantId): array
    {
        $currentMRR = $this->calculateCurrentMRR($tenantId)['total'];

        // Last month MRR
        $lastMonthMRR = $this->getMRRAtDate($tenantId, now()->subMonth());
        $monthlyGrowth = $lastMonthMRR > 0
            ? (($currentMRR - $lastMonthMRR) / $lastMonthMRR) * 100
            : 0;

        // Last quarter MRR
        $lastQuarterMRR = $this->getMRRAtDate($tenantId, now()->subMonths(3));
        $quarterlyGrowth = $lastQuarterMRR > 0
            ? (($currentMRR - $lastQuarterMRR) / $lastQuarterMRR) * 100
            : 0;

        // Last year MRR
        $lastYearMRR = $this->getMRRAtDate($tenantId, now()->subYear());
        $yearlyGrowth = $lastYearMRR > 0
            ? (($currentMRR - $lastYearMRR) / $lastYearMRR) * 100
            : 0;

        // New MRR this month
        $newMRR = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('monthly_price');

        // Churned MRR this month
        $churnedMRR = Contract::where('tenant_id', $tenantId)
            ->whereIn('status', ['terminated', 'cancelled'])
            ->where('end_date', '>=', now()->startOfMonth())
            ->sum('monthly_price');

        // Net MRR change
        $netMRRChange = $newMRR - $churnedMRR;

        return [
            'current_mrr' => round($currentMRR, 2),
            'arr' => round($currentMRR * 12, 2),
            'monthly_growth_rate' => round($monthlyGrowth, 2),
            'quarterly_growth_rate' => round($quarterlyGrowth, 2),
            'yearly_growth_rate' => round($yearlyGrowth, 2),
            'new_mrr' => round($newMRR, 2),
            'churned_mrr' => round($churnedMRR, 2),
            'net_mrr_change' => round($netMRRChange, 2),
            'expansion_mrr' => round($netMRRChange - $newMRR + $churnedMRR, 2),
            'quick_ratio' => $churnedMRR > 0
                ? round($newMRR / $churnedMRR, 2)
                : null,
        ];
    }

    /**
     * Calculate revenue at risk from expiring contracts
     */
    public function calculateRevenueAtRisk(int $tenantId): array
    {
        $periods = [
            '7_days' => now()->addDays(7),
            '30_days' => now()->addDays(30),
            '60_days' => now()->addDays(60),
            '90_days' => now()->addDays(90),
        ];

        $atRisk = [];

        foreach ($periods as $key => $endDate) {
            $contracts = Contract::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('end_date', '>=', now())
                ->where('end_date', '<=', $endDate)
                ->where(function ($q) {
                    $q->where('auto_renewal', false)
                      ->orWhereNull('auto_renewal');
                })
                ->get();

            $atRisk[$key] = [
                'contracts' => $contracts->count(),
                'monthly_revenue' => round($contracts->sum('monthly_price'), 2),
                'annual_revenue' => round($contracts->sum('monthly_price') * 12, 2),
            ];
        }

        return $atRisk;
    }

    /**
     * Calculate pipeline revenue (leads & prospects)
     */
    public function calculatePipelineRevenue(int $tenantId): array
    {
        // Leads with budget info
        $leads = Lead::where('tenant_id', $tenantId)
            ->whereIn('status', ['new', 'contacted', 'qualified', 'negotiation'])
            ->get();

        $leadPipeline = $leads->sum(function ($lead) {
            $budget = $lead->budget_max ?? $lead->budget_min ?? 0;
            $probability = match ($lead->status) {
                'new' => 0.10,
                'contacted' => 0.20,
                'qualified' => 0.40,
                'negotiation' => 0.60,
                default => 0.10,
            };
            return $budget * $probability;
        });

        // Prospects (reservations in progress)
        $prospects = Prospect::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'in_progress'])
            ->get();

        $prospectPipeline = $prospects->sum(function ($prospect) {
            $value = $prospect->estimated_monthly_value ?? 0;
            $probability = match ($prospect->status) {
                'pending' => 0.30,
                'in_progress' => 0.60,
                default => 0.20,
            };
            return $value * $probability;
        });

        return [
            'leads' => [
                'count' => $leads->count(),
                'weighted_value' => round($leadPipeline, 2),
                'total_potential' => round($leads->sum(fn($l) => $l->budget_max ?? $l->budget_min ?? 0), 2),
            ],
            'prospects' => [
                'count' => $prospects->count(),
                'weighted_value' => round($prospectPipeline, 2),
                'total_potential' => round($prospects->sum('estimated_monthly_value'), 2),
            ],
            'total_pipeline' => round($leadPipeline + $prospectPipeline, 2),
        ];
    }

    /**
     * Get historical revenue data
     */
    public function getHistoricalRevenue(int $tenantId, int $months): array
    {
        $history = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->copy()->startOfMonth();
            $endOfMonth = $date->copy()->endOfMonth();

            // Revenue from invoices
            $invoiceRevenue = Invoice::where('tenant_id', $tenantId)
                ->where('status', 'paid')
                ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
                ->sum('total_amount');

            // New contracts
            $newContracts = Contract::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Churned contracts
            $churnedContracts = Contract::where('tenant_id', $tenantId)
                ->whereIn('status', ['terminated', 'cancelled'])
                ->whereBetween('end_date', [$startOfMonth, $endOfMonth])
                ->count();

            $history[] = [
                'month' => $date->format('Y-m'),
                'month_label' => $date->translatedFormat('M Y'),
                'revenue' => round($invoiceRevenue, 2),
                'mrr' => $this->getMRRAtDate($tenantId, $endOfMonth),
                'new_contracts' => $newContracts,
                'churned_contracts' => $churnedContracts,
                'net_contracts' => $newContracts - $churnedContracts,
            ];
        }

        return $history;
    }

    /**
     * Analyze seasonality patterns
     */
    public function analyzeSeasonality(int $tenantId): array
    {
        // Get 2 years of data if available
        $invoices = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->where('paid_at', '>=', now()->subYears(2))
            ->select(DB::raw('MONTH(paid_at) as month'), DB::raw('SUM(total_amount) as revenue'))
            ->groupBy(DB::raw('MONTH(paid_at)'))
            ->get()
            ->keyBy('month');

        $avgRevenue = $invoices->avg('revenue') ?: 1;

        $seasonality = [];
        $monthNames = ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aou', 'Sep', 'Oct', 'Nov', 'Dec'];

        for ($m = 1; $m <= 12; $m++) {
            $revenue = $invoices[$m]?->revenue ?? $avgRevenue;
            $index = ($revenue / $avgRevenue) * 100;

            $seasonality[] = [
                'month' => $m,
                'month_name' => $monthNames[$m - 1],
                'index' => round($index, 1),
                'trend' => $index > 105 ? 'high' : ($index < 95 ? 'low' : 'normal'),
            ];
        }

        // Identify peak and low seasons
        $peak = collect($seasonality)->sortByDesc('index')->first();
        $low = collect($seasonality)->sortBy('index')->first();

        return [
            'monthly' => $seasonality,
            'peak_season' => [
                'month' => $peak['month_name'],
                'index' => $peak['index'],
            ],
            'low_season' => [
                'month' => $low['month_name'],
                'index' => $low['index'],
            ],
            'variance' => round(collect($seasonality)->std('index'), 1),
        ];
    }

    /**
     * Calculate forecast confidence
     */
    protected function calculateForecastConfidence(int $tenantId): array
    {
        // Factors affecting confidence
        $dataPoints = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->count();

        $monthsOfData = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->selectRaw('COUNT(DISTINCT DATE_FORMAT(paid_at, "%Y-%m")) as months')
            ->first()
            ->months ?? 0;

        $dataQuality = min(100, ($dataPoints / 100) * 50 + ($monthsOfData / 24) * 50);

        // Volatility check
        $revenueHistory = $this->getHistoricalRevenue($tenantId, 6);
        $revenues = collect($revenueHistory)->pluck('revenue');
        $volatility = $revenues->count() > 1
            ? ($revenues->std() / max(1, $revenues->avg())) * 100
            : 50;

        $volatilityScore = max(0, 100 - $volatility);

        $overallConfidence = ($dataQuality * 0.6) + ($volatilityScore * 0.4);

        return [
            'overall' => round($overallConfidence, 0),
            'data_quality' => round($dataQuality, 0),
            'volatility_score' => round($volatilityScore, 0),
            'months_of_data' => $monthsOfData,
            'data_points' => $dataPoints,
            'recommendation' => $overallConfidence >= 70
                ? 'Previsions fiables'
                : ($overallConfidence >= 50
                    ? 'Previsions indicatives'
                    : 'Plus de donnees necessaires'),
        ];
    }

    /**
     * Get MRR at a specific date
     */
    protected function getMRRAtDate(int $tenantId, Carbon $date): float
    {
        return Contract::where('tenant_id', $tenantId)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                  ->orWhere('end_date', '>=', $date);
            })
            ->whereIn('status', ['active', 'terminated'])
            ->sum('monthly_price');
    }

    /**
     * Calculate historical growth rate
     */
    protected function calculateHistoricalGrowthRate(int $tenantId): float
    {
        $history = $this->getHistoricalRevenue($tenantId, 6);

        if (count($history) < 2) {
            return 3.0; // Default 3% monthly growth
        }

        $growthRates = [];
        for ($i = 1; $i < count($history); $i++) {
            $prev = $history[$i - 1]['mrr'];
            $curr = $history[$i]['mrr'];
            if ($prev > 0) {
                $growthRates[] = (($curr - $prev) / $prev) * 100;
            }
        }

        return count($growthRates) > 0 ? array_sum($growthRates) / count($growthRates) : 3.0;
    }

    /**
     * Calculate monthly churn rate
     */
    protected function calculateMonthlyChurnRate(int $tenantId): float
    {
        $totalContracts = Contract::where('tenant_id', $tenantId)
            ->where('created_at', '<=', now()->subMonth())
            ->count();

        $churnedContracts = Contract::where('tenant_id', $tenantId)
            ->whereIn('status', ['terminated', 'cancelled'])
            ->where('end_date', '>=', now()->subMonth())
            ->where('end_date', '<=', now())
            ->count();

        return $totalContracts > 0 ? ($churnedContracts / $totalContracts) * 100 : 5.0;
    }

    /**
     * Calculate expected new revenue from pipeline
     */
    protected function calculateExpectedNewRevenue(int $tenantId): float
    {
        $history = $this->getHistoricalRevenue($tenantId, 6);
        $avgNewContracts = collect($history)->avg('new_contracts') ?: 2;

        // Average contract value
        $avgContractValue = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->avg('monthly_price') ?: 80;

        return $avgNewContracts * $avgContractValue;
    }

    /**
     * Get seasonality factors
     */
    protected function getSeasonalityFactors(int $tenantId): array
    {
        $seasonality = $this->analyzeSeasonality($tenantId);
        $factors = [];

        foreach ($seasonality['monthly'] as $month) {
            $factors[$month['month']] = $month['index'] / 100;
        }

        return $factors;
    }

    /**
     * Get expected renewals for a month
     */
    protected function getExpectedRenewals(int $tenantId, Carbon $date): float
    {
        $expiringContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereBetween('end_date', [
                $date->copy()->startOfMonth(),
                $date->copy()->endOfMonth(),
            ])
            ->get();

        // Assume 70% renewal rate for auto-renewal, 40% for manual
        return $expiringContracts->sum(function ($contract) {
            $renewalRate = $contract->auto_renewal ? 0.70 : 0.40;
            return $contract->monthly_price * $renewalRate;
        });
    }
}
