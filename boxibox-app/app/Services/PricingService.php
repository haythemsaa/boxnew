<?php

namespace App\Services;

use App\Models\Box;
use App\Models\BookingPromoCode;
use App\Models\BookingSettings;
use App\Models\Promotion;
use Carbon\Carbon;

class PricingService
{
    // TVA rate (French standard rate)
    protected float $vatRate = 0.20;

    // Whether to apply seasonal pricing (can be disabled per tenant)
    protected bool $applySeasonalPricing = true;

    /**
     * Calculate final price with dynamic pricing rules
     */
    public function calculatePrice(Box $box, ?string $promotionCode = null, ?Carbon $startDate = null): array
    {
        $basePrice = $box->current_price;
        $discount = 0;
        $appliedPromotion = null;

        // Apply seasonal pricing (optional)
        $seasonalMultiplier = $this->applySeasonalPricing
            ? $this->getSeasonalMultiplier($startDate ?? now())
            : 1.0;
        $seasonalPrice = round($basePrice * $seasonalMultiplier, 2);

        // Apply promotion if provided (try BookingPromoCode first, then legacy Promotion)
        if ($promotionCode) {
            // Try booking promo code
            $promo = BookingPromoCode::findValidCode($promotionCode, $box->tenant_id, $box->site_id);
            if ($promo) {
                $discount = $promo->calculateDiscount($seasonalPrice);
                $appliedPromotion = [
                    'code' => $promo->code,
                    'name' => $promo->name,
                    'type' => $promo->discount_type,
                    'value' => $promo->discount_value,
                    'label' => $promo->discount_label,
                ];
            } else {
                // Fallback to legacy Promotion model
                $promotion = Promotion::where('code', $promotionCode)
                    ->where('tenant_id', $box->tenant_id)
                    ->first();

                if ($promotion && $promotion->isValid()) {
                    $discount = $promotion->calculateDiscount($seasonalPrice);
                    $appliedPromotion = [
                        'code' => $promotion->code,
                        'name' => $promotion->name,
                        'type' => $promotion->type,
                        'value' => $promotion->value,
                    ];
                }
            }
        }

        $finalPrice = max(0, round($seasonalPrice - $discount, 2));

        return [
            'base_price' => $basePrice,
            'seasonal_multiplier' => $seasonalMultiplier,
            'seasonal_adjustment' => round($seasonalPrice - $basePrice, 2),
            'seasonal_price' => $seasonalPrice,
            'discount' => round($discount, 2),
            'final_price' => $finalPrice,
            'promotion' => $appliedPromotion,
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

    /**
     * Calculate complete booking total with prorata, deposit, taxes
     * This is the main method to use for public booking
     */
    public function calculateBookingTotal(
        Box $box,
        Carbon $startDate,
        ?int $durationMonths = null,
        ?string $promoCode = null,
        ?BookingSettings $settings = null,
        bool $includeInsurance = false,
        float $insuranceMonthly = 15.00
    ): array {
        // Get settings if not provided
        if (!$settings) {
            $settings = BookingSettings::getForTenant($box->tenant_id, $box->site_id);
        }

        // 1. Calculate base monthly price with seasonal adjustment
        $pricing = $this->calculatePrice($box, $promoCode, $startDate);
        $monthlyPriceHT = $pricing['final_price'];

        // 2. Apply duration discount if applicable
        $durationDiscount = 0;
        $durationDiscountPercent = 0;
        if ($durationMonths && $durationMonths >= 3) {
            $durationData = $this->calculateDurationDiscount($monthlyPriceHT, $durationMonths);
            $durationDiscount = $durationData['discount_amount'];
            $durationDiscountPercent = $durationData['discount_percent'];
            $monthlyPriceHT = $durationData['final_price'];
        }

        // 3. Calculate first month prorata
        $firstMonthProrata = $this->calculateFirstMonthProrata($monthlyPriceHT, $startDate);

        // 4. Calculate deposit
        $depositAmount = 0;
        if ($settings && $settings->require_deposit) {
            if ($settings->deposit_percentage > 0) {
                $depositAmount = round($pricing['base_price'] * ($settings->deposit_percentage / 100), 2);
            } else {
                $depositAmount = $settings->deposit_amount ?? 0;
            }
        }

        // 5. Calculate insurance if included
        $insuranceAmount = $includeInsurance ? $insuranceMonthly : 0;

        // 6. Calculate subtotal HT (before tax)
        $subtotalHT = round($firstMonthProrata['amount'] + $depositAmount + $insuranceAmount, 2);

        // 7. Calculate TVA (VAT)
        // Note: In France, storage rental is subject to 20% VAT
        // But deposit is usually not taxed (it's a guarantee, not a service)
        $taxableAmount = $firstMonthProrata['amount'] + $insuranceAmount;
        $vatAmount = round($taxableAmount * $this->vatRate, 2);

        // 8. Calculate total TTC (including tax)
        $totalTTC = round($subtotalHT + $vatAmount, 2);

        // 9. Build breakdown
        return [
            // Monthly recurring
            'monthly_price_base' => $pricing['base_price'],
            'monthly_price_ht' => $monthlyPriceHT,
            'monthly_price_ttc' => round($monthlyPriceHT * (1 + $this->vatRate), 2),

            // First month details
            'first_month' => [
                'full_month_ht' => $monthlyPriceHT,
                'prorata_days' => $firstMonthProrata['days'],
                'prorata_total_days' => $firstMonthProrata['total_days'],
                'prorata_amount_ht' => $firstMonthProrata['amount'],
                'prorata_percentage' => $firstMonthProrata['percentage'],
                'is_prorated' => $firstMonthProrata['is_prorated'],
            ],

            // Deposit
            'deposit' => [
                'amount' => $depositAmount,
                'required' => $settings?->require_deposit ?? false,
                'percentage' => $settings?->deposit_percentage ?? 0,
            ],

            // Insurance
            'insurance' => [
                'included' => $includeInsurance,
                'monthly_amount' => $insuranceMonthly,
                'first_month_amount' => $insuranceAmount,
            ],

            // Discounts applied
            'discounts' => [
                'seasonal' => [
                    'multiplier' => $pricing['seasonal_multiplier'],
                    'adjustment' => $pricing['seasonal_adjustment'],
                    'label' => $this->getSeasonalLabel($pricing['seasonal_multiplier']),
                ],
                'promo' => $pricing['promotion'],
                'promo_amount' => $pricing['discount'],
                'duration' => [
                    'months' => $durationMonths,
                    'percent' => $durationDiscountPercent,
                    'amount' => $durationDiscount,
                ],
            ],

            // Taxes
            'vat' => [
                'rate' => $this->vatRate,
                'rate_percent' => $this->vatRate * 100,
                'taxable_amount' => $taxableAmount,
                'amount' => $vatAmount,
            ],

            // Totals
            'subtotal_ht' => $subtotalHT,
            'total_ttc' => $totalTTC,

            // Payment due now (first month + deposit + insurance + VAT)
            'due_now' => $totalTTC,

            // Subsequent monthly payments
            'monthly_recurring_ht' => $monthlyPriceHT + $insuranceAmount,
            'monthly_recurring_ttc' => round(($monthlyPriceHT + $insuranceAmount) * (1 + $this->vatRate), 2),
        ];
    }

    /**
     * Calculate first month prorata based on start date
     */
    public function calculateFirstMonthProrata(float $monthlyPrice, Carbon $startDate): array
    {
        $dayOfMonth = $startDate->day;
        $daysInMonth = $startDate->daysInMonth;

        // If starting on 1st, no prorata needed
        if ($dayOfMonth === 1) {
            return [
                'amount' => $monthlyPrice,
                'days' => $daysInMonth,
                'total_days' => $daysInMonth,
                'percentage' => 100,
                'is_prorated' => false,
            ];
        }

        // Calculate remaining days in the month (including start day)
        $remainingDays = $daysInMonth - $dayOfMonth + 1;
        $prorataPercentage = round(($remainingDays / $daysInMonth) * 100, 1);
        $prorataAmount = round(($monthlyPrice / $daysInMonth) * $remainingDays, 2);

        return [
            'amount' => $prorataAmount,
            'days' => $remainingDays,
            'total_days' => $daysInMonth,
            'percentage' => $prorataPercentage,
            'is_prorated' => true,
        ];
    }

    /**
     * Get seasonal pricing label
     */
    protected function getSeasonalLabel(float $multiplier): ?string
    {
        if ($multiplier > 1) {
            return 'Haute saison (+' . round(($multiplier - 1) * 100) . '%)';
        } elseif ($multiplier < 1) {
            return 'Basse saison (' . round(($multiplier - 1) * 100) . '%)';
        }
        return null;
    }

    /**
     * Disable seasonal pricing
     */
    public function disableSeasonalPricing(): self
    {
        $this->applySeasonalPricing = false;
        return $this;
    }

    /**
     * Set custom VAT rate
     */
    public function setVatRate(float $rate): self
    {
        $this->vatRate = $rate;
        return $this;
    }
}
