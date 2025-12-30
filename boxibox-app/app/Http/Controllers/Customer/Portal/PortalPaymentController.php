<?php

namespace App\Http\Controllers\Customer\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SavedPaymentMethod;
use App\Services\StripeUnifiedService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portal Payment Controller
 * Handles all payment-related functionality for customer portal
 */
class PortalPaymentController extends Controller
{
    /**
     * Get authenticated customer from session
     */
    protected function getAuthenticatedCustomer(): Customer
    {
        $customerId = session('customer_portal_id');

        if (!$customerId) {
            abort(403, 'No customer authenticated. Please log in.');
        }

        return Customer::findOrFail($customerId);
    }

    /**
     * Display payments history
     */
    public function index(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $payments = Payment::where('customer_id', $customer->id)
            ->with(['invoice', 'contract'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Customer/Portal/Payments', [
            'customer' => $customer,
            'payments' => $payments,
        ]);
    }

    /**
     * Display payment methods
     */
    public function paymentMethods(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $methods = SavedPaymentMethod::where('customer_id', $customer->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return Inertia::render('Customer/Portal/PaymentMethods', [
            'customer' => $customer,
            'paymentMethods' => $methods,
        ]);
    }

    /**
     * Display invoice payment page
     */
    public function payInvoice(Invoice $invoice, StripeUnifiedService $stripeService): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Verify invoice belongs to customer
        if ($invoice->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to invoice.');
        }

        // Get saved payment methods
        $savedMethods = SavedPaymentMethod::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->orderBy('is_default', 'desc')
            ->get();

        return Inertia::render('Customer/Portal/PayInvoice', [
            'customer' => $customer,
            'invoice' => $invoice->load(['contract.box', 'items']),
            'savedPaymentMethods' => $savedMethods,
            'stripePublicKey' => $stripeService->getPublicKey(),
        ]);
    }

    /**
     * Create Stripe payment intent for invoice
     */
    public function createPaymentIntent(Request $request, Invoice $invoice, StripeUnifiedService $stripeService)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($invoice->customer_id !== $customer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'Invoice already paid'], 400);
        }

        try {
            $stripeCustomerId = $stripeService->getOrCreateCustomer($customer);

            $result = $stripeService->createPaymentIntentForInvoice($invoice, $stripeCustomerId);

            if (!$result) {
                return response()->json(['error' => 'Failed to create payment intent'], 500);
            }

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Failed to create payment intent', [
                'invoice_id' => $invoice->id,
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Payment initialization failed'], 500);
        }
    }

    /**
     * Confirm payment after Stripe processing
     */
    public function confirmPayment(Request $request, Invoice $invoice, StripeUnifiedService $stripeService)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($invoice->customer_id !== $customer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        try {
            $result = $stripeService->verifyPaymentIntent($request->payment_intent_id);

            if (!$result || !$result['success']) {
                return response()->json(['error' => 'Payment verification failed'], 400);
            }

            // Record payment
            $payment = Payment::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'contract_id' => $invoice->contract_id,
                'amount' => $result['amount'],
                'payment_method' => 'card',
                'payment_gateway' => 'stripe',
                'gateway_payment_id' => $result['intent_id'],
                'payment_intent_id' => $result['intent_id'],
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Update invoice
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'balance' => 0,
            ]);

            Log::info('Portal payment completed', [
                'invoice_id' => $invoice->id,
                'payment_id' => $payment->id,
                'customer_id' => $customer->id,
            ]);

            return response()->json([
                'success' => true,
                'payment_id' => $payment->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Payment confirmation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Payment confirmation failed'], 500);
        }
    }

    /**
     * Add new payment method
     */
    public function addPaymentMethod(Request $request, StripeUnifiedService $stripeService)
    {
        $customer = $this->getAuthenticatedCustomer();

        $request->validate([
            'payment_method_id' => 'required|string',
            'set_as_default' => 'boolean',
        ]);

        try {
            $paymentMethod = $stripeService->savePaymentMethod(
                $customer,
                $request->payment_method_id,
                $request->boolean('set_as_default', true)
            );

            return response()->json([
                'success' => true,
                'payment_method' => $paymentMethod,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add payment method', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'Failed to save payment method'], 500);
        }
    }

    /**
     * Delete payment method
     */
    public function deletePaymentMethod(SavedPaymentMethod $method)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($method->customer_id !== $customer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $method->update(['status' => 'deleted']);

        return response()->json(['success' => true]);
    }

    /**
     * Set default payment method
     */
    public function setDefaultPaymentMethod(SavedPaymentMethod $method)
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($method->customer_id !== $customer->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Unset other defaults
        SavedPaymentMethod::where('customer_id', $customer->id)
            ->update(['is_default' => false]);

        $method->update(['is_default' => true]);

        return response()->json(['success' => true]);
    }
}
