<?php

namespace Database\Factories;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $name = fake()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name) . '-' . fake()->unique()->numberBetween(1000, 9999),
            'domain' => fake()->optional()->domainName(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country' => 'France',
            'plan' => fake()->randomElement([
                Tenant::PLAN_STARTER,
                Tenant::PLAN_PROFESSIONAL,
                Tenant::PLAN_BUSINESS,
            ]),
            'widget_level' => fake()->randomElement([
                Tenant::WIDGET_NONE,
                Tenant::WIDGET_BASIC,
                Tenant::WIDGET_PRO,
            ]),
            'billing_cycle' => fake()->randomElement([
                Tenant::BILLING_MONTHLY,
                Tenant::BILLING_YEARLY,
            ]),
            'max_sites' => fake()->numberBetween(1, 10),
            'max_boxes' => fake()->numberBetween(50, 500),
            'max_users' => fake()->numberBetween(3, 20),
            'is_active' => true,
            'settings' => [],
            'features' => [],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function trial(): static
    {
        return $this->state(fn (array $attributes) => [
            'trial_ends_at' => now()->addDays(14),
        ]);
    }

    public function starter(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => Tenant::PLAN_STARTER,
            'max_sites' => 1,
            'max_boxes' => 100,
            'max_users' => 3,
        ]);
    }

    public function professional(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => Tenant::PLAN_PROFESSIONAL,
            'max_sites' => 2,
            'max_boxes' => 300,
            'max_users' => 10,
        ]);
    }

    public function business(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => Tenant::PLAN_BUSINESS,
            'max_sites' => 5,
            'max_boxes' => 1000,
            'max_users' => 25,
        ]);
    }

    public function enterprise(): static
    {
        return $this->state(fn (array $attributes) => [
            'plan' => Tenant::PLAN_ENTERPRISE,
            'max_sites' => null,
            'max_boxes' => null,
            'max_users' => null,
            'widget_level' => Tenant::WIDGET_WHITELABEL,
        ]);
    }
}
