<?php

namespace App\Services;

use App\Models\Box;
use App\Models\Promotion;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate final price with dynamic pricing rules
     */
    public function calculatePrice(Box $box, ?string $promotionCode = null, ?Carbon $startDate = null): array
    {
        $basePrice = $box->current_price;
        $discount = 0;
        $appliedPromotion = null;

        // Apply seasonal pricing
        $seasonalMultiplier = $this->getSeasonalMultiplier($startDate ?? now());
        $seasonalPrice = $basePrice * $seasonalMultiplier;

        // Apply promotion if provided
        if ($promotionCode) {
            $promotion = Promotion::where('code', $promotionCode)
                ->where('tenant_id', $box->tenant_id)
                ->first();

            if ($promotion && $promotion->isValid()) {
                $discount = $promotion->calculateDiscount($seasonalPrice);
                $appliedPromotion = $promotion;
            }
        }

        $finalPrice = max(0, $seasonalPrice - $discount);

        return [
            'base_price' => $basePrice,
            'seasonal_multiplier' => $seasonalMultiplier,
            'seasonal_price' => $seasonalPrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'promotion' => $appliedPromotion ? [
                'code' => $appliedPromotion->code,
                'name' => $appliedPromotion->name,
                'type' => $appliedPromotion->type,
                'value' => $appliedPromotion->value,
            ] : null,
        ];
    }

    /**
     * Get seasonal pricing multiplier
     */
    protected function getSeasonalMultiplier(Carbon $date): float
    {
        $month = $date->month;

        // Peak season (May-September): +10%
        if ($month >= 5 && $month <= 9) {
            return 1.10;
        }

        // Low season (January, February): -5%
        if ($month <= 2) {
            return 0.95;
        }

        // Regular season
        return 1.00;
    }

    /**
     * Calculate prorated amount
     */
    public function calculateProration(float $monthlyPrice, Carbon $startDate, Carbon $endDate): float
    {
        $daysInMonth = $startDate->daysInMonth;
        $daysUsed = $startDate->diffInDays($endDate) + 1;

        return ($monthlyPrice / $daysInMonth) * $daysUsed;
    }

    /**
     * Calculate discount for long-term rental
     */
    public function calculateDurationDiscount(float $basePrice, int $months): array
    {
        $discountPercent = match (true) {
            $months >= 12 => 15, // 1 year: 15% off
            $months >= 6 => 10,  // 6 months: 10% off
            $months >= 3 => 5,   // 3 months: 5% off
            default => 0,
        };

        $discount = $basePrice * ($discountPercent / 100);
        $finalPrice = $basePrice - $discount;

        return [
            'months' => $months,
            'discount_percent' => $discountPercent,
            'discount_amount' => $discount,
            'original_price' => $basePrice,
            'final_price' => $finalPrice,
        ];
    }

    /**
     * Calculate total cost with all applicable discounts
     */
    public function calculateTotalCost(
        Box $box,
        int $months = 1,
        ?string $promotionCode = null,
        ?Carbon $startDate = null
    ): array {
        // Get base pricing with seasonality and promotions
        $pricing = $this->calculatePrice($box, $promotionCode, $startDate);

        // Apply duration discount
        $durationDiscount = $this->calculateDurationDiscount($pricing['final_price'], $months);

        // Calculate total
        $monthlyPrice = $durationDiscount['final_price'];
        $totalCost = $monthlyPrice * $months;

        // Calculate first payment (deposit + first month)
        $deposit = $box->current_price; // Typically 1 month
        $firstPayment = $deposit + $monthlyPrice;

        return [
            'base_monthly_price' => $pricing['base_price'],
            'seasonal_adjustment' => $pricing['seasonal_price'] - $pricing['base_price'],
            'promotion_discount' => $pricing['discount'],
            'duration_discount' => $durationDiscount['discount_amount'],
            'final_monthly_price' => $monthlyPrice,
            'rental_months' => $months,
            'total_rental_cost' => $totalCost,
            'deposit_amount' => $deposit,
            'first_payment' => $firstPayment,
            'subsequent_payments' => $monthlyPrice,
            'total_first_payment' => $firstPayment,
            'promotion' => $pricing['promotion'],
            'duration_discount_percent' => $durationDiscount['discount_percent'],
        ];
    }
}
