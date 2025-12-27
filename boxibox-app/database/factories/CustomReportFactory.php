<?php

namespace Database\Factories;

use App\Models\CustomReport;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CustomReport>
 */
class CustomReportFactory extends Factory
{
    protected $model = CustomReport::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'created_by' => User::factory(),
            'name' => $this->faker->words(3, true) . ' Report',
            'description' => $this->faker->sentence(),
            'type' => $this->faker->randomElement(['rent_roll', 'revenue', 'occupancy', 'aging', 'activity']),
            'columns' => ['name', 'value', 'date'],
            'filters' => [],
            'grouping' => [],
            'sorting' => [['column' => 'name', 'direction' => 'asc']],
            'is_public' => false,
            'is_favorite' => false,
        ];
    }

    /**
     * Indicate the report is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate the report is a favorite.
     */
    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_favorite' => true,
        ]);
    }

    /**
     * Set specific report type.
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => $type,
        ]);
    }
}
