<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        $amount = fake()->randomFloat(2, 50, 500);
        $paidAt = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'invoice_id' => Invoice::factory(),
            'contract_id' => Contract::factory(),
            'payment_number' => 'PAY-' . strtoupper(fake()->unique()->bothify('????????')),
            'type' => 'payment',
            'status' => 'completed',
            'amount' => $amount,
            'fee' => 0,
            'currency' => 'EUR',
            'method' => fake()->randomElement(['card', 'sepa', 'bank_transfer', 'cash']),
            'payment_method' => fake()->randomElement(['visa', 'mastercard', 'sepa_debit']),
            'gateway' => 'stripe',
            'paid_at' => $paidAt,
            'processed_at' => $paidAt,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'paid_at' => now(),
            'processed_at' => now(),
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_at' => null,
            'processed_at' => null,
        ]);
    }

    public function failed(string $code = 'card_declined', string $message = 'Your card was declined'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
            'paid_at' => null,
            'failed_at' => now(),
            'failure_code' => $code,
            'failure_message' => $message,
        ]);
    }

    public function refunded(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'refunded',
            'refunded_amount' => $attributes['amount'] ?? 100,
        ]);
    }

    public function refund(float $amount = null): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'refund',
            'amount' => -abs($amount ?? $attributes['amount'] ?? 100),
            'status' => 'completed',
        ]);
    }

    public function card(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'card',
            'gateway' => 'stripe',
            'card_brand' => fake()->randomElement(['visa', 'mastercard']),
            'card_last_four' => fake()->numerify('####'),
        ]);
    }

    public function sepa(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'sepa',
            'gateway' => 'gocardless',
            'payment_method' => 'sepa_debit',
        ]);
    }

    public function bankTransfer(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'bank_transfer',
            'gateway' => null,
        ]);
    }

    public function cash(): static
    {
        return $this->state(fn (array $attributes) => [
            'method' => 'cash',
            'gateway' => null,
        ]);
    }

    public function withStripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'gateway' => 'stripe',
            'gateway_payment_id' => 'pi_' . fake()->regexify('[A-Za-z0-9]{24}'),
            'stripe_payment_intent_id' => 'pi_' . fake()->regexify('[A-Za-z0-9]{24}'),
        ]);
    }
}
