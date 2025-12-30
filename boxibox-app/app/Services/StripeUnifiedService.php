<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;
use Illuminate\Support\Facades\Log;

/**
 * Unified Stripe Service
 * Consolidates StripeService, StripePaymentService, and StripeConnectService
 */
class StripeUnifiedService
{
    protected StripeClient $stripe;
    protected string $publicKey;
    protected string $webhookSecret;
    protected int $timeout = 30; // seconds

    public function __construct()
    {
        $secretKey = config('services.stripe.secret') ?? config('services.stripe.secret_key');
        $this->publicKey = config('services.stripe.public_key') ?? config('services.stripe.key') ?? '';
        $this->webhookSecret = config('services.stripe.webhook_secret') ?? '';

        $this->stripe = new StripeClient([
            'api_key' => $secretKey,
            'stripe_version' => '2023-10-16',
        ]);
    }

    // ============================================
    // CUSTOMER MANAGEMENT
    // ============================================

    /**
     * Get or create Stripe customer from local Customer model
     */
    public function getOrCreateCustomer(Customer $customer): string
    {
        if ($customer->stripe_customer_id) {
            return $customer->stripe_customer_id;
        }

        try {
            $stripeCustomer = $this->stripe->customers->create([
                'email' => $customer->email,
                'name' => $customer->full_name,
                'phone' => $customer->phone ?? $customer->mobile,
                'metadata' => [
                    'customer_id' => $customer->id,
                    'tenant_id' => $customer->tenant_id,
                ],
            ]);

            $customer->update(['stripe_customer_id' => $stripeCustomer->id]);

            Log::info('Stripe customer created', [
                'customer_id' => $customer->id,
                'stripe_customer_id' => $stripeCustomer->id,
            ]);

            return $stripeCustomer->id;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to create Stripe customer: " . $e->getMessage());
        }
    }

    /**
     * Create Stripe customer from array data (legacy support)
     */
    public function createCustomerFromArray(array $data): ?string
    {
        try {
            $stripeCustomer = $this->stripe->customers->create([
                'email' => $data['email'],
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'metadata' => $data['metadata'] ?? [],
            ]);

            Log::info('Stripe customer created from array', [
                'email' => $data['email'],
                'stripe_customer_id' => $stripeCustomer->id,
            ]);

            return $stripeCustomer->id;
        } catch (ApiErrorException $e) {
            Log::error('Failed to create Stripe customer', [
                'email' => $data['email'] ?? 'unknown',
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ============================================
    // PAYMENT INTENTS
    // ============================================

    /**
     * Create payment intent for Customer model
     */
    public function createPaymentIntent(
        Customer $customer,
        float $amount,
        array $options = []
    ): array {
        $stripeCustomerId = $this->getOrCreateCustomer($customer);

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($amount * 100),
                'currency' => $options['currency'] ?? 'eur',
                'customer' => $stripeCustomerId,
                'payment_method_types' => $options['payment_method_types'] ?? [
                    'card',
                    'sepa_debit',
                    'bancontact',
                    'ideal',
                ],
                'setup_future_usage' => ($options['save_payment_method'] ?? false) ? 'off_session' : null,
                'metadata' => array_merge([
                    'customer_id' => $customer->id,
                    'tenant_id' => $customer->tenant_id,
                ], $options['metadata'] ?? []),
            ]);

            Log::info('Payment intent created', [
                'customer_id' => $customer->id,
                'intent_id' => $paymentIntent->id,
                'amount' => $amount,
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create payment intent', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to create payment intent: " . $e->getMessage());
        }
    }

    /**
     * Create payment intent for Invoice
     */
    public function createPaymentIntentForInvoice(Invoice $invoice, ?string $stripeCustomerId = null): ?array
    {
        try {
            $params = [
                'amount' => (int) ($invoice->total * 100),
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

            $intent = $this->stripe->paymentIntents->create($params);

            Log::info('Payment intent created for invoice', [
                'invoice_id' => $invoice->id,
                'intent_id' => $intent->id,
            ]);

            return [
                'client_secret' => $intent->client_secret,
                'intent_id' => $intent->id,
                'amount' => $invoice->total,
                'status' => $intent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create payment intent for invoice', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Verify payment intent status
     */
    public function verifyPaymentIntent(string $intentId): ?array
    {
        try {
            $intent = $this->stripe->paymentIntents->retrieve($intentId);

            return [
                'success' => $intent->status === 'succeeded',
                'intent_id' => $intent->id,
                'amount' => $intent->amount / 100,
                'status' => $intent->status,
                'charge_id' => $intent->latest_charge ?? null,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to verify payment intent', [
                'intent_id' => $intentId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ============================================
    // SETUP INTENTS (Save Payment Methods)
    // ============================================

    /**
     * Create setup intent for saving payment method
     */
    public function createSetupIntent(Customer $customer): array
    {
        $stripeCustomerId = $this->getOrCreateCustomer($customer);

        try {
            $setupIntent = $this->stripe->setupIntents->create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card', 'sepa_debit'],
                'metadata' => [
                    'customer_id' => $customer->id,
                    'tenant_id' => $customer->tenant_id,
                ],
            ]);

            return [
                'client_secret' => $setupIntent->client_secret,
                'setup_intent_id' => $setupIntent->id,
                'status' => $setupIntent->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create setup intent', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to create setup intent: " . $e->getMessage());
        }
    }

    // ============================================
    // PAYMENT METHODS
    // ============================================

    /**
     * Save payment method for customer
     */
    public function savePaymentMethod(
        Customer $customer,
        string $paymentMethodId,
        bool $setAsDefault = false
    ): PaymentMethod {
        $stripeCustomerId = $this->getOrCreateCustomer($customer);

        try {
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $stripeCustomerId,
            ]);

            $pm = $this->stripe->paymentMethods->retrieve($paymentMethodId);

            if ($setAsDefault) {
                PaymentMethod::where('customer_id', $customer->id)
                    ->update(['is_default' => false]);
            }

            return PaymentMethod::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'gateway' => 'stripe',
                'gateway_method_id' => $paymentMethodId,
                'type' => $pm->type,
                'brand' => $pm->card->brand ?? $pm->sepa_debit->bank_code ?? null,
                'last4' => $pm->card->last4 ?? $pm->sepa_debit->last4 ?? null,
                'exp_month' => $pm->card->exp_month ?? null,
                'exp_year' => $pm->card->exp_year ?? null,
                'is_default' => $setAsDefault,
                'metadata' => (array) $pm->toArray(),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Failed to save payment method', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);
            throw new \Exception("Failed to save payment method: " . $e->getMessage());
        }
    }

    // ============================================
    // CHARGING
    // ============================================

    /**
     * Charge customer with saved payment method (off-session)
     */
    public function chargeCustomer(
        Customer $customer,
        float $amount,
        ?PaymentMethod $paymentMethod = null,
        array $metadata = []
    ): Payment {
        if (!$paymentMethod) {
            $paymentMethod = PaymentMethod::where('customer_id', $customer->id)
                ->where('is_default', true)
                ->first();

            if (!$paymentMethod) {
                throw new \Exception("No default payment method found");
            }
        }

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($amount * 100),
                'currency' => 'eur',
                'customer' => $customer->stripe_customer_id,
                'payment_method' => $paymentMethod->gateway_method_id,
                'off_session' => true,
                'confirm' => true,
                'metadata' => array_merge([
                    'customer_id' => $customer->id,
                    'tenant_id' => $customer->tenant_id,
                ], $metadata),
            ]);

            return Payment::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod->type,
                'payment_gateway' => 'stripe',
                'gateway_payment_id' => $paymentIntent->id,
                'gateway_customer_id' => $customer->stripe_customer_id,
                'payment_intent_id' => $paymentIntent->id,
                'status' => $paymentIntent->status === 'succeeded' ? 'completed' : 'pending',
                'gateway_metadata' => (array) $paymentIntent->toArray(),
            ]);
        } catch (ApiErrorException $e) {
            Log::error('Failed to charge customer', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return Payment::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'amount' => $amount,
                'payment_method' => $paymentMethod->type,
                'payment_gateway' => 'stripe',
                'status' => 'failed',
                'notes' => $e->getMessage(),
                'gateway_metadata' => ['error' => $e->getMessage()],
            ]);
        }
    }

    // ============================================
    // SUBSCRIPTIONS
    // ============================================

    /**
     * Create subscription
     */
    public function createSubscription(string $stripeCustomerId, string $priceId, array $metadata = []): ?array
    {
        try {
            $subscription = $this->stripe->subscriptions->create([
                'customer' => $stripeCustomerId,
                'items' => [['price' => $priceId]],
                'payment_behavior' => 'default_incomplete',
                'expand' => ['latest_invoice.payment_intent'],
                'metadata' => $metadata,
            ]);

            Log::info('Subscription created', [
                'subscription_id' => $subscription->id,
                'customer_id' => $stripeCustomerId,
            ]);

            return [
                'subscription_id' => $subscription->id,
                'status' => $subscription->status,
                'current_period_end' => $subscription->current_period_end,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create subscription', [
                'customer_id' => $stripeCustomerId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        try {
            $this->stripe->subscriptions->cancel($subscriptionId);
            Log::info('Subscription cancelled', ['subscription_id' => $subscriptionId]);
            return true;
        } catch (ApiErrorException $e) {
            Log::error('Failed to cancel subscription', [
                'subscription_id' => $subscriptionId,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    // ============================================
    // REFUNDS
    // ============================================

    /**
     * Create refund for payment
     */
    public function createRefund(Payment $payment, ?float $amount = null): array
    {
        if (!$payment->payment_intent_id) {
            throw new \Exception("Payment intent ID not found");
        }

        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $payment->payment_intent_id,
                'amount' => $amount ? (int) ($amount * 100) : null,
                'metadata' => [
                    'payment_id' => $payment->id,
                    'tenant_id' => $payment->tenant_id,
                ],
            ]);

            $payment->update([
                'status' => $amount && $amount < $payment->amount ? 'partially_refunded' : 'refunded',
            ]);

            Log::info('Refund created', [
                'payment_id' => $payment->id,
                'refund_id' => $refund->id,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to create refund', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Refund by charge ID (legacy support)
     */
    public function refundCharge(string $chargeId, ?int $amountCents = null): ?array
    {
        try {
            $params = ['charge' => $chargeId];
            if ($amountCents) {
                $params['amount'] = $amountCents;
            }

            $refund = $this->stripe->refunds->create($params);

            return [
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to refund charge', [
                'charge_id' => $chargeId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    // ============================================
    // WEBHOOKS
    // ============================================

    /**
     * Verify and construct webhook event
     */
    public function constructWebhookEvent(string $payload, string $signature): \Stripe\Event
    {
        if (empty($this->webhookSecret)) {
            throw new \Exception("Webhook secret not configured");
        }

        return \Stripe\Webhook::constructEvent($payload, $signature, $this->webhookSecret);
    }

    // ============================================
    // ANALYTICS
    // ============================================

    /**
     * Get payment analytics for tenant
     */
    public function getPaymentAnalytics(int $tenantId, $startDate, $endDate): array
    {
        $payments = Payment::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $total = $payments->sum('amount');
        $successful = $payments->where('status', 'completed')->count();
        $failed = $payments->where('status', 'failed')->count();
        $totalAttempts = $payments->count();

        return [
            'total_amount' => $total,
            'total_payments' => $totalAttempts,
            'successful_payments' => $successful,
            'failed_payments' => $failed,
            'success_rate' => $totalAttempts > 0 ? round(($successful / $totalAttempts) * 100, 2) : 0,
            'average_payment' => $totalAttempts > 0 ? round($total / $totalAttempts, 2) : 0,
            'by_method' => $payments->groupBy('payment_method')->map->count(),
            'by_status' => $payments->groupBy('status')->map->count(),
        ];
    }

    // ============================================
    // UTILITIES
    // ============================================

    /**
     * Get public key for frontend
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Retrieve Stripe invoice
     */
    public function retrieveStripeInvoice(string $invoiceId): ?array
    {
        try {
            $invoice = $this->stripe->invoices->retrieve($invoiceId);

            return [
                'id' => $invoice->id,
                'amount_paid' => $invoice->amount_paid / 100,
                'amount_due' => $invoice->amount_due / 100,
                'status' => $invoice->status,
                'url' => $invoice->hosted_invoice_url,
            ];
        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve Stripe invoice', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
