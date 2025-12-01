<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\StripePaymentService;
use App\Services\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class StripePaymentController extends Controller
{
    protected StripePaymentService $stripeService;
    protected SecurityAuditService $auditService;

    public function __construct(
        StripePaymentService $stripeService,
        SecurityAuditService $auditService
    ) {
        $this->stripeService = $stripeService;
        $this->auditService = $auditService;
    }

    /**
     * Show payment form for invoice.
     */
    public function show(Invoice $invoice): Response
    {
        // Check authorization
        $this->authorize('view', $invoice);

        // Create payment intent
        $paymentData = $this->stripeService->createPaymentIntent($invoice);

        if (!$paymentData) {
            return back()->withErrors(['stripe' => 'Unable to create payment. Please try again.']);
        }

        return Inertia::render('Payments/StripeCheckout', [
            'invoice' => $invoice,
            'clientSecret' => $paymentData['client_secret'],
            'amount' => $paymentData['amount'],
            'stripePublicKey' => $this->stripeService->getPublicKey(),
        ]);
    }

    /**
     * Confirm payment after Stripe processes it.
     */
    public function confirm(Request $request, Invoice $invoice): RedirectResponse
    {
        $request->validate([
            'payment_intent_id' => 'required|string',
        ]);

        $this->authorize('view', $invoice);

        // Verify payment intent with Stripe
        $paymentResult = $this->stripeService->verifyPaymentIntent($request->payment_intent_id);

        if (!$paymentResult || !$paymentResult['success']) {
            $this->auditService->logSecurityEvent(
                'stripe_payment_failed',
                "Invoice#{$invoice->id}",
                'failure',
                ['intent_id' => $request->payment_intent_id]
            );

            return redirect()->route('tenant.invoices.show', $invoice->id)
                ->withErrors(['stripe' => 'Payment verification failed. Please try again.']);
        }

        // Create payment record if not already created by webhook
        $existingPayment = Payment::where('stripe_payment_intent_id', $request->payment_intent_id)
            ->where('invoice_id', $invoice->id)
            ->first();

        if (!$existingPayment) {
            Payment::create([
                'invoice_id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id,
                'customer_id' => $invoice->customer_id,
                'amount' => $paymentResult['amount'],
                'currency' => 'eur',
                'payment_method' => 'card',
                'gateway' => 'stripe',
                'stripe_charge_id' => $paymentResult['charge_id'],
                'stripe_payment_intent_id' => $paymentResult['intent_id'],
                'status' => 'completed',
                'paid_at' => now(),
            ]);
        }

        // Update invoice
        $remaining = $invoice->total - ($invoice->amount_paid + $paymentResult['amount']);

        if ($remaining <= 0) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'amount_paid' => $invoice->total,
            ]);
        } else {
            $invoice->update([
                'status' => 'partial',
                'amount_paid' => $invoice->amount_paid + $paymentResult['amount'],
            ]);
        }

        $this->auditService->logSecurityEvent(
            'stripe_payment_completed',
            "Invoice#{$invoice->id}",
            'success',
            [
                'amount' => $paymentResult['amount'],
                'charge_id' => $paymentResult['charge_id'],
            ]
        );

        return redirect()->route('tenant.invoices.show', $invoice->id)
            ->with('success', 'Payment completed successfully.');
    }

    /**
     * Handle payment cancellation.
     */
    public function cancel(Invoice $invoice): RedirectResponse
    {
        $this->authorize('view', $invoice);

        $this->auditService->logSecurityEvent(
            'stripe_payment_cancelled',
            "Invoice#{$invoice->id}",
            'failure'
        );

        return redirect()->route('tenant.invoices.show', $invoice->id)
            ->with('warning', 'Payment was cancelled.');
    }
}
