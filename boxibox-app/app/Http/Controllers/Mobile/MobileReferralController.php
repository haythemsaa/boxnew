<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerReferral;
use App\Models\ReferralSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class MobileReferralController extends Controller
{
    /**
     * Referral program dashboard
     */
    public function index(Request $request)
    {
        $customer = $this->getCustomer($request);

        // Ensure customer has a referral code
        if (!$customer->referral_code) {
            $customer->update([
                'referral_code' => $this->generateReferralCode(),
            ]);
        }

        // Get referral settings
        $settings = ReferralSetting::where('tenant_id', $customer->tenant_id)->first();

        // Get referrals
        $referrals = CustomerReferral::where('referrer_customer_id', $customer->id)
            ->with(['referred', 'contract'])
            ->latest()
            ->get();

        // Stats
        $stats = [
            'total_referrals' => $referrals->count(),
            'converted_referrals' => $referrals->whereIn('status', ['converted', 'rewarded'])->count(),
            'pending_referrals' => $referrals->where('status', 'pending')->count(),
            'total_earned' => $referrals->where('referrer_reward_paid', true)->sum('referrer_reward'),
            'pending_rewards' => $referrals->where('status', 'converted')
                ->where('referrer_reward_paid', false)
                ->sum('referrer_reward'),
            'credits_available' => $customer->referral_credits ?? 0,
        ];

        return Inertia::render('Mobile/Referral/Index', [
            'customer' => $customer,
            'referrals' => $referrals,
            'stats' => $stats,
            'settings' => $settings,
            'shareUrl' => url('/r/' . $customer->referral_code),
        ]);
    }

    /**
     * Send referral invitation
     */
    public function invite(Request $request)
    {
        $customer = $this->getCustomer($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'nullable|string|max:500',
            'send_email' => 'boolean',
            'send_sms' => 'boolean',
        ]);

        if (!$validated['email'] && !$validated['phone']) {
            return response()->json([
                'error' => 'Email ou téléphone requis',
            ], 422);
        }

        // Check for existing invitation
        $existingQuery = CustomerReferral::where('referrer_customer_id', $customer->id)
            ->where('status', 'pending');

        if ($validated['email']) {
            $existingQuery->where('invited_email', $validated['email']);
        }
        if ($validated['phone']) {
            $existingQuery->orWhere('invited_phone', $validated['phone']);
        }

        if ($existingQuery->exists()) {
            return response()->json([
                'error' => 'Une invitation est déjà en cours pour cette personne',
            ], 422);
        }

        // Get settings
        $settings = ReferralSetting::where('tenant_id', $customer->tenant_id)->first();

        $referral = CustomerReferral::create([
            'tenant_id' => $customer->tenant_id,
            'referrer_customer_id' => $customer->id,
            'referral_code' => $customer->referral_code,
            'invited_name' => $validated['name'],
            'invited_email' => $validated['email'] ?? null,
            'invited_phone' => $validated['phone'] ?? null,
            'referrer_reward' => $settings?->referrer_reward_amount ?? 25.00,
            'referred_reward' => $settings?->referred_reward_amount ?? 25.00,
            'reward_type' => $settings?->reward_type ?? 'credit',
            'source' => 'app',
            'expires_at' => now()->addDays($settings?->referral_expiry_days ?? 90),
        ]);

        // Send invitation
        if ($request->boolean('send_email') && $referral->invited_email) {
            // TODO: Send email invitation
        }

        if ($request->boolean('send_sms') && $referral->invited_phone) {
            // TODO: Send SMS invitation
        }

        return response()->json([
            'success' => true,
            'referral' => $referral,
            'message' => 'Invitation envoyée avec succès',
        ]);
    }

    /**
     * Get shareable content
     */
    public function getShareContent(Request $request)
    {
        $customer = $this->getCustomer($request);
        $settings = ReferralSetting::where('tenant_id', $customer->tenant_id)->first();

        $shareUrl = url('/r/' . $customer->referral_code);

        $shareText = "Rejoignez " . ($settings?->program_name ?? 'notre service de stockage') . " avec mon code parrain et bénéficiez de " . ($settings?->referred_reward_amount ?? 25) . "€ de réduction !";

        return response()->json([
            'url' => $shareUrl,
            'code' => $customer->referral_code,
            'text' => $shareText,
            'reward_amount' => $settings?->referred_reward_amount ?? 25,
            'share_options' => [
                'whatsapp' => "https://wa.me/?text=" . urlencode($shareText . " " . $shareUrl),
                'facebook' => "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($shareUrl),
                'twitter' => "https://twitter.com/intent/tweet?text=" . urlencode($shareText) . "&url=" . urlencode($shareUrl),
                'linkedin' => "https://www.linkedin.com/sharing/share-offsite/?url=" . urlencode($shareUrl),
                'email' => "mailto:?subject=" . urlencode("Invitation - " . ($settings?->program_name ?? 'Service de stockage')) . "&body=" . urlencode($shareText . "\n\n" . $shareUrl),
                'sms' => "sms:?body=" . urlencode($shareText . " " . $shareUrl),
            ],
        ]);
    }

    /**
     * Cancel pending invitation
     */
    public function cancelInvitation(Request $request, CustomerReferral $referral)
    {
        $customer = $this->getCustomer($request);

        if ($referral->referrer_customer_id !== $customer->id) {
            abort(403);
        }

        if (!$referral->isPending()) {
            return response()->json([
                'error' => 'Cette invitation ne peut plus être annulée',
            ], 422);
        }

        $referral->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Invitation annulée',
        ]);
    }

    /**
     * Resend invitation
     */
    public function resendInvitation(Request $request, CustomerReferral $referral)
    {
        $customer = $this->getCustomer($request);

        if ($referral->referrer_customer_id !== $customer->id) {
            abort(403);
        }

        if (!$referral->isPending()) {
            return response()->json([
                'error' => 'Cette invitation ne peut plus être renvoyée',
            ], 422);
        }

        // Reset expiry
        $settings = ReferralSetting::where('tenant_id', $customer->tenant_id)->first();
        $referral->update([
            'expires_at' => now()->addDays($settings?->referral_expiry_days ?? 90),
        ]);

        // Resend notifications
        if ($referral->invited_email) {
            // TODO: Send email
        }

        if ($referral->invited_phone) {
            // TODO: Send SMS
        }

        return response()->json([
            'success' => true,
            'message' => 'Invitation renvoyée',
        ]);
    }

    /**
     * Use referral credits
     */
    public function useCredits(Request $request)
    {
        $customer = $this->getCustomer($request);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        if ($validated['amount'] > $customer->referral_credits) {
            return response()->json([
                'error' => 'Crédits insuffisants',
            ], 422);
        }

        // This would typically be applied to an invoice
        // For now, just return the available amount
        return response()->json([
            'available_credits' => $customer->referral_credits,
            'requested_amount' => $validated['amount'],
            'message' => 'Les crédits seront appliqués à votre prochaine facture',
        ]);
    }

    /**
     * Public: Validate referral code (for booking widget)
     */
    public function validateCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:8',
            'tenant_id' => 'nullable|integer',
        ]);

        $customer = Customer::where('referral_code', strtoupper($validated['code']))->first();

        if (!$customer) {
            return response()->json([
                'valid' => false,
                'error' => 'Code parrain invalide',
            ], 404);
        }

        // Get settings
        $settings = ReferralSetting::where('tenant_id', $customer->tenant_id)->first();

        if (!$settings?->is_active) {
            return response()->json([
                'valid' => false,
                'error' => 'Programme de parrainage non disponible',
            ], 422);
        }

        return response()->json([
            'valid' => true,
            'referrer_name' => $customer->first_name,
            'discount_amount' => $settings->referred_reward_amount,
            'reward_type' => $settings->reward_type,
            'message' => "Code valide ! Vous bénéficierez de {$settings->referred_reward_amount}€ de réduction.",
        ]);
    }

    /**
     * Generate unique referral code
     */
    protected function generateReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Customer::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Get customer from session
     */
    protected function getCustomer(Request $request): Customer
    {
        $customerId = session('mobile_customer_id');

        if (!$customerId) {
            abort(401);
        }

        return Customer::findOrFail($customerId);
    }
}
