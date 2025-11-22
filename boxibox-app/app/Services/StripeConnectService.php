<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class StripeConnectService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    /**
     * Create or retrieve Stripe customer
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
                'phone' => $customer->phone,
                'metadata' => [
                    'customer_id' => $customer->id,
                    'tenant_id' => $customer->tenant_id,
                ],
            ]);

            $customer->update(['stripe_customer_id' => $stripeCustomer->id]);

            return $stripeCustomer->id;
        } catch (ApiErrorException $e) {
            throw new \Exception("Failed to create Stripe customer: " . $e->getMessage());
        }
    }

    /**
     * Create payment intent
     */
    public function createPaymentIntent(
        Customer $customer,
        float $amount,
        array $options = []
    ): array {
        $stripeCustomerId = $this->getOrCreateCustomer($customer);

        try {
            $paymentIntent = $this->stripe->paymentIntents->create([
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => $options['currency'] ?? 'eur',
                'customer' => $stripeCustomerId,
                'payment_method_types' => $options['payment_method_types'] ?? [
                    'card',
                    'sepa_debit',
                    'bancontact',
                    'ideal',
                    'giropay',
                ],
                'setup_future_usage' => $options['save_payment_method'] ?? true ? 'off_session' : null,
                'metadata' => array_merge([
                    'customer_id' => $customer->id,
                    'tenant_id' => $customer->tenant_id,
                ], $options['metadata'] ?? []),
            ]);

            return [
                'client_secret' => $paymentIntent->client_secret,
                'payment_intent_id' => $paymentIntent->id,
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception("Failed to create payment intent: " . $e->getMessage());
        }
    }

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
            ];
        } catch (ApiErrorException $e) {
            throw new \Exception("Failed to create setup intent: " . $e->getMessage());
        }
    }

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
            // Attach payment method to customer
            $this->stripe->paymentMethods->attach($paymentMethodId, [
                'customer' => $stripeCustomerId,
            ]);

            // Get payment method details
            $pm = $this->stripe->paymentMethods->retrieve($paymentMethodId);

            // If setting as default, unset other defaults
            if ($setAsDefault) {
                PaymentMethod::where('customer_id', $customer->id)
                    ->update(['is_default' => false]);
            }

            // Create local payment method record
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
            throw new \Exception("Failed to save payment method: " . $e->getMessage());
        }
    }

    /**
     * Charge customer with saved payment method
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
                throw new \Exception("No default payment method found for customer");
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

            // Create payment record
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
            // Create failed payment record
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

    /**
     * Handle failed payment retry
     */
    public function retryFailedPayment(Payment $payment): Payment
    {
        if ($payment->retry_count >= 3) {
            throw new \Exception("Maximum retry attempts reached");
        }

        $customer = $payment->customer;
        $paymentMethod = PaymentMethod::where('customer_id', $customer->id)
            ->where('is_default', true)
            ->first();

        if (!$paymentMethod) {
            throw new \Exception("No payment method available for retry");
        }

        $payment->update([
            'retry_count' => $payment->retry_count + 1,
            'next_retry_at' => now()->addDays($payment->retry_count * 3), // Day 3, 6, 9
        ]);

        return $this->chargeCustomer($customer, $payment->amount, $paymentMethod, [
            'retry_attempt' => $payment->retry_count,
            'original_payment_id' => $payment->id,
        ]);
    }

    /**
     * Create refund
     */
    public function createRefund(Payment $payment, ?float $amount = null): array
    {
        if (!$payment->payment_intent_id) {
            throw new \Exception("Payment intent ID not found");
        }

        try {
            $refund = $this->stripe->refunds->create([
                'payment_intent' => $payment->payment_intent_id,
                'amount' => $amount ? (int) ($amount * 100) : null, // Null = full refund
                'metadata' => [
                    'payment_id' => $payment->id,
                    'tenant_id' => $payment->tenant_id,
                ],
            ]);

            $payment->update([
                'status' => $amount && $amount < $payment->amount ? 'partially_refunded' : 'refunded',
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
            ];
        } catch (ApiErrorException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get payment analytics
     */
    public function getPaymentAnalytics($tenantId, $startDate, $endDate): array
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
            'success_rate' => $totalAttempts > 0 ? ($successful / $totalAttempts) * 100 : 0,
            'average_payment' => $totalAttempts > 0 ? $total / $totalAttempts : 0,
            'by_method' => $payments->groupBy('payment_method')->map->count(),
            'by_status' => $payments->groupBy('status')->map->count(),
        ];
    }
}
