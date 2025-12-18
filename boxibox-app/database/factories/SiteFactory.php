<?php

namespace Database\Factories;

use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $cities = ['Paris', 'Lyon', 'Marseille', 'Bordeaux', 'Toulouse', 'Nantes', 'Nice', 'Lille'];
        $city = fake()->randomElement($cities);

        return [
            'tenant_id' => Tenant::factory(),
            'name' => 'BoxiBox ' . $city . ' ' . fake()->streetSuffix(),
            'code' => strtoupper(Str::random(3)) . '-' . fake()->numberBetween(100, 999),
            'address' => fake()->streetAddress(),
            'city' => $city,
            'postal_code' => fake()->postcode(),
            'country' => 'France',
            'latitude' => fake()->latitude(43, 51),
            'longitude' => fake()->longitude(-1, 8),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'is_active' => true,
            'self_service_enabled' => fake()->boolean(70),
            'opening_hours' => [
                'monday' => ['09:00', '18:00'],
                'tuesday' => ['09:00', '18:00'],
                'wednesday' => ['09:00', '18:00'],
                'thursday' => ['09:00', '18:00'],
                'friday' => ['09:00', '18:00'],
                'saturday' => ['09:00', '12:00'],
                'sunday' => null,
            ],
            'access_hours' => [
                'start' => '06:00',
                'end' => '22:00',
            ],
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function selfService(): static
    {
        return $this->state(fn (array $attributes) => [
            'self_service_enabled' => true,
            'access_hours' => [
                'start' => '00:00',
                'end' => '23:59',
            ],
        ]);
    }
}
