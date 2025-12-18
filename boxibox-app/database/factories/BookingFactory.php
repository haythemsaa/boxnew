<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Box;
use App\Models\Site;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Booking>
 */
class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('now', '+2 weeks');
        $monthlyPrice = fake()->randomElement([49, 79, 99, 119, 149, 179]);
        $depositAmount = $monthlyPrice * 2;

        return [
            'uuid' => (string) Str::uuid(),
            'tenant_id' => Tenant::factory(),
            'site_id' => Site::factory(),
            'box_id' => Box::factory(),
            'booking_number' => 'BK' . date('Y') . str_pad(fake()->unique()->numberBetween(1, 99999), 5, '0', STR_PAD_LEFT),
            'customer_first_name' => fake()->firstName(),
            'customer_last_name' => fake()->lastName(),
            'customer_email' => fake()->unique()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'customer_address' => fake()->streetAddress(),
            'customer_city' => fake()->city(),
            'customer_postal_code' => fake()->postcode(),
            'customer_country' => 'France',
            'start_date' => $startDate,
            'rental_type' => 'monthly',
            'duration_type' => fake()->randomElement(['indefinite', 'fixed']),
            'planned_duration_months' => fake()->numberBetween(1, 12),
            'monthly_price' => $monthlyPrice,
            'deposit_amount' => $depositAmount,
            'total_amount' => $monthlyPrice + $depositAmount,
            'status' => 'pending',
            'source' => fake()->randomElement(['website', 'widget', 'manual']),
            'terms_accepted' => true,
            'terms_accepted_at' => now(),
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function confirmed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'confirmed',
        ]);
    }

    public function depositPaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'deposit_paid',
        ]);
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
        ]);
    }

    public function fromWidget(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'widget',
            'source_url' => fake()->url(),
        ]);
    }

    public function fromWebsite(): static
    {
        return $this->state(fn (array $attributes) => [
            'source' => 'website',
        ]);
    }

    public function withPromoCode(string $code = 'PROMO10', float $discount = 10): static
    {
        return $this->state(fn (array $attributes) => [
            'promo_code' => $code,
            'discount_amount' => $discount,
            'total_amount' => ($attributes['monthly_price'] ?? 100) + ($attributes['deposit_amount'] ?? 200) - $discount,
        ]);
    }

    public function withSpecialNeeds(): static
    {
        return $this->state(fn (array $attributes) => [
            'needs_24h_access' => true,
            'needs_climate_control' => fake()->boolean(),
            'needs_electricity' => fake()->boolean(),
            'needs_insurance' => true,
        ]);
    }

    public function company(): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_company' => fake()->company(),
            'customer_vat_number' => 'FR' . fake()->numberBetween(10000000000, 99999999999),
        ]);
    }
}
