<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     */
    public function handle(Request $request): Response
    {
        $event = $request->json()->all();
        $eventType = $event['type'] ?? null;

        Log::info('Stripe webhook received', [
            'event_type' => $eventType,
            'event_id' => $event['id'] ?? null,
        ]);

        match ($eventType) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($event),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($event),
            'invoice.paid' => $this->handleInvoicePaid($event),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($event),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event),
            default => Log::info('Unhandled Stripe webhook event', ['type' => $eventType]),
        };

        return response()->json(['success' => true], 200);
    }

    /**
     * Handle payment intent succeeded.
     */
    protected function handlePaymentIntentSucceeded(array $event): void
    {
        $data = $event['data']['object'] ?? [];
        $invoiceId = $data['metadata']['invoice_id'] ?? null;

        if (!$invoiceId) {
            return;
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            Log::warning('Invoice not found for payment intent', ['invoice_id' => $invoiceId]);
            return;
        }

        // Create payment record
        $amount = $data['amount_received'] / 100; // Convert from cents

        Payment::create([
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_method' => 'stripe',
            'stripe_charge_id' => $data['charges']['data'][0]['id'] ?? null,
            'stripe_payment_intent_id' => $data['id'],
            'status' => 'completed',
        ]);

        // Update invoice status
        $remaining = $invoice->total - ($invoice->amount_paid + $amount);

        if ($remaining <= 0) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => now(),
                'amount_paid' => $invoice->total,
            ]);

            Log::info('Invoice marked as paid via Stripe', ['invoice_id' => $invoiceId]);
        } else {
            $invoice->update([
                'status' => 'partial',
                'amount_paid' => $invoice->amount_paid + $amount,
            ]);

            Log::info('Partial payment recorded via Stripe', ['invoice_id' => $invoiceId, 'amount' => $amount]);
        }
    }

    /**
     * Handle payment intent failed.
     */
    protected function handlePaymentIntentFailed(array $event): void
    {
        $data = $event['data']['object'] ?? [];
        $invoiceId = $data['metadata']['invoice_id'] ?? null;

        if (!$invoiceId) {
            return;
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return;
        }

        Log::warning('Stripe payment intent failed', [
            'invoice_id' => $invoiceId,
            'error' => $data['last_payment_error']['message'] ?? 'Unknown error',
        ]);

        // Optionally update invoice status to failed
        // Could send notification to customer here
    }

    /**
     * Handle invoice paid.
     */
    protected function handleInvoicePaid(array $event): void
    {
        $data = $event['data']['object'] ?? [];
        $amount = $data['amount_paid'] / 100; // Convert from cents

        Log::info('Stripe invoice paid', [
            'stripe_invoice_id' => $data['id'],
            'amount' => $amount,
        ]);

        // Can be used for recurring payment tracking
    }

    /**
     * Handle invoice payment failed.
     */
    protected function handleInvoicePaymentFailed(array $event): void
    {
        $data = $event['data']['object'] ?? [];

        Log::warning('Stripe invoice payment failed', [
            'stripe_invoice_id' => $data['id'],
            'error' => $data['last_finalization_error']['message'] ?? 'Unknown error',
        ]);

        // Could send notification to customer here
    }

    /**
     * Handle subscription updated.
     */
    protected function handleSubscriptionUpdated(array $event): void
    {
        $data = $event['data']['object'] ?? [];

        Log::info('Stripe subscription updated', [
            'subscription_id' => $data['id'],
            'status' => $data['status'],
        ]);

        // Update subscription status in database if needed
    }

    /**
     * Handle subscription deleted.
     */
    protected function handleSubscriptionDeleted(array $event): void
    {
        $data = $event['data']['object'] ?? [];

        Log::info('Stripe subscription deleted', [
            'subscription_id' => $data['id'],
        ]);

        // Update subscription status in database if needed
    }
}
