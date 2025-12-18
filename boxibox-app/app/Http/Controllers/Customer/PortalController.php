<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\CustomerPortalSettings;
use App\Models\CustomerRequest;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\MaintenanceTicket;
use App\Models\SavedPaymentMethod;
use App\Models\CustomerNotificationPreference;
use App\Models\InsurancePlan;
use App\Models\InsurancePolicy;
use App\Models\InsuranceClaim;
use App\Services\StripePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class PortalController extends Controller
{
    /**
     * Display login page
     */
    public function loginPage()
    {
        if (session('customer_portal_id')) {
            return redirect()->route('customer.portal.dashboard');
        }

        return Inertia::render('Customer/Portal/Login');
    }

    /**
     * Handle login
     */
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('email', $validated['email'])->first();

        if (!$customer || !Hash::check($validated['password'], $customer->password ?? '')) {
            return back()->withErrors(['email' => 'Ces identifiants ne correspondent pas à nos enregistrements.']);
        }

        session(['customer_portal_id' => $customer->id]);

        return redirect()->route('customer.portal.dashboard')
            ->with('success', 'Bienvenue, ' . $customer->first_name . ' !');
    }

    /**
     * Handle logout
     */
    public function logout()
    {
        session()->forget('customer_portal_id');

        return redirect()->route('customer.portal.login')
            ->with('success', 'Vous avez été déconnecté.');
    }

    /**
     * Customer portal dashboard
     */
    public function dashboard(): Response
    {
        $customer = $this->getAuthenticatedCustomer();
        $tenantId = $customer->tenant_id;
        $settings = CustomerPortalSettings::getForTenant($tenantId);

        // Active contracts
        $contracts = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->with(['box.site', 'site'])
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'contract_number' => $c->contract_number,
                'box_code' => $c->box?->number,
                'site_name' => $c->site?->name ?? $c->box?->site?->name,
                'monthly_price' => $c->monthly_price,
                'start_date' => $c->start_date?->format('d/m/Y'),
                'end_date' => $c->end_date?->format('d/m/Y'),
                'status' => $c->status,
            ]);

        // Recent invoices
        $invoices = Invoice::where('customer_id', $customer->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'total' => $i->total,
                'status' => $i->status,
                'due_date' => $i->due_date?->format('d/m/Y'),
                'is_overdue' => $i->status === 'pending' && $i->due_date?->isPast(),
            ]);

        // Stats
        $stats = [
            'active_contracts' => $contracts->count(),
            'total_monthly' => $contracts->sum('monthly_price'),
            'pending_invoices' => Invoice::where('customer_id', $customer->id)->where('status', 'pending')->count(),
            'pending_amount' => Invoice::where('customer_id', $customer->id)->where('status', 'pending')->sum('total'),
        ];

        // Access codes (if any)
        $accessCodes = $contracts->map(fn($c) => [
            'site_name' => $c['site_name'],
            'box_code' => $c['box_code'],
            'access_code' => '****' . substr(md5($c['id']), 0, 4), // Masked for demo
        ]);

        return Inertia::render('Customer/Portal/Dashboard', [
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
            ],
            'contracts' => $contracts,
            'invoices' => $invoices,
            'stats' => $stats,
            'accessCodes' => $accessCodes,
            'settings' => $settings,
        ]);
    }

    /**
     * List customer's contracts
     */
    public function contracts(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $contracts = Contract::where('customer_id', $customer->id)
            ->with(['box.site', 'site', 'invoices' => fn($q) => $q->latest()->limit(3)])
            ->latest()
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'contract_number' => $c->contract_number,
                'box_code' => $c->box?->number,
                'box_size' => $c->box?->size_m2,
                'site_name' => $c->site?->name ?? $c->box?->site?->name,
                'site_address' => $c->site?->address ?? $c->box?->site?->address,
                'monthly_price' => $c->monthly_price,
                'start_date' => $c->start_date?->format('d/m/Y'),
                'end_date' => $c->end_date?->format('d/m/Y'),
                'status' => $c->status,
                'recent_invoices' => $c->invoices->map(fn($i) => [
                    'id' => $i->id,
                    'invoice_number' => $i->invoice_number,
                    'total' => $i->total,
                    'status' => $i->status,
                ]),
            ]);

        return Inertia::render('Customer/Portal/Contracts', [
            'contracts' => $contracts,
        ]);
    }

    /**
     * View single contract
     */
    public function showContract(Contract $contract): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        $contract->load(['box.site', 'site', 'invoices', 'payments']);

        return Inertia::render('Customer/Portal/ContractShow', [
            'contract' => [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'box' => $contract->box ? [
                    'code' => $contract->box->number,
                    'size_m2' => $contract->box->size_m2,
                    'floor' => $contract->box->floor,
                ] : null,
                'site' => [
                    'name' => $contract->site?->name ?? $contract->box?->site?->name,
                    'address' => $contract->site?->address ?? $contract->box?->site?->address,
                    'phone' => $contract->site?->phone ?? $contract->box?->site?->phone,
                ],
                'monthly_price' => $contract->monthly_price,
                'deposit' => $contract->deposit,
                'start_date' => $contract->start_date?->format('d/m/Y'),
                'end_date' => $contract->end_date?->format('d/m/Y'),
                'status' => $contract->status,
                'billing_cycle' => $contract->billing_cycle ?? 'monthly',
            ],
            'invoices' => $contract->invoices->map(fn($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'total' => $i->total,
                'status' => $i->status,
                'due_date' => $i->due_date?->format('d/m/Y'),
                'paid_at' => $i->paid_at?->format('d/m/Y'),
            ]),
            'payments' => $contract->payments->map(fn($p) => [
                'id' => $p->id,
                'amount' => $p->amount,
                'payment_date' => $p->paid_at?->format('d/m/Y'),
                'payment_method' => $p->payment_method,
            ]),
        ]);
    }

    /**
     * List customer's invoices
     */
    public function invoices(Request $request): Response
    {
        $customer = $this->getAuthenticatedCustomer();
        $status = $request->input('status');

        $query = Invoice::where('customer_id', $customer->id)
            ->with(['contract.site', 'contract.box']);

        if ($status) {
            $query->where('status', $status);
        }

        $invoices = $query->latest()->paginate(15);

        // Stats
        $stats = [
            'total' => Invoice::where('customer_id', $customer->id)->count(),
            'pending' => Invoice::where('customer_id', $customer->id)->where('status', 'pending')->count(),
            'paid' => Invoice::where('customer_id', $customer->id)->where('status', 'paid')->count(),
            'overdue' => Invoice::where('customer_id', $customer->id)
                ->where('status', 'pending')
                ->where('due_date', '<', now())
                ->count(),
        ];

        return Inertia::render('Customer/Portal/Invoices', [
            'invoices' => $invoices,
            'stats' => $stats,
            'filters' => ['status' => $status],
        ]);
    }

    /**
     * View single invoice
     */
    public function showInvoice(Invoice $invoice): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($invoice->customer_id !== $customer->id) {
            abort(403);
        }

        $invoice->load(['contract.site', 'contract.box', 'payments', 'items']);

        return Inertia::render('Customer/Portal/InvoiceShow', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'subtotal' => $invoice->subtotal,
                'tax' => $invoice->tax,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date?->format('d/m/Y'),
                'due_date' => $invoice->due_date?->format('d/m/Y'),
                'paid_at' => $invoice->paid_at?->format('d/m/Y'),
                'contract' => $invoice->contract ? [
                    'contract_number' => $invoice->contract->contract_number,
                    'box_code' => $invoice->contract->box?->number,
                    'site_name' => $invoice->contract->site?->name,
                ] : null,
                'items' => $invoice->items?->map(fn($item) => [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                ]) ?? [],
            ],
            'payments' => $invoice->payments->map(fn($p) => [
                'amount' => $p->amount,
                'payment_date' => $p->paid_at?->format('d/m/Y'),
                'payment_method' => $p->payment_method,
            ]),
        ]);
    }

    /**
     * Download invoice PDF
     */
    public function downloadInvoice(Invoice $invoice)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($invoice->customer_id !== $customer->id) {
            abort(403);
        }

        $invoice->load(['customer', 'contract.box.site', 'items', 'tenant']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'customer' => $customer,
            'tenant' => $invoice->tenant,
        ]);

        return $pdf->download("facture-{$invoice->invoice_number}.pdf");
    }

    /**
     * Download contract PDF
     */
    public function downloadContract(Contract $contract)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        $contract->load(['customer', 'box.site', 'tenant']);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.contract', [
            'contract' => $contract,
            'customer' => $customer,
            'tenant' => $contract->tenant,
        ]);

        return $pdf->download("contrat-{$contract->contract_number}.pdf");
    }

    /**
     * Payment history
     */
    public function payments(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $payments = Payment::where('customer_id', $customer->id)
            ->with(['invoice.contract'])
            ->latest()
            ->paginate(15);

        // Stats
        $stats = [
            'total_paid' => Payment::where('customer_id', $customer->id)
                ->where('status', 'completed')
                ->sum('amount'),
            'this_year' => Payment::where('customer_id', $customer->id)
                ->where('status', 'completed')
                ->whereYear('created_at', now()->year)
                ->sum('amount'),
            'payment_count' => Payment::where('customer_id', $customer->id)->count(),
        ];

        return Inertia::render('Customer/Portal/Payments', [
            'payments' => $payments,
            'stats' => $stats,
        ]);
    }

    /**
     * Manage payment methods
     */
    public function paymentMethods(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $methods = SavedPaymentMethod::where('customer_id', $customer->id)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'last_four' => $m->last_four,
                'brand' => $m->brand,
                'expiry' => $m->expiry_month . '/' . $m->expiry_year,
                'is_default' => $m->is_default,
            ]);

        return Inertia::render('Customer/Portal/PaymentMethods', [
            'methods' => $methods,
        ]);
    }

    /**
     * Customer profile
     */
    public function profile(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        return Inertia::render('Customer/Portal/Profile', [
            'customer' => [
                'id' => $customer->id,
                'first_name' => $customer->first_name,
                'last_name' => $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'address' => $customer->address,
                'city' => $customer->city,
                'postal_code' => $customer->postal_code,
                'country' => $customer->country,
                'company_name' => $customer->company_name,
                'vat_number' => $customer->vat_number,
            ],
        ]);
    }

    /**
     * Update customer profile
     */
    public function updateProfile(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Profil mis à jour avec succès.');
    }

    /**
     * Change password
     */
    public function changePassword(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Verify current password
        if (!Hash::check($validated['current_password'], $customer->password)) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $customer->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', 'Mot de passe modifié avec succès.');
    }

    /**
     * Notification preferences
     */
    public function notificationPreferences(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $preferences = CustomerNotificationPreference::firstOrCreate(
            ['customer_id' => $customer->id],
            [
                'email_invoices' => true,
                'email_reminders' => true,
                'email_marketing' => false,
                'sms_invoices' => false,
                'sms_reminders' => true,
                'push_enabled' => true,
            ]
        );

        return Inertia::render('Customer/Portal/NotificationPreferences', [
            'preferences' => $preferences,
        ]);
    }

    /**
     * Update notification preferences
     */
    public function updateNotificationPreferences(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'email_invoices' => 'boolean',
            'email_reminders' => 'boolean',
            'email_marketing' => 'boolean',
            'sms_invoices' => 'boolean',
            'sms_reminders' => 'boolean',
            'push_enabled' => 'boolean',
        ]);

        CustomerNotificationPreference::updateOrCreate(
            ['customer_id' => $customer->id],
            $validated
        );

        return back()->with('success', 'Préférences mises à jour.');
    }

    /**
     * Submit a request (maintenance, box change, termination)
     */
    public function submitRequest(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'type' => 'required|in:maintenance,box_change,termination,general',
            'contract_id' => 'nullable|exists:contracts,id',
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'priority' => 'nullable|in:low,medium,high',
        ]);

        // Verify contract belongs to customer
        if ($validated['contract_id']) {
            $contract = Contract::find($validated['contract_id']);
            if ($contract->customer_id !== $customer->id) {
                abort(403);
            }
        }

        CustomerRequest::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'contract_id' => $validated['contract_id'],
            'type' => $validated['type'],
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'pending',
        ]);

        return back()->with('success', 'Demande envoyée avec succès. Nous vous répondrons rapidement.');
    }

    /**
     * List customer's requests
     */
    public function requests(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $requests = CustomerRequest::where('customer_id', $customer->id)
            ->with('contract')
            ->latest()
            ->paginate(10);

        return Inertia::render('Customer/Portal/Requests', [
            'requests' => $requests,
        ]);
    }

    /**
     * Support / Contact
     */
    public function support(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $contracts = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->get(['id', 'contract_number']);

        return Inertia::render('Customer/Portal/Support', [
            'contracts' => $contracts,
        ]);
    }

    /**
     * Autopay settings page
     */
    public function autopay(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Get contracts with autopay status
        $contracts = Contract::where('customer_id', $customer->id)
            ->whereIn('status', ['active', 'pending_signature'])
            ->with(['box.site', 'site'])
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'contract_number' => $c->contract_number,
                'box_name' => $c->box?->name ?? $c->box?->number,
                'site_name' => $c->site?->name ?? $c->box?->site?->name,
                'monthly_price' => $c->monthly_price,
                'auto_pay' => $c->auto_pay,
                'billing_day' => $c->billing_day,
                'next_billing' => $c->start_date?->copy()->day($c->billing_day)->addMonthsNoOverflow(
                    now()->day >= $c->billing_day ? 1 : 0
                )->format('d/m/Y'),
            ]);

        // Get saved payment methods
        $paymentMethods = SavedPaymentMethod::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'last_four' => $m->last_four,
                'brand' => $m->brand,
                'exp_month' => $m->exp_month,
                'exp_year' => $m->exp_year,
                'is_default' => $m->is_default,
                'label' => $m->type === 'card'
                    ? ucfirst($m->brand) . ' •••• ' . $m->last_four
                    : 'SEPA •••• ' . $m->iban_last_four,
            ]);

        return Inertia::render('Customer/Portal/Autopay', [
            'contracts' => $contracts,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * Toggle autopay for a contract
     */
    public function toggleAutopay(Request $request, Contract $contract)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'payment_method_id' => 'nullable|exists:saved_payment_methods,id',
        ]);

        // Verify payment method belongs to customer
        if ($validated['payment_method_id']) {
            $method = SavedPaymentMethod::find($validated['payment_method_id']);
            if ($method->customer_id !== $customer->id) {
                abort(403);
            }
        }

        $contract->update([
            'auto_pay' => $validated['enabled'],
        ]);

        $message = $validated['enabled']
            ? 'Paiement automatique activé pour ce contrat.'
            : 'Paiement automatique désactivé.';

        return back()->with('success', $message);
    }

    /**
     * Referral program page
     */
    public function referral(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Generate referral code if not exists
        if (!$customer->referral_code) {
            $customer->update([
                'referral_code' => strtoupper(substr($customer->first_name, 0, 3) . rand(1000, 9999)),
            ]);
        }

        // Get referral stats (mock for now, would come from referrals table)
        $referralStats = [
            'code' => $customer->referral_code,
            'total_referrals' => 0,
            'successful_referrals' => 0,
            'pending_reward' => 0,
            'total_earned' => 0,
            'reward_per_referral' => 50, // €50 per successful referral
        ];

        // Referral history (would come from referrals table)
        $referralHistory = [];

        return Inertia::render('Customer/Portal/Referral', [
            'stats' => $referralStats,
            'history' => $referralHistory,
            'shareUrl' => url('/reserve?ref=' . $customer->referral_code),
        ]);
    }

    /**
     * Documents center - all contracts and invoices in one place
     */
    public function documents(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Get all contracts with PDF
        $contracts = Contract::where('customer_id', $customer->id)
            ->with(['site', 'box.site'])
            ->latest()
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'type' => 'contract',
                'name' => 'Contrat ' . $c->contract_number,
                'description' => ($c->site?->name ?? $c->box?->site?->name) . ' - Box ' . ($c->box?->name ?? $c->box?->number),
                'date' => $c->created_at?->format('d/m/Y'),
                'status' => $c->status,
                'has_pdf' => true,
            ]);

        // Get all invoices with PDF
        $invoices = Invoice::where('customer_id', $customer->id)
            ->with(['contract'])
            ->latest()
            ->get()
            ->map(fn($i) => [
                'id' => $i->id,
                'type' => 'invoice',
                'name' => 'Facture ' . $i->invoice_number,
                'description' => 'Montant: ' . number_format($i->total, 2) . ' €',
                'date' => $i->issue_date?->format('d/m/Y'),
                'status' => $i->status,
                'has_pdf' => true,
            ]);

        // Merge and sort by date
        $documents = $contracts->concat($invoices)->sortByDesc('date')->values();

        // Stats
        $stats = [
            'total_contracts' => $contracts->count(),
            'active_contracts' => $contracts->where('status', 'active')->count(),
            'total_invoices' => $invoices->count(),
            'paid_invoices' => $invoices->where('status', 'paid')->count(),
        ];

        return Inertia::render('Customer/Portal/Documents', [
            'documents' => $documents,
            'stats' => $stats,
        ]);
    }

    /**
     * Size calculator page
     */
    public function sizeCalculator(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Predefined items with their typical sizes (in m³)
        $items = [
            ['id' => 'box_small', 'name' => 'Carton petit', 'icon' => 'box', 'volume' => 0.03],
            ['id' => 'box_medium', 'name' => 'Carton moyen', 'icon' => 'box', 'volume' => 0.06],
            ['id' => 'box_large', 'name' => 'Carton grand', 'icon' => 'box', 'volume' => 0.12],
            ['id' => 'suitcase', 'name' => 'Valise', 'icon' => 'suitcase', 'volume' => 0.08],
            ['id' => 'chair', 'name' => 'Chaise', 'icon' => 'chair', 'volume' => 0.2],
            ['id' => 'table_small', 'name' => 'Table basse', 'icon' => 'table', 'volume' => 0.3],
            ['id' => 'table_dining', 'name' => 'Table à manger', 'icon' => 'table', 'volume' => 0.8],
            ['id' => 'sofa_2', 'name' => 'Canapé 2 places', 'icon' => 'sofa', 'volume' => 1.5],
            ['id' => 'sofa_3', 'name' => 'Canapé 3 places', 'icon' => 'sofa', 'volume' => 2.5],
            ['id' => 'bed_single', 'name' => 'Lit simple', 'icon' => 'bed', 'volume' => 1.0],
            ['id' => 'bed_double', 'name' => 'Lit double', 'icon' => 'bed', 'volume' => 2.0],
            ['id' => 'mattress', 'name' => 'Matelas', 'icon' => 'bed', 'volume' => 0.5],
            ['id' => 'wardrobe', 'name' => 'Armoire', 'icon' => 'wardrobe', 'volume' => 2.0],
            ['id' => 'dresser', 'name' => 'Commode', 'icon' => 'dresser', 'volume' => 0.8],
            ['id' => 'desk', 'name' => 'Bureau', 'icon' => 'desk', 'volume' => 0.6],
            ['id' => 'bookshelf', 'name' => 'Bibliothèque', 'icon' => 'bookshelf', 'volume' => 1.2],
            ['id' => 'tv', 'name' => 'TV + meuble', 'icon' => 'tv', 'volume' => 0.5],
            ['id' => 'fridge', 'name' => 'Réfrigérateur', 'icon' => 'fridge', 'volume' => 1.0],
            ['id' => 'washing_machine', 'name' => 'Lave-linge', 'icon' => 'washing', 'volume' => 0.5],
            ['id' => 'bike', 'name' => 'Vélo', 'icon' => 'bike', 'volume' => 0.5],
            ['id' => 'ski', 'name' => 'Skis (paire)', 'icon' => 'ski', 'volume' => 0.1],
            ['id' => 'tire', 'name' => 'Pneu', 'icon' => 'tire', 'volume' => 0.15],
        ];

        // Box size recommendations (m²)
        $boxSizes = [
            ['size' => 1, 'name' => 'Casier 1m²', 'description' => 'Quelques cartons, documents', 'max_volume' => 2],
            ['size' => 2, 'name' => 'Box 2m²', 'description' => 'Studio, petit déménagement', 'max_volume' => 5],
            ['size' => 4, 'name' => 'Box 4m²', 'description' => 'Appartement T1-T2', 'max_volume' => 10],
            ['size' => 6, 'name' => 'Box 6m²', 'description' => 'Appartement T2-T3', 'max_volume' => 15],
            ['size' => 10, 'name' => 'Box 10m²', 'description' => 'Appartement T3-T4', 'max_volume' => 25],
            ['size' => 15, 'name' => 'Box 15m²', 'description' => 'Maison, gros déménagement', 'max_volume' => 40],
            ['size' => 20, 'name' => 'Box 20m²', 'description' => 'Grande maison, professionnel', 'max_volume' => 55],
        ];

        return Inertia::render('Customer/Portal/SizeCalculator', [
            'items' => $items,
            'boxSizes' => $boxSizes,
        ]);
    }

    /**
     * Insurance / Protection plans page
     */
    public function insurance(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Get customer's contracts with insurance status
        $contracts = Contract::where('customer_id', $customer->id)
            ->whereIn('status', ['active', 'pending_signature'])
            ->with(['box.site', 'site', 'insurancePolicy.plan'])
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'contract_number' => $c->contract_number,
                'box_name' => $c->box?->name ?? $c->box?->number,
                'box_size' => $c->box?->size_m2 ?? $c->box?->volume,
                'site_name' => $c->site?->name ?? $c->box?->site?->name,
                'monthly_price' => $c->monthly_price,
                'has_insurance' => $c->insurancePolicy !== null && $c->insurancePolicy->isActive(),
                'insurance' => $c->insurancePolicy ? [
                    'id' => $c->insurancePolicy->id,
                    'plan_name' => $c->insurancePolicy->plan?->name,
                    'coverage_amount' => $c->insurancePolicy->coverage_amount,
                    'premium_monthly' => $c->insurancePolicy->premium_monthly,
                    'status' => $c->insurancePolicy->status,
                    'start_date' => $c->insurancePolicy->start_date?->format('d/m/Y'),
                    'end_date' => $c->insurancePolicy->end_date?->format('d/m/Y'),
                    'declared_value' => $c->insurancePolicy->declared_value,
                    'deductible' => $c->insurancePolicy->deductible,
                ] : null,
            ]);

        // Get available insurance plans
        $plans = InsurancePlan::active()
            ->with('provider')
            ->orderBy('order')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'code' => $p->code,
                'description' => $p->description,
                'coverage_amount' => $p->coverage_amount,
                'covered_risks' => $p->covered_risks,
                'exclusions' => $p->exclusions,
                'price_monthly' => $p->price_monthly,
                'price_yearly' => $p->price_yearly,
                'pricing_type' => $p->pricing_type,
                'deductible' => $p->deductible,
                'is_default' => $p->is_default,
                'provider_name' => $p->provider?->name,
            ]);

        // Get customer's active policies
        $activePolicies = InsurancePolicy::where('customer_id', $customer->id)
            ->active()
            ->with(['plan', 'contract.box'])
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'policy_number' => $p->policy_number,
                'plan_name' => $p->plan?->name,
                'contract_number' => $p->contract?->contract_number,
                'box_name' => $p->contract?->box?->name ?? $p->contract?->box?->number,
                'coverage_amount' => $p->coverage_amount,
                'premium_monthly' => $p->premium_monthly,
                'status' => $p->status,
                'start_date' => $p->start_date?->format('d/m/Y'),
                'end_date' => $p->end_date?->format('d/m/Y'),
                'declared_value' => $p->declared_value,
                'deductible' => $p->deductible,
                'auto_renew' => $p->auto_renew,
            ]);

        // Get customer's claims
        $claims = InsuranceClaim::whereHas('policy', function ($q) use ($customer) {
            $q->where('customer_id', $customer->id);
        })
            ->with('policy.plan')
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'claim_number' => $c->claim_number,
                'policy_number' => $c->policy?->policy_number,
                'incident_date' => $c->incident_date?->format('d/m/Y'),
                'incident_type' => $c->incident_type,
                'claimed_amount' => $c->claimed_amount,
                'approved_amount' => $c->approved_amount,
                'status' => $c->status,
                'submitted_at' => $c->submitted_at?->format('d/m/Y'),
            ]);

        // Stats
        $stats = [
            'total_contracts' => $contracts->count(),
            'insured_contracts' => $contracts->where('has_insurance', true)->count(),
            'total_coverage' => $activePolicies->sum('coverage_amount'),
            'monthly_premium' => $activePolicies->sum('premium_monthly'),
            'active_claims' => $claims->whereIn('status', ['pending', 'under_review'])->count(),
        ];

        return Inertia::render('Customer/Portal/Insurance', [
            'contracts' => $contracts,
            'plans' => $plans,
            'policies' => $activePolicies,
            'claims' => $claims,
            'stats' => $stats,
        ]);
    }

    /**
     * Subscribe to an insurance plan
     */
    public function subscribeInsurance(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'plan_id' => 'required|exists:insurance_plans,id',
            'declared_value' => 'nullable|numeric|min:0',
            'items_description' => 'nullable|string|max:2000',
        ]);

        // Verify contract belongs to customer
        $contract = Contract::find($validated['contract_id']);
        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        // Check if contract already has active insurance
        $existingPolicy = InsurancePolicy::where('contract_id', $contract->id)
            ->active()
            ->first();

        if ($existingPolicy) {
            return back()->withErrors(['contract_id' => 'Ce contrat a déjà une assurance active.']);
        }

        // Get plan and calculate premium
        $plan = InsurancePlan::findOrFail($validated['plan_id']);
        $boxSize = $contract->box?->size_m2 ?? 5;
        $premium = $plan->calculatePremium($boxSize, $validated['declared_value'] ?? null);

        // Create policy
        $policy = InsurancePolicy::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'plan_id' => $plan->id,
            'policy_number' => InsurancePolicy::generatePolicyNumber(),
            'coverage_amount' => $plan->coverage_amount,
            'premium_monthly' => $premium,
            'premium_yearly' => $premium * 12 * 0.9, // 10% discount for yearly
            'deductible' => $plan->deductible,
            'status' => 'active',
            'start_date' => now(),
            'declared_value' => $validated['declared_value'],
            'items_description' => $validated['items_description'],
            'payment_frequency' => 'monthly',
            'auto_renew' => true,
        ]);

        return back()->with('success', 'Assurance souscrite avec succès ! Numéro de police : ' . $policy->policy_number);
    }

    /**
     * Cancel an insurance policy
     */
    public function cancelInsurance(Request $request, InsurancePolicy $policy)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($policy->customer_id !== $customer->id) {
            abort(403);
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $policy->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $validated['reason'],
            'end_date' => now()->endOfMonth(), // Coverage until end of current month
        ]);

        return back()->with('success', 'Assurance résiliée. La couverture reste active jusqu\'au ' . $policy->end_date->format('d/m/Y') . '.');
    }

    /**
     * Submit an insurance claim
     */
    public function submitClaim(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'policy_id' => 'required|exists:insurance_policies,id',
            'incident_date' => 'required|date|before_or_equal:today',
            'incident_type' => 'required|in:theft,damage,fire,water,other',
            'description' => 'required|string|max:2000',
            'items_damaged' => 'nullable|array',
            'estimated_damage' => 'required|numeric|min:0',
        ]);

        // Verify policy belongs to customer
        $policy = InsurancePolicy::find($validated['policy_id']);
        if ($policy->customer_id !== $customer->id) {
            abort(403);
        }

        // Check if policy is active
        if (!$policy->isActive()) {
            return back()->withErrors(['policy_id' => 'Cette police n\'est pas active.']);
        }

        // Create claim
        $claim = InsuranceClaim::create([
            'tenant_id' => $customer->tenant_id,
            'policy_id' => $policy->id,
            'claim_number' => InsuranceClaim::generateClaimNumber(),
            'incident_date' => $validated['incident_date'],
            'incident_type' => $validated['incident_type'],
            'description' => $validated['description'],
            'items_damaged' => $validated['items_damaged'],
            'estimated_damage' => $validated['estimated_damage'],
            'claimed_amount' => $validated['estimated_damage'],
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        return back()->with('success', 'Déclaration de sinistre soumise. Numéro : ' . $claim->claim_number . '. Nous vous contacterons sous 48h.');
    }

    /**
     * Get authenticated customer
     */
    protected function getAuthenticatedCustomer(): Customer
    {
        // In production, this would get the customer from the portal auth guard
        // For now, we'll use a demo customer or the first one
        $customerId = session('customer_portal_id');

        if ($customerId) {
            return Customer::findOrFail($customerId);
        }

        // Demo fallback
        return Customer::first() ?? abort(403, 'No customer authenticated');
    }

    // ============================================
    // ONLINE PAYMENT METHODS
    // ============================================

    /**
     * Show payment page for an invoice
     */
    public function payInvoice(Invoice $invoice): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($invoice->customer_id !== $customer->id) {
            abort(403);
        }

        // Check if invoice can be paid
        if (!in_array($invoice->status, ['sent', 'pending', 'overdue', 'partial'])) {
            return redirect()->route('customer.portal.invoices')
                ->with('error', 'Cette facture ne peut pas etre payee en ligne.');
        }

        $invoice->load(['contract.site', 'contract.box', 'items']);

        // Get saved payment methods
        $savedMethods = SavedPaymentMethod::where('customer_id', $customer->id)
            ->where('is_active', true)
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'type' => $m->type,
                'last_four' => $m->last_four,
                'brand' => $m->brand,
                'exp_month' => $m->exp_month,
                'exp_year' => $m->exp_year,
                'is_default' => $m->is_default,
                'label' => $m->type === 'card'
                    ? ucfirst($m->brand) . ' **** ' . $m->last_four
                    : 'SEPA **** ' . ($m->iban_last_four ?? $m->last_four),
            ]);

        // Get tenant's Stripe public key
        $tenant = $invoice->tenant;
        $stripePublicKey = config('services.stripe.public_key');

        // Calculate remaining amount
        $remainingAmount = $invoice->total - ($invoice->paid_amount ?? 0);

        return Inertia::render('Customer/Portal/PayInvoice', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'total' => $invoice->total,
                'paid_amount' => $invoice->paid_amount ?? 0,
                'remaining_amount' => $remainingAmount,
                'subtotal' => $invoice->subtotal,
                'tax_amount' => $invoice->tax_amount,
                'status' => $invoice->status,
                'issue_date' => $invoice->invoice_date?->format('d/m/Y'),
                'due_date' => $invoice->due_date?->format('d/m/Y'),
                'is_overdue' => $invoice->status === 'overdue' || ($invoice->due_date && $invoice->due_date->isPast()),
                'contract' => $invoice->contract ? [
                    'contract_number' => $invoice->contract->contract_number,
                    'box_code' => $invoice->contract->box?->number,
                    'site_name' => $invoice->contract->site?->name ?? $invoice->contract->box?->site?->name,
                ] : null,
                'items' => $invoice->items?->map(fn($item) => [
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'total' => $item->total,
                ]) ?? [],
            ],
            'savedMethods' => $savedMethods,
            'stripePublicKey' => $stripePublicKey,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->full_name,
                'email' => $customer->email,
            ],
        ]);
    }

    /**
     * Create a payment intent for an invoice
     */
    public function createPaymentIntent(Request $request, Invoice $invoice, StripePaymentService $stripeService)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($invoice->customer_id !== $customer->id) {
            abort(403);
        }

        // Check if invoice can be paid
        if (!in_array($invoice->status, ['sent', 'pending', 'overdue', 'partial'])) {
            return response()->json([
                'error' => 'Cette facture ne peut pas etre payee.',
            ], 400);
        }

        $validated = $request->validate([
            'amount' => 'nullable|numeric|min:0.50',
            'save_card' => 'boolean',
        ]);

        // Use remaining amount or specified amount
        $remainingAmount = $invoice->total - ($invoice->paid_amount ?? 0);
        $amount = $validated['amount'] ?? $remainingAmount;

        // Validate amount is not more than remaining
        if ($amount > $remainingAmount) {
            return response()->json([
                'error' => 'Le montant depasse le solde de la facture.',
            ], 400);
        }

        try {
            // Get or create Stripe customer ID
            $stripeCustomerId = $customer->stripe_customer_id;
            if (!$stripeCustomerId) {
                $stripeCustomerId = $stripeService->createCustomer(
                    $customer->email,
                    $customer->full_name,
                    ['customer_id' => $customer->id, 'tenant_id' => $customer->tenant_id]
                );

                if ($stripeCustomerId) {
                    $customer->update(['stripe_customer_id' => $stripeCustomerId]);
                }
            }

            // Create payment intent with custom amount if partial
            $result = $stripeService->createPaymentIntent($invoice, $stripeCustomerId);

            if (!$result) {
                return response()->json([
                    'error' => 'Erreur lors de la creation du paiement.',
                ], 500);
            }

            // If custom amount, update the intent
            if ($amount != $invoice->total) {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret_key'));
                \Stripe\PaymentIntent::update($result['intent_id'], [
                    'amount' => intval($amount * 100),
                    'metadata' => [
                        'invoice_id' => $invoice->id,
                        'tenant_id' => $invoice->tenant_id,
                        'customer_id' => $invoice->customer_id,
                        'partial_payment' => true,
                        'original_total' => $invoice->total,
                    ],
                ]);
            }

            Log::info('Payment intent created for customer portal', [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'clientSecret' => $result['client_secret'],
                'intentId' => $result['intent_id'],
                'amount' => $amount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create payment intent', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la creation du paiement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Confirm payment completion
     */
    public function confirmPayment(Request $request, Invoice $invoice, StripePaymentService $stripeService)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($invoice->customer_id !== $customer->id) {
            abort(403);
        }

        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'save_card' => 'boolean',
        ]);

        try {
            // Verify payment intent
            $result = $stripeService->verifyPaymentIntent($validated['payment_intent_id']);

            if (!$result || $result['status'] !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'error' => 'Le paiement n\'a pas pu etre confirme.',
                ], 400);
            }

            // Record payment
            $amount = $result['amount'] / 100; // Convert from cents
            $invoice->recordPayment($amount);

            // Create payment record
            Payment::create([
                'tenant_id' => $invoice->tenant_id,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_method' => 'card',
                'paid_at' => now(),
                'status' => 'completed',
                'reference' => $validated['payment_intent_id'],
                'stripe_payment_id' => $validated['payment_intent_id'],
            ]);

            // Update customer revenue if invoice is now fully paid
            if ($invoice->status === 'paid') {
                $customer->increment('total_revenue', $invoice->total);
            }

            Log::info('Payment confirmed via customer portal', [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'payment_intent' => $validated['payment_intent_id'],
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Paiement effectue avec succes !',
                'invoice_status' => $invoice->fresh()->status,
                'paid_amount' => $invoice->fresh()->paid_amount,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to confirm payment', [
                'invoice_id' => $invoice->id,
                'payment_intent_id' => $validated['payment_intent_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la confirmation du paiement.',
            ], 500);
        }
    }

    /**
     * Pay multiple invoices at once
     */
    public function payMultipleInvoices(Request $request, StripePaymentService $stripeService)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
        ]);

        // Verify all invoices belong to customer and can be paid
        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'pending', 'overdue', 'partial'])
            ->get();

        if ($invoices->count() !== count($validated['invoice_ids'])) {
            return response()->json([
                'error' => 'Certaines factures ne peuvent pas etre payees.',
            ], 400);
        }

        // Calculate total
        $total = $invoices->sum(fn($i) => $i->total - ($i->paid_amount ?? 0));

        if ($total <= 0) {
            return response()->json([
                'error' => 'Aucun montant a payer.',
            ], 400);
        }

        try {
            // Get or create Stripe customer ID
            $stripeCustomerId = $customer->stripe_customer_id;
            if (!$stripeCustomerId) {
                $stripeCustomerId = $stripeService->createCustomer(
                    $customer->email,
                    $customer->full_name,
                    ['customer_id' => $customer->id, 'tenant_id' => $customer->tenant_id]
                );

                if ($stripeCustomerId) {
                    $customer->update(['stripe_customer_id' => $stripeCustomerId]);
                }
            }

            // Create combined payment intent
            \Stripe\Stripe::setApiKey(config('services.stripe.secret_key'));

            $invoiceNumbers = $invoices->pluck('invoice_number')->join(', ');

            $intent = \Stripe\PaymentIntent::create([
                'amount' => intval($total * 100),
                'currency' => 'eur',
                'customer' => $stripeCustomerId,
                'description' => "Factures: {$invoiceNumbers}",
                'metadata' => [
                    'invoice_ids' => implode(',', $validated['invoice_ids']),
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'payment_type' => 'multiple_invoices',
                ],
            ]);

            return response()->json([
                'clientSecret' => $intent->client_secret,
                'intentId' => $intent->id,
                'amount' => $total,
                'invoiceCount' => $invoices->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create multi-invoice payment intent', [
                'invoice_ids' => $validated['invoice_ids'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la creation du paiement.',
            ], 500);
        }
    }

    /**
     * Add a new payment method
     */
    public function addPaymentMethod(Request $request, StripePaymentService $stripeService)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'payment_method_id' => 'required|string',
            'set_default' => 'boolean',
        ]);

        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret_key'));

            // Get or create Stripe customer
            $stripeCustomerId = $customer->stripe_customer_id;
            if (!$stripeCustomerId) {
                $stripeCustomerId = $stripeService->createCustomer(
                    $customer->email,
                    $customer->full_name,
                    ['customer_id' => $customer->id, 'tenant_id' => $customer->tenant_id]
                );
                $customer->update(['stripe_customer_id' => $stripeCustomerId]);
            }

            // Attach payment method to customer
            $paymentMethod = \Stripe\PaymentMethod::retrieve($validated['payment_method_id']);
            $paymentMethod->attach(['customer' => $stripeCustomerId]);

            // Save to database
            $savedMethod = SavedPaymentMethod::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'stripe_payment_method_id' => $paymentMethod->id,
                'type' => $paymentMethod->type,
                'brand' => $paymentMethod->card?->brand,
                'last_four' => $paymentMethod->card?->last4,
                'exp_month' => $paymentMethod->card?->exp_month,
                'exp_year' => $paymentMethod->card?->exp_year,
                'is_default' => $validated['set_default'] ?? false,
                'is_active' => true,
            ]);

            // If set as default, unset others
            if ($validated['set_default'] ?? false) {
                SavedPaymentMethod::where('customer_id', $customer->id)
                    ->where('id', '!=', $savedMethod->id)
                    ->update(['is_default' => false]);

                // Update Stripe default
                \Stripe\Customer::update($stripeCustomerId, [
                    'invoice_settings' => [
                        'default_payment_method' => $paymentMethod->id,
                    ],
                ]);
            }

            Log::info('Payment method added via customer portal', [
                'customer_id' => $customer->id,
                'payment_method_type' => $paymentMethod->type,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Methode de paiement ajoutee.',
                'method' => [
                    'id' => $savedMethod->id,
                    'type' => $savedMethod->type,
                    'last_four' => $savedMethod->last_four,
                    'brand' => $savedMethod->brand,
                    'is_default' => $savedMethod->is_default,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add payment method', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de l\'ajout de la methode de paiement.',
            ], 500);
        }
    }

    /**
     * Delete a saved payment method
     */
    public function deletePaymentMethod(SavedPaymentMethod $method)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($method->customer_id !== $customer->id) {
            abort(403);
        }

        try {
            // Detach from Stripe
            if ($method->stripe_payment_method_id) {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret_key'));
                $paymentMethod = \Stripe\PaymentMethod::retrieve($method->stripe_payment_method_id);
                $paymentMethod->detach();
            }

            $method->delete();

            return response()->json([
                'success' => true,
                'message' => 'Methode de paiement supprimee.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete payment method', [
                'method_id' => $method->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la suppression.',
            ], 500);
        }
    }

    /**
     * Set a payment method as default
     */
    public function setDefaultPaymentMethod(SavedPaymentMethod $method)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($method->customer_id !== $customer->id) {
            abort(403);
        }

        try {
            // Unset other defaults
            SavedPaymentMethod::where('customer_id', $customer->id)
                ->update(['is_default' => false]);

            $method->update(['is_default' => true]);

            // Update Stripe
            if ($customer->stripe_customer_id && $method->stripe_payment_method_id) {
                \Stripe\Stripe::setApiKey(config('services.stripe.secret_key'));
                \Stripe\Customer::update($customer->stripe_customer_id, [
                    'invoice_settings' => [
                        'default_payment_method' => $method->stripe_payment_method_id,
                    ],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Methode de paiement par defaut mise a jour.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to set default payment method', [
                'method_id' => $method->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Erreur lors de la mise a jour.',
            ], 500);
        }
    }

    // ============================================
    // Box Upgrade/Downgrade Methods
    // ============================================

    /**
     * Display the box change page (upgrade/downgrade)
     */
    public function changeBoxPage(Contract $contract): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        if ($contract->status !== 'active') {
            return redirect()->route('customer.portal.contracts')
                ->with('error', 'Ce contrat n\'est pas actif.');
        }

        $contract->load(['box.site', 'site']);

        // Get available boxes from the same site
        $siteId = $contract->site_id ?? $contract->box?->site_id;

        $availableBoxes = \App\Models\Box::where('site_id', $siteId)
            ->where('status', 'available')
            ->where('id', '!=', $contract->box_id)
            ->orderByRaw('(length * width)')
            ->get()
            ->map(fn($box) => [
                'id' => $box->id,
                'code' => $box->number,
                'name' => $box->name,
                'size_m2' => $box->size_m2,
                'size_category' => $box->size_category,
                'floor' => $box->floor,
                'current_price' => $box->current_price,
                'features' => $box->features ?? [],
                'is_upgrade' => $box->current_price > $contract->monthly_price,
                'is_downgrade' => $box->current_price < $contract->monthly_price,
                'price_difference' => $box->current_price - $contract->monthly_price,
            ]);

        // Group by size category
        $boxesByCategory = $availableBoxes->groupBy('size_category');

        return Inertia::render('Customer/Portal/ChangeBox', [
            'contract' => [
                'id' => $contract->id,
                'contract_number' => $contract->contract_number,
                'current_box' => $contract->box ? [
                    'id' => $contract->box->id,
                    'code' => $contract->box->number,
                    'name' => $contract->box->name,
                    'size_m2' => $contract->box->size_m2,
                    'size_category' => $contract->box->size_category,
                    'floor' => $contract->box->floor,
                ] : null,
                'site_name' => $contract->site?->name ?? $contract->box?->site?->name,
                'monthly_price' => $contract->monthly_price,
            ],
            'availableBoxes' => $availableBoxes,
            'boxesByCategory' => $boxesByCategory,
        ]);
    }

    /**
     * Preview box change (calculate prorated amounts)
     */
    public function previewBoxChange(Request $request, Contract $contract)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($contract->customer_id !== $customer->id) {
            return response()->json(['error' => 'Non autorise'], 403);
        }

        $validated = $request->validate([
            'new_box_id' => 'required|exists:boxes,id',
            'effective_date' => 'nullable|date|after_or_equal:today',
        ]);

        $newBox = \App\Models\Box::findOrFail($validated['new_box_id']);

        // Verify box is available and in the same site
        $siteId = $contract->site_id ?? $contract->box?->site_id;
        if ($newBox->site_id !== $siteId || $newBox->status !== 'available') {
            return response()->json(['error' => 'Ce box n\'est pas disponible'], 400);
        }

        $effectiveDate = isset($validated['effective_date'])
            ? \Carbon\Carbon::parse($validated['effective_date'])
            : now();

        // Calculate prorated amounts
        $currentPrice = $contract->monthly_price;
        $newPrice = $newBox->current_price;
        $priceDifference = $newPrice - $currentPrice;

        // Calculate days remaining in current billing period
        $billingStartOfMonth = now()->startOfMonth();
        $billingEndOfMonth = now()->endOfMonth();
        $daysInMonth = $billingEndOfMonth->diffInDays($billingStartOfMonth) + 1;
        $daysRemaining = $billingEndOfMonth->diffInDays($effectiveDate) + 1;

        // Prorated credit for old box (unused days)
        $proratedCredit = round(($currentPrice / $daysInMonth) * $daysRemaining, 2);

        // Prorated charge for new box (remaining days)
        $proratedCharge = round(($newPrice / $daysInMonth) * $daysRemaining, 2);

        // Net amount due/credit
        $netAmount = $proratedCharge - $proratedCredit;

        $isUpgrade = $newPrice > $currentPrice;
        $isDowngrade = $newPrice < $currentPrice;

        return response()->json([
            'current_box' => [
                'code' => $contract->box?->number,
                'size_m2' => $contract->box?->size_m2,
                'monthly_price' => $currentPrice,
            ],
            'new_box' => [
                'id' => $newBox->id,
                'code' => $newBox->number,
                'name' => $newBox->name,
                'size_m2' => $newBox->size_m2,
                'monthly_price' => $newPrice,
            ],
            'effective_date' => $effectiveDate->format('Y-m-d'),
            'calculation' => [
                'days_remaining' => $daysRemaining,
                'days_in_month' => $daysInMonth,
                'prorated_credit' => $proratedCredit,
                'prorated_charge' => $proratedCharge,
                'net_amount' => $netAmount,
                'is_upgrade' => $isUpgrade,
                'is_downgrade' => $isDowngrade,
                'monthly_difference' => $priceDifference,
            ],
            'summary' => [
                'type' => $isUpgrade ? 'upgrade' : ($isDowngrade ? 'downgrade' : 'lateral'),
                'amount_due' => $netAmount > 0 ? $netAmount : 0,
                'credit_amount' => $netAmount < 0 ? abs($netAmount) : 0,
                'new_monthly_price' => $newPrice,
            ],
        ]);
    }

    /**
     * Process box change request
     */
    public function processBoxChange(Request $request, Contract $contract)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($contract->customer_id !== $customer->id) {
            return response()->json(['error' => 'Non autorise'], 403);
        }

        if ($contract->status !== 'active') {
            return response()->json(['error' => 'Contrat non actif'], 400);
        }

        $validated = $request->validate([
            'new_box_id' => 'required|exists:boxes,id',
            'effective_date' => 'nullable|date|after_or_equal:today',
            'reason' => 'nullable|string|max:500',
            'payment_method_id' => 'nullable|exists:saved_payment_methods,id',
        ]);

        $newBox = \App\Models\Box::findOrFail($validated['new_box_id']);

        // Verify box is available and in the same site
        $siteId = $contract->site_id ?? $contract->box?->site_id;
        if ($newBox->site_id !== $siteId || $newBox->status !== 'available') {
            return response()->json(['error' => 'Ce box n\'est pas disponible'], 400);
        }

        $effectiveDate = isset($validated['effective_date'])
            ? \Carbon\Carbon::parse($validated['effective_date'])
            : now();

        try {
            \DB::beginTransaction();

            $oldBox = $contract->box;
            $currentPrice = $contract->monthly_price;
            $newPrice = $newBox->current_price;
            $isUpgrade = $newPrice > $currentPrice;

            // Calculate prorated amounts
            $billingEndOfMonth = now()->endOfMonth();
            $daysInMonth = now()->daysInMonth;
            $daysRemaining = $billingEndOfMonth->diffInDays($effectiveDate) + 1;
            $proratedCredit = round(($currentPrice / $daysInMonth) * $daysRemaining, 2);
            $proratedCharge = round(($newPrice / $daysInMonth) * $daysRemaining, 2);
            $netAmount = $proratedCharge - $proratedCredit;

            // Create box change request record
            $changeRequest = \App\Models\CustomerRequest::create([
                'customer_id' => $customer->id,
                'tenant_id' => $customer->tenant_id,
                'contract_id' => $contract->id,
                'type' => $isUpgrade ? 'box_upgrade' : 'box_downgrade',
                'status' => 'pending',
                'details' => [
                    'old_box_id' => $oldBox?->id,
                    'old_box_code' => $oldBox?->code,
                    'old_price' => $currentPrice,
                    'new_box_id' => $newBox->id,
                    'new_box_code' => $newBox->code,
                    'new_price' => $newPrice,
                    'effective_date' => $effectiveDate->format('Y-m-d'),
                    'prorated_credit' => $proratedCredit,
                    'prorated_charge' => $proratedCharge,
                    'net_amount' => $netAmount,
                    'reason' => $validated['reason'] ?? null,
                ],
            ]);

            // If immediate effect and upgrade, process payment if needed
            if ($effectiveDate->isToday() && $netAmount > 0) {
                // Process prorated payment for upgrade
                if (isset($validated['payment_method_id'])) {
                    $paymentMethod = SavedPaymentMethod::where('id', $validated['payment_method_id'])
                        ->where('customer_id', $customer->id)
                        ->first();

                    if ($paymentMethod) {
                        try {
                            $stripeService = app(StripePaymentService::class);

                            // Charge the prorated amount
                            $paymentIntent = $stripeService->createPaymentIntentWithSavedMethod(
                                $customer,
                                $netAmount,
                                'Prorated charge for box change - ' . $oldBox?->code . ' to ' . $newBox->code,
                                $paymentMethod->stripe_payment_method_id
                            );

                            // Create payment record
                            Payment::create([
                                'tenant_id' => $customer->tenant_id,
                                'customer_id' => $customer->id,
                                'contract_id' => $contract->id,
                                'amount' => $netAmount,
                                'payment_method' => 'card',
                                'paid_at' => now(),
                                'status' => 'completed',
                                'type' => 'prorated_upgrade',
                                'reference' => $paymentIntent->id ?? null,
                                'notes' => 'Box change from ' . $oldBox?->code . ' to ' . $newBox->code,
                            ]);

                            // Approve request immediately
                            $changeRequest->update(['status' => 'approved']);

                            // Execute the box change
                            $this->executeBoxChange($contract, $oldBox, $newBox, $newPrice);

                        } catch (\Exception $e) {
                            Log::error('Box change payment failed', [
                                'error' => $e->getMessage(),
                                'customer_id' => $customer->id,
                            ]);
                            // Request remains pending, admin will need to handle
                        }
                    }
                }
            }

            \DB::commit();

            // Notify admin
            // TODO: Send notification to admin

            $message = $changeRequest->status === 'approved'
                ? 'Votre changement de box a ete effectue avec succes.'
                : 'Votre demande de changement de box a ete soumise et sera traitee sous 24-48h.';

            return response()->json([
                'success' => true,
                'message' => $message,
                'request_id' => $changeRequest->id,
                'status' => $changeRequest->status,
            ]);

        } catch (\Exception $e) {
            \DB::rollBack();
            Log::error('Box change request failed', [
                'error' => $e->getMessage(),
                'customer_id' => $customer->id,
                'contract_id' => $contract->id,
            ]);

            return response()->json([
                'error' => 'Une erreur est survenue lors du traitement de votre demande.',
            ], 500);
        }
    }

    /**
     * Execute the actual box change (internal method)
     */
    protected function executeBoxChange(Contract $contract, $oldBox, $newBox, float $newPrice): void
    {
        // Update contract
        $contract->update([
            'box_id' => $newBox->id,
            'monthly_price' => $newPrice,
        ]);

        // Free the old box
        if ($oldBox) {
            $oldBox->update(['status' => 'available']);
        }

        // Occupy the new box
        $newBox->update(['status' => 'occupied']);

        // Create audit log
        \App\Models\AuditLog::create([
            'tenant_id' => $contract->tenant_id,
            'user_id' => null, // Customer action
            'action' => 'box_change',
            'model_type' => Contract::class,
            'model_id' => $contract->id,
            'changes' => [
                'old_box_id' => $oldBox?->id,
                'new_box_id' => $newBox->id,
                'old_price' => $contract->getOriginal('monthly_price'),
                'new_price' => $newPrice,
            ],
            'ip_address' => request()->ip(),
        ]);
    }

    /**
     * List customer's box change requests
     */
    public function boxChangeRequests(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $requests = \App\Models\CustomerRequest::where('customer_id', $customer->id)
            ->whereIn('type', ['box_upgrade', 'box_downgrade'])
            ->with(['contract.box'])
            ->latest()
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'type' => $r->type,
                'status' => $r->status,
                'contract_number' => $r->contract?->contract_number,
                'old_box' => $r->details['old_box_code'] ?? null,
                'new_box' => $r->details['new_box_code'] ?? null,
                'effective_date' => $r->details['effective_date'] ?? null,
                'net_amount' => $r->details['net_amount'] ?? 0,
                'created_at' => $r->created_at->format('d/m/Y H:i'),
                'processed_at' => $r->processed_at?->format('d/m/Y H:i'),
            ]);

        return Inertia::render('Customer/Portal/BoxChangeRequests', [
            'requests' => $requests,
        ]);
    }

    /**
     * Cancel a pending box change request
     */
    public function cancelBoxChangeRequest(\App\Models\CustomerRequest $customerRequest)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($customerRequest->customer_id !== $customer->id) {
            return response()->json(['error' => 'Non autorise'], 403);
        }

        if ($customerRequest->status !== 'pending') {
            return response()->json(['error' => 'Cette demande ne peut plus etre annulee'], 400);
        }

        $customerRequest->update([
            'status' => 'cancelled',
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Votre demande a ete annulee.',
        ]);
    }
}
