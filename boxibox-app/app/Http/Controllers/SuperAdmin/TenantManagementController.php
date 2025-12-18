<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Customer;
use App\Models\Box;
use App\Models\Site;
use App\Models\Contract;
use App\Models\PlatformInvoice;
use App\Models\TenantSubscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Contrôleur pour la gestion complète des tenants par le SuperAdmin
 * Permet de créer des contrats, boxes, clients pour n'importe quel tenant
 */
class TenantManagementController extends Controller
{
    /**
     * Créer un client pour un tenant (SuperAdmin)
     */
    public function createCustomer(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'vat_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
        ]);

        $customer = Customer::create(array_merge($validated, [
            'tenant_id' => $tenant->id,
            'status' => 'active',
        ]));

        return back()->with('success', "Client {$customer->first_name} {$customer->last_name} créé pour {$tenant->name}");
    }

    /**
     * Liste des clients d'un tenant
     */
    public function customers(Tenant $tenant)
    {
        $customers = $tenant->customers()
            ->withCount('contracts')
            ->latest()
            ->paginate(20);

        return Inertia::render('SuperAdmin/Tenants/Customers', [
            'tenant' => $tenant,
            'customers' => $customers,
        ]);
    }

    /**
     * Créer un box pour un tenant (SuperAdmin)
     */
    public function createBox(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'number' => 'required|string|max:50',
            'floor_id' => 'nullable|exists:floors,id',
            'size' => 'required|string|max:50',
            'width' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'depth' => 'nullable|numeric',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:available,occupied,reserved,maintenance',
            'description' => 'nullable|string',
        ]);

        // Vérifier que le site appartient au tenant
        $site = Site::where('id', $validated['site_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $box = Box::create(array_merge($validated, [
            'tenant_id' => $tenant->id,
        ]));

        return back()->with('success', "Box {$box->number} créé pour {$tenant->name}");
    }

    /**
     * Liste des boxes d'un tenant
     */
    public function boxes(Tenant $tenant)
    {
        $boxes = Box::where('tenant_id', $tenant->id)
            ->with(['site:id,name', 'floor:id,name'])
            ->latest()
            ->paginate(20);

        $sites = $tenant->sites()->get(['id', 'name']);

        return Inertia::render('SuperAdmin/Tenants/Boxes', [
            'tenant' => $tenant,
            'boxes' => $boxes,
            'sites' => $sites,
        ]);
    }

    /**
     * Créer un contrat pour un tenant (SuperAdmin)
     */
    public function createContract(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'box_id' => 'required|exists:boxes,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after:start_date',
            'monthly_price' => 'required|numeric|min:0',
            'deposit_amount' => 'nullable|numeric|min:0',
            'billing_cycle' => 'required|in:monthly,quarterly,yearly',
            'payment_method' => 'required|string|in:card,bank_transfer,sepa,cash,check',
            'status' => 'required|in:draft,active,terminated,suspended',
            'notes' => 'nullable|string',
        ]);

        // Vérifier que le client et le box appartiennent au tenant
        $customer = Customer::where('id', $validated['customer_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        $box = Box::where('id', $validated['box_id'])
            ->where('tenant_id', $tenant->id)
            ->firstOrFail();

        // Générer le numéro de contrat
        $lastContract = Contract::where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();
        $contractNumber = 'CNT-' . str_pad(($lastContract ? $lastContract->id + 1 : 1), 6, '0', STR_PAD_LEFT);

        $contract = Contract::create(array_merge($validated, [
            'tenant_id' => $tenant->id,
            'contract_number' => $contractNumber,
        ]));

        // Mettre à jour le statut du box
        if ($validated['status'] === 'active') {
            $box->update(['status' => 'occupied']);
        }

        return back()->with('success', "Contrat {$contractNumber} créé pour {$tenant->name}");
    }

    /**
     * Liste des contrats d'un tenant
     */
    public function contracts(Tenant $tenant)
    {
        $contracts = $tenant->contracts()
            ->with(['customer:id,first_name,last_name,email', 'box:id,number,size'])
            ->latest()
            ->paginate(20);

        $customers = $tenant->customers()->get(['id', 'first_name', 'last_name', 'email']);
        $boxes = Box::where('tenant_id', $tenant->id)
            ->where('status', 'available')
            ->with('site:id,name')
            ->get(['id', 'number', 'size', 'site_id']);

        return Inertia::render('SuperAdmin/Tenants/Contracts', [
            'tenant' => $tenant,
            'contracts' => $contracts,
            'customers' => $customers,
            'boxes' => $boxes,
        ]);
    }

    /**
     * Gérer l'abonnement d'un tenant
     */
    public function subscription(Tenant $tenant)
    {
        $currentSubscription = TenantSubscription::where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest()
            ->first();

        $plans = SubscriptionPlan::active()->get();

        $platformInvoices = PlatformInvoice::where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(10);

        $subscriptionHistory = TenantSubscription::where('tenant_id', $tenant->id)
            ->with('plan')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('SuperAdmin/Tenants/Subscription', [
            'tenant' => $tenant,
            'currentSubscription' => $currentSubscription,
            'plans' => $plans,
            'platformInvoices' => $platformInvoices,
            'subscriptionHistory' => $subscriptionHistory,
        ]);
    }

    /**
     * Changer l'abonnement d'un tenant
     */
    public function changeSubscription(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'start_date' => 'nullable|date',
            'trial_days' => 'nullable|integer|min:0|max:90',
        ]);

        $plan = SubscriptionPlan::findOrFail($validated['plan_id']);

        $price = $validated['billing_cycle'] === 'yearly'
            ? $plan->yearly_price
            : $plan->monthly_price;

        $startDate = $validated['start_date'] ?? now();
        $isTrial = isset($validated['trial_days']) && $validated['trial_days'] > 0;

        // Terminer l'abonnement actuel
        TenantSubscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'ends_at' => now(),
            ]);

        // Créer le nouvel abonnement
        $subscription = TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'billing_cycle' => $validated['billing_cycle'],
            'status' => $isTrial ? 'trial' : 'active',
            'trial_ends_at' => $isTrial ? now()->addDays($validated['trial_days']) : null,
            'starts_at' => $startDate,
            'ends_at' => null,
            'price' => $price,
        ]);

        // Mettre à jour le tenant
        $tenant->update([
            'current_plan_id' => $plan->id,
            'plan' => $plan->code,
            'billing_cycle' => $validated['billing_cycle'],
            'subscription_status' => $subscription->status,
            'max_sites' => $plan->max_sites,
            'max_boxes' => $plan->max_boxes,
            'max_users' => $plan->max_users,
        ]);

        return back()->with('success', "Abonnement changé pour le plan {$plan->name}");
    }

    /**
     * Suspendre l'abonnement d'un tenant
     */
    public function suspendSubscription(Tenant $tenant)
    {
        TenantSubscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->update(['status' => 'suspended']);

        $tenant->update([
            'subscription_status' => 'suspended',
            'is_active' => false,
        ]);

        return back()->with('success', "Abonnement de {$tenant->name} suspendu");
    }

    /**
     * Réactiver l'abonnement d'un tenant
     */
    public function reactivateSubscription(Tenant $tenant)
    {
        TenantSubscription::where('tenant_id', $tenant->id)
            ->where('status', 'suspended')
            ->update(['status' => 'active']);

        $tenant->update([
            'subscription_status' => 'active',
            'is_active' => true,
        ]);

        return back()->with('success', "Abonnement de {$tenant->name} réactivé");
    }

    /**
     * Créer une facture plateforme pour un tenant
     */
    public function createPlatformInvoice(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|integer|min:1',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
        ]);

        $subtotal = collect($validated['line_items'])->sum(fn($item) => $item['quantity'] * $item['unit_price']);
        $taxRate = $validated['tax_rate'] ?? 20;
        $taxAmount = $subtotal * ($taxRate / 100);
        $totalAmount = $subtotal + $taxAmount;

        $invoice = PlatformInvoice::create([
            'invoice_number' => PlatformInvoice::generateInvoiceNumber(),
            'tenant_id' => $tenant->id,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => 'pending',
            'issue_date' => now(),
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'] ?? null,
            'line_items' => $validated['line_items'],
        ]);

        return back()->with('success', "Facture {$invoice->invoice_number} créée pour {$tenant->name}");
    }

    /**
     * Statistiques financières d'un tenant
     */
    public function financials(Tenant $tenant)
    {
        $stats = [
            'total_paid' => PlatformInvoice::where('tenant_id', $tenant->id)
                ->where('status', 'paid')
                ->sum('total_amount'),
            'total_pending' => PlatformInvoice::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->sum('total_amount'),
            'total_overdue' => PlatformInvoice::where('tenant_id', $tenant->id)
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->sum('total_amount'),
        ];

        $invoices = PlatformInvoice::where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(20);

        $currentSubscription = TenantSubscription::where('tenant_id', $tenant->id)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        return Inertia::render('SuperAdmin/Tenants/Financials', [
            'tenant' => $tenant,
            'stats' => $stats,
            'invoices' => $invoices,
            'currentSubscription' => $currentSubscription,
        ]);
    }

    /**
     * Mettre à jour les limites d'un tenant
     */
    public function updateLimits(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'max_sites' => 'nullable|integer|min:1',
            'max_boxes' => 'nullable|integer|min:1',
            'max_users' => 'nullable|integer|min:1',
        ]);

        $tenant->update($validated);

        return back()->with('success', 'Limites mises à jour avec succès');
    }
}
