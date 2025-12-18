<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['individual', 'company']);
        $civility = fake()->randomElement(['mr', 'mrs', 'ms']);

        return [
            'tenant_id' => Tenant::factory(),
            'type' => $type,
            'civility' => $civility,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'mobile' => fake()->optional()->phoneNumber(),
            'birth_date' => fake()->optional()->dateTimeBetween('-70 years', '-18 years'),
            'company_name' => $type === 'company' ? fake()->company() : null,
            'vat_number' => $type === 'company' ? 'FR' . fake()->numberBetween(10000000000, 99999999999) : null,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country' => 'France',
            'status' => 'active',
            'credit_score' => fake()->optional()->numberBetween(300, 850),
            'outstanding_balance' => 0,
            'total_contracts' => 0,
            'total_revenue' => 0,
        ];
    }

    public function individual(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'individual',
            'company_name' => null,
            'vat_number' => null,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'company',
            'company_name' => fake()->company(),
            'vat_number' => 'FR' . fake()->numberBetween(10000000000, 99999999999),
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    public function withBalance(float $amount = 150.00): static
    {
        return $this->state(fn (array $attributes) => [
            'outstanding_balance' => $amount,
        ]);
    }
}
