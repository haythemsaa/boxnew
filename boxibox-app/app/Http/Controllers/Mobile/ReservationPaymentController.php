<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Site;
use App\Services\PayPalService;
use App\Services\StripeConnectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ReservationPaymentController extends Controller
{
    protected StripeConnectService $stripeService;
    protected PayPalService $paypalService;

    public function __construct(StripeConnectService $stripeService, PayPalService $paypalService)
    {
        $this->stripeService = $stripeService;
        $this->paypalService = $paypalService;
    }

    protected function getCustomer(): Customer
    {
        $customerId = session('mobile_customer_id');
        return Customer::findOrFail($customerId);
    }

    /**
     * Display reservation page with available boxes
     */
    public function index(Request $request): Response
    {
        $customer = $this->getCustomer();

        $sites = Site::where('tenant_id', $customer->tenant_id)
            ->where('is_active', true)
            ->with(['boxes' => function ($query) {
                $query->where('status', 'available')
                    ->orderBy('volume');
            }])
            ->get();

        return Inertia::render('Mobile/Reserve/Index', [
            'sites' => $sites,
            'customer' => $customer->only(['id', 'first_name', 'last_name', 'email', 'phone']),
            'stripeKey' => config('services.stripe.key'),
        ]);
    }

    /**
     * Calculate total for a reservation
     */
    public function calculateTotal(Request $request)
    {
        $validated = $request->validate([
            'box_id' => 'required|exists:boxes,id',
            'start_date' => 'required|date|after_or_equal:today',
            'include_deposit' => 'boolean',
            'include_insurance' => 'boolean',
            'duration_months' => 'integer|min:1|max:24',
        ]);

        $customer = $this->getCustomer();
        $box = Box::where('id', $validated['box_id'])
            ->where('status', 'available')
            ->firstOrFail();

        // Verify tenant access
        if ($box->site->tenant_id !== $customer->tenant_id) {
            return response()->json(['error' => 'Box non disponible'], 403);
        }

        // Calculate totals with correct tax rates (FISCAL COMPLIANCE)
        // - Storage/rent: 20% VAT
        // - Deposit: 0% VAT (security deposit, not taxable)
        // - Insurance: 9% tax (assurance tax in France)
        $monthlyPrice = $box->monthly_price;
        $monthlyPriceWithVat = round($monthlyPrice * 1.20, 2); // 20% VAT on storage
        $monthlyVat = round($monthlyPrice * 0.20, 2);

        $depositAmount = $validated['include_deposit'] ? ($monthlyPrice * 2) : 0; // No VAT on deposit

        $insuranceBasePrice = 15.00;
        $insuranceWithTax = $validated['include_insurance'] ? round($insuranceBasePrice * 1.09, 2) : 0; // 9% tax
        $insuranceTax = $validated['include_insurance'] ? round($insuranceBasePrice * 0.09, 2) : 0;

        $total = round($monthlyPriceWithVat + $depositAmount + $insuranceWithTax, 2);

        return response()->json([
            'breakdown' => [
                'first_month' => $monthlyPrice,
                'first_month_vat' => $monthlyVat,
                'first_month_ttc' => $monthlyPriceWithVat,
                'deposit' => $depositAmount,
                'deposit_note' => 'Depot non soumis a TVA',
                'insurance' => $insuranceBasePrice,
                'insurance_tax' => $insuranceTax,
                'insurance_ttc' => $insuranceWithTax,
            ],
            'total' => $total,
            'monthly_recurring' => round($monthlyPriceWithVat + $insuranceWithTax, 2),
            'box' => [
                'id' => $box->id,
                'number' => $box->number,
                'volume' => $box->volume,
                'monthly_price' => $monthlyPrice,
            ],
        ]);
    }

    /**
     * Create payment intent for reservation
     */
    public function createPaymentIntent(Request $request)
    {
        $validated = $request->validate([
            'box_id' => 'required|exists:boxes,id',
            'start_date' => 'required|date|after_or_equal:today',
            'include_deposit' => 'boolean',
            'include_insurance' => 'boolean',
        ]);

        $customer = $this->getCustomer();
        $box = Box::where('id', $validated['box_id'])
            ->where('status', 'available')
            ->with('site')
            ->firstOrFail();

        if ($box->site->tenant_id !== $customer->tenant_id) {
            return response()->json(['error' => 'Box non disponible'], 403);
        }

        // Calculate total with correct tax rates (FISCAL COMPLIANCE)
        // - Storage/rent: 20% VAT
        // - Deposit: 0% VAT (security deposit, not taxable)
        // - Insurance: 9% tax (assurance tax in France)
        $monthlyPrice = $box->monthly_price;
        $monthlyPriceWithVat = $monthlyPrice * 1.20; // 20% VAT on storage

        $depositAmount = $validated['include_deposit'] ? ($monthlyPrice * 2) : 0; // No VAT on deposit
        $insuranceAmount = $validated['include_insurance'] ? (15.00 * 1.09) : 0; // 9% tax on insurance

        $total = round($monthlyPriceWithVat + $depositAmount + $insuranceAmount, 2);

        try {
            // Create a unique token for this reservation attempt
            $reservationToken = bin2hex(random_bytes(16));

            $intent = $this->stripeService->createPaymentIntent($customer, $total, [
                'metadata' => [
                    'type' => 'reservation',
                    'box_id' => $box->id,
                    'start_date' => $validated['start_date'],
                    'include_deposit' => $validated['include_deposit'] ? '1' : '0',
                    'include_insurance' => $validated['include_insurance'] ? '1' : '0',
                    'reservation_token' => $reservationToken,
                ],
            ]);

            // Store reservation data in session
            session([
                "reservation_{$reservationToken}" => [
                    'box_id' => $box->id,
                    'start_date' => $validated['start_date'],
                    'include_deposit' => $validated['include_deposit'] ?? false,
                    'include_insurance' => $validated['include_insurance'] ?? false,
                    'total' => $total,
                    'deposit_amount' => $depositAmount,
                    'monthly_price' => $monthlyPrice,
                ],
            ]);

            return response()->json([
                'clientSecret' => $intent['client_secret'],
                'paymentIntentId' => $intent['payment_intent_id'],
                'reservationToken' => $reservationToken,
                'amount' => $total,
            ]);
        } catch (\Exception $e) {
            Log::error('Reservation payment intent failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la creation du paiement'], 500);
        }
    }

    /**
     * Confirm reservation after successful payment
     */
    public function confirmReservation(Request $request)
    {
        $validated = $request->validate([
            'payment_intent_id' => 'required|string',
            'reservation_token' => 'required|string',
        ]);

        $customer = $this->getCustomer();
        $reservationData = session("reservation_{$validated['reservation_token']}");

        if (!$reservationData) {
            return response()->json(['error' => 'Session de reservation expiree'], 400);
        }

        try {
            // Verify payment with Stripe
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $paymentIntent = $stripe->paymentIntents->retrieve($validated['payment_intent_id']);

            if ($paymentIntent->status !== 'succeeded') {
                return response()->json(['error' => 'Paiement non confirme'], 400);
            }

            // Get and lock the box
            $box = Box::where('id', $reservationData['box_id'])
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if (!$box) {
                // Refund the payment
                $this->stripeService->createRefund($paymentIntent->id, $reservationData['total']);
                return response()->json(['error' => 'Box n\'est plus disponible. Remboursement en cours.'], 400);
            }

            DB::beginTransaction();

            try {
                // Create contract
                $contract = Contract::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'box_id' => $box->id,
                    'site_id' => $box->site_id,
                    'contract_number' => 'CTR-' . strtoupper(uniqid()),
                    'status' => 'active',
                    'start_date' => $reservationData['start_date'],
                    'end_date' => now()->addYear()->format('Y-m-d'),
                    'monthly_price' => $reservationData['monthly_price'],
                    'deposit_amount' => $reservationData['deposit_amount'],
                    'deposit_paid' => $reservationData['include_deposit'],
                    'billing_frequency' => 'monthly',
                    'billing_day' => now()->day,
                    'payment_method' => 'card',
                    'auto_renew' => true,
                    'access_code' => str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                ]);

                // Update box status
                $box->update(['status' => 'occupied']);

                // Calculate VAT correctly per item (FISCAL COMPLIANCE)
                // - Storage/rent: 20% VAT
                // - Deposit: 0% VAT (security deposit, not taxable in France)
                // - Insurance: 9% tax (assurance tax in France)
                $invoiceItems = [];
                $subtotal = 0;
                $totalTax = 0;

                // Storage item (20% VAT)
                $storagePrice = $reservationData['monthly_price'];
                $storageTax = $storagePrice * 0.20;
                $invoiceItems[] = [
                    'description' => "Location Box {$box->number} - 1er mois",
                    'quantity' => 1,
                    'unit_price' => $storagePrice,
                    'tax_rate' => 20,
                    'tax_amount' => $storageTax,
                    'total' => $storagePrice + $storageTax,
                ];
                $subtotal += $storagePrice;
                $totalTax += $storageTax;

                // Deposit (0% VAT - not taxable per French fiscal law)
                if ($reservationData['include_deposit']) {
                    $depositAmount = $reservationData['deposit_amount'];
                    $invoiceItems[] = [
                        'description' => 'Depot de garantie (non soumis a TVA)',
                        'quantity' => 1,
                        'unit_price' => $depositAmount,
                        'tax_rate' => 0,
                        'tax_amount' => 0,
                        'total' => $depositAmount,
                    ];
                    $subtotal += $depositAmount;
                }

                // Insurance (9% assurance tax in France)
                if ($reservationData['include_insurance']) {
                    $insurancePrice = 15.00;
                    $insuranceTax = $insurancePrice * 0.09;
                    $invoiceItems[] = [
                        'description' => 'Assurance',
                        'quantity' => 1,
                        'unit_price' => $insurancePrice,
                        'tax_rate' => 9,
                        'tax_amount' => $insuranceTax,
                        'total' => $insurancePrice + $insuranceTax,
                    ];
                    $subtotal += $insurancePrice;
                    $totalTax += $insuranceTax;
                }

                $grandTotal = $subtotal + $totalTax;

                // Create first invoice with correct tax calculation
                $invoice = Invoice::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'contract_id' => $contract->id,
                    'invoice_number' => 'FAC' . date('Ym') . str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT),
                    'type' => 'invoice',
                    'status' => 'paid',
                    'invoice_date' => now(),
                    'due_date' => now()->addDays(30),
                    'paid_at' => now(),
                    'subtotal' => round($subtotal, 2),
                    'tax_rate' => null, // Mixed rates, see items
                    'tax_amount' => round($totalTax, 2),
                    'total' => round($grandTotal, 2),
                    'paid_amount' => round($grandTotal, 2),
                    'items' => $invoiceItems,
                ]);

                // Create payment record
                Payment::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'contract_id' => $contract->id,
                    'payment_number' => 'PAY-' . strtoupper(uniqid()),
                    'amount' => $reservationData['total'],
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

                // Update customer stats
                $customer->increment('total_contracts');

                DB::commit();

                // Clear session data
                session()->forget("reservation_{$validated['reservation_token']}");

                return response()->json([
                    'success' => true,
                    'contract_id' => $contract->id,
                    'message' => 'Reservation confirmee avec succes',
                    'redirect' => route('mobile.contracts.show', $contract->id),
                ]);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('Reservation confirmation failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur lors de la confirmation: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Create PayPal order for reservation
     */
    public function createPayPalOrder(Request $request)
    {
        $validated = $request->validate([
            'box_id' => 'required|exists:boxes,id',
            'start_date' => 'required|date|after_or_equal:today',
            'include_deposit' => 'boolean',
            'include_insurance' => 'boolean',
        ]);

        $customer = $this->getCustomer();
        $box = Box::where('id', $validated['box_id'])
            ->where('status', 'available')
            ->with('site')
            ->firstOrFail();

        if ($box->site->tenant_id !== $customer->tenant_id) {
            return response()->json(['error' => 'Box non disponible'], 403);
        }

        // Calculate total
        $monthlyPrice = $box->monthly_price;
        $depositAmount = $validated['include_deposit'] ? ($monthlyPrice * 2) : 0;
        $insuranceAmount = $validated['include_insurance'] ? 15.00 : 0;
        $total = $monthlyPrice + $depositAmount + $insuranceAmount;

        // Create reservation token
        $reservationToken = bin2hex(random_bytes(16));

        try {
            $order = $this->paypalService->createOrder(
                $total,
                'EUR',
                "Reservation Box {$box->number}",
                route('mobile.reserve.paypal.success', ['token' => $reservationToken]),
                route('mobile.reserve.paypal.cancel'),
                [
                    'type' => 'reservation',
                    'box_id' => $box->id,
                    'customer_id' => $customer->id,
                ]
            );

            // Store reservation data
            session([
                "reservation_{$reservationToken}" => [
                    'box_id' => $box->id,
                    'start_date' => $validated['start_date'],
                    'include_deposit' => $validated['include_deposit'] ?? false,
                    'include_insurance' => $validated['include_insurance'] ?? false,
                    'total' => $total,
                    'deposit_amount' => $depositAmount,
                    'monthly_price' => $monthlyPrice,
                    'paypal_order_id' => $order['order_id'],
                ],
            ]);

            return response()->json([
                'orderId' => $order['order_id'],
                'approvalUrl' => $order['approval_url'],
                'reservationToken' => $reservationToken,
            ]);
        } catch (\Exception $e) {
            Log::error('PayPal reservation order failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Erreur PayPal'], 500);
        }
    }

    /**
     * Capture PayPal payment and confirm reservation
     */
    public function capturePayPalOrder(Request $request)
    {
        $token = $request->query('token'); // Reservation token
        $paypalToken = $request->query('token'); // PayPal also sends token

        $reservationData = session("reservation_{$token}");

        if (!$reservationData) {
            return redirect()->route('mobile.reserve')
                ->with('error', 'Session de reservation expiree');
        }

        $customer = $this->getCustomer();

        try {
            $capture = $this->paypalService->captureOrder($reservationData['paypal_order_id']);

            if ($capture['status'] !== 'COMPLETED') {
                return redirect()->route('mobile.reserve')
                    ->with('error', 'Paiement PayPal non complete');
            }

            // Same logic as confirmReservation but with PayPal data
            $box = Box::where('id', $reservationData['box_id'])
                ->where('status', 'available')
                ->lockForUpdate()
                ->first();

            if (!$box) {
                // TODO: Refund PayPal
                return redirect()->route('mobile.reserve')
                    ->with('error', 'Box n\'est plus disponible');
            }

            DB::beginTransaction();

            try {
                // Create contract
                $contract = Contract::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'box_id' => $box->id,
                    'site_id' => $box->site_id,
                    'contract_number' => 'CTR-' . strtoupper(uniqid()),
                    'status' => 'active',
                    'start_date' => $reservationData['start_date'],
                    'end_date' => now()->addYear()->format('Y-m-d'),
                    'monthly_price' => $reservationData['monthly_price'],
                    'deposit_amount' => $reservationData['deposit_amount'],
                    'deposit_paid' => $reservationData['include_deposit'],
                    'billing_frequency' => 'monthly',
                    'billing_day' => now()->day,
                    'payment_method' => 'paypal',
                    'auto_renew' => true,
                    'access_code' => str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT),
                ]);

                $box->update(['status' => 'occupied']);

                // Create invoice
                $invoice = Invoice::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'contract_id' => $contract->id,
                    'invoice_number' => 'FAC' . date('Ym') . str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT),
                    'type' => 'invoice',
                    'status' => 'paid',
                    'invoice_date' => now(),
                    'due_date' => now()->addDays(30),
                    'paid_at' => now(),
                    'subtotal' => $reservationData['total'] / 1.20,
                    'tax_rate' => 20,
                    'tax_amount' => $reservationData['total'] - ($reservationData['total'] / 1.20),
                    'total' => $reservationData['total'],
                    'paid_amount' => $reservationData['total'],
                    'items' => json_encode([
                        ['description' => "Location Box {$box->number}", 'quantity' => 1, 'unit_price' => $reservationData['monthly_price']],
                    ]),
                ]);

                // Create payment
                Payment::create([
                    'tenant_id' => $customer->tenant_id,
                    'customer_id' => $customer->id,
                    'invoice_id' => $invoice->id,
                    'contract_id' => $contract->id,
                    'payment_number' => 'PAY-' . strtoupper(uniqid()),
                    'amount' => $reservationData['total'],
                    'method' => 'paypal',
                    'gateway' => 'paypal',
                    'status' => 'completed',
                    'gateway_payment_id' => $capture['capture_id'],
                    'gateway_response' => $capture,
                    'paid_at' => now(),
                    'processed_at' => now(),
                ]);

                $customer->increment('total_contracts');

                DB::commit();

                session()->forget("reservation_{$token}");

                return redirect()->route('mobile.contracts.show', $contract->id)
                    ->with('success', 'Reservation confirmee avec succes!');
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
        } catch (\Exception $e) {
            Log::error('PayPal reservation capture failed', ['error' => $e->getMessage()]);
            return redirect()->route('mobile.reserve')
                ->with('error', 'Erreur lors du traitement PayPal');
        }
    }

    /**
     * Cancel reservation payment
     */
    public function paymentCancel()
    {
        return redirect()->route('mobile.reserve')
            ->with('warning', 'Reservation annulee');
    }
}
