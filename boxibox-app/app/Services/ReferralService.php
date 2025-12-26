<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Models\ReferralSettings;
use App\Models\Contract;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;

class ReferralService
{
    /**
     * Validate a referral code and check if customer can use it
     */
    public function validateCode(string $code, int $tenantId, ?int $newCustomerId = null): array
    {
        $referralCode = ReferralCode::findValidCode($code, $tenantId);

        if (!$referralCode) {
            return [
                'valid' => false,
                'error' => 'Code de parrainage invalide ou expiré',
            ];
        }

        $settings = ReferralSettings::getForTenant($tenantId);

        if (!$settings || !$settings->is_active) {
            return [
                'valid' => false,
                'error' => 'Le programme de parrainage n\'est pas actif',
            ];
        }

        // Check if referrer has active contract (if required)
        if ($settings->require_active_contract && $referralCode->customer_id) {
            $hasActiveContract = Contract::where('customer_id', $referralCode->customer_id)
                ->where('status', 'active')
                ->exists();

            if (!$hasActiveContract) {
                return [
                    'valid' => false,
                    'error' => 'Le parrain n\'a pas de contrat actif',
                ];
            }
        }

        // Check if customer is not referring themselves
        if ($newCustomerId && $referralCode->customer_id === $newCustomerId) {
            return [
                'valid' => false,
                'error' => 'Vous ne pouvez pas utiliser votre propre code',
            ];
        }

        return [
            'valid' => true,
            'code' => $referralCode,
            'settings' => $settings,
            'referee_reward' => [
                'type' => $settings->referee_reward_type,
                'value' => $settings->referee_reward_value,
                'label' => $settings->referee_reward_label,
            ],
        ];
    }

    /**
     * Apply referral code to a booking
     */
    public function applyToBooking(string $code, Booking $booking, Customer $customer): ?Referral
    {
        $validation = $this->validateCode($code, $booking->tenant_id, $customer->id);

        if (!$validation['valid']) {
            return null;
        }

        $referralCode = $validation['code'];
        $settings = $validation['settings'];

        return DB::transaction(function () use ($referralCode, $booking, $customer, $settings) {
            // Create the referral record
            $referral = Referral::create([
                'tenant_id' => $booking->tenant_id,
                'referral_code_id' => $referralCode->id,
                'referrer_customer_id' => $referralCode->customer_id,
                'referee_customer_id' => $customer->id,
                'booking_id' => $booking->id,
                'status' => 'pending',
                'reward_snapshot' => [
                    'referrer_reward_type' => $settings->referrer_reward_type,
                    'referrer_reward_value' => $settings->referrer_reward_value,
                    'referee_reward_type' => $settings->referee_reward_type,
                    'referee_reward_value' => $settings->referee_reward_value,
                    'reward_delay_days' => $settings->reward_delay_days,
                ],
            ]);

            // Increment usage count
            $referralCode->incrementUses();

            return $referral;
        });
    }

    /**
     * Process referral when contract is created
     */
    public function processContractCreation(Contract $contract): void
    {
        // Find pending referral for this customer
        $referral = Referral::where('referee_customer_id', $contract->customer_id)
            ->where('tenant_id', $contract->tenant_id)
            ->where('status', 'pending')
            ->whereNull('contract_id')
            ->first();

        if (!$referral) {
            return;
        }

        $settings = ReferralSettings::getForTenant($contract->tenant_id);
        if (!$settings || !$settings->is_active) {
            return;
        }

        DB::transaction(function () use ($referral, $contract, $settings) {
            // Link contract to referral
            $referral->update(['contract_id' => $contract->id]);

            // Check minimum rental requirement
            if ($settings->min_rental_months > 0) {
                // Mark as pending until min rental period is met
                // Will be qualified by scheduled job
                return;
            }

            // Immediately qualify if no delay required
            if ($settings->reward_delay_days <= 0) {
                $this->qualifyReferral($referral);
            }
        });
    }

    /**
     * Qualify a referral and create rewards
     */
    public function qualifyReferral(Referral $referral): void
    {
        if ($referral->status !== 'pending') {
            return;
        }

        $snapshot = $referral->reward_snapshot ?? [];

        DB::transaction(function () use ($referral, $snapshot) {
            $referral->markAsQualified();

            // Create reward for referee (new customer)
            $this->createReward($referral, 'referee', [
                'type' => $snapshot['referee_reward_type'] ?? 'percentage',
                'value' => $snapshot['referee_reward_value'] ?? 10,
            ]);

            // Create reward for referrer (existing customer)
            if ($referral->referrer_customer_id) {
                $this->createReward($referral, 'referrer', [
                    'type' => $snapshot['referrer_reward_type'] ?? 'percentage',
                    'value' => $snapshot['referrer_reward_value'] ?? 10,
                ]);
            }
        });
    }

    /**
     * Create a reward record
     */
    protected function createReward(Referral $referral, string $recipientType, array $rewardConfig): ReferralReward
    {
        $customerId = $recipientType === 'referrer'
            ? $referral->referrer_customer_id
            : $referral->referee_customer_id;

        return ReferralReward::create([
            'tenant_id' => $referral->tenant_id,
            'referral_id' => $referral->id,
            'customer_id' => $customerId,
            'recipient_type' => $recipientType,
            'reward_type' => $rewardConfig['type'],
            'reward_value' => $rewardConfig['value'],
            'description' => $this->getRewardDescription($recipientType, $rewardConfig),
            'status' => 'pending',
        ]);
    }

    /**
     * Get reward description
     */
    protected function getRewardDescription(string $recipientType, array $config): string
    {
        $prefix = $recipientType === 'referrer' ? 'Parrainage' : 'Bienvenue filleul';

        return match ($config['type']) {
            'percentage' => "{$prefix}: {$config['value']}% de réduction",
            'fixed' => "{$prefix}: {$config['value']}€ de réduction",
            'free_month' => "{$prefix}: {$config['value']} mois gratuit(s)",
            'credit' => "{$prefix}: {$config['value']}€ de crédit",
            default => "{$prefix}: récompense",
        };
    }

    /**
     * Calculate discount amount from reward
     */
    public function calculateRewardDiscount(ReferralReward $reward, float $basePrice): float
    {
        return match ($reward->reward_type) {
            'percentage' => round($basePrice * ($reward->reward_value / 100), 2),
            'fixed' => min($reward->reward_value, $basePrice),
            'free_month' => $basePrice, // Full month free
            'credit' => min($reward->reward_value, $basePrice),
            default => 0,
        };
    }

    /**
     * Get or create referral code for a customer
     */
    public function getOrCreateCode(Customer $customer): ReferralCode
    {
        $existingCode = ReferralCode::where('customer_id', $customer->id)
            ->where('tenant_id', $customer->tenant_id)
            ->where('is_active', true)
            ->first();

        if ($existingCode) {
            return $existingCode;
        }

        return ReferralCode::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'name' => "Code de {$customer->full_name}",
        ]);
    }

    /**
     * Get referral statistics for a customer
     */
    public function getCustomerStats(Customer $customer): array
    {
        $code = ReferralCode::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->first();

        if (!$code) {
            return [
                'has_code' => false,
                'code' => null,
                'total_referrals' => 0,
                'successful_referrals' => 0,
                'pending_referrals' => 0,
                'total_rewards' => 0,
                'pending_rewards' => 0,
            ];
        }

        $referrals = Referral::where('referrer_customer_id', $customer->id);

        $pendingRewardsAmount = ReferralReward::where('customer_id', $customer->id)
            ->where('status', 'pending')
            ->sum('reward_amount');

        $appliedRewardsAmount = ReferralReward::where('customer_id', $customer->id)
            ->where('status', 'applied')
            ->sum('reward_amount');

        return [
            'has_code' => true,
            'code' => $code->code,
            'share_url' => $code->share_url,
            'total_referrals' => (clone $referrals)->count(),
            'successful_referrals' => (clone $referrals)->whereIn('status', ['qualified', 'rewarded'])->count(),
            'pending_referrals' => (clone $referrals)->where('status', 'pending')->count(),
            'total_rewards' => $appliedRewardsAmount,
            'pending_rewards' => $pendingRewardsAmount,
        ];
    }

    /**
     * Get pending referrals that need to be qualified
     */
    public function getPendingForQualification(int $tenantId): \Illuminate\Database\Eloquent\Collection
    {
        $settings = ReferralSettings::getForTenant($tenantId);

        if (!$settings) {
            return collect();
        }

        return Referral::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->whereNotNull('contract_id')
            ->where('created_at', '<=', now()->subDays($settings->reward_delay_days))
            ->with(['referrer', 'referee', 'contract'])
            ->get();
    }

    /**
     * Process all pending referrals for qualification
     */
    public function processQualifications(int $tenantId): int
    {
        $pending = $this->getPendingForQualification($tenantId);
        $qualified = 0;

        foreach ($pending as $referral) {
            // Check contract is still active
            if ($referral->contract && $referral->contract->status === 'active') {
                $this->qualifyReferral($referral);
                $qualified++;
            }
        }

        return $qualified;
    }
}
