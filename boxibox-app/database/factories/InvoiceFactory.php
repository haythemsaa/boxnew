<?php

namespace Database\Factories;

use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 50, 500);
        $taxRate = 20.00;
        $taxAmount = $subtotal * ($taxRate / 100);
        $total = $subtotal + $taxAmount;
        $invoiceDate = fake()->dateTimeBetween('-3 months', 'now');
        $dueDate = (clone $invoiceDate)->modify('+30 days');

        return [
            'tenant_id' => Tenant::factory(),
            'customer_id' => Customer::factory(),
            'contract_id' => Contract::factory(),
            'invoice_number' => 'FAC' . date('Ym') . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'type' => 'rent',
            'status' => 'pending',
            'invoice_date' => $invoiceDate,
            'due_date' => $dueDate,
            'period_start' => $invoiceDate,
            'period_end' => (clone $invoiceDate)->modify('+1 month'),
            'items' => [
                [
                    'description' => 'Location mensuelle Box',
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'total' => $subtotal,
                ],
            ],
            'subtotal' => $subtotal,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'discount_amount' => 0,
            'total' => $total,
            'paid_amount' => 0,
            'currency' => 'EUR',
            'is_recurring' => false,
            'reminder_count' => 0,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'paid_amount' => 0,
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'paid_amount' => 0,
        ]);
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'paid_amount' => $attributes['total'] ?? 100,
            'paid_at' => now(),
        ]);
    }

    public function partial(float $amount = 50): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'partial',
            'paid_amount' => $amount,
        ]);
    }

    public function overdue(int $daysOverdue = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => now()->subDays($daysOverdue),
            'paid_amount' => 0,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function withAmount(float $subtotal): static
    {
        $taxAmount = $subtotal * 0.20;
        $total = $subtotal + $taxAmount;

        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
            'items' => [
                [
                    'description' => 'Location mensuelle Box',
                    'quantity' => 1,
                    'unit_price' => $subtotal,
                    'total' => $subtotal,
                ],
            ],
        ]);
    }

    public function recurring(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_recurring' => true,
            'type' => 'rent',
        ]);
    }
}
