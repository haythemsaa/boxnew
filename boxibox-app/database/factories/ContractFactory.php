<?php

namespace Database\Factories;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Contract>
 */
class ContractFactory extends Factory
{
    protected $model = Contract::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-6 months', 'now');
        $endDate = fake()->optional(0.7)->dateTimeBetween($startDate, '+2 years');
        $monthlyPrice = fake()->randomElement([49, 79, 99, 119, 149, 179, 199, 249]);

        return [
            'tenant_id' => Tenant::factory(),
            'site_id' => Site::factory(),
            'customer_id' => Customer::factory(),
            'box_id' => Box::factory(),
            'contract_number' => 'CTR' . date('Y') . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'status' => 'active',
            'type' => 'standard',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'notice_period_days' => 30,
            'auto_renew' => fake()->boolean(60),
            'renewal_period' => 'monthly',
            'monthly_price' => $monthlyPrice,
            'deposit_amount' => $monthlyPrice * 2,
            'deposit_paid' => true,
            'discount_percentage' => 0,
            'discount_amount' => 0,
            'billing_frequency' => 'monthly',
            'billing_day' => fake()->numberBetween(1, 28),
            'payment_method' => fake()->randomElement(['card', 'sepa', 'bank_transfer']),
            'auto_pay' => fake()->boolean(70),
            'signed_by_customer' => true,
            'customer_signed_at' => $startDate,
            'signed_by_staff' => true,
            'staff_signed_at' => $startDate,
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'signed_by_customer' => false,
            'customer_signed_at' => null,
            'signed_by_staff' => false,
            'staff_signed_at' => null,
        ]);
    }

    public function terminated(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'terminated',
            'actual_end_date' => now(),
            'termination_reason' => fake()->randomElement(['customer_request', 'non_payment', 'end_of_term']),
        ]);
    }

    public function expiringSoon(int $days = 15): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'end_date' => now()->addDays($days),
        ]);
    }

    public function withDiscount(float $percentage = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_percentage' => $percentage,
        ]);
    }

    public function unsigned(): static
    {
        return $this->state(fn (array $attributes) => [
            'signed_by_customer' => false,
            'customer_signed_at' => null,
            'signed_by_staff' => false,
            'staff_signed_at' => null,
        ]);
    }
}
