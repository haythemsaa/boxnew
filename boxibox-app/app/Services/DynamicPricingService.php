<?php

namespace App\Services;

use App\Models\Box;
use App\Models\PricingRule;
use App\Models\Site;
use Carbon\Carbon;

class DynamicPricingService
{
    /**
     * Calculate optimal price for a box based on multiple factors
     *
     * @param Box $box
     * @param array $options
     * @return float
     */
    public function calculateOptimalPrice(Box $box, array $options = []): float
    {
        $basePrice = $box->current_price ?? $box->base_price ?? 0;

        // Apply all pricing strategies
        $occupancyMultiplier = $this->getOccupancyMultiplier($box->site);
        $seasonalMultiplier = $this->getSeasonalMultiplier();
        $durationDiscount = $this->getDurationDiscount($options['duration'] ?? 1);
        $customerTypeDiscount = $this->getCustomerTypeDiscount($options['customer_type'] ?? 'new');

        // Calculate final price
        $adjustedPrice = $basePrice * $occupancyMultiplier * $seasonalMultiplier;
        $adjustedPrice = $adjustedPrice * (1 - $durationDiscount) * (1 - $customerTypeDiscount);

        // Apply promotional rules if any
        $promotionalPrice = $this->applyPromotions($adjustedPrice, $box, $options);

        // Ensure price stays within acceptable bounds
        $minPrice = $basePrice * 0.5; // Never go below 50% of base
        $maxPrice = $basePrice * 1.5; // Never go above 150% of base

        return max($minPrice, min($maxPrice, $promotionalPrice));
    }

    /**
     * Get pricing multiplier based on site occupancy
     *
     * @param Site $site
     * @return float
     */
    protected function getOccupancyMultiplier(Site $site): float
    {
        $occupancyRate = $this->calculateOccupancyRate($site);

        // Dynamic pricing based on occupancy
        if ($occupancyRate < 0.70) {
            // Low occupancy: Aggressive discounts
            return 0.75; // -25%
        } elseif ($occupancyRate < 0.85) {
            // Medium occupancy: Slight discount
            return 0.90; // -10%
        } elseif ($occupancyRate < 0.95) {
            // Good occupancy: Standard price
            return 1.0;
        } else {
            // Very high occupancy: Surge pricing
            return 1.20; // +20%
        }
    }

    /**
     * Calculate occupancy rate for a site
     *
     * @param Site $site
     * @return float
     */
    protected function calculateOccupancyRate(Site $site): float
    {
        $totalBoxes = Box::where('site_id', $site->id)->count();
        $occupiedBoxes = Box::where('site_id', $site->id)
            ->where('status', 'occupied')
            ->count();

        return $totalBoxes > 0 ? $occupiedBoxes / $totalBoxes : 0;
    }

    /**
     * Get seasonal multiplier
     *
     * @return float
     */
    protected function getSeasonalMultiplier(): float
    {
        $month = Carbon::now()->month;

        // High season (moving season): May-September
        if ($month >= 5 && $month <= 9) {
            return 1.10; // +10%
        }

        // Low season: November-February
        if ($month >= 11 || $month <= 2) {
            return 0.95; // -5%
        }

        // Normal season
        return 1.0;
    }

    /**
     * Get discount based on rental duration
     *
     * @param int $months
     * @return float
     */
    protected function getDurationDiscount(int $months): float
    {
        if ($months >= 12) {
            return 0.20; // 20% off for 12+ months
        } elseif ($months >= 6) {
            return 0.15; // 15% off for 6+ months
        } elseif ($months >= 3) {
            return 0.10; // 10% off for 3+ months
        }

        return 0; // No discount for monthly
    }

    /**
     * Get discount based on customer type
     *
     * @param string $customerType
     * @return float
     */
    protected function getCustomerTypeDiscount(string $customerType): float
    {
        return match($customerType) {
            'new' => 0.10, // 10% first month discount
            'returning' => 0.05, // 5% welcome back
            'vip' => 0.15, // 15% loyalty discount
            default => 0
        };
    }

    /**
     * Apply promotional rules
     *
     * @param float $price
     * @param Box $box
     * @param array $options
     * @return float
     */
    protected function applyPromotions(float $price, Box $box, array $options): float
    {
        $activePromotions = PricingRule::where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('valid_from')->orWhere('valid_from', '<=', Carbon::now());
            })
            ->where(function ($q) {
                $q->whereNull('valid_until')->orWhere('valid_until', '>=', Carbon::now());
            })
            ->get();

        foreach ($activePromotions as $promo) {
            if ($this->promotionApplies($promo, $box, $options)) {
                if ($promo->adjustment_type === 'percentage') {
                    $price = $price * (1 - $promo->adjustment_value / 100);
                } else {
                    $price = $price - $promo->adjustment_value;
                }
            }
        }

        return $price;
    }

    /**
     * Check if promotion applies to this box/customer
     *
     * @param PricingRule $promo
     * @param Box $box
     * @param array $options
     * @return bool
     */
    protected function promotionApplies(PricingRule $promo, Box $box, array $options): bool
    {
        // Check site
        if ($promo->site_id && $box->site_id !== $promo->site_id) {
            return false;
        }

        // Check conditions from JSON field
        $conditions = is_array($promo->conditions) ? $promo->conditions : json_decode($promo->conditions, true) ?? [];

        // Check box type from conditions
        if (!empty($conditions['box_type']) && $box->type !== $conditions['box_type']) {
            return false;
        }

        // Check customer type from conditions
        if (!empty($conditions['customer_type']) && ($options['customer_type'] ?? null) !== $conditions['customer_type']) {
            return false;
        }

        return true;
    }

    /**
     * Get revenue optimization recommendations
     *
     * @param Site $site
     * @return array
     */
    public function getRevenueRecommendations(Site $site): array
    {
        $occupancyRate = $this->calculateOccupancyRate($site);
        $currentRevenue = $this->calculateCurrentRevenue($site);
        $optimalRevenue = $this->calculateOptimalRevenue($site);

        $recommendations = [];

        // Low occupancy recommendations
        if ($occupancyRate < 0.70) {
            $recommendations[] = [
                'type' => 'discount',
                'priority' => 'high',
                'title' => 'Lancer une promotion agressive',
                'description' => "Taux d'occupation bas ({$this->formatPercentage($occupancyRate)}). Proposer -25% pour attirer nouveaux clients.",
                'impact' => '+15-20% occupation estimée',
                'action' => 'create_promotion'
            ];
        }

        // High occupancy recommendations
        if ($occupancyRate > 0.90) {
            $recommendations[] = [
                'type' => 'price_increase',
                'priority' => 'medium',
                'title' => 'Augmenter les prix',
                'description' => "Excellente occupation ({$this->formatPercentage($occupancyRate)}). Opportunité d'augmenter les prix de 10-15%.",
                'impact' => '+' . number_format(($optimalRevenue - $currentRevenue), 0) . '€/mois',
                'action' => 'adjust_prices'
            ];
        }

        // Revenue gap
        $revenueGap = $optimalRevenue - $currentRevenue;
        if ($revenueGap > 1000) {
            $recommendations[] = [
                'type' => 'optimization',
                'priority' => 'high',
                'title' => 'Optimiser la tarification',
                'description' => "Écart de " . number_format($revenueGap, 0) . "€/mois entre revenus actuels et optimaux.",
                'impact' => '+' . number_format($revenueGap, 0) . '€/mois',
                'action' => 'enable_dynamic_pricing'
            ];
        }

        return $recommendations;
    }

    /**
     * Calculate current monthly revenue for a site
     *
     * @param Site $site
     * @return float
     */
    protected function calculateCurrentRevenue(Site $site): float
    {
        return Box::where('site_id', $site->id)
            ->where('status', 'occupied')
            ->sum('current_price') ?: Box::where('site_id', $site->id)
                ->where('status', 'occupied')
                ->sum('base_price');
    }

    /**
     * Calculate optimal monthly revenue if all boxes priced optimally
     *
     * @param Site $site
     * @return float
     */
    protected function calculateOptimalRevenue(Site $site): float
    {
        $boxes = Box::where('site_id', $site->id)->get();
        $optimalRevenue = 0;

        foreach ($boxes as $box) {
            if ($box->status === 'occupied') {
                $optimalPrice = $this->calculateOptimalPrice($box);
                $optimalRevenue += $optimalPrice;
            }
        }

        return $optimalRevenue;
    }

    /**
     * Forecast revenue for next N months
     *
     * @param Site $site
     * @param int $months
     * @return array
     */
    public function forecastRevenue(Site $site, int $months = 12): array
    {
        $forecast = [];
        $currentOccupancy = $this->calculateOccupancyRate($site);

        for ($i = 1; $i <= $months; $i++) {
            $month = Carbon::now()->addMonths($i);

            // Simple forecast based on seasonal trends
            $seasonalFactor = $this->getSeasonalMultiplier();
            $projectedOccupancy = min(1.0, $currentOccupancy * $seasonalFactor);
            $projectedRevenue = $this->calculateCurrentRevenue($site) * $seasonalFactor;

            $forecast[] = [
                'month' => $month->format('Y-m'),
                'month_name' => $month->format('F Y'),
                'projected_occupancy' => $projectedOccupancy,
                'projected_revenue' => $projectedRevenue,
                'confidence' => $i <= 3 ? 'high' : ($i <= 6 ? 'medium' : 'low')
            ];
        }

        return $forecast;
    }

    /**
     * Format percentage for display
     *
     * @param float $value
     * @return string
     */
    protected function formatPercentage(float $value): string
    {
        return number_format($value * 100, 1) . '%';
    }

    /**
     * Get pricing analytics for dashboard
     *
     * @param Site $site
     * @return array
     */
    public function getPricingAnalytics(Site $site): array
    {
        $occupancyRate = $this->calculateOccupancyRate($site);
        $currentRevenue = $this->calculateCurrentRevenue($site);
        $optimalRevenue = $this->calculateOptimalRevenue($site);
        $revenueGap = $optimalRevenue - $currentRevenue;

        return [
            'occupancy' => [
                'rate' => $occupancyRate,
                'formatted' => $this->formatPercentage($occupancyRate),
                'status' => $this->getOccupancyStatus($occupancyRate)
            ],
            'revenue' => [
                'current' => $currentRevenue,
                'optimal' => $optimalRevenue,
                'gap' => $revenueGap,
                'gap_percentage' => $currentRevenue > 0 ? ($revenueGap / $currentRevenue) : 0
            ],
            'recommendations' => $this->getRevenueRecommendations($site),
            'forecast' => $this->forecastRevenue($site, 6)
        ];
    }

    /**
     * Get occupancy status label
     *
     * @param float $rate
     * @return string
     */
    protected function getOccupancyStatus(float $rate): string
    {
        if ($rate < 0.70) return 'low';
        if ($rate < 0.85) return 'medium';
        if ($rate < 0.95) return 'good';
        return 'excellent';
    }
}
