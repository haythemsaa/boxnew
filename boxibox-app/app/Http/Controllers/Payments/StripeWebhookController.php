<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     * SECURITY: Validates Stripe signature before processing.
     */
    public function handle(Request $request): Response
    {
        // Verify Stripe webhook signature (CRITICAL for security)
        $webhookSecret = config('services.stripe.webhook_secret');
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        if (!$webhookSecret) {
            Log::error('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        if (!$sigHeader) {
            Log::warning('Stripe webhook missing signature header');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook parsing failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid payload'], 400);
        }

        $eventType = $event->type;
        $eventData = $event->data->object->toArray();

        Log::info('Stripe webhook verified', [
            'event_type' => $eventType,
            'event_id' => $event->id,
        ]);

        match ($eventType) {
            'payment_intent.succeeded' => $this->handlePaymentIntentSucceeded($eventData),
            'payment_intent.payment_failed' => $this->handlePaymentIntentFailed($eventData),
            'invoice.paid' => $this->handleInvoicePaid($eventData),
            'invoice.payment_failed' => $this->handleInvoicePaymentFailed($eventData),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($eventData),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($eventData),
            default => Log::info('Unhandled Stripe webhook event', ['type' => $eventType]),
        };

        return response()->json(['success' => true], 200);
    }

    /**
     * Handle payment intent succeeded.
     * SECURITY: Uses DB transaction with lock to prevent duplicate payments.
     */
    protected function handlePaymentIntentSucceeded(array $data): void
    {
        $invoiceId = $data['metadata']['invoice_id'] ?? null;
        $paymentIntentId = $data['id'] ?? null;

        if (!$invoiceId || !$paymentIntentId) {
            return;
        }

        // Safely convert amount from cents
        $amount = isset($data['amount_received']) && is_numeric($data['amount_received'])
            ? $data['amount_received'] / 100
            : 0;

        if ($amount <= 0) {
            Log::error('Invalid amount in Stripe webhook', ['data' => $data]);
            return;
        }

        // Use transaction with lock to prevent race condition (duplicate payments)
        DB::transaction(function () use ($invoiceId, $paymentIntentId, $amount, $data) {
            // Lock the invoice row
            $invoice = Invoice::lockForUpdate()->find($invoiceId);
            if (!$invoice) {
                Log::warning('Invoice not found for payment intent', ['invoice_id' => $invoiceId]);
                return;
            }

            // Check for existing payment with same intent (idempotency)
            $existingPayment = Payment::where('stripe_payment_intent_id', $paymentIntentId)
                ->lockForUpdate()
                ->first();

            if ($existingPayment) {
                Log::info('Payment already processed for intent', ['intent_id' => $paymentIntentId]);
                return;
            }

            // Create payment record
            Payment::create([
                'invoice_id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id,
                'customer_id' => $invoice->customer_id,
                'amount' => $amount,
                'payment_method' => 'stripe',
                'stripe_charge_id' => $data['charges']['data'][0]['id'] ?? null,
                'stripe_payment_intent_id' => $paymentIntentId,
                'status' => 'completed',
                'paid_at' => now(),
            ]);

            // Update invoice status
            $remaining = $invoice->total - ($invoice->amount_paid + $amount);

            if ($remaining <= 0) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => now(),
                    'amount_paid' => $invoice->total,
                ]);
                Log::info('Invoice marked as paid via Stripe webhook', ['invoice_id' => $invoiceId]);
            } else {
                $invoice->update([
                    'status' => 'partial',
                    'amount_paid' => $invoice->amount_paid + $amount,
                ]);
                Log::info('Partial payment recorded via Stripe webhook', ['invoice_id' => $invoiceId, 'amount' => $amount]);
            }
        });
    }

    /**
     * Handle payment intent failed.
     */
    protected function handlePaymentIntentFailed(array $data): void
    {
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
    protected function handleInvoicePaid(array $data): void
    {
        $amount = ($data['amount_paid'] ?? 0) / 100; // Convert from cents

        Log::info('Stripe invoice paid', [
            'stripe_invoice_id' => $data['id'],
            'amount' => $amount,
        ]);

        // Can be used for recurring payment tracking
    }

    /**
     * Handle invoice payment failed.
     */
    protected function handleInvoicePaymentFailed(array $data): void
    {
        Log::warning('Stripe invoice payment failed', [
            'stripe_invoice_id' => $data['id'] ?? 'unknown',
            'error' => $data['last_finalization_error']['message'] ?? 'Unknown error',
        ]);

        // Could send notification to customer here
    }

    /**
     * Handle subscription updated.
     */
    protected function handleSubscriptionUpdated(array $data): void
    {
        Log::info('Stripe subscription updated', [
            'subscription_id' => $data['id'] ?? 'unknown',
            'status' => $data['status'] ?? 'unknown',
        ]);

        // Update subscription status in database if needed
    }

    /**
     * Handle subscription deleted.
     */
    protected function handleSubscriptionDeleted(array $data): void
    {
        Log::info('Stripe subscription deleted', [
            'subscription_id' => $data['id'] ?? 'unknown',
        ]);

        // Update subscription status in database if needed
    }
}
