<?php

namespace Database\Factories;

use App\Models\Box;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Box>
 */
class BoxFactory extends Factory
{
    protected $model = Box::class;

    public function definition(): array
    {
        $sizes = [
            ['length' => 1, 'width' => 1, 'height' => 2, 'price' => 29],
            ['length' => 2, 'width' => 1, 'height' => 2, 'price' => 49],
            ['length' => 2, 'width' => 2, 'height' => 2.5, 'price' => 79],
            ['length' => 3, 'width' => 2, 'height' => 2.5, 'price' => 119],
            ['length' => 4, 'width' => 3, 'height' => 2.5, 'price' => 179],
            ['length' => 5, 'width' => 4, 'height' => 2.5, 'price' => 249],
        ];

        $size = fake()->randomElement($sizes);
        $volume = $size['length'] * $size['width'] * $size['height'];

        return [
            'tenant_id' => Tenant::factory(),
            'site_id' => Site::factory(),
            'name' => 'Box ' . strtoupper(fake()->randomLetter()) . fake()->numberBetween(1, 99),
            'description' => fake()->optional()->sentence(),
            'length' => $size['length'],
            'width' => $size['width'],
            'height' => $size['height'],
            'volume' => $volume,
            'status' => 'available',
            'base_price' => $size['price'],
            'current_price' => $size['price'],
            'climate_controlled' => fake()->boolean(20),
            'has_electricity' => fake()->boolean(30),
            'has_alarm' => fake()->boolean(80),
        ];
    }

    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'available',
        ]);
    }

    public function occupied(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'occupied',
        ]);
    }

    public function reserved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'reserved',
        ]);
    }

    public function maintenance(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'maintenance',
        ]);
    }

    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'length' => 1,
            'width' => 1,
            'height' => 2,
            'volume' => 2,
            'base_price' => 29,
            'current_price' => 29,
        ]);
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'length' => 3,
            'width' => 2,
            'height' => 2.5,
            'volume' => 15,
            'base_price' => 119,
            'current_price' => 119,
        ]);
    }

    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'length' => 5,
            'width' => 4,
            'height' => 2.5,
            'volume' => 50,
            'base_price' => 249,
            'current_price' => 249,
        ]);
    }

    public function climateControlled(): static
    {
        return $this->state(fn (array $attributes) => [
            'climate_controlled' => true,
            'current_price' => ($attributes['base_price'] ?? 100) * 1.2,
        ]);
    }
}
