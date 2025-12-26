<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\StripePaymentService;
use App\Services\SecurityAuditService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
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
     * SECURITY: Uses DB transaction with lock to prevent race conditions.
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

        // Use transaction with lock to prevent double payments (race condition)
        $paymentCreated = DB::transaction(function () use ($request, $invoice, $paymentResult) {
            // Lock the invoice row to prevent concurrent updates
            $lockedInvoice = Invoice::lockForUpdate()->find($invoice->id);

            if (!$lockedInvoice) {
                return false;
            }

            // Check for existing payment with pessimistic locking
            $existingPayment = Payment::where('stripe_payment_intent_id', $request->payment_intent_id)
                ->where('invoice_id', $invoice->id)
                ->lockForUpdate()
                ->first();

            if ($existingPayment) {
                // Payment already exists - likely created by webhook
                return 'already_exists';
            }

            // Create payment record
            Payment::create([
                'invoice_id' => $invoice->id,
                'tenant_id' => $lockedInvoice->tenant_id,
                'customer_id' => $lockedInvoice->customer_id,
                'amount' => $paymentResult['amount'],
                'currency' => 'eur',
                'payment_method' => 'card',
                'gateway' => 'stripe',
                'stripe_charge_id' => $paymentResult['charge_id'],
                'stripe_payment_intent_id' => $paymentResult['intent_id'],
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Update invoice within same transaction
            $remaining = $lockedInvoice->total - ($lockedInvoice->amount_paid + $paymentResult['amount']);

            if ($remaining <= 0) {
                $lockedInvoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'amount_paid' => $lockedInvoice->total,
                ]);
            } else {
                $lockedInvoice->update([
                    'status' => 'partial',
                    'amount_paid' => $lockedInvoice->amount_paid + $paymentResult['amount'],
                ]);
            }

            return 'created';
        });

        if ($paymentCreated === false) {
            return redirect()->route('tenant.invoices.show', $invoice->id)
                ->withErrors(['stripe' => 'Invoice not found.']);
        }

        $this->auditService->logSecurityEvent(
            'stripe_payment_completed',
            "Invoice#{$invoice->id}",
            'success',
            [
                'amount' => $paymentResult['amount'],
                'charge_id' => $paymentResult['charge_id'],
                'source' => $paymentCreated === 'already_exists' ? 'webhook' : 'direct',
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
