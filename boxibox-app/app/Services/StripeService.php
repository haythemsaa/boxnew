<?php

namespace App\Services;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;
use Stripe\Charge;
use Exception;

/**
 * @deprecated Use StripeUnifiedService instead
 * This class is kept for backwards compatibility
 */
class StripeService
{
    protected StripeUnifiedService $unifiedService;

    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
        $this->unifiedService = app(StripeUnifiedService::class);
    }

    /**
     * Create or retrieve Stripe customer
     */
    public function createCustomer(array $data): Customer
    {
        return Customer::create([
            'email' => $data['email'],
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    /**
     * Create payment intent
     */
    public function createPaymentIntent(float $amount, string $currency = 'eur', array $options = []): PaymentIntent
    {
        return PaymentIntent::create([
            'amount' => (int)($amount * 100), // Convert to cents
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            ...$options,
        ]);
    }

    /**
     * Attach payment method to customer
     */
    public function attachPaymentMethod(string $paymentMethodId, string $customerId): PaymentMethod
    {
        return PaymentMethod::retrieve($paymentMethodId)->attach([
            'customer' => $customerId,
        ]);
    }

    /**
     * Charge customer
     */
    public function chargeCustomer(string $customerId, float $amount, string $currency = 'eur', array $metadata = []): Charge
    {
        return Charge::create([
            'amount' => (int)($amount * 100),
            'currency' => $currency,
            'customer' => $customerId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Process webhook event
     */
    public function handleWebhook(string $payload, string $signature): void
    {
        $endpoint_secret = config('services.stripe.webhook_secret');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                $endpoint_secret
            );

            // Handle the event based on type
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
                default:
                    // Unhandled event type
            }
        } catch (Exception $e) {
            throw $e;
        }
    }

    protected function handlePaymentIntentSucceeded($paymentIntent): void
    {
        // Update payment record in database
        \Log::info('Payment succeeded', ['payment_intent' => $paymentIntent->id]);
    }

    protected function handlePaymentIntentFailed($paymentIntent): void
    {
        // Handle failed payment
        \Log::error('Payment failed', ['payment_intent' => $paymentIntent->id]);
    }

    protected function handleChargeSucceeded($charge): void
    {
        // Update payment record
        \Log::info('Charge succeeded', ['charge' => $charge->id]);
    }
}
