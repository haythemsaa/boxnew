<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\PlatformInvoice;
use App\Models\PricingPlan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlatformBillingController extends Controller
{
    public function index(Request $request)
    {
        $query = PlatformInvoice::with('tenant:id,name,email')
            ->latest();

        if ($request->filled('tenant_id')) {
            $query->where('tenant_id', $request->tenant_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('issue_date', '<=', $request->date_to);
        }

        $invoices = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total_revenue' => PlatformInvoice::paid()->sum('total_amount'),
            'pending_amount' => PlatformInvoice::pending()->sum('total_amount'),
            'overdue_amount' => PlatformInvoice::overdue()->sum('total_amount'),
            'this_month' => PlatformInvoice::paid()
                ->whereMonth('paid_date', now()->month)
                ->whereYear('paid_date', now()->year)
                ->sum('total_amount'),
        ];

        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/Billing/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
            'tenants' => $tenants,
            'filters' => $request->only(['tenant_id', 'status', 'date_from', 'date_to']),
        ]);
    }

    public function create()
    {
        $tenants = Tenant::select('id', 'name', 'email', 'plan', 'widget_level', 'billing_cycle')
            ->orderBy('name')
            ->get()
            ->map(function ($tenant) {
                $tenant->monthly_price = $tenant->getMonthlyPrice();
                $tenant->widget_addon_price = $tenant->getWidgetAddonPrice();
                return $tenant;
            });

        // Get pricing from PricingPlan model
        $planPricing = [];
        foreach (PricingPlan::all() as $slug => $plan) {
            $planPricing[$slug] = $plan['price_monthly'] ?? 0;
        }

        $widgetPricing = [];
        foreach (PricingPlan::widgets() as $slug => $widget) {
            $widgetPricing[$slug] = $widget['price_monthly'];
        }

        return Inertia::render('SuperAdmin/Billing/Create', [
            'tenants' => $tenants,
            'planPricing' => $planPricing,
            'widgetPricing' => $widgetPricing,
            'plans' => PricingPlan::all(),
            'widgets' => PricingPlan::widgets(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'line_items' => 'required|array|min:1',
            'line_items.*.description' => 'required|string',
            'line_items.*.quantity' => 'required|integer|min:1',
            'line_items.*.unit_price' => 'required|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'due_date' => 'required|date|after_or_equal:today',
            'notes' => 'nullable|string',
            'status' => 'required|in:draft,pending',
        ]);

        $subtotal = collect($validated['line_items'])->sum(fn($item) => $item['quantity'] * $item['unit_price']);
        $taxRate = $validated['tax_rate'] ?? 20;
        $taxAmount = $subtotal * ($taxRate / 100);
        $totalAmount = $subtotal + $taxAmount;

        PlatformInvoice::create([
            'invoice_number' => PlatformInvoice::generateInvoiceNumber(),
            'tenant_id' => $validated['tenant_id'],
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'status' => $validated['status'],
            'issue_date' => now(),
            'due_date' => $validated['due_date'],
            'notes' => $validated['notes'],
            'line_items' => $validated['line_items'],
        ]);

        return redirect()->route('superadmin.billing.index')
            ->with('success', 'Facture créée avec succès.');
    }

    public function show(PlatformInvoice $platformInvoice)
    {
        $platformInvoice->load('tenant');

        return Inertia::render('SuperAdmin/Billing/Show', [
            'invoice' => $platformInvoice,
        ]);
    }

    public function markAsPaid(Request $request, PlatformInvoice $platformInvoice)
    {
        $validated = $request->validate([
            'payment_method' => 'nullable|string',
            'payment_reference' => 'nullable|string',
        ]);

        $platformInvoice->markAsPaid(
            $validated['payment_method'] ?? null,
            $validated['payment_reference'] ?? null
        );

        return back()->with('success', 'Facture marquée comme payée.');
    }

    public function cancel(PlatformInvoice $platformInvoice)
    {
        $platformInvoice->update(['status' => 'cancelled']);

        return back()->with('success', 'Facture annulée.');
    }

    public function sendReminder(PlatformInvoice $platformInvoice)
    {
        // TODO: Implement email sending
        return back()->with('success', 'Rappel envoyé au tenant.');
    }

    public function generateMonthlyInvoices()
    {
        $tenants = Tenant::where('is_active', true)
            ->whereNotIn('plan', ['free', ''])
            ->whereNotNull('plan')
            ->get();

        $created = 0;
        foreach ($tenants as $tenant) {
            $planConfig = $tenant->getPlanConfig();
            if (!$planConfig) continue;

            // Calculate base price based on billing cycle
            $basePrice = $tenant->billing_cycle === Tenant::BILLING_YEARLY
                ? ($planConfig['price_yearly'] / 12)
                : ($planConfig['price_monthly'] ?? 0);

            if ($basePrice <= 0) continue;

            // Calculate widget addon price
            $widgetAddonPrice = $tenant->getWidgetAddonPrice();

            $lineItems = [];

            // Add plan subscription line
            $planName = ucfirst($tenant->plan);
            $billingLabel = $tenant->billing_cycle === Tenant::BILLING_YEARLY ? '(annuel)' : '(mensuel)';
            $lineItems[] = [
                'description' => "Abonnement {$planName} {$billingLabel} - " . now()->format('F Y'),
                'quantity' => 1,
                'unit_price' => $basePrice,
            ];

            // Add widget addon line if applicable
            if ($widgetAddonPrice > 0 && $tenant->widget_level) {
                $widgetName = match ($tenant->widget_level) {
                    'basic' => 'Widget Basic',
                    'pro' => 'Widget Pro',
                    'whitelabel' => 'Widget White-Label',
                    default => 'Widget',
                };
                $lineItems[] = [
                    'description' => "Add-on {$widgetName} - " . now()->format('F Y'),
                    'quantity' => 1,
                    'unit_price' => $widgetAddonPrice,
                ];
            }

            $subtotal = collect($lineItems)->sum(fn($item) => $item['quantity'] * $item['unit_price']);
            $taxAmount = $subtotal * 0.20; // 20% TVA
            $totalAmount = $subtotal + $taxAmount;

            PlatformInvoice::create([
                'invoice_number' => PlatformInvoice::generateInvoiceNumber(),
                'tenant_id' => $tenant->id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'issue_date' => now(),
                'due_date' => now()->addDays(30),
                'line_items' => $lineItems,
            ]);
            $created++;
        }

        return back()->with('success', "{$created} factures mensuelles générées.");
    }
}
