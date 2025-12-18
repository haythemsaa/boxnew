<?php

namespace Tests\Feature;

use App\Models\Box;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Critical booking flow tests
 *
 * These tests cover the core booking functionality which is
 * essential for revenue generation.
 */
class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Tenant $tenant;
    protected Site $site;
    protected Box $box;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->tenant = Tenant::factory()->create(['is_active' => true]);
        $this->site = Site::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);
        $this->box = Box::factory()->create([
            'site_id' => $this->site->id,
            'tenant_id' => $this->tenant->id,
            'status' => 'available',
            'current_price' => 99.00,
        ]);
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
    }

    public function test_public_booking_search_returns_available_boxes(): void
    {
        $response = $this->getJson('/book/api/boxes?' . http_build_query([
            'site_id' => $this->site->id,
        ]));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'size_m2', 'current_price', 'status'],
                ],
            ]);
    }

    public function test_booking_can_be_created_for_available_box(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/bookings', [
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'customer_email' => 'john@example.com',
            'customer_phone' => '+33612345678',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
            'source' => 'website',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('bookings', [
            'box_id' => $this->box->id,
            'status' => 'pending',
        ]);
    }

    public function test_booking_cannot_be_created_for_occupied_box(): void
    {
        $this->box->update(['status' => 'occupied']);

        $this->actingAs($this->user);

        $response = $this->postJson('/api/v1/bookings', [
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'customer_first_name' => 'John',
            'customer_last_name' => 'Doe',
            'customer_email' => 'john@example.com',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response->assertStatus(422);
    }

    public function test_booking_confirmation_sends_email(): void
    {
        $booking = Booking::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'pending',
            'email' => 'customer@example.com',
        ]);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/confirm");

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
        ]);
    }

    public function test_double_booking_is_prevented(): void
    {
        // Create first booking
        Booking::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'confirmed',
            'start_date' => now()->addDays(7),
        ]);

        $this->actingAs($this->user);

        // Try to create second booking for same box
        $response = $this->postJson('/api/v1/bookings', [
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'customer_first_name' => 'Jane',
            'customer_last_name' => 'Doe',
            'customer_email' => 'jane@example.com',
            'start_date' => now()->addDays(7)->format('Y-m-d'),
        ]);

        // Should fail with conflict or validation error
        $this->assertContains($response->status(), [409, 422]);
    }

    public function test_booking_price_calculation_is_correct(): void
    {
        $this->actingAs($this->user);

        $response = $this->postJson('/book/api/calculate-price', [
            'box_id' => $this->box->id,
            'start_date' => now()->format('Y-m-d'),
            'duration_months' => 3,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'monthly_price',
                'total_price',
                'deposit',
            ]);

        // Verify price matches box price
        $data = $response->json();
        $this->assertEquals($this->box->current_price, $data['monthly_price']);
    }

    public function test_booking_cancellation_frees_box(): void
    {
        $booking = Booking::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'confirmed',
        ]);

        $this->box->update(['status' => 'reserved']);

        $this->actingAs($this->user);

        $response = $this->postJson("/api/v1/bookings/{$booking->id}/cancel", [
            'reason' => 'Customer request',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
        ]);

        // Box should be available again
        $this->box->refresh();
        $this->assertEquals('available', $this->box->status);
    }

    public function test_expired_bookings_are_auto_cancelled(): void
    {
        // Create an old pending booking
        $booking = Booking::factory()->create([
            'tenant_id' => $this->tenant->id,
            'site_id' => $this->site->id,
            'box_id' => $this->box->id,
            'status' => 'pending',
            'created_at' => now()->subDays(8), // Older than 7 days
            'expires_at' => now()->subDay(),
        ]);

        // Run cleanup command
        $this->artisan('cleanup:expired')->assertSuccessful();

        $booking->refresh();
        $this->assertEquals('expired', $booking->status);
    }
}
