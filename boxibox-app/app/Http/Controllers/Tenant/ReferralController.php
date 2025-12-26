<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralCode;
use App\Models\ReferralReward;
use App\Models\ReferralSettings;
use App\Models\Customer;
use App\Services\ReferralService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReferralController extends Controller
{
    protected ReferralService $referralService;

    public function __construct(ReferralService $referralService)
    {
        $this->referralService = $referralService;
    }

    /**
     * Display referral dashboard
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $settings = ReferralSettings::getForTenant($tenantId);

        // Stats
        $stats = [
            'total_referrals' => Referral::where('tenant_id', $tenantId)->count(),
            'successful_referrals' => Referral::where('tenant_id', $tenantId)->whereIn('status', ['qualified', 'rewarded'])->count(),
            'pending_referrals' => Referral::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'active_codes' => ReferralCode::where('tenant_id', $tenantId)->where('is_active', true)->count(),
            'total_rewards_amount' => ReferralReward::where('tenant_id', $tenantId)->where('status', 'applied')->sum('reward_amount'),
            'pending_rewards_amount' => ReferralReward::where('tenant_id', $tenantId)->where('status', 'pending')->sum('reward_amount'),
        ];

        // Recent referrals
        $referrals = Referral::where('tenant_id', $tenantId)
            ->with(['referrer', 'referee', 'referralCode', 'contract'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function ($referral) {
                return [
                    'id' => $referral->id,
                    'referrer' => $referral->referrer ? [
                        'id' => $referral->referrer->id,
                        'name' => $referral->referrer->full_name,
                    ] : null,
                    'referee' => [
                        'id' => $referral->referee->id,
                        'name' => $referral->referee->full_name,
                    ],
                    'code' => $referral->referralCode->code,
                    'status' => $referral->status,
                    'qualified_at' => $referral->qualified_at?->format('d/m/Y'),
                    'created_at' => $referral->created_at->format('d/m/Y'),
                ];
            });

        // Top referrers
        $topReferrers = ReferralCode::where('tenant_id', $tenantId)
            ->whereNotNull('customer_id')
            ->with('customer')
            ->withCount(['referrals as successful_count' => function ($q) {
                $q->whereIn('status', ['qualified', 'rewarded']);
            }])
            ->having('successful_count', '>', 0)
            ->orderByDesc('successful_count')
            ->limit(10)
            ->get()
            ->map(function ($code) {
                return [
                    'customer' => $code->customer ? [
                        'id' => $code->customer->id,
                        'name' => $code->customer->full_name,
                    ] : null,
                    'code' => $code->code,
                    'successful_referrals' => $code->successful_count,
                    'total_uses' => $code->uses_count,
                ];
            });

        return Inertia::render('Tenant/Referrals/Index', [
            'settings' => $settings,
            'stats' => $stats,
            'referrals' => $referrals,
            'topReferrers' => $topReferrers,
        ]);
    }

    /**
     * Update referral settings
     */
    public function updateSettings(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'is_active' => 'required|boolean',
            'referrer_reward_type' => 'required|in:percentage,fixed,free_month',
            'referrer_reward_value' => 'required|numeric|min:0',
            'referrer_reward_description' => 'nullable|string|max:255',
            'referee_reward_type' => 'required|in:percentage,fixed,free_month',
            'referee_reward_value' => 'required|numeric|min:0',
            'referee_reward_description' => 'nullable|string|max:255',
            'min_rental_months' => 'required|integer|min:0',
            'max_referrals_per_customer' => 'nullable|integer|min:1',
            'reward_delay_days' => 'required|integer|min:0',
            'require_active_contract' => 'required|boolean',
        ]);

        ReferralSettings::updateOrCreate(
            ['tenant_id' => $tenantId, 'site_id' => null],
            $validated
        );

        return back()->with('success', 'Paramètres de parrainage mis à jour');
    }

    /**
     * List all referral codes
     */
    public function codes(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $codes = ReferralCode::where('tenant_id', $tenantId)
            ->with('customer')
            ->withCount('referrals')
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Tenant/Referrals/Codes', [
            'codes' => $codes,
        ]);
    }

    /**
     * Create a new referral code
     */
    public function createCode(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'name' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:20|unique:referral_codes,code',
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:today',
        ]);

        $code = ReferralCode::create([
            'tenant_id' => $tenantId,
            'customer_id' => $validated['customer_id'] ?? null,
            'code' => $validated['code'] ? strtoupper($validated['code']) : null,
            'name' => $validated['name'] ?? null,
            'max_uses' => $validated['max_uses'] ?? null,
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        return back()->with('success', "Code {$code->code} créé avec succès");
    }

    /**
     * Toggle code status
     */
    public function toggleCode(ReferralCode $code)
    {
        $this->authorize('manage', $code);

        $code->update(['is_active' => !$code->is_active]);

        return back()->with('success', $code->is_active ? 'Code activé' : 'Code désactivé');
    }

    /**
     * Delete a code
     */
    public function deleteCode(ReferralCode $code)
    {
        $this->authorize('manage', $code);

        $code->delete();

        return back()->with('success', 'Code supprimé');
    }

    /**
     * List pending rewards
     */
    public function rewards(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $rewards = ReferralReward::where('tenant_id', $tenantId)
            ->with(['customer', 'referral.referrer', 'referral.referee'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return Inertia::render('Tenant/Referrals/Rewards', [
            'rewards' => $rewards,
        ]);
    }

    /**
     * Apply a pending reward
     */
    public function applyReward(ReferralReward $reward)
    {
        $this->authorize('manage', $reward);

        if (!$reward->isPending()) {
            return back()->withErrors(['error' => 'Cette récompense a déjà été traitée']);
        }

        $reward->apply();

        // Mark referral as rewarded if all rewards are applied
        $referral = $reward->referral;
        $pendingRewards = $referral->rewards()->where('status', 'pending')->count();

        if ($pendingRewards === 0) {
            $referral->markAsRewarded();
        }

        return back()->with('success', 'Récompense appliquée');
    }

    /**
     * Generate code for a customer
     */
    public function generateForCustomer(Customer $customer)
    {
        $this->authorize('manage', $customer);

        $code = $this->referralService->getOrCreateCode($customer);

        return back()->with('success', "Code {$code->code} généré pour {$customer->full_name}");
    }

    /**
     * Export referral data
     */
    public function export(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $referrals = Referral::where('tenant_id', $tenantId)
            ->with(['referrer', 'referee', 'referralCode'])
            ->get();

        $csv = "Parrain,Filleul,Code,Statut,Date création,Date qualification\n";

        foreach ($referrals as $referral) {
            $csv .= implode(',', [
                $referral->referrer?->full_name ?? 'N/A',
                $referral->referee->full_name,
                $referral->referralCode->code,
                $referral->status,
                $referral->created_at->format('Y-m-d'),
                $referral->qualified_at?->format('Y-m-d') ?? '',
            ]) . "\n";
        }

        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="referrals-' . date('Y-m-d') . '.csv"',
        ]);
    }
}
