<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/dashboard');

        $response->assertStatus(401);
    }

    public function test_api_accepts_sanctum_token(): void
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ])->getJson('/api/v1/dashboard');

        // Should not be 401 (may be 200 or other status depending on route setup)
        $response->assertStatus(200);
    }

    public function test_rate_limiting_is_applied(): void
    {
        // Make multiple rapid requests to trigger rate limiting
        for ($i = 0; $i < 70; $i++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'test@test.com',
                'password' => 'password',
            ]);
        }

        // The 71st request should be rate limited
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        // Should get 429 Too Many Requests
        $response->assertStatus(429);
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/');

        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_csrf_is_required_for_web_routes(): void
    {
        $response = $this->post('/login', [
            'email' => 'test@test.com',
            'password' => 'password',
        ]);

        // Without CSRF token, should get 419
        $response->assertStatus(419);
    }

    public function test_webhook_routes_bypass_csrf(): void
    {
        // Webhook routes should not require CSRF
        $response = $this->postJson('/api/webhooks/stripe', [
            'type' => 'payment_intent.succeeded',
            'data' => ['object' => ['id' => 'pi_test']],
        ]);

        // Should not be 419 (CSRF error)
        $this->assertNotEquals(419, $response->status());
    }
}
