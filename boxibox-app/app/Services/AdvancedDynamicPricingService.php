<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Site;
use App\Models\PriceHistory;
use App\Models\PricingExperiment;
use App\Models\ExperimentExposure;
use App\Models\DemandForecast;
use App\Models\CompetitorPrice;
use App\Models\PriceAdjustment;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;

class AdvancedDynamicPricingService
{
    protected DynamicPricingService $baseService;

    // ML feature weights (trained weights would come from model)
    protected array $featureWeights = [
        'occupancy' => 0.35,
        'demand' => 0.25,
        'competitor' => 0.15,
        'seasonality' => 0.15,
        'day_of_week' => 0.10,
    ];

    public function __construct(DynamicPricingService $baseService)
    {
        $this->baseService = $baseService;
    }

    /**
     * Calculate optimal price using ML-enhanced algorithm
     */
    public function calculateMLPrice(Box $box, array $options = []): array
    {
        $cacheKey = "ml_price_{$box->id}_" . md5(json_encode($options));

        return Cache::remember($cacheKey, 300, function () use ($box, $options) {
            $basePrice = $box->current_price ?? $box->base_price ?? 0;

            // Calculate individual factors
            $factors = $this->calculatePricingFactors($box, $options);

            // Apply ML model prediction
            $mlMultiplier = $this->predictOptimalMultiplier($box, $factors);

            // Calculate confidence based on data availability
            $confidence = $this->calculateConfidence($box);

            // Final price calculation
            $calculatedPrice = $basePrice * $mlMultiplier;

            // Apply bounds (max 30% discount for revenue protection)
            $minPrice = $basePrice * 0.70; // Max 30% discount
            $maxPrice = $basePrice * 2.0;
            $finalPrice = max($minPrice, min($maxPrice, $calculatedPrice));

            // Check for active A/B test
            $abTestResult = $this->applyABTest($box, $finalPrice, $options['visitor_id'] ?? null);
            if ($abTestResult) {
                $finalPrice = $abTestResult['price'];
            }

            return [
                'base_price' => $basePrice,
                'calculated_price' => round($calculatedPrice, 2),
                'final_price' => round($finalPrice, 2),
                'multiplier' => round($mlMultiplier, 4),
                'factors' => $factors,
                'confidence' => $confidence,
                'ab_test' => $abTestResult,
                'price_change_percentage' => $basePrice > 0 ? round(($finalPrice - $basePrice) / $basePrice * 100, 1) : 0,
            ];
        });
    }

    /**
     * Calculate all pricing factors
     */
    protected function calculatePricingFactors(Box $box, array $options): array
    {
        $site = $box->site;
        $tenantId = $box->tenant_id;

        // 1. Occupancy factor
        $occupancyRate = $this->calculateOccupancyRate($site);
        $occupancyFactor = $this->getOccupancyFactor($occupancyRate);

        // 2. Demand factor (based on recent inquiries/views)
        $demandFactor = $this->getDemandFactor($box);

        // 3. Competitor factor
        $competitorFactor = $this->getCompetitorFactor($box);

        // 4. Seasonality factor
        $seasonalityFactor = $this->getSeasonalityFactor();

        // 5. Day of week factor
        $dayOfWeekFactor = $this->getDayOfWeekFactor();

        // 6. Time since available factor
        $timeFactor = $this->getTimeSinceAvailableFactor($box);

        // 7. Box features premium
        $featuresPremium = $this->getFeaturesPremium($box);

        return [
            'occupancy' => [
                'rate' => $occupancyRate,
                'factor' => $occupancyFactor,
                'weight' => $this->featureWeights['occupancy'],
            ],
            'demand' => [
                'level' => $this->getDemandLevel($box),
                'factor' => $demandFactor,
                'weight' => $this->featureWeights['demand'],
            ],
            'competitor' => [
                'average_price' => $this->getCompetitorAveragePrice($box),
                'factor' => $competitorFactor,
                'weight' => $this->featureWeights['competitor'],
            ],
            'seasonality' => [
                'season' => $this->getCurrentSeason(),
                'factor' => $seasonalityFactor,
                'weight' => $this->featureWeights['seasonality'],
            ],
            'day_of_week' => [
                'day' => now()->dayOfWeek,
                'is_weekend' => now()->isWeekend(),
                'factor' => $dayOfWeekFactor,
                'weight' => $this->featureWeights['day_of_week'],
            ],
            'time_available' => [
                'days' => $this->getDaysAvailable($box),
                'factor' => $timeFactor,
            ],
            'features_premium' => [
                'factor' => $featuresPremium,
                'details' => $this->getFeatureDetails($box),
            ],
        ];
    }

    /**
     * Predict optimal price multiplier using ML model
     */
    protected function predictOptimalMultiplier(Box $box, array $factors): float
    {
        // Weighted average of factors
        $weightedSum = 0;
        $totalWeight = 0;

        foreach (['occupancy', 'demand', 'competitor', 'seasonality', 'day_of_week'] as $key) {
            if (isset($factors[$key])) {
                $weightedSum += $factors[$key]['factor'] * $factors[$key]['weight'];
                $totalWeight += $factors[$key]['weight'];
            }
        }

        $baseMultiplier = $totalWeight > 0 ? $weightedSum / $totalWeight : 1.0;

        // Apply time factor (boxes available too long get discounted)
        $baseMultiplier *= $factors['time_available']['factor'] ?? 1.0;

        // Apply features premium
        $baseMultiplier *= $factors['features_premium']['factor'] ?? 1.0;

        // Use historical conversion data to refine
        $historicalAdjustment = $this->getHistoricalAdjustment($box, $baseMultiplier);

        return $baseMultiplier * $historicalAdjustment;
    }

    /**
     * Get occupancy-based pricing factor
     */
    protected function getOccupancyFactor(float $rate): float
    {
        // More granular occupancy-based pricing
        return match (true) {
            $rate < 0.50 => 0.70,  // Very low: -30%
            $rate < 0.60 => 0.80,  // Low: -20%
            $rate < 0.70 => 0.85,  // Below target: -15%
            $rate < 0.80 => 0.95,  // Approaching target: -5%
            $rate < 0.85 => 1.00,  // At target: standard
            $rate < 0.90 => 1.05,  // Above target: +5%
            $rate < 0.95 => 1.15,  // High: +15%
            default => 1.25,       // Very high: +25%
        };
    }

    /**
     * Get demand-based pricing factor
     */
    protected function getDemandFactor(Box $box): float
    {
        $forecast = DemandForecast::where('site_id', $box->site_id)
            ->where('forecast_date', today())
            ->where('box_category', $this->getBoxCategory($box))
            ->first();

        if (!$forecast) {
            return 1.0;
        }

        return match (true) {
            $forecast->predicted_demand >= 10 => 1.20, // Very high demand: +20%
            $forecast->predicted_demand >= 5 => 1.10,  // High demand: +10%
            $forecast->predicted_demand >= 2 => 1.00,  // Normal demand
            $forecast->predicted_demand >= 1 => 0.95,  // Low demand: -5%
            default => 0.90,                           // Very low demand: -10%
        };
    }

    /**
     * Get competitor-based pricing factor
     */
    protected function getCompetitorFactor(Box $box): float
    {
        $competitorAvg = $this->getCompetitorAveragePrice($box);

        if (!$competitorAvg || $competitorAvg == 0) {
            return 1.0;
        }

        $ourPrice = $box->current_price ?? $box->base_price ?? 0;
        $priceDiff = ($ourPrice - $competitorAvg) / $competitorAvg;

        // Position ourselves competitively
        return match (true) {
            $priceDiff > 0.20 => 0.90,   // We're 20%+ more expensive: discount
            $priceDiff > 0.10 => 0.95,   // We're 10-20% more expensive: slight discount
            $priceDiff > -0.10 => 1.00,  // Within 10%: maintain
            $priceDiff > -0.20 => 1.05,  // We're 10-20% cheaper: can increase
            default => 1.10,              // We're 20%+ cheaper: increase more
        };
    }

    /**
     * Get competitor average price for similar boxes
     */
    protected function getCompetitorAveragePrice(Box $box): ?float
    {
        $category = $this->getBoxCategory($box);

        return CompetitorPrice::where('site_id', $box->site_id)
            ->where('box_category', $category)
            ->recent(30)
            ->avg('monthly_price');
    }

    /**
     * Get seasonality factor
     */
    protected function getSeasonalityFactor(): float
    {
        $month = now()->month;

        // French moving season patterns
        return match ($month) {
            6, 7 => 1.15,      // June-July: Peak moving season +15%
            8, 9 => 1.10,      // August-September: High season +10%
            5, 10 => 1.05,     // May, October: Shoulder season +5%
            1, 2, 12 => 0.90,  // Winter: Low season -10%
            11 => 0.95,        // November: -5%
            default => 1.00,   // Standard
        };
    }

    /**
     * Get day of week factor
     */
    protected function getDayOfWeekFactor(): float
    {
        $dayOfWeek = now()->dayOfWeek;

        // Weekend inquiries often convert better
        return match ($dayOfWeek) {
            0 => 1.05,     // Sunday: +5%
            6 => 1.03,     // Saturday: +3%
            5 => 1.02,     // Friday: +2%
            default => 1.0,
        };
    }

    /**
     * Get time since available factor
     */
    protected function getTimeSinceAvailableFactor(Box $box): float
    {
        if ($box->status !== 'available') {
            return 1.0;
        }

        $daysAvailable = $this->getDaysAvailable($box);

        // Progressive discount for boxes available too long
        return match (true) {
            $daysAvailable > 90 => 0.80,   // 3+ months: -20%
            $daysAvailable > 60 => 0.85,   // 2-3 months: -15%
            $daysAvailable > 30 => 0.90,   // 1-2 months: -10%
            $daysAvailable > 14 => 0.95,   // 2-4 weeks: -5%
            default => 1.0,
        };
    }

    /**
     * Get days a box has been available
     */
    protected function getDaysAvailable(Box $box): int
    {
        $lastContract = Contract::where('box_id', $box->id)
            ->whereIn('status', ['terminated', 'completed'])
            ->orderBy('end_date', 'desc')
            ->first();

        if ($lastContract && $lastContract->end_date) {
            return now()->diffInDays($lastContract->end_date);
        }

        return now()->diffInDays($box->created_at);
    }

    /**
     * Get features premium factor
     */
    protected function getFeaturesPremium(Box $box): float
    {
        $premium = 1.0;

        if ($box->climate_controlled) {
            $premium += 0.15; // +15% for climate control
        }

        if ($box->has_electricity) {
            $premium += 0.05; // +5% for electricity
        }

        if ($box->has_alarm) {
            $premium += 0.05; // +5% for alarm
        }

        if ($box->ground_floor ?? false) {
            $premium += 0.03; // +3% for ground floor
        }

        if ($box->drive_up_access ?? false) {
            $premium += 0.08; // +8% for drive-up access
        }

        return $premium;
    }

    /**
     * Get feature details for pricing explanation
     */
    protected function getFeatureDetails(Box $box): array
    {
        $details = [];

        if ($box->climate_controlled) {
            $details['climate_controlled'] = '+15%';
        }
        if ($box->has_electricity) {
            $details['electricity'] = '+5%';
        }
        if ($box->has_alarm) {
            $details['alarm'] = '+5%';
        }
        if ($box->ground_floor ?? false) {
            $details['ground_floor'] = '+3%';
        }
        if ($box->drive_up_access ?? false) {
            $details['drive_up_access'] = '+8%';
        }

        return $details;
    }

    /**
     * Get historical conversion adjustment
     */
    protected function getHistoricalAdjustment(Box $box, float $multiplier): float
    {
        // Get recent price history for similar boxes
        $history = PriceHistory::where('site_id', $box->site_id)
            ->forTraining()
            ->get();

        if ($history->count() < 10) {
            return 1.0; // Not enough data
        }

        // Calculate conversion rates at different price points
        $rentedCount = $history->where('was_rented', true)->count();
        $totalCount = $history->count();

        // Protect against division by zero
        $conversionRate = $totalCount > 0 ? $rentedCount / $totalCount : 0;

        // If conversion rate is low, suggest lower prices
        if ($conversionRate < 0.20) {
            return 0.95;
        }

        // If conversion rate is high, prices might be too low
        if ($conversionRate > 0.80) {
            return 1.05;
        }

        return 1.0;
    }

    /**
     * Apply A/B test if active
     */
    protected function applyABTest(Box $box, float $price, ?string $visitorId): ?array
    {
        if (!$visitorId) {
            return null;
        }

        $experiment = PricingExperiment::running()
            ->where('tenant_id', $box->tenant_id)
            ->where(function ($q) use ($box) {
                $q->whereNull('site_id')
                    ->orWhere('site_id', $box->site_id);
            })
            ->first();

        if (!$experiment) {
            return null;
        }

        // Get variant for this visitor
        $variant = $experiment->getVariantForVisitor($visitorId);

        // Calculate modified price
        $modifiedPrice = $price;
        if ($variant['type'] === 'percentage') {
            $modifiedPrice = $price * (1 + $variant['price_modifier'] / 100);
        } elseif ($variant['type'] === 'fixed') {
            $modifiedPrice = $price + $variant['price_modifier'];
        }

        // Record exposure
        ExperimentExposure::create([
            'experiment_id' => $experiment->id,
            'box_id' => $box->id,
            'visitor_id' => $visitorId,
            'variant_name' => $variant['name'],
            'price_shown' => $modifiedPrice,
        ]);

        return [
            'experiment_id' => $experiment->id,
            'experiment_name' => $experiment->name,
            'variant' => $variant['name'],
            'price' => round($modifiedPrice, 2),
            'original_price' => $price,
        ];
    }

    /**
     * Calculate confidence score
     */
    protected function calculateConfidence(Box $box): array
    {
        $score = 0;
        $factors = [];

        // Check price history availability
        $historyCount = PriceHistory::where('site_id', $box->site_id)->count();
        if ($historyCount >= 100) {
            $score += 30;
            $factors[] = 'Historique abondant';
        } elseif ($historyCount >= 20) {
            $score += 15;
            $factors[] = 'Historique suffisant';
        }

        // Check competitor data
        $competitorCount = CompetitorPrice::where('site_id', $box->site_id)->recent()->count();
        if ($competitorCount >= 5) {
            $score += 25;
            $factors[] = 'Données concurrents disponibles';
        } elseif ($competitorCount >= 2) {
            $score += 10;
            $factors[] = 'Quelques données concurrents';
        }

        // Check demand forecast
        $forecast = DemandForecast::where('site_id', $box->site_id)
            ->where('forecast_date', today())
            ->exists();
        if ($forecast) {
            $score += 20;
            $factors[] = 'Prévision de demande active';
        }

        // Check occupancy data
        $occupancy = $this->calculateOccupancyRate($box->site);
        if ($occupancy > 0) {
            $score += 15;
            $factors[] = 'Taux d\'occupation calculé';
        }

        // Seasonality always available
        $score += 10;
        $factors[] = 'Saisonnalité appliquée';

        $level = match (true) {
            $score >= 80 => 'high',
            $score >= 50 => 'medium',
            default => 'low',
        };

        return [
            'score' => $score,
            'level' => $level,
            'factors' => $factors,
        ];
    }

    /**
     * Helper methods
     */
    protected function calculateOccupancyRate(Site $site): float
    {
        $totalBoxes = Box::where('site_id', $site->id)->count();
        $occupiedBoxes = Box::where('site_id', $site->id)
            ->where('status', 'occupied')
            ->count();

        return $totalBoxes > 0 ? $occupiedBoxes / $totalBoxes : 0;
    }

    protected function getBoxCategory(Box $box): string
    {
        $size = $box->length * $box->width;

        return match (true) {
            $size < 2 => 'xs',
            $size < 5 => 'small',
            $size < 10 => 'medium',
            $size < 20 => 'large',
            $size < 30 => 'xl',
            default => 'xxl',
        };
    }

    protected function getDemandLevel(Box $box): string
    {
        $forecast = DemandForecast::where('site_id', $box->site_id)
            ->where('forecast_date', today())
            ->where('box_category', $this->getBoxCategory($box))
            ->first();

        return $forecast?->demand_level ?? 'unknown';
    }

    protected function getCurrentSeason(): string
    {
        $month = now()->month;

        return match (true) {
            $month >= 6 && $month <= 8 => 'high',
            $month >= 3 && $month <= 5 => 'medium',
            $month >= 9 && $month <= 11 => 'medium',
            default => 'low',
        };
    }

    /**
     * Batch update prices for all boxes
     */
    public function batchUpdatePrices(int $tenantId, ?int $siteId = null, bool $autoApply = false): array
    {
        $query = Box::where('tenant_id', $tenantId)
            ->where('status', 'available');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $boxes = $query->get();
        $results = [
            'processed' => 0,
            'adjusted' => 0,
            'increases' => 0,
            'decreases' => 0,
            'total_impact' => 0,
            'adjustments' => [],
        ];

        foreach ($boxes as $box) {
            $pricing = $this->calculateMLPrice($box);
            $results['processed']++;

            $oldPrice = $box->current_price ?? $box->base_price;
            $newPrice = $pricing['final_price'];
            $change = $newPrice - $oldPrice;

            if (abs($change) > 0.01) {
                $results['adjusted']++;
                $results['total_impact'] += $change;

                if ($change > 0) {
                    $results['increases']++;
                } else {
                    $results['decreases']++;
                }

                $adjustment = [
                    'box_id' => $box->id,
                    'box_name' => $box->display_name,
                    'old_price' => $oldPrice,
                    'new_price' => $newPrice,
                    'change' => round($change, 2),
                    'change_percent' => round($pricing['price_change_percentage'], 1),
                    'confidence' => $pricing['confidence']['level'],
                ];

                $results['adjustments'][] = $adjustment;

                if ($autoApply) {
                    $box->update(['current_price' => $newPrice]);

                    PriceAdjustment::create([
                        'tenant_id' => $tenantId,
                        'box_id' => $box->id,
                        'old_price' => $oldPrice,
                        'new_price' => $newPrice,
                        'adjustment_percentage' => $pricing['price_change_percentage'],
                        'trigger' => 'ml',
                        'trigger_details' => $pricing['factors'],
                        'auto_applied' => true,
                    ]);

                    // Record in price history
                    PriceHistory::create([
                        'tenant_id' => $tenantId,
                        'box_id' => $box->id,
                        'site_id' => $box->site_id,
                        'base_price' => $box->base_price,
                        'calculated_price' => $pricing['calculated_price'],
                        'final_price' => $newPrice,
                        'occupancy_rate' => $pricing['factors']['occupancy']['rate'],
                        'season' => $pricing['factors']['seasonality']['season'],
                        'day_of_week' => now()->dayOfWeek,
                        'is_holiday' => $this->isHoliday(),
                        'features' => $pricing['factors'],
                    ]);
                }
            }
        }

        return $results;
    }

    /**
     * Generate demand forecast for next 30 days
     */
    public function generateDemandForecast(int $tenantId, int $siteId): array
    {
        $forecasts = [];

        for ($i = 0; $i < 30; $i++) {
            $date = today()->addDays($i);
            $dayOfWeek = $date->dayOfWeek;
            $month = $date->month;

            // Base demand from historical data
            $baseDemand = $this->getHistoricalDemand($siteId, $dayOfWeek, $month);

            // Apply seasonal adjustment
            $seasonalFactor = $this->getSeasonalityFactor();
            $adjustedDemand = $baseDemand * $seasonalFactor;

            // Apply day of week adjustment
            $dowFactor = match ($dayOfWeek) {
                0 => 0.7, // Sunday
                6 => 1.2, // Saturday
                1 => 0.9, // Monday
                5 => 1.1, // Friday
                default => 1.0,
            };
            $adjustedDemand *= $dowFactor;

            // Calculate conversion prediction
            $conversionRate = $this->getHistoricalConversion($siteId);

            // Calculate confidence interval
            $stdDev = $baseDemand * 0.3;
            $confidenceLower = max(0, $adjustedDemand - 1.96 * $stdDev);
            $confidenceUpper = $adjustedDemand + 1.96 * $stdDev;

            // Recommended price modifier based on demand
            $priceModifier = match (true) {
                $adjustedDemand >= 10 => 1.15,
                $adjustedDemand >= 5 => 1.05,
                $adjustedDemand >= 2 => 1.00,
                default => 0.95,
            };

            foreach (CompetitorPrice::getCategories() as $category) {
                DemandForecast::updateOrCreate(
                    [
                        'tenant_id' => $tenantId,
                        'site_id' => $siteId,
                        'forecast_date' => $date,
                        'box_category' => $category,
                    ],
                    [
                        'predicted_demand' => round($adjustedDemand * $this->getCategoryDemandWeight($category), 2),
                        'predicted_conversion' => $conversionRate,
                        'confidence_lower' => round($confidenceLower, 2),
                        'confidence_upper' => round($confidenceUpper, 2),
                        'recommended_price_modifier' => $priceModifier,
                        'factors' => [
                            'base_demand' => $baseDemand,
                            'seasonal_factor' => $seasonalFactor,
                            'dow_factor' => $dowFactor,
                            'day_of_week' => $dayOfWeek,
                            'month' => $month,
                        ],
                    ]
                );
            }

            $forecasts[] = [
                'date' => $date->format('Y-m-d'),
                'demand' => round($adjustedDemand, 2),
                'price_modifier' => $priceModifier,
            ];
        }

        return $forecasts;
    }

    /**
     * Get historical demand (simplified - would use real data)
     */
    protected function getHistoricalDemand(int $siteId, int $dayOfWeek, int $month): float
    {
        // In a real implementation, this would query historical inquiry data
        $baseRate = 3.0; // Average daily inquiries

        // Seasonal adjustment
        $monthMultiplier = match ($month) {
            6, 7, 8, 9 => 1.5,
            5, 10 => 1.2,
            1, 2, 12 => 0.7,
            default => 1.0,
        };

        return $baseRate * $monthMultiplier;
    }

    /**
     * Get historical conversion rate
     */
    protected function getHistoricalConversion(int $siteId): float
    {
        $history = PriceHistory::where('site_id', $siteId)
            ->whereNotNull('was_rented')
            ->take(100)
            ->get();

        if ($history->count() < 10) {
            return 0.30; // Default 30% conversion
        }

        // Protect against division by zero
        $totalCount = $history->count();
        return $totalCount > 0 ? $history->where('was_rented', true)->count() / $totalCount : 0.30;
    }

    /**
     * Get category demand weight
     */
    protected function getCategoryDemandWeight(string $category): float
    {
        return match ($category) {
            'small' => 1.3,  // Small boxes more popular
            'medium' => 1.2,
            'large' => 1.0,
            'xl' => 0.7,
            'xxl' => 0.5,
            default => 0.8,
        };
    }

    /**
     * Check if today is a holiday
     */
    protected function isHoliday(): bool
    {
        $today = today();

        // French public holidays (simplified)
        $holidays = [
            '01-01', // New Year
            '05-01', // Labor Day
            '05-08', // Victory Day
            '07-14', // Bastille Day
            '08-15', // Assumption
            '11-01', // All Saints
            '11-11', // Armistice
            '12-25', // Christmas
        ];

        return in_array($today->format('m-d'), $holidays);
    }

    /**
     * Get pricing dashboard data
     */
    public function getDashboardData(int $tenantId, ?int $siteId = null): array
    {
        $query = Box::where('tenant_id', $tenantId);
        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $boxes = $query->get();

        // Calculate metrics
        $totalRevenue = $boxes->where('status', 'occupied')->sum('current_price');
        $potentialRevenue = 0;
        $revenueGap = 0;

        foreach ($boxes->where('status', 'available') as $box) {
            $pricing = $this->calculateMLPrice($box);
            $potentialRevenue += $pricing['final_price'];
        }

        // Recent adjustments
        $recentAdjustments = PriceAdjustment::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Active experiments
        $activeExperiments = PricingExperiment::where('tenant_id', $tenantId)
            ->running()
            ->get();

        // Demand forecasts
        $forecasts = DemandForecast::where('tenant_id', $tenantId)
            ->where('forecast_date', '>=', today())
            ->where('forecast_date', '<=', today()->addDays(7))
            ->orderBy('forecast_date')
            ->get()
            ->groupBy('forecast_date');

        return [
            'summary' => [
                'current_monthly_revenue' => round($totalRevenue, 2),
                'potential_additional_revenue' => round($potentialRevenue, 2),
                'optimization_opportunity' => round($potentialRevenue * 0.1, 2), // 10% improvement potential
            ],
            'recent_adjustments' => $recentAdjustments,
            'active_experiments' => $activeExperiments,
            'demand_forecast' => $forecasts,
            'pricing_health' => [
                'boxes_below_market' => $this->countBoxesBelowMarket($boxes),
                'boxes_above_market' => $this->countBoxesAboveMarket($boxes),
                'boxes_optimized' => $boxes->count() - $this->countBoxesBelowMarket($boxes) - $this->countBoxesAboveMarket($boxes),
            ],
        ];
    }

    protected function countBoxesBelowMarket(Collection $boxes): int
    {
        $count = 0;
        foreach ($boxes as $box) {
            $competitorAvg = $this->getCompetitorAveragePrice($box);
            if ($competitorAvg && $box->current_price < $competitorAvg * 0.9) {
                $count++;
            }
        }
        return $count;
    }

    protected function countBoxesAboveMarket(Collection $boxes): int
    {
        $count = 0;
        foreach ($boxes as $box) {
            $competitorAvg = $this->getCompetitorAveragePrice($box);
            if ($competitorAvg && $box->current_price > $competitorAvg * 1.1) {
                $count++;
            }
        }
        return $count;
    }
}
