<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class MobileController extends Controller
{
    /**
     * Get the authenticated mobile customer
     */
    protected function getCustomer(): Customer
    {
        $customerId = session('mobile_customer_id');
        return Customer::findOrFail($customerId);
    }

    /**
     * Display login page
     */
    public function loginPage(): Response|\Illuminate\Http\RedirectResponse
    {
        // Redirect if already logged in
        if (session('mobile_customer_id')) {
            return redirect()->route('mobile.dashboard');
        }

        return Inertia::render('Mobile/Auth/Login');
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

        if (!$customer) {
            return back()->withErrors([
                'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.'
            ]);
        }

        // Check password - try customer's linked user first, then customer's own password
        $passwordValid = false;

        // If customer has a linked user account, check user's password
        if ($customer->user_id && $customer->user) {
            $passwordValid = Hash::check($validated['password'], $customer->user->password ?? '');
        }

        // If no user or user password didn't match, try customer's own password field
        if (!$passwordValid && $customer->password) {
            $passwordValid = Hash::check($validated['password'], $customer->password);
        }

        if (!$passwordValid) {
            return back()->withErrors([
                'email' => 'Ces identifiants ne correspondent pas à nos enregistrements.'
            ]);
        }

        // Store customer ID in session
        session(['mobile_customer_id' => $customer->id]);

        return redirect()->route('mobile.dashboard')
            ->with('success', 'Bienvenue, ' . $customer->first_name . ' !');
    }

    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        session()->forget('mobile_customer_id');

        return redirect()->route('mobile.login')
            ->with('success', 'Vous avez été déconnecté.');
    }

    /**
     * Mobile Dashboard
     */
    public function dashboard(Request $request): Response
    {
        $customer = $this->getCustomer();

        $activeContracts = $customer->contracts()
            ->where('status', 'active')
            ->with(['box.site'])
            ->get();

        $recentInvoices = $customer->invoices()
            ->with('contract.box')
            ->orderBy('invoice_date', 'desc')
            ->limit(5)
            ->get();

        $recentPayments = $customer->payments()
            ->with('invoice')
            ->where('status', 'completed')
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get();

        $overdueInvoices = $customer->invoices()
            ->where('status', 'overdue')
            ->get();

        // Calculate outstanding balance (total - paid_amount for unpaid invoices)
        $unpaidInvoices = $customer->invoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->get();
        $outstandingBalance = $unpaidInvoices->sum(function ($invoice) {
            return $invoice->total - ($invoice->paid_amount ?? 0);
        });

        $stats = [
            'active_contracts' => $activeContracts->count(),
            'total_invoices' => $customer->invoices()->count(),
            'pending_invoices' => $unpaidInvoices->count(),
            'total_paid' => $customer->payments()
                ->where('status', 'completed')
                ->sum('amount'),
            'outstanding_balance' => $outstandingBalance,
        ];

        return Inertia::render('Mobile/Dashboard', [
            'customer' => $customer,
            'activeContracts' => $activeContracts,
            'recentInvoices' => $recentInvoices,
            'recentPayments' => $recentPayments,
            'overdueInvoices' => $overdueInvoices,
            'stats' => $stats,
        ]);
    }

    /**
     * Mobile Boxes List
     */
    public function boxes(Request $request): Response
    {
        $customer = $this->getCustomer();

        $contracts = $customer->contracts()
            ->with(['box.site'])
            ->get()
            ->map(function ($contract) {
                $contract->days_until_expiry = $contract->end_date
                    ? Carbon::now()->diffInDays($contract->end_date, false)
                    : null;
                return $contract;
            });

        return Inertia::render('Mobile/Boxes/Index', [
            'contracts' => $contracts,
        ]);
    }

    /**
     * Mobile Invoices List
     */
    public function invoices(Request $request): Response
    {
        $customer = $this->getCustomer();

        $query = $customer->invoices()->with('contract.box');

        if ($request->has('contract_id')) {
            $query->where('contract_id', $request->contract_id);
        }

        $invoices = $query->orderBy('invoice_date', 'desc')->get();

        $stats = [
            'total' => $invoices->count(),
            'pending' => $invoices->whereIn('status', ['sent'])->count(),
            'overdue' => $invoices->where('status', 'overdue')->count(),
        ];

        return Inertia::render('Mobile/Invoices/Index', [
            'invoices' => $invoices,
            'stats' => $stats,
        ]);
    }

    /**
     * Mobile Invoice Detail
     */
    public function invoiceShow(Invoice $invoice): Response
    {
        $this->authorizeCustomerAccess($invoice->customer_id);

        $invoice->load(['contract.box.site', 'payments']); // 'items' is a JSON column, not a relationship

        return Inertia::render('Mobile/Invoices/Show', [
            'invoice' => $invoice,
        ]);
    }

    /**
     * Mobile Payments List
     */
    public function payments(Request $request): Response
    {
        $customer = $this->getCustomer();

        $payments = $customer->payments()
            ->with('invoice')
            ->where('status', 'completed')
            ->orderBy('paid_at', 'desc')
            ->get();

        $now = Carbon::now();
        $thisMonthStart = $now->copy()->startOfMonth();
        $thisYearStart = $now->copy()->startOfYear();

        $stats = [
            'total_paid' => $payments->sum('amount'),
            'this_month' => $payments->filter(fn($p) => Carbon::parse($p->paid_at)->gte($thisMonthStart))->sum('amount'),
            'this_year' => $payments->filter(fn($p) => Carbon::parse($p->paid_at)->gte($thisYearStart))->sum('amount'),
            'count' => $payments->count(),
        ];

        return Inertia::render('Mobile/Payments/Index', [
            'payments' => $payments,
            'stats' => $stats,
        ]);
    }

    /**
     * Mobile Payment Detail
     */
    public function paymentShow(Payment $payment): Response
    {
        $this->authorizeCustomerAccess($payment->customer_id);

        $payment->load(['invoice', 'contract.box']);

        return Inertia::render('Mobile/Payments/Show', [
            'payment' => $payment,
        ]);
    }

    /**
     * Mobile Contracts List
     */
    public function contracts(Request $request): Response
    {
        $customer = $this->getCustomer();

        $contracts = $customer->contracts()
            ->with(['box.site', 'invoices'])
            ->get()
            ->map(function ($contract) {
                $contract->days_until_expiry = $contract->end_date && $contract->status === 'active'
                    ? max(0, Carbon::now()->diffInDays($contract->end_date, false))
                    : null;
                return $contract;
            });

        $stats = [
            'active' => $contracts->where('status', 'active')->count(),
            'expiring_soon' => $contracts->filter(fn($c) => $c->days_until_expiry !== null && $c->days_until_expiry <= 30)->count(),
        ];

        return Inertia::render('Mobile/Contracts/Index', [
            'contracts' => $contracts,
            'stats' => $stats,
        ]);
    }

    /**
     * Mobile Contract Detail
     */
    public function contractShow(Contract $contract): Response
    {
        $this->authorizeCustomerAccess($contract->customer_id);

        $contract->load(['box.site', 'invoices' => function ($query) {
            $query->latest('invoice_date')->limit(5);
        }]);

        $contract->days_until_expiry = $contract->end_date && $contract->status === 'active'
            ? max(0, Carbon::now()->diffInDays($contract->end_date, false))
            : null;

        return Inertia::render('Mobile/Contracts/Show', [
            'contract' => $contract,
        ]);
    }

    /**
     * Mobile Reserve - Step 1: Site selection
     */
    public function reserve(Request $request): Response
    {
        $sites = Site::where('is_active', true)
            ->withCount(['boxes as available_boxes' => function ($query) {
                $query->where('status', 'available');
            }])
            ->get()
            ->map(function ($site) {
                $site->min_price = $site->boxes()->where('status', 'available')->min('base_price') ?? 0;
                return $site;
            });

        $boxes = Box::where('status', 'available')
            ->with('site')
            ->get()
            ->map(function ($box) {
                return [
                    'id' => $box->id,
                    'site_id' => $box->site_id,
                    'name' => $box->name,
                    'code' => $box->number,
                    'area' => $box->area,
                    'volume' => $box->volume,
                    'dimensions' => $box->formatted_dimensions ?? "{$box->width}x{$box->length}x{$box->height}m",
                    'price' => $box->current_price ?? $box->base_price,
                    'deposit' => $box->deposit ?? 0,
                    'status' => $box->status,
                    'promo' => null, // Can add promotional pricing later
                ];
            });

        return Inertia::render('Mobile/Reserve/Index', [
            'sites' => $sites,
            'boxes' => $boxes,
        ]);
    }

    /**
     * Store Reservation
     */
    public function storeReservation(Request $request)
    {
        $validated = $request->validate([
            'site_id' => 'required|exists:sites,id',
            'box_id' => 'required|exists:boxes,id',
            'start_date' => 'required|date|after_or_equal:today',
            'duration_months' => 'required|integer|min:1|max:24',
            'accept_terms' => 'required|accepted',
        ]);

        $customer = $this->getCustomer();
        $box = Box::findOrFail($validated['box_id']);

        // Check if box is still available
        if ($box->status !== 'available') {
            return back()->withErrors(['box_id' => 'Ce box n\'est plus disponible.']);
        }

        // Calculate end date
        $startDate = Carbon::parse($validated['start_date']);
        $endDate = $startDate->copy()->addMonths($validated['duration_months']);

        // Create contract
        $contract = Contract::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'box_id' => $box->id,
            'site_id' => $box->site_id,
            'contract_number' => 'CTR-' . strtoupper(uniqid()),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'monthly_price' => $box->current_price ?? $box->base_price,
            'status' => 'pending_signature',
        ]);

        // Update box status
        $box->update(['status' => 'reserved']);

        // Create notification for tenant admins
        Notification::create([
            'tenant_id' => $customer->tenant_id,
            'type' => Notification::TYPE_BOOKING,
            'title' => 'Nouvelle réservation mobile',
            'message' => "Le client {$customer->first_name} {$customer->last_name} a réservé le box {$box->name} depuis l'application mobile. Début: {$startDate->format('d/m/Y')}, Durée: {$validated['duration_months']} mois.",
            'priority' => Notification::PRIORITY_HIGH,
            'channels' => ['in_app'],
            'status' => Notification::STATUS_SENT,
            'sent_at' => now(),
            'related_type' => Contract::class,
            'related_id' => $contract->id,
            'data' => [
                'customer_id' => $customer->id,
                'customer_name' => "{$customer->first_name} {$customer->last_name}",
                'customer_email' => $customer->email,
                'box_id' => $box->id,
                'box_name' => $box->name,
                'site_id' => $box->site_id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'duration_months' => $validated['duration_months'],
                'monthly_price' => $box->current_price ?? $box->base_price,
                'source' => 'mobile_app',
            ],
        ]);

        return redirect()->route('mobile.contracts.show', $contract->id)
            ->with('success', 'Reservation effectuee avec succes! Veuillez proceder au paiement.');
    }

    /**
     * Pay Page
     */
    public function pay(Request $request): Response
    {
        $customer = $this->getCustomer();

        $unpaidInvoices = $customer->invoices()
            ->whereIn('status', ['sent', 'overdue'])
            ->orderBy('due_date')
            ->get();

        // Get saved payment methods (placeholder)
        $savedCards = []; // Would come from payment gateway

        return Inertia::render('Mobile/Pay/Index', [
            'unpaidInvoices' => $unpaidInvoices,
            'savedCards' => $savedCards,
            'preselectedInvoiceId' => $request->invoice_id,
        ]);
    }

    /**
     * Process Payment
     */
    public function processPayment(Request $request)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
            'method' => 'required|in:card,sepa',
            'amount' => 'required|numeric|min:0.01',
        ]);

        $customer = $this->getCustomer();

        // Verify invoices belong to customer
        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'overdue'])
            ->get();

        if ($invoices->count() !== count($validated['invoice_ids'])) {
            return back()->withErrors(['invoice_ids' => 'Factures invalides.']);
        }

        // Process payment (placeholder - integrate with payment gateway)
        foreach ($invoices as $invoice) {
            $payment = Payment::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'contract_id' => $invoice->contract_id,
                'payment_number' => 'PAY-' . strtoupper(uniqid()),
                'amount' => $invoice->balance,
                'method' => $validated['method'],
                'type' => 'payment',
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Update invoice
            $invoice->update([
                'status' => 'paid',
                'balance' => 0,
                'paid_at' => now(),
            ]);
        }

        return redirect()->route('mobile.payments')
            ->with('success', 'Paiement effectue avec succes!');
    }

    /**
     * Access Page
     */
    public function access(Request $request): Response
    {
        $customer = $this->getCustomer();

        $contractId = $request->contract_id;

        if ($contractId) {
            $contract = Contract::where('id', $contractId)
                ->where('customer_id', $customer->id)
                ->with('box.site')
                ->firstOrFail();
        } else {
            $contract = $customer->contracts()
                ->where('status', 'active')
                ->with('box.site')
                ->first();
        }

        // Generate access code (placeholder)
        $accessCode = $contract ? strtoupper(substr(md5($contract->id . $contract->customer_id), 0, 6)) : null;

        // Access history (placeholder)
        $accessHistory = [];

        return Inertia::render('Mobile/Access/Index', [
            'contract' => $contract,
            'accessCode' => $accessCode,
            'accessHistory' => $accessHistory,
        ]);
    }

    /**
     * More Menu Page
     */
    public function more(Request $request): Response
    {
        $customer = $this->getCustomer();

        $stats = [
            'boxes' => $customer->contracts()->where('status', 'active')->count(),
            'contracts' => $customer->contracts()->count(),
            'total_paid' => $customer->payments()->where('status', 'completed')->sum('amount'),
        ];

        return Inertia::render('Mobile/More/Index', [
            'stats' => $stats,
        ]);
    }

    /**
     * Contract PDF Download
     */
    public function contractPdf(Contract $contract)
    {
        $this->authorizeCustomerAccess($contract->customer_id);

        // Generate and return PDF (placeholder)
        return redirect()->route('mobile.contracts.show', $contract->id)
            ->with('info', 'PDF en cours de generation...');
    }

    /**
     * Invoice PDF Download
     */
    public function invoicePdf(Invoice $invoice)
    {
        $this->authorizeCustomerAccess($invoice->customer_id);

        // Generate and return PDF (placeholder)
        return redirect()->route('mobile.invoices.show', $invoice->id)
            ->with('info', 'PDF en cours de generation...');
    }

    /**
     * Payment Receipt Download
     */
    public function paymentReceipt(Payment $payment)
    {
        $this->authorizeCustomerAccess($payment->customer_id);

        // Generate and return receipt (placeholder)
        return redirect()->route('mobile.payments.show', $payment->id)
            ->with('info', 'Recu en cours de generation...');
    }

    /**
     * Terminate Contract
     */
    public function terminateContract(Request $request, Contract $contract)
    {
        $this->authorizeCustomerAccess($contract->customer_id);

        if ($contract->status !== 'active') {
            return back()->withErrors(['error' => 'Ce contrat ne peut pas etre resilie.']);
        }

        // Calculate termination date (usually end of current month + notice period)
        $terminationDate = Carbon::now()->addMonth()->endOfMonth();

        $contract->update([
            'status' => 'terminated',
            'actual_end_date' => $terminationDate,
            'termination_reason' => 'Resiliation par le client',
        ]);

        return redirect()->route('mobile.contracts')
            ->with('success', 'Demande de resiliation enregistree. Le contrat prendra fin le ' . $terminationDate->format('d/m/Y'));
    }

    /**
     * Renew Contract
     */
    public function renewContract(Request $request, Contract $contract)
    {
        $this->authorizeCustomerAccess($contract->customer_id);

        $validated = $request->validate([
            'duration_months' => 'required|integer|min:1|max:24',
        ]);

        if ($contract->status !== 'active') {
            return back()->withErrors(['error' => 'Ce contrat ne peut pas etre renouvele.']);
        }

        $newEndDate = Carbon::parse($contract->end_date)->addMonths($validated['duration_months']);

        $contract->update([
            'end_date' => $newEndDate,
        ]);

        return redirect()->route('mobile.contracts.show', $contract->id)
            ->with('success', 'Contrat renouvele jusqu\'au ' . $newEndDate->format('d/m/Y'));
    }

    /**
     * Profile Page
     */
    public function profile(Request $request): Response
    {
        $customer = $this->getCustomer();

        return Inertia::render('Mobile/Profile/Index', [
            'customer' => $customer,
        ]);
    }

    /**
     * Update Profile
     */
    public function updateProfile(Request $request)
    {
        $customer = $this->getCustomer();

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'company_name' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Profil mis a jour avec succes.');
    }

    /**
     * Update Address
     */
    public function updateAddress(Request $request)
    {
        $customer = $this->getCustomer();

        $validated = $request->validate([
            'address' => 'required|string|max:500',
            'postal_code' => 'required|string|max:20',
            'city' => 'required|string|max:255',
            'country' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Adresse mise a jour avec succes.');
    }

    /**
     * Change Password
     */
    public function changePassword(Request $request)
    {
        $customer = $this->getCustomer();

        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        // Check current password
        if (!Hash::check($validated['current_password'], $customer->password ?? '')) {
            return back()->withErrors(['current_password' => 'Le mot de passe actuel est incorrect.']);
        }

        $customer->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', 'Mot de passe modifié avec succès.');
    }

    /**
     * Delete Account
     */
    public function deleteAccount(Request $request)
    {
        $customer = $this->getCustomer();

        // Check for active contracts
        if ($customer->contracts()->where('status', 'active')->exists()) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas supprimer votre compte avec des contrats actifs.']);
        }

        // Soft delete customer
        $customer->delete();

        // Clear session
        session()->forget('mobile_customer_id');

        return redirect()->route('mobile.login')
            ->with('success', 'Votre compte a été supprimé.');
    }

    /**
     * Support Page
     */
    public function support(Request $request): Response
    {
        $customer = $this->getCustomer();

        $contracts = $customer->contracts()
            ->with('box')
            ->where('status', 'active')
            ->get();

        // Placeholder for support tickets
        $tickets = [];

        return Inertia::render('Mobile/Support/Index', [
            'contracts' => $contracts,
            'tickets' => $tickets,
        ]);
    }

    /**
     * Send Support Message
     */
    public function sendSupport(Request $request)
    {
        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'contract_id' => 'nullable|exists:contracts,id',
            'attachment' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png',
        ]);

        // Store support ticket (placeholder)
        // In production, integrate with support system (Zendesk, Freshdesk, etc.)

        return back()->with('success', 'Message envoye avec succes. Nous vous repondrons rapidement.');
    }

    /**
     * FAQ Page
     */
    public function faq(): Response
    {
        return Inertia::render('Mobile/Help/Faq');
    }

    /**
     * Settings Page
     */
    public function settings(Request $request): Response
    {
        $customer = $this->getCustomer();

        // Get user settings (placeholder - could be stored in a settings table)
        $userSettings = [
            'push_notifications' => true,
            'email_notifications' => true,
            'payment_reminders' => true,
            'security_alerts' => true,
            'marketing_emails' => false,
            'dark_mode' => false,
            'language' => 'fr',
            'date_format' => 'dd/mm/yyyy',
            'access_history' => true,
            'analytics_cookies' => true,
        ];

        return Inertia::render('Mobile/Settings/Index', [
            'userSettings' => $userSettings,
        ]);
    }

    /**
     * Update Settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'push_notifications' => 'boolean',
            'email_notifications' => 'boolean',
            'payment_reminders' => 'boolean',
            'security_alerts' => 'boolean',
            'marketing_emails' => 'boolean',
            'dark_mode' => 'boolean',
            'language' => 'in:fr,en,es,de',
            'date_format' => 'in:dd/mm/yyyy,mm/dd/yyyy,yyyy-mm-dd',
            'access_history' => 'boolean',
            'analytics_cookies' => 'boolean',
        ]);

        // Store settings (placeholder)

        return back()->with('success', 'Parametres mis a jour.');
    }

    /**
     * Export User Data
     */
    public function exportData(Request $request)
    {
        $customer = $this->getCustomer();

        // Generate data export (GDPR compliance)
        // In production, generate a proper export file

        return response()->json([
            'message' => 'Export en cours de preparation. Vous recevrez un email.',
        ]);
    }

    /**
     * Documents Page
     */
    public function documents(Request $request): Response
    {
        $customer = $this->getCustomer();

        // Get customer documents (placeholder)
        $documents = [];

        // Add contract documents
        $contracts = $customer->contracts()->with('box')->get();
        foreach ($contracts as $contract) {
            $documents[] = [
                'id' => 'contract-' . $contract->id,
                'name' => 'Contrat ' . $contract->contract_number,
                'description' => 'Box ' . $contract->box->name,
                'type' => 'contract',
                'mime_type' => 'application/pdf',
                'size' => 125000,
                'created_at' => $contract->created_at,
                'url' => route('mobile.contracts.pdf', $contract->id),
            ];
        }

        // Add invoice documents
        $invoices = $customer->invoices()->latest('invoice_date')->limit(20)->get();
        foreach ($invoices as $invoice) {
            $documents[] = [
                'id' => 'invoice-' . $invoice->id,
                'name' => 'Facture ' . $invoice->invoice_number,
                'description' => $invoice->invoice_date->format('F Y'),
                'type' => 'invoice',
                'mime_type' => 'application/pdf',
                'size' => 85000,
                'created_at' => $invoice->created_at,
                'url' => route('mobile.invoices.pdf', $invoice->id),
            ];
        }

        return Inertia::render('Mobile/Documents/Index', [
            'documents' => $documents,
        ]);
    }

    /**
     * Upload Document
     */
    public function uploadDocument(Request $request)
    {
        $validated = $request->validate([
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png',
            'type' => 'required|in:identity,proof_address,insurance,other',
        ]);

        $customer = $this->getCustomer();

        // Store document (placeholder)
        $path = $request->file('file')->store('customer-documents/' . $customer->id, 'private');

        return back()->with('success', 'Document envoye avec succes.');
    }

    /**
     * Download Document
     */
    public function downloadDocument(Request $request, $documentId)
    {
        // Placeholder - implement document download
        return back()->with('info', 'Telechargement en cours...');
    }

    /**
     * Payment Methods Page
     */
    public function paymentMethods(Request $request): Response
    {
        $customer = $this->getCustomer();

        // Placeholder for payment methods (integrate with Stripe)
        $cards = [];
        $sepaMandate = null;
        $autoPay = false;

        return Inertia::render('Mobile/PaymentMethods/Index', [
            'cards' => $cards,
            'sepaMandate' => $sepaMandate,
            'autoPay' => $autoPay,
        ]);
    }

    /**
     * Add Card
     */
    public function addCard(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required|string',
            'expiry' => 'required|string',
            'cvv' => 'required|string',
            'holder' => 'required|string',
            'setDefault' => 'boolean',
        ]);

        // Integrate with Stripe/payment gateway (placeholder)

        return back()->with('success', 'Carte ajoutee avec succes.');
    }

    /**
     * Set Default Card
     */
    public function setDefaultCard(Request $request, $cardId)
    {
        // Integrate with Stripe/payment gateway (placeholder)

        return back()->with('success', 'Carte par defaut mise a jour.');
    }

    /**
     * Delete Card
     */
    public function deleteCard(Request $request, $cardId)
    {
        // Integrate with Stripe/payment gateway (placeholder)

        return back()->with('success', 'Carte supprimee.');
    }

    /**
     * Setup SEPA
     */
    public function setupSepa(Request $request)
    {
        $validated = $request->validate([
            'holder' => 'required|string|max:255',
            'iban' => 'required|string|max:34',
            'bic' => 'nullable|string|max:11',
            'accept' => 'required|accepted',
        ]);

        // Integrate with Stripe SEPA (placeholder)

        return back()->with('success', 'Mandat SEPA configure avec succes.');
    }

    /**
     * Revoke SEPA
     */
    public function revokeSepa(Request $request)
    {
        // Integrate with Stripe SEPA (placeholder)

        return back()->with('success', 'Mandat SEPA revoque.');
    }

    /**
     * Toggle Auto Pay
     */
    public function toggleAutoPay(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
        ]);

        // Store auto pay preference (placeholder)

        return back()->with('success', 'Preference de paiement automatique mise a jour.');
    }

    /**
     * Helper: Authorize customer access
     */
    private function authorizeCustomerAccess(int $customerId): void
    {
        $customer = $this->getCustomer();
        if ($customer->id !== $customerId) {
            abort(403, 'Acces non autorise.');
        }
    }
}
