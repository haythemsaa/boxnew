<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;

class StripePaymentService
{
    protected $stripeKey;
    protected $stripeSecret;

    public function __construct()
    {
        $this->stripeKey = config('services.stripe.public_key');
        $this->stripeSecret = config('services.stripe.secret_key');
    }

    /**
     * Create a Stripe customer for a customer.
     */
    public function createCustomer(string $email, string $name, array $metadata = []): ?string
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecret);

            $customer = \Stripe\Customer::create([
                'email' => $email,
                'name' => $name,
                'metadata' => $metadata,
            ]);

            Log::info('Stripe customer created', [
                'email' => $email,
                'stripe_customer_id' => $customer->id,
            ]);

            return $customer->id;
        } catch (\Exception $e) {
            Log::error('Failed to create Stripe customer', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a payment intent for an invoice.
     */
    public function createPaymentIntent(Invoice $invoice, ?string $stripeCustomerId = null): ?array
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecret);

            $params = [
                'amount' => intval($invoice->total * 100), // Convert to cents
                'currency' => 'eur',
                'description' => "Invoice {$invoice->invoice_number}",
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'tenant_id' => $invoice->tenant_id,
                    'customer_id' => $invoice->customer_id,
                ],
            ];

            if ($stripeCustomerId) {
                $params['customer'] = $stripeCustomerId;
            }

            $intent = \Stripe\PaymentIntent::create($params);

            Log::info('Stripe payment intent created', [
                'invoice_id' => $invoice->id,
                'intent_id' => $intent->id,
                'amount' => $invoice->total,
            ]);

            return [
                'client_secret' => $intent->client_secret,
                'intent_id' => $intent->id,
                'amount' => $invoice->total,
                'status' => $intent->status,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create payment intent', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify and complete a payment intent.
     */
    public function verifyPaymentIntent(string $intentId): ?array
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecret);

            $intent = \Stripe\PaymentIntent::retrieve($intentId);

            if ($intent->status === 'succeeded') {
                return [
                    'success' => true,
                    'intent_id' => $intent->id,
                    'amount' => $intent->amount / 100,
                    'status' => $intent->status,
                    'charge_id' => $intent->charges->data[0]->id ?? null,
                ];
            }

            return [
                'success' => false,
                'intent_id' => $intent->id,
                'status' => $intent->status,
                'error' => 'Payment not completed',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to verify payment intent', [
                'intent_id' => $intentId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a setup intent for recurring payments.
     */
    public function createSetupIntent(string $stripeCustomerId): ?array
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecret);

            $intent = \Stripe\SetupIntent::create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
            ]);

            return [
                'client_secret' => $intent->client_secret,
                'intent_id' => $intent->id,
                'status' => $intent->status,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create setup intent', [
                'customer_id' => $stripeCustomerId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create a subscription for recurring billing.
     */
    public function createSubscription(string $stripeCustomerId, string $priceId, array $metadata = []): ?array
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecret);

            $subscription = \Stripe\Subscription::create([
                'customer' => $stripeCustomerId,
                'items' => [
                    ['price' => $priceId],
                ],
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => $metadata,
            ]);

            Log::info('Stripe subscription created', [
                'subscription_id' => $subscription->id,
                'customer_id' => $stripeCustomerId,
            ]);

            return [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to create subscription', [
                'customer_id' => $stripeCustomerId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecret);

            $subscription = \Stripe\Subscription::retrieve($subscriptionId);
            $subscription->cancel();

            Log::info('Stripe subscription cancelled', [
                'subscription_id' => $subscriptionId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Retrieve invoice from Stripe.
     */
    public function retrieveInvoice(string $invoiceId): ?array
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecret);

            $invoice = \Stripe\Invoice::retrieve($invoiceId);

            return [
                'id' => $invoice->id,
                'amount_paid' => $invoice->amount_paid / 100,
                'amount_due' => $invoice->amount_due / 100,
                'status' => $invoice->status,
                'url' => $invoice->hosted_invoice_url,
                'paid_at' => $invoice->paid_at,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to retrieve invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Refund a charge.
     */
    public function refundCharge(string $chargeId, int $amountCents = null): ?array
    {
        try {
            \Stripe\Stripe::setApiKey($this->stripeSecret);

            $params = [];
            if ($amountCents) {
                $params['amount'] = $amountCents;
            }

            $refund = \Stripe\Refund::create([
                'charge' => $chargeId,
                ...$params,
            ]);

            Log::info('Stripe charge refunded', [
                'charge_id' => $chargeId,
                'refund_id' => $refund->id,
            ]);

            return [
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to refund charge', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get Stripe public key for frontend.
     */
    public function getPublicKey(): string
    {
        return $this->stripeKey;
    }
}
