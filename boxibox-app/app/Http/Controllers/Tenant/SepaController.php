<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\GocardlessPayment;
use App\Models\Invoice;
use App\Models\SepaMandate;
use App\Services\GoCardlessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SepaController extends Controller
{
    public function __construct(
        protected GoCardlessService $gocardless
    ) {}

    /**
     * SEPA Dashboard
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        // Get mandates with stats
        $mandates = SepaMandate::where('tenant_id', $tenantId)
            ->with('customer:id,first_name,last_name,email,company_name')
            ->latest()
            ->paginate(20);

        // Stats
        $stats = [
            'active_mandates' => SepaMandate::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'pending_mandates' => SepaMandate::where('tenant_id', $tenantId)->whereIn('status', ['pending', 'pending_submission', 'submitted'])->count(),
            'total_collected' => SepaMandate::where('tenant_id', $tenantId)->sum('total_collected'),
            'pending_payments' => GocardlessPayment::where('tenant_id', $tenantId)->pending()->sum('amount'),
            'failed_payments_30d' => GocardlessPayment::where('tenant_id', $tenantId)
                ->failed()
                ->where('created_at', '>=', now()->subDays(30))
                ->count(),
        ];

        // Recent payments
        $recentPayments = GocardlessPayment::where('tenant_id', $tenantId)
            ->with(['customer:id,first_name,last_name', 'invoice:id,invoice_number'])
            ->latest()
            ->limit(10)
            ->get();

        return Inertia::render('Tenant/SEPA/Dashboard', [
            'mandates' => $mandates,
            'stats' => $stats,
            'recentPayments' => $recentPayments,
        ]);
    }

    /**
     * Mandate details
     */
    public function show(Request $request, SepaMandate $mandate): Response
    {
        $this->authorizeMandate($request, $mandate);

        $mandate->load(['customer', 'contract']);

        $payments = GocardlessPayment::where('sepa_mandate_id', $mandate->id)
            ->with('invoice:id,invoice_number,total')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Tenant/SEPA/MandateShow', [
            'mandate' => $mandate,
            'payments' => $payments,
        ]);
    }

    /**
     * Initiate mandate setup (redirect flow)
     */
    public function setupMandate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'contract_id' => 'nullable|exists:contracts,id',
        ]);

        $customer = Customer::findOrFail($validated['customer_id']);

        // Ensure customer belongs to tenant
        if ($customer->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Generate session token
        $sessionToken = Str::uuid()->toString();

        // Store in session for later verification
        session([
            'gocardless_session_' . $sessionToken => [
                'customer_id' => $customer->id,
                'contract_id' => $validated['contract_id'] ?? null,
                'tenant_id' => $request->user()->tenant_id,
            ],
        ]);

        try {
            $redirectFlow = $this->gocardless->createRedirectFlow(
                $customer,
                route('tenant.sepa.callback'),
                $sessionToken
            );

            return response()->json([
                'success' => true,
                'redirect_url' => $redirectFlow['redirect_url'],
                'flow_id' => $redirectFlow['id'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la creation du mandat: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle redirect callback from GoCardless
     */
    public function handleCallback(Request $request)
    {
        $flowId = $request->query('redirect_flow_id');

        if (!$flowId) {
            return redirect()->route('tenant.sepa.index')
                ->with('error', 'Parametres de retour invalides');
        }

        // Find session data
        $sessionData = null;
        foreach (session()->all() as $key => $value) {
            if (str_starts_with($key, 'gocardless_session_')) {
                $sessionData = $value;
                $sessionToken = str_replace('gocardless_session_', '', $key);
                break;
            }
        }

        if (!$sessionData) {
            return redirect()->route('tenant.sepa.index')
                ->with('error', 'Session expiree');
        }

        try {
            // Complete the redirect flow
            $result = $this->gocardless->completeRedirectFlow($flowId, $sessionToken);

            // Get mandate details
            $mandateId = $result['links']['mandate'] ?? null;
            $customerId = $result['links']['customer'] ?? null;

            if (!$mandateId) {
                throw new \Exception('Mandate ID not returned');
            }

            // Get full mandate details
            $mandateDetails = $this->gocardless->getMandate($mandateId);

            // Create local mandate record
            $mandate = SepaMandate::create([
                'tenant_id' => $sessionData['tenant_id'],
                'customer_id' => $sessionData['customer_id'],
                'contract_id' => $sessionData['contract_id'],
                'gocardless_mandate_id' => $mandateId,
                'gocardless_customer_id' => $customerId,
                'gocardless_bank_account_id' => $mandateDetails['links']['customer_bank_account'] ?? null,
                'rum' => $mandateDetails['reference'] ?? 'SEPA-' . date('Ymd') . '-' . Str::upper(Str::random(8)),
                'scheme' => $mandateDetails['scheme'] ?? 'sepa_core',
                'status' => $mandateDetails['status'] ?? 'pending_submission',
                'account_holder' => $result['customer_bank_account']['account_holder_name'] ?? null,
                'iban' => null, // Don't store full IBAN
                'bic' => $result['customer_bank_account']['bank_code'] ?? null,
            ]);

            // Clear session
            session()->forget('gocardless_session_' . $sessionToken);

            return redirect()->route('tenant.sepa.show', $mandate)
                ->with('success', 'Mandat SEPA cree avec succes !');

        } catch (\Exception $e) {
            return redirect()->route('tenant.sepa.index')
                ->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Cancel a mandate
     */
    public function cancelMandate(Request $request, SepaMandate $mandate): JsonResponse
    {
        $this->authorizeMandate($request, $mandate);

        if (!$mandate->gocardless_mandate_id) {
            // Local mandate only
            $mandate->update(['status' => 'cancelled']);
            return response()->json(['success' => true]);
        }

        try {
            $this->gocardless->cancelMandate($mandate->gocardless_mandate_id);
            $mandate->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Mandat annule',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create payment for invoice
     */
    public function chargeInvoice(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'mandate_id' => 'nullable|exists:sepa_mandates,id',
            'charge_date' => 'nullable|date|after:today',
        ]);

        $invoice = Invoice::findOrFail($validated['invoice_id']);

        if ($invoice->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Get mandate
        $mandate = isset($validated['mandate_id'])
            ? SepaMandate::findOrFail($validated['mandate_id'])
            : $invoice->customer->sepaMandates()->active()->first();

        if (!$mandate || !$mandate->isActive()) {
            return response()->json([
                'success' => false,
                'error' => 'Aucun mandat SEPA actif trouve pour ce client',
            ], 400);
        }

        if (!$mandate->gocardless_mandate_id) {
            return response()->json([
                'success' => false,
                'error' => 'Ce mandat n\'est pas lie a GoCardless',
            ], 400);
        }

        try {
            $payment = $this->gocardless->chargeInvoice($invoice);

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'error' => 'Impossible de creer le prelevement',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'payment' => $payment,
                'message' => 'Prelevement SEPA initie',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get pending payments
     */
    public function pendingPayments(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $payments = GocardlessPayment::where('tenant_id', $tenantId)
            ->pending()
            ->with(['customer:id,first_name,last_name', 'invoice:id,invoice_number'])
            ->orderBy('charge_date')
            ->get();

        return response()->json([
            'success' => true,
            'payments' => $payments,
        ]);
    }

    /**
     * Cancel a payment
     */
    public function cancelPayment(Request $request, GocardlessPayment $payment): JsonResponse
    {
        if ($payment->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        if (!$payment->isPending()) {
            return response()->json([
                'success' => false,
                'error' => 'Ce prelevement ne peut plus etre annule',
            ], 400);
        }

        try {
            $this->gocardless->cancelPayment($payment->gocardless_payment_id);
            $payment->update(['status' => 'cancelled', 'cancelled_at' => now()]);

            return response()->json([
                'success' => true,
                'message' => 'Prelevement annule',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Retry a failed payment
     */
    public function retryPayment(Request $request, GocardlessPayment $payment): JsonResponse
    {
        if ($payment->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        if (!$payment->hasFailed()) {
            return response()->json([
                'success' => false,
                'error' => 'Seuls les prelevements echoues peuvent etre reessayes',
            ], 400);
        }

        try {
            $result = $this->gocardless->retryPayment($payment->gocardless_payment_id);

            $payment->update([
                'status' => $result['status'],
                'charge_date' => $result['charge_date'] ?? null,
                'failed_at' => null,
                'failure_reason' => null,
                'failure_description' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Prelevement relance',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Bulk charge invoices
     */
    public function bulkCharge(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array',
            'invoice_ids.*' => 'exists:invoices,id',
        ]);

        $tenantId = $request->user()->tenant_id;
        $results = [];

        foreach ($validated['invoice_ids'] as $invoiceId) {
            $invoice = Invoice::find($invoiceId);

            if (!$invoice || $invoice->tenant_id !== $tenantId) {
                $results[] = [
                    'invoice_id' => $invoiceId,
                    'success' => false,
                    'error' => 'Facture non trouvee',
                ];
                continue;
            }

            try {
                $payment = $this->gocardless->chargeInvoice($invoice);

                $results[] = [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoice->invoice_number,
                    'success' => $payment !== null,
                    'payment_id' => $payment?->id,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoice->invoice_number,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        $successCount = collect($results)->where('success', true)->count();

        return response()->json([
            'success' => true,
            'message' => "{$successCount} prelevement(s) initie(s)",
            'results' => $results,
        ]);
    }

    protected function authorizeMandate(Request $request, SepaMandate $mandate): void
    {
        if ($mandate->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }
    }
}
