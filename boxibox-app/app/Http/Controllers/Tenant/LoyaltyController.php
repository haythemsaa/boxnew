<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\LoyaltyProgram;
use App\Models\LoyaltyPoints;
use App\Models\LoyaltyTransaction;
use App\Models\LoyaltyReward;
use App\Models\LoyaltyRedemption;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class LoyaltyController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $program = LoyaltyProgram::where('tenant_id', $tenantId)->first();

        $stats = [
            'total_members' => LoyaltyPoints::whereHas('customer', fn($q) => $q->where('tenant_id', $tenantId))->count(),
            'total_points_issued' => LoyaltyTransaction::whereHas('loyaltyPoints.customer', fn($q) => $q->where('tenant_id', $tenantId))
                ->whereIn('type', ['earned', 'bonus', 'referral'])
                ->sum('points'),
            'total_points_redeemed' => LoyaltyTransaction::whereHas('loyaltyPoints.customer', fn($q) => $q->where('tenant_id', $tenantId))
                ->where('type', 'redeemed')
                ->sum('points') * -1,
            'active_rewards' => $program ? LoyaltyReward::where('loyalty_program_id', $program->id)->active()->count() : 0,
        ];

        // Top membres
        $topMembers = LoyaltyPoints::whereHas('customer', fn($q) => $q->where('tenant_id', $tenantId))
            ->with('customer')
            ->orderByDesc('total_points_earned')
            ->limit(10)
            ->get();

        return Inertia::render('Tenant/Loyalty/Index', [
            'program' => $program,
            'stats' => $stats,
            'topMembers' => $topMembers,
        ]);
    }

    public function settings()
    {
        $tenantId = Auth::user()->tenant_id;
        $program = LoyaltyProgram::where('tenant_id', $tenantId)->first();

        return Inertia::render('Tenant/Loyalty/Settings', [
            'program' => $program,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'points_per_euro' => 'required|integer|min:1',
            'min_points_redeem' => 'required|integer|min:1',
            'points_expiry_months' => 'nullable|integer|min:1',
            'welcome_bonus' => 'nullable|integer|min:0',
            'referral_bonus' => 'nullable|integer|min:0',
            'birthday_bonus' => 'nullable|integer|min:0',
            'tier_bronze_min' => 'nullable|integer|min:0',
            'tier_silver_min' => 'nullable|integer|min:0',
            'tier_gold_min' => 'nullable|integer|min:0',
            'tier_platinum_min' => 'nullable|integer|min:0',
            'is_active' => 'boolean',
        ]);

        $tenantId = Auth::user()->tenant_id;

        $program = LoyaltyProgram::updateOrCreate(
            ['tenant_id' => $tenantId],
            [
                'name' => $validated['name'],
                'points_per_euro' => $validated['points_per_euro'],
                'min_points_redeem' => $validated['min_points_redeem'],
                'points_expiry_months' => $validated['points_expiry_months'] ?? null,
                'welcome_bonus' => $validated['welcome_bonus'] ?? 0,
                'referral_bonus' => $validated['referral_bonus'] ?? 0,
                'birthday_bonus' => $validated['birthday_bonus'] ?? 0,
                'tier_thresholds' => [
                    'bronze' => $validated['tier_bronze_min'] ?? 0,
                    'silver' => $validated['tier_silver_min'] ?? 500,
                    'gold' => $validated['tier_gold_min'] ?? 2000,
                    'platinum' => $validated['tier_platinum_min'] ?? 5000,
                ],
                'is_active' => $validated['is_active'] ?? true,
            ]
        );

        return back()->with('success', 'Programme de fidélité mis à jour.');
    }

    public function members(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = LoyaltyPoints::whereHas('customer', fn($q) => $q->where('tenant_id', $tenantId))
            ->with('customer');

        if ($request->filled('tier')) {
            $query->where('current_tier', $request->tier);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $members = $query->orderByDesc('current_balance')->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Loyalty/Members', [
            'members' => $members,
            'filters' => $request->only(['tier', 'search']),
        ]);
    }

    public function showMember(LoyaltyPoints $loyaltyPoints)
    {
        $loyaltyPoints->load(['customer', 'transactions' => fn($q) => $q->latest()->limit(50)]);

        return Inertia::render('Tenant/Loyalty/MemberShow', [
            'member' => $loyaltyPoints,
        ]);
    }

    public function adjustPoints(Request $request, LoyaltyPoints $loyaltyPoints)
    {
        $validated = $request->validate([
            'points' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255',
        ]);

        $type = $validated['points'] > 0 ? 'bonus' : 'adjustment';

        LoyaltyTransaction::create([
            'loyalty_points_id' => $loyaltyPoints->id,
            'type' => $type,
            'points' => $validated['points'],
            'balance_after' => $loyaltyPoints->current_balance + $validated['points'],
            'description' => $validated['reason'],
        ]);

        $loyaltyPoints->increment('current_balance', $validated['points']);
        if ($validated['points'] > 0) {
            $loyaltyPoints->increment('total_earned', $validated['points']);
        }

        return back()->with('success', 'Points ajustés.');
    }

    // Récompenses
    public function rewards()
    {
        $tenantId = Auth::user()->tenant_id;
        $program = LoyaltyProgram::where('tenant_id', $tenantId)->first();

        $rewards = $program
            ? LoyaltyReward::where('loyalty_program_id', $program->id)
                ->withCount('redemptions')
                ->get()
            : collect();

        return Inertia::render('Tenant/Loyalty/Rewards', [
            'rewards' => $rewards,
            'program' => $program,
        ]);
    }

    public function storeReward(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;
        $program = LoyaltyProgram::where('tenant_id', $tenantId)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'points_required' => 'required|integer|min:1',
            'value' => 'nullable|numeric|min:0',
            'min_tier' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'terms_conditions' => 'nullable|string',
        ]);

        LoyaltyReward::create([
            'loyalty_program_id' => $program->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'points_required' => $validated['points_required'],
            'value' => $validated['value'] ?? null,
            'min_tier' => $validated['min_tier'] ?? null,
            'stock_quantity' => $validated['stock_quantity'] ?? null,
            'valid_from' => $validated['valid_from'] ?? null,
            'valid_until' => $validated['valid_until'] ?? null,
            'terms_conditions' => $validated['terms_conditions'] ?? null,
            'is_active' => true,
        ]);

        return back()->with('success', 'Récompense créée.');
    }

    public function updateReward(Request $request, LoyaltyReward $reward)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string',
            'points_required' => 'required|integer|min:1',
            'value' => 'nullable|numeric|min:0',
            'min_tier' => 'nullable|string',
            'stock_quantity' => 'nullable|integer|min:0',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'terms_conditions' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $reward->update($validated);

        return back()->with('success', 'Récompense mise à jour.');
    }

    public function destroyReward(LoyaltyReward $reward)
    {
        $reward->delete();
        return back()->with('success', 'Récompense supprimée.');
    }

    // Échanges
    public function redemptions(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = LoyaltyRedemption::whereHas('loyaltyPoints.customer', fn($q) => $q->where('tenant_id', $tenantId))
            ->with(['loyaltyPoints.customer', 'reward']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $redemptions = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Loyalty/Redemptions', [
            'redemptions' => $redemptions,
            'filters' => $request->only(['status']),
        ]);
    }

    public function processRedemption(Request $request, LoyaltyRedemption $redemption)
    {
        $validated = $request->validate([
            'action' => 'required|in:approve,reject,mark_used',
        ]);

        switch ($validated['action']) {
            case 'mark_used':
                $redemption->update([
                    'status' => 'used',
                    'used_at' => now(),
                ]);
                break;
            case 'reject':
                // Rembourser les points
                $redemption->loyaltyPoints->increment('current_balance', $redemption->points_spent);
                LoyaltyTransaction::create([
                    'loyalty_points_id' => $redemption->loyalty_points_id,
                    'type' => 'adjustment',
                    'points' => $redemption->points_spent,
                    'balance_after' => $redemption->loyaltyPoints->current_balance,
                    'description' => 'Remboursement - échange annulé',
                ]);
                $redemption->update(['status' => 'cancelled']);
                break;
        }

        return back()->with('success', 'Échange mis à jour.');
    }

    // Actions automatiques
    public function awardWelcomeBonus(Customer $customer)
    {
        $program = LoyaltyProgram::where('tenant_id', $customer->tenant_id)->active()->first();
        if (!$program || !$program->welcome_bonus) {
            return;
        }

        $loyaltyPoints = LoyaltyPoints::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'current_balance' => 0,
                'total_earned' => 0,
                'total_redeemed' => 0,
                'current_tier' => 'bronze',
            ]
        );

        $loyaltyPoints->earn($program->welcome_bonus, 'Bonus de bienvenue');
    }

    public function awardPaymentPoints(Customer $customer, float $amount)
    {
        $program = LoyaltyProgram::where('tenant_id', $customer->tenant_id)->active()->first();
        if (!$program) {
            return;
        }

        $points = (int) floor($amount * $program->points_per_euro);
        if ($points <= 0) {
            return;
        }

        $loyaltyPoints = LoyaltyPoints::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'current_balance' => 0,
                'total_earned' => 0,
                'total_redeemed' => 0,
                'current_tier' => 'bronze',
            ]
        );

        $loyaltyPoints->earn($points, "Paiement de {$amount}€");
    }
}
