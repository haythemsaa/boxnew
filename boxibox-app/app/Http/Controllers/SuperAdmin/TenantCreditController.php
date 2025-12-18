<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantCredit;
use App\Models\TenantUsage;
use App\Models\CreditPack;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Gestion des crédits Email/SMS des tenants par le SuperAdmin
 */
class TenantCreditController extends Controller
{
    /**
     * Vue d'ensemble des crédits et usage de tous les tenants
     */
    public function index(Request $request)
    {
        $query = Tenant::with(['subscriptionPlan'])
            ->withCount(['sites', 'customers']);

        // Filtrage
        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                    ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        if ($request->plan_id) {
            $query->where('current_plan_id', $request->plan_id);
        }

        $tenants = $query->orderBy('name')->paginate(20);

        // Enrichir avec les données d'usage
        $tenants->getCollection()->transform(function ($tenant) {
            $usage = TenantUsage::currentMonth($tenant->id);
            $emailCredits = TenantCredit::where('tenant_id', $tenant->id)->email()->active()->sum('credits_remaining');
            $smsCredits = TenantCredit::where('tenant_id', $tenant->id)->sms()->active()->sum('credits_remaining');

            $tenant->usage = [
                'emails_sent' => $usage->emails_sent,
                'emails_quota' => $usage->emails_quota,
                'emails_percent' => $usage->email_usage_percent,
                'sms_sent' => $usage->sms_sent,
                'sms_quota' => $usage->sms_quota,
                'sms_percent' => $usage->sms_usage_percent,
            ];
            $tenant->credits = [
                'emails' => $emailCredits,
                'sms' => $smsCredits,
            ];

            return $tenant;
        });

        $plans = SubscriptionPlan::active()->orderBy('sort_order')->get(['id', 'name', 'code']);
        $creditPacks = CreditPack::active()->ordered()->get();

        // Stats globales
        $stats = [
            'total_emails_sent_today' => TenantUsage::whereDate('period_start', '<=', now())
                ->whereDate('period_end', '>=', now())
                ->sum('emails_sent'),
            'total_sms_sent_today' => TenantUsage::whereDate('period_start', '<=', now())
                ->whereDate('period_end', '>=', now())
                ->sum('sms_sent'),
            'total_email_credits' => TenantCredit::email()->active()->sum('credits_remaining'),
            'total_sms_credits' => TenantCredit::sms()->active()->sum('credits_remaining'),
            'tenants_over_quota' => Tenant::whereRaw('emails_sent_this_month > 0')->count(), // Simplified
        ];

        return Inertia::render('SuperAdmin/Credits/Index', [
            'tenants' => $tenants,
            'plans' => $plans,
            'creditPacks' => $creditPacks,
            'stats' => $stats,
            'filters' => $request->only(['search', 'plan_id']),
        ]);
    }

    /**
     * Détail d'un tenant avec historique de consommation
     */
    public function show(Tenant $tenant)
    {
        $tenant->load('subscriptionPlan');

        // Usage actuel
        $currentUsage = TenantUsage::currentMonth($tenant->id);

        // Historique des 12 derniers mois
        $usageHistory = TenantUsage::where('tenant_id', $tenant->id)
            ->orderBy('period_start', 'desc')
            ->limit(12)
            ->get();

        // Crédits actifs
        $activeCredits = TenantCredit::where('tenant_id', $tenant->id)
            ->active()
            ->orderBy('expires_at')
            ->get();

        // Historique des crédits
        $creditHistory = TenantCredit::where('tenant_id', $tenant->id)
            ->orderBy('purchased_at', 'desc')
            ->limit(20)
            ->get();

        // Packs disponibles
        $creditPacks = CreditPack::active()->ordered()->get();

        return Inertia::render('SuperAdmin/Credits/Show', [
            'tenant' => $tenant,
            'currentUsage' => $currentUsage,
            'usageHistory' => $usageHistory,
            'activeCredits' => $activeCredits,
            'creditHistory' => $creditHistory,
            'creditPacks' => $creditPacks,
        ]);
    }

    /**
     * Ajouter des crédits manuellement à un tenant
     */
    public function addCredits(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'type' => 'required|in:email,sms',
            'credits' => 'required|integer|min:1',
            'reason' => 'nullable|string|max:255',
            'expires_at' => 'nullable|date|after:today',
        ]);

        TenantCredit::create([
            'tenant_id' => $tenant->id,
            'type' => $validated['type'],
            'credits_purchased' => $validated['credits'],
            'credits_remaining' => $validated['credits'],
            'amount_paid' => 0,
            'currency' => 'EUR',
            'payment_method' => 'manual',
            'purchased_at' => now(),
            'expires_at' => $validated['expires_at'] ?? null,
            'status' => 'active',
        ]);

        $typeLabel = $validated['type'] === 'email' ? 'emails' : 'SMS';

        return back()->with('success', "{$validated['credits']} crédits {$typeLabel} ajoutés à {$tenant->name}");
    }

    /**
     * Ajouter un pack de crédits à un tenant
     */
    public function addPack(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'pack_id' => 'required|exists:credit_packs,id',
            'payment_method' => 'required|in:manual,promo,gift',
        ]);

        $pack = CreditPack::findOrFail($validated['pack_id']);

        $expiresAt = $pack->validity_days
            ? now()->addDays($pack->validity_days)
            : null;

        TenantCredit::create([
            'tenant_id' => $tenant->id,
            'type' => $pack->type,
            'credits_purchased' => $pack->credits,
            'credits_remaining' => $pack->credits,
            'amount_paid' => $validated['payment_method'] === 'manual' ? $pack->price : 0,
            'currency' => $pack->currency,
            'payment_method' => $validated['payment_method'],
            'purchased_at' => now(),
            'expires_at' => $expiresAt,
            'status' => 'active',
        ]);

        return back()->with('success', "Pack '{$pack->name}' ajouté à {$tenant->name}");
    }

    /**
     * Révoquer des crédits
     */
    public function revokeCredits(TenantCredit $credit)
    {
        $tenant = $credit->tenant;

        $credit->update([
            'status' => 'expired',
            'credits_remaining' => 0,
        ]);

        return back()->with('success', "Crédits révoqués pour {$tenant->name}");
    }

    /**
     * Réinitialiser l'usage mensuel d'un tenant
     */
    public function resetUsage(Tenant $tenant)
    {
        $tenant->update([
            'emails_sent_this_month' => 0,
            'sms_sent_this_month' => 0,
            'usage_reset_at' => now(),
        ]);

        $usage = TenantUsage::currentMonth($tenant->id);
        $usage->update([
            'emails_sent' => 0,
            'sms_sent' => 0,
        ]);

        return back()->with('success', "Compteurs réinitialisés pour {$tenant->name}");
    }

    /**
     * Modifier le plan d'un tenant
     */
    public function changePlan(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
        ]);

        $plan = SubscriptionPlan::find($validated['plan_id']);

        $tenant->update([
            'current_plan_id' => $plan->id,
        ]);

        // Mettre à jour les quotas dans l'usage courant
        $usage = TenantUsage::currentMonth($tenant->id);
        $usage->update([
            'emails_quota' => $plan->emails_per_month ?? 500,
            'sms_quota' => $plan->sms_per_month ?? 0,
        ]);

        return back()->with('success', "Plan changé vers '{$plan->name}' pour {$tenant->name}");
    }

    /**
     * Gérer les packs de crédits disponibles
     */
    public function packs()
    {
        $packs = CreditPack::ordered()->get();

        return Inertia::render('SuperAdmin/Credits/Packs', [
            'packs' => $packs,
        ]);
    }

    /**
     * Créer un pack de crédits
     */
    public function storePack(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:email,sms',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'validity_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        CreditPack::create($validated);

        return back()->with('success', 'Pack créé avec succès');
    }

    /**
     * Mettre à jour un pack
     */
    public function updatePack(Request $request, CreditPack $pack)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'credits' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'validity_days' => 'nullable|integer|min:1',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer',
        ]);

        $pack->update($validated);

        return back()->with('success', 'Pack mis à jour');
    }

    /**
     * Supprimer un pack
     */
    public function destroyPack(CreditPack $pack)
    {
        $pack->delete();

        return back()->with('success', 'Pack supprimé');
    }

    /**
     * Initialiser les packs par défaut
     */
    public function seedDefaultPacks()
    {
        CreditPack::seedDefaults();

        return back()->with('success', 'Packs par défaut créés');
    }
}
