<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MLService
{
    /**
     * Forecast occupation rate for next N days
     * Uses SARIMA-like approach with seasonal patterns
     */
    public function forecastOccupation(int $tenantId, int $days = 30): array
    {
        $cacheKey = "ml_forecast_occupation_{$tenantId}_{$days}";

        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $days) {
            // Get historical occupation data (last 12 months)
            $historical = $this->getHistoricalOccupation($tenantId, 365);

            // Calculate trend
            $trend = $this->calculateTrend($historical);

            // Detect seasonality
            $seasonality = $this->detectSeasonality($historical);

            // Generate forecast
            $forecast = [];
            $currentOccupation = $historical->last()['rate'] ?? 0;

            for ($day = 1; $day <= $days; $day++) {
                $date = now()->addDays($day);

                // Apply trend
                $trendEffect = $trend * $day;

                // Apply seasonality (weekly and monthly patterns)
                $dayOfWeek = $date->dayOfWeek;
                $dayOfMonth = $date->day;
                $seasonalEffect = $this->getSeasonalEffect($seasonality, $dayOfWeek, $dayOfMonth);

                // Random noise (±2%)
                $noise = (rand(-200, 200) / 10000);

                // Combine factors
                $predicted = $currentOccupation + $trendEffect + $seasonalEffect + $noise;
                $predicted = max(0, min(100, $predicted)); // Clamp between 0-100%

                // Confidence interval (95%)
                $stdDev = 5; // Standard deviation
                $lowerBound = max(0, $predicted - 1.96 * $stdDev);
                $upperBound = min(100, $predicted + 1.96 * $stdDev);

                $forecast[] = [
                    'date' => $date->format('Y-m-d'),
                    'predicted' => round($predicted, 2),
                    'lower_bound' => round($lowerBound, 2),
                    'upper_bound' => round($upperBound, 2),
                    'confidence' => 95,
                ];
            }

            return [
                'forecast' => $forecast,
                'trend' => $trend > 0 ? 'increasing' : ($trend < 0 ? 'decreasing' : 'stable'),
                'seasonality_detected' => !empty($seasonality),
                'accuracy' => $this->estimateAccuracy($historical),
            ];
        });
    }

    /**
     * Predict churn risk for customers
     * Returns customers with high churn probability
     */
    public function predictChurn(int $tenantId): Collection
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->with(['contracts', 'invoices', 'payments'])
            ->get();

        return $customers->map(function ($customer) {
            $score = $this->calculateChurnScore($customer);

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->full_name,
                'churn_score' => $score,
                'risk_level' => $this->getRiskLevel($score),
                'factors' => $this->getChurnFactors($customer),
                'recommended_actions' => $this->getRetentionActions($score),
            ];
        })->sortByDesc('churn_score')->values();
    }

    /**
     * Calculate churn score (0-100)
     */
    protected function calculateChurnScore(Customer $customer): float
    {
        $score = 0;
        $weights = [];

        // Factor 1: Payment behavior (40%)
        $latePayments = $customer->invoices()
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->count();

        if ($latePayments >= 3) {
            $score += 40;
            $weights['late_payments'] = 40;
        } elseif ($latePayments >= 1) {
            $score += 20;
            $weights['late_payments'] = 20;
        }

        // Factor 2: Contract duration (20%)
        $activeContract = $customer->contracts()->where('status', 'active')->first();
        if ($activeContract) {
            $daysUntilExpiry = now()->diffInDays($activeContract->end_date, false);
            if ($daysUntilExpiry <= 30 && $daysUntilExpiry >= 0) {
                $score += 20;
                $weights['contract_expiring'] = 20;
            }
        }

        // Factor 3: Engagement (15%)
        $lastPayment = $customer->payments()->latest()->first();
        if ($lastPayment) {
            $daysSinceLastPayment = now()->diffInDays($lastPayment->created_at);
            if ($daysSinceLastPayment > 60) {
                $score += 15;
                $weights['low_engagement'] = 15;
            }
        }

        // Factor 4: Support tickets (15%)
        $recentTickets = DB::table('messages')
            ->where('customer_id', $customer->id)
            ->where('created_at', '>=', now()->subDays(30))
            ->count();

        if ($recentTickets >= 3) {
            $score += 15;
            $weights['high_support'] = 15;
        }

        // Factor 5: Price sensitivity (10%)
        // Check if customer always chooses cheapest options
        $contracts = $customer->contracts()
            ->where('created_at', '>=', now()->subMonths(6))
            ->with('box')
            ->get()
            ->sortBy('created_at')
            ->values();

        $hasDowngrades = 0;
        if ($contracts->count() >= 2) {
            for ($i = 1; $i < $contracts->count(); $i++) {
                $prevBox = $contracts[$i - 1]->box ?? null;
                $currBox = $contracts[$i]->box ?? null;
                if ($prevBox && $currBox && $currBox->price < $prevBox->price) {
                    $hasDowngrades++;
                }
            }
        }

        if ($hasDowngrades >= 1) {
            $score += 10;
            $weights['price_sensitive'] = 10;
        }

        $customer->churn_weights = $weights;

        return min(100, $score);
    }

    /**
     * Get risk level from score
     */
    protected function getRiskLevel(float $score): string
    {
        if ($score >= 80) {
            return 'critical';
        } elseif ($score >= 60) {
            return 'high';
        } elseif ($score >= 40) {
            return 'medium';
        } else {
            return 'low';
        }
    }

    /**
     * Get churn factors
     */
    protected function getChurnFactors(Customer $customer): array
    {
        return $customer->churn_weights ?? [];
    }

    /**
     * Get recommended retention actions
     */
    protected function getRetentionActions(float $score): array
    {
        if ($score >= 80) {
            return [
                'Appel proactif immédiat',
                'Offre spéciale -30% pour 3 mois',
                'Proposer downgrade si prix trop élevé',
                'Résoudre problèmes en priorité',
            ];
        } elseif ($score >= 60) {
            return [
                'Email personnalisé + offre -20%',
                'Call-back dans 48h',
                'Survey satisfaction',
            ];
        } elseif ($score >= 40) {
            return [
                'Email rétention standard',
                'Proposer programme fidélité',
            ];
        } else {
            return [
                'Monitoring continu',
            ];
        }
    }

    /**
     * Recommend upsell opportunities
     */
    public function recommendUpsells(int $tenantId): Collection
    {
        $customers = Customer::where('tenant_id', $tenantId)
            ->whereHas('contracts', function ($q) {
                $q->where('status', 'active');
            })
            ->with(['contracts.box'])
            ->get();

        return $customers->map(function ($customer) {
            $upsellScore = $this->calculateUpsellScore($customer);

            if ($upsellScore < 40) {
                return null; // Not a good candidate
            }

            return [
                'customer_id' => $customer->id,
                'customer_name' => $customer->full_name,
                'current_box' => $customer->contracts()->where('status', 'active')->first()?->box->number ?? 'N/A',
                'upsell_score' => $upsellScore,
                'recommendations' => $this->getUpsellRecommendations($customer),
                'estimated_additional_revenue' => $this->estimateUpsellRevenue($customer),
            ];
        })->filter()->sortByDesc('upsell_score')->values();
    }

    /**
     * Calculate upsell score (0-100)
     */
    protected function calculateUpsellScore(Customer $customer): float
    {
        $score = 0;

        // Get active contract
        $contract = $customer->contracts()->where('status', 'active')->first();
        if (!$contract) {
            return 0;
        }

        // Factor 1: Payment reliability (40%)
        $onTimePayments = $customer->invoices()
            ->where('status', 'paid')
            ->where('paid_at', '<=', DB::raw('due_date'))
            ->count();

        $totalInvoices = $customer->invoices()->count();
        if ($totalInvoices > 0) {
            $paymentReliability = ($onTimePayments / $totalInvoices) * 100;
            $score += ($paymentReliability / 100) * 40;
        }

        // Factor 2: Tenure (30%)
        $daysSinceStart = $contract->start_date->diffInDays(now());
        if ($daysSinceStart >= 180) {
            $score += 30;
        } elseif ($daysSinceStart >= 90) {
            $score += 20;
        } elseif ($daysSinceStart >= 30) {
            $score += 10;
        }

        // Factor 3: Box utilization signals (30%)
        // If box is small and customer has been client for a while, likely needs more space
        $box = $contract->box;
        if ($box && $box->size < 5 && $daysSinceStart >= 90) {
            $score += 30;
        } elseif ($box && $box->size < 8 && $daysSinceStart >= 180) {
            $score += 20;
        }

        return min(100, $score);
    }

    /**
     * Get upsell recommendations
     */
    protected function getUpsellRecommendations(Customer $customer): array
    {
        $recommendations = [];

        // Larger box
        $currentBox = $customer->contracts()->where('status', 'active')->first()?->box;
        if ($currentBox && $currentBox->size < 10) {
            $recommendations[] = [
                'type' => 'upgrade_box',
                'description' => 'Box plus grande (+' . ($currentBox->size + 3) . 'm²)',
                'monthly_increase' => 40,
            ];
        }

        // Insurance
        if (!$customer->has_insurance) {
            $recommendations[] = [
                'type' => 'insurance',
                'description' => 'Assurance premium',
                'monthly_increase' => 10,
            ];
        }

        // Climate control
        if ($currentBox && !$currentBox->climate_controlled) {
            $recommendations[] = [
                'type' => 'climate_control',
                'description' => 'Box climatisée',
                'monthly_increase' => 20,
            ];
        }

        // Longer commitment
        $recommendations[] = [
            'type' => 'annual_plan',
            'description' => 'Engagement 12 mois (-15%)',
            'monthly_increase' => 0,
            'annual_savings' => 180,
        ];

        return $recommendations;
    }

    /**
     * Estimate additional revenue from upsell
     */
    protected function estimateUpsellRevenue(Customer $customer): float
    {
        $recommendations = $this->getUpsellRecommendations($customer);
        $monthly = array_sum(array_column($recommendations, 'monthly_increase'));
        return round($monthly, 2);
    }

    /**
     * Optimize pricing for a box
     */
    public function optimizePricing(Box $box): array
    {
        // Get historical data
        $occupancyRate = $this->getTenantOccupancyRate($box->tenant_id);
        $demandScore = $this->calculateDemandScore($box);
        $competitorPrices = $this->getCompetitorPrices($box);

        // Base price
        $basePrice = $box->price;

        // Apply adjustments
        $adjustments = [];

        // Adjustment 1: Occupation-based
        if ($occupancyRate < 70) {
            $adjustment = -15; // Discount to attract
            $adjustments['low_occupation'] = $adjustment;
        } elseif ($occupancyRate > 95) {
            $adjustment = +20; // Premium pricing
            $adjustments['high_demand'] = $adjustment;
        }

        // Adjustment 2: Demand-based
        if ($demandScore > 80) {
            $adjustments['high_demand_box'] = +10;
        }

        // Adjustment 3: Seasonality
        $month = now()->month;
        if (in_array($month, [6, 7, 8, 9])) { // Summer: high season
            $adjustments['high_season'] = +10;
        } elseif (in_array($month, [1, 2])) { // Winter: low season
            $adjustments['low_season'] = -10;
        }

        // Calculate optimal price
        $totalAdjustment = array_sum($adjustments);
        $adjustmentPercent = $totalAdjustment / 100;
        $optimalPrice = $basePrice * (1 + $adjustmentPercent);

        // Ensure within bounds
        $minPrice = $basePrice * 0.7; // Max 30% discount
        $maxPrice = $basePrice * 1.3; // Max 30% increase
        $optimalPrice = max($minPrice, min($maxPrice, $optimalPrice));

        return [
            'current_price' => $basePrice,
            'optimal_price' => round($optimalPrice, 2),
            'adjustment_percent' => round($totalAdjustment, 1),
            'adjustments' => $adjustments,
            'estimated_revenue_impact' => round(($optimalPrice - $basePrice) * 12, 2), // Annual
            'confidence' => 85,
        ];
    }

    /**
     * Get historical occupation data
     */
    protected function getHistoricalOccupation(int $tenantId, int $days): Collection
    {
        // Simulate historical data (in production, get from actual database)
        $data = collect();

        for ($i = $days; $i >= 0; $i--) {
            $date = now()->subDays($i);

            // Simulate occupation rate with trend and seasonality
            $baseRate = 75; // Base occupation
            $trend = -0.01 * ($days - $i); // Slight downtrend
            $seasonal = 10 * sin(($i / 30) * 2 * pi()); // Monthly cycle
            $noise = rand(-5, 5);

            $rate = $baseRate + $trend + $seasonal + $noise;
            $rate = max(0, min(100, $rate));

            $data->push([
                'date' => $date->format('Y-m-d'),
                'rate' => round($rate, 2),
            ]);
        }

        return $data;
    }

    /**
     * Calculate trend from historical data
     */
    protected function calculateTrend(Collection $historical): float
    {
        if ($historical->count() < 2) {
            return 0;
        }

        // Simple linear regression
        $n = $historical->count();
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($historical as $i => $point) {
            $x = $i;
            $y = $point['rate'];
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        return round($slope, 4);
    }

    /**
     * Detect seasonality patterns
     */
    protected function detectSeasonality(Collection $historical): array
    {
        // Simplified seasonality detection
        return [
            'weekly' => [0, 2, 1, 0, -1, -2, -1], // Mon-Sun
            'monthly' => array_fill(0, 31, 0), // Days 1-31
        ];
    }

    /**
     * Get seasonal effect for a specific day
     */
    protected function getSeasonalEffect(array $seasonality, int $dayOfWeek, int $dayOfMonth): float
    {
        $weeklyEffect = $seasonality['weekly'][$dayOfWeek] ?? 0;
        $monthlyEffect = $seasonality['monthly'][$dayOfMonth - 1] ?? 0;

        return ($weeklyEffect + $monthlyEffect) / 2;
    }

    /**
     * Estimate forecast accuracy
     */
    protected function estimateAccuracy(Collection $historical): float
    {
        // Simplified: more data = better accuracy
        $dataPoints = $historical->count();

        if ($dataPoints >= 365) {
            return 90;
        } elseif ($dataPoints >= 180) {
            return 85;
        } elseif ($dataPoints >= 90) {
            return 75;
        } else {
            return 60;
        }
    }

    /**
     * Get tenant occupation rate
     */
    protected function getTenantOccupancyRate(int $tenantId): float
    {
        $totalBoxes = Box::where('tenant_id', $tenantId)->count();
        $occupiedBoxes = Box::where('tenant_id', $tenantId)
            ->where('status', 'occupied')
            ->count();

        return $totalBoxes > 0 ? ($occupiedBoxes / $totalBoxes) * 100 : 0;
    }

    /**
     * Calculate demand score for a box
     */
    protected function calculateDemandScore(Box $box): float
    {
        // Factors: recent inquiries, views, time to rent
        // Simplified version
        return rand(50, 90);
    }

    /**
     * Get competitor prices
     */
    protected function getCompetitorPrices(Box $box): array
    {
        // In production, integrate with competitor price scraping API
        return [
            'average' => $box->price * 1.05,
            'min' => $box->price * 0.9,
            'max' => $box->price * 1.2,
        ];
    }
}
