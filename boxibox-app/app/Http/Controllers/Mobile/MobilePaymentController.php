<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\PayPalService;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class MobilePaymentController extends Controller
{
    protected StripeConnectService $stripeService;
    protected PayPalService $paypalService;

    public function __construct(StripeConnectService $stripeService, PayPalService $paypalService)
    {
        $this->stripeService = $stripeService;
        $this->paypalService = $paypalService;
    }

    /**
     * Get the authenticated customer from session
     */
    protected function getCustomer(): Customer
    {
        $customerId = session('mobile_customer_id');
        return Customer::findOrFail($customerId);
    }

    /**
     * Display payment page with unpaid invoices
     */
    public function index(Request $request): Response
    {
        $customer = $this->getCustomer();

        $unpaidInvoices = Invoice::where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->with('contract:id,box_id', 'contract.box:id,number')
            ->orderBy('due_date')
            ->get()
            ->map(fn($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date,
                'total' => $invoice->total,
                'paid_amount' => $invoice->paid_amount,
                'balance' => $invoice->total - $invoice->paid_amount,
                'status' => $invoice->status,
                'contract' => $invoice->contract,
            ]);

        $savedCards = PaymentMethod::where('customer_id', $customer->id)
            ->where('type', 'card')
            ->where('is_active', true)
            ->get()
            ->map(fn($card) => [
                'id' => $card->id,
                'brand' => $card->card_brand,
                'last4' => $card->card_last_four,
                'expiry' => $card->card_exp_month . '/' . $card->card_exp_year,
                'is_default' => $card->is_default,
            ]);

        $hasSepa = PaymentMethod::where('customer_id', $customer->id)
            ->where('type', 'sepa_debit')
            ->where('is_active', true)
            ->exists();

        return Inertia::render('Mobile/Pay/Index', [
            'unpaidInvoices' => $unpaidInvoices,
            'savedCards' => $savedCards,
            'hasSepa' => $hasSepa,
            'preselectedInvoiceId' => $request->query('invoice_id'),
            'stripeKey' => config('services.stripe.key'),
            'paypalClientId' => config('services.paypal.client_id'),
        ]);
    }

    /**
     * Create Stripe PaymentIntent
     */
    public function createStripeIntent(Request $request)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
            'save_card' => 'boolean',
        ]);

        $customer = $this->getCustomer();

        // Verify invoices belong to customer and calculate total
        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->get();

        if ($invoices->count() !== count($validated['invoice_ids'])) {
            return response()->json(['error' => 'Factures invalides'], 422);
        }

        $totalAmount = $invoices->sum(fn($inv) => $inv->total - $inv->paid_amount);

        if ($totalAmount <= 0) {
            return response()->json(['error' => 'Montant invalide'], 422);
        }

        try {
            $intent = $this->stripeService->createPaymentIntent($customer, $totalAmount, [
                'save_payment_method' => $validated['save_card'] ?? false,
                'metadata' => [
                    'invoice_ids' => implode(',', $validated['invoice_ids']),
                    'type' => 'invoice_payment',
                ],
            ]);

            return response()->json([
                'clientSecret' => $intent['client_secret'],
                'paymentIntentId' => $intent['payment_intent_id'],
                'amount' => $totalAmount,
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe intent creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la creation du paiement'], 500);
        }
    }

    /**
     * Confirm Stripe payment after client-side confirmation
     */
    public function confirmStripePayment(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'invoice_ids' => 'required|array|min:1',
        ]);

        $customer = $this->getCustomer();

        try {
            // Verify payment with Stripe
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($validated['payment_intent_id']);

            if ($paymentIntent->status !== 'succeeded') {
                return response()->json([
                    'success' => false,
                    'error' => 'Le paiement n\'a pas ete confirme',
                ], 400);
            }

            // Get invoices with lock to prevent race conditions
            $invoices = Invoice::whereIn('id', $validated['invoice_ids'])
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->get();

            // Use transaction to ensure atomicity
            DB::transaction(function () use ($invoices, $customer, $paymentIntent) {
                // Create payment records for each invoice
                foreach ($invoices as $invoice) {
                    $amount = $invoice->total - $invoice->paid_amount;

                    if ($amount <= 0) {
                        continue; // Skip already paid invoices
                    }

                    Payment::create([
                        'tenant_id' => $customer->tenant_id,
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'contract_id' => $invoice->contract_id,
                        'payment_number' => 'PAY-' . strtoupper(uniqid()),
                        'amount' => $amount,
                        'method' => 'card',
                        'gateway' => 'stripe',
                        'status' => 'completed',
                        'stripe_payment_intent_id' => $paymentIntent->id,
                        'stripe_charge_id' => $paymentIntent->latest_charge,
                        'card_brand' => $paymentIntent->payment_method_details?->card?->brand ?? null,
                        'card_last_four' => $paymentIntent->payment_method_details?->card?->last4 ?? null,
                        'paid_at' => now(),
                        'processed_at' => now(),
                    ]);

                    // Update invoice
                    $invoice->recordPayment($amount);
                }
            });

            // Save payment method if requested (outside transaction - non-critical)
            if ($paymentIntent->setup_future_usage && $paymentIntent->payment_method) {
                $this->savePaymentMethod($customer, $paymentIntent->payment_method);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paiement effectue avec succes',
                'redirect' => route('mobile.pay.success'),
            ]);
        } catch (\Exception $e) {
            Log::error('Stripe confirmation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'error' => 'Erreur lors de la confirmation du paiement',
            ], 500);
        }
    }

    /**
     * Create PayPal order
     */
    public function createPayPalOrder(Request $request)
    {
        $validated = $request->validate([
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
        ]);

        $customer = $this->getCustomer();

        // Verify invoices and calculate total
        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->get();

        if ($invoices->count() !== count($validated['invoice_ids'])) {
            return response()->json(['error' => 'Factures invalides'], 422);
        }

        $totalAmount = $invoices->sum(fn($inv) => $inv->total - $inv->paid_amount);
        $invoiceNumbers = $invoices->pluck('invoice_number')->join(', ');

        try {
            $order = $this->paypalService->createOrder(
                $totalAmount,
                'EUR',
                "Paiement factures: {$invoiceNumbers}",
                route('mobile.pay.paypal.success', ['invoice_ids' => implode(',', $validated['invoice_ids'])]),
                route('mobile.pay.paypal.cancel'),
                [
                    'customer_id' => $customer->id,
                    'invoice_ids' => $validated['invoice_ids'],
                ]
            );

            return response()->json([
                'orderId' => $order['order_id'],
                'approvalUrl' => $order['approval_url'],
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal order creation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur PayPal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Capture PayPal payment after approval
     */
    public function capturePayPalOrder(Request $request)
    {
        $token = $request->query('token'); // PayPal order ID
        $invoiceIds = explode(',', $request->query('invoice_ids', ''));

        if (!$token || empty($invoiceIds)) {
            return redirect()->route('mobile.pay.index')
                ->with('error', 'Parametres de paiement invalides');
        }

        $customer = $this->getCustomer();

        try {
            $capture = $this->paypalService->captureOrder($token);

            if ($capture['status'] !== 'COMPLETED') {
                return redirect()->route('mobile.pay.index')
                    ->with('error', 'Le paiement PayPal n\'a pas ete complete');
            }

            // Get invoices with lock to prevent race conditions
            $invoices = Invoice::whereIn('id', $invoiceIds)
                ->where('customer_id', $customer->id)
                ->lockForUpdate()
                ->get();

            // Use transaction to ensure atomicity
            DB::transaction(function () use ($invoices, $customer, $capture) {
                foreach ($invoices as $invoice) {
                    $amount = $invoice->total - $invoice->paid_amount;

                    if ($amount <= 0) {
                        continue; // Skip already paid invoices
                    }

                    Payment::create([
                        'tenant_id' => $customer->tenant_id,
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'contract_id' => $invoice->contract_id,
                        'payment_number' => 'PAY-' . strtoupper(uniqid()),
                        'amount' => $amount,
                        'method' => 'paypal',
                        'gateway' => 'paypal',
                        'status' => 'completed',
                        'gateway_payment_id' => $capture['capture_id'],
                        'gateway_response' => $capture,
                        'paid_at' => now(),
                        'processed_at' => now(),
                    ]);

                    $invoice->recordPayment($amount);
                }
            });

            return redirect()->route('mobile.pay.success');
        } catch (\Exception $e) {
            Log::error('PayPal capture failed', ['error' => $e->getMessage()]);
            return redirect()->route('mobile.pay.index')
                ->with('error', 'Erreur lors du traitement PayPal');
        }
    }

    /**
     * Payment cancelled
     */
    public function paymentCancel()
    {
        return redirect()->route('mobile.pay.index')
            ->with('warning', 'Paiement annule');
    }

    /**
     * Payment success page
     */
    public function paymentSuccess(): Response
    {
        return Inertia::render('Mobile/Pay/Success');
    }

    /**
     * Get saved payment methods
     */
    public function getPaymentMethods()
    {
        $customer = $this->getCustomer();

        $cards = PaymentMethod::where('customer_id', $customer->id)
            ->where('type', 'card')
            ->where('is_active', true)
            ->get()
            ->map(fn($card) => [
                'id' => $card->id,
                'brand' => $card->card_brand,
                'last4' => $card->card_last_four,
                'expiry' => $card->card_exp_month . '/' . $card->card_exp_year,
                'is_default' => $card->is_default,
            ]);

        return response()->json(['cards' => $cards]);
    }

    /**
     * Charge a saved card
     */
    public function chargeSavedCard(Request $request)
    {
        $validated = $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'invoice_ids' => 'required|array|min:1',
        ]);

        $customer = $this->getCustomer();

        $paymentMethod = PaymentMethod::where('id', $validated['payment_method_id'])
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $invoices = Invoice::whereIn('id', $validated['invoice_ids'])
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->lockForUpdate()
            ->get();

        $totalAmount = $invoices->sum(fn($inv) => $inv->total - $inv->paid_amount);

        if ($totalAmount <= 0) {
            return response()->json(['error' => 'Montant invalide ou factures deja payees'], 422);
        }

        try {
            $result = $this->stripeService->chargeCustomer(
                $customer,
                $totalAmount,
                $paymentMethod->stripe_payment_method_id
            );

            // Use transaction to ensure atomicity
            DB::transaction(function () use ($invoices, $customer, $paymentMethod, $result) {
                foreach ($invoices as $invoice) {
                    $amount = $invoice->total - $invoice->paid_amount;

                    if ($amount <= 0) {
                        continue; // Skip already paid invoices
                    }

                    Payment::create([
                        'tenant_id' => $customer->tenant_id,
                        'customer_id' => $customer->id,
                        'invoice_id' => $invoice->id,
                        'contract_id' => $invoice->contract_id,
                        'payment_number' => 'PAY-' . strtoupper(uniqid()),
                        'amount' => $amount,
                        'method' => 'card',
                        'gateway' => 'stripe',
                        'status' => 'completed',
                        'stripe_payment_intent_id' => $result['payment_intent_id'],
                        'card_brand' => $paymentMethod->card_brand,
                        'card_last_four' => $paymentMethod->card_last_four,
                        'paid_at' => now(),
                        'processed_at' => now(),
                    ]);

                    $invoice->recordPayment($amount);
                }
            });

            return response()->json([
                'success' => true,
                'redirect' => route('mobile.pay.success'),
            ]);
        } catch (\Exception $e) {
            Log::error('Saved card charge failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Echec du paiement par carte'], 500);
        }
    }

    /**
     * Save a payment method for the customer
     */
    protected function savePaymentMethod(Customer $customer, string $stripePaymentMethodId): void
    {
        try {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $pm = $stripe->paymentMethods->retrieve($stripePaymentMethodId);

            // Check if already saved
            $exists = PaymentMethod::where('customer_id', $customer->id)
                ->where('stripe_payment_method_id', $stripePaymentMethodId)
                ->exists();

            if (!$exists && $pm->type === 'card') {
                PaymentMethod::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'type' => 'card',
                    'stripe_payment_method_id' => $stripePaymentMethodId,
                    'card_brand' => $pm->card->brand,
                    'card_last_four' => $pm->card->last4,
                    'card_exp_month' => $pm->card->exp_month,
                    'card_exp_year' => $pm->card->exp_year,
                    'is_default' => PaymentMethod::where('customer_id', $customer->id)->count() === 0,
                    'is_active' => true,
                ]);
            }
        } catch (\Exception $e) {
            Log::warning('Failed to save payment method', ['error' => $e->getMessage()]);
        }
    }
}
