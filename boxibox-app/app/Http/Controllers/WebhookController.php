<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\StripeService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    protected StripeService $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Handle Stripe webhook events
     */
    public function handleStripe(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');

        // SECURITY: Validate webhook secret is configured
        if (empty($webhookSecret)) {
            Log::error('Stripe webhook secret not configured');
            return response()->json(['error' => 'Webhook not configured'], 500);
        }

        // SECURITY: Validate signature header is present
        if (empty($signature)) {
            Log::warning('Stripe webhook missing signature header');
            return response()->json(['error' => 'Missing signature'], 400);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $webhookSecret
            );

            // Handle the event
            switch ($event->type) {
                case 'payment_intent.succeeded':
                    $this->handlePaymentIntentSucceeded($event->data->object);
                    break;

                case 'payment_intent.payment_failed':
                    $this->handlePaymentIntentFailed($event->data->object);
                    break;

                case 'charge.succeeded':
                    $this->handleChargeSucceeded($event->data->object);
                    break;

                case 'charge.failed':
                    $this->handleChargeFailed($event->data->object);
                    break;

                case 'customer.created':
                    $this->handleCustomerCreated($event->data->object);
                    break;

                case 'customer.updated':
                    $this->handleCustomerUpdated($event->data->object);
                    break;

                case 'invoice.payment_succeeded':
                    $this->handleInvoicePaymentSucceeded($event->data->object);
                    break;

                case 'invoice.payment_failed':
                    $this->handleInvoicePaymentFailed($event->data->object);
                    break;

                default:
                    Log::info('Unhandled webhook event type: ' . $event->type);
            }

            return response()->json(['status' => 'success']);

        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);
            return response()->json(['error' => 'Invalid signature'], 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook invalid payload', [
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'Invalid payload'], 400);
        } catch (\Exception $e) {
            Log::error('Stripe webhook processing error: ' . $e->getMessage());
            return response()->json(['error' => 'Processing error'], 500);
        }
    }

    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        Log::info('Payment Intent Succeeded', [
            'payment_intent_id' => $paymentIntent->id,
            'amount' => $paymentIntent->amount / 100,
        ]);

        // Find payment by transaction_id and update status
        if (isset($paymentIntent->metadata['payment_id'])) {
            $payment = Payment::find($paymentIntent->metadata['payment_id']);
            if ($payment) {
                $payment->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                    'transaction_id' => $paymentIntent->id,
                ]);

                // Update related invoice
                if ($payment->invoice_id) {
                    $invoice = $payment->invoice;
                    $invoice->paid_amount += $payment->amount;
                    $invoice->balance = $invoice->total - $invoice->paid_amount;

                    if ($invoice->balance <= 0) {
                        $invoice->status = 'paid';
                        $invoice->paid_at = now();
                    }

                    $invoice->save();
                }
            }
        }
    }

    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        Log::error('Payment Intent Failed', [
            'payment_intent_id' => $paymentIntent->id,
            'error' => $paymentIntent->last_payment_error,
        ]);

        if (isset($paymentIntent->metadata['payment_id'])) {
            $payment = Payment::find($paymentIntent->metadata['payment_id']);
            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'notes' => $paymentIntent->last_payment_error->message ?? 'Payment failed',
                ]);
            }
        }
    }

    protected function handleChargeSucceeded($charge): void
    {
        Log::info('Charge Succeeded', [
            'charge_id' => $charge->id,
            'amount' => $charge->amount / 100,
        ]);
    }

    protected function handleChargeFailed($charge): void
    {
        Log::error('Charge Failed', [
            'charge_id' => $charge->id,
            'failure_message' => $charge->failure_message,
        ]);
    }

    protected function handleCustomerCreated($customer): void
    {
        Log::info('Stripe Customer Created', [
            'customer_id' => $customer->id,
            'email' => $customer->email,
        ]);
    }

    protected function handleCustomerUpdated($customer): void
    {
        Log::info('Stripe Customer Updated', [
            'customer_id' => $customer->id,
        ]);
    }

    protected function handleInvoicePaymentSucceeded($stripeInvoice): void
    {
        Log::info('Invoice Payment Succeeded', [
            'invoice_id' => $stripeInvoice->id,
        ]);
    }

    protected function handleInvoicePaymentFailed($stripeInvoice): void
    {
        Log::error('Invoice Payment Failed', [
            'invoice_id' => $stripeInvoice->id,
        ]);
    }
}
