<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    use RefreshDatabase;

    /*
    |--------------------------------------------------------------------------
    | Laravel Default Health Check
    |--------------------------------------------------------------------------
    */

    public function test_health_check_endpoint_returns_ok(): void
    {
        $response = $this->get('/up');

        $response->assertStatus(200);
    }

    /*
    |--------------------------------------------------------------------------
    | API Health Check Endpoints
    |--------------------------------------------------------------------------
    */

    public function test_api_health_liveness_returns_ok(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure(['status', 'timestamp'])
            ->assertJsonPath('status', 'ok');
    }

    public function test_api_health_readiness_returns_ready(): void
    {
        $response = $this->getJson('/api/health/ready');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'checks' => [
                    'database',
                    'cache',
                ],
            ])
            ->assertJsonPath('status', 'ready');
    }

    public function test_api_health_detailed_returns_all_checks(): void
    {
        $response = $this->getJson('/api/health/detailed');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
                'environment',
                'checks' => [
                    'database',
                    'redis',
                    'cache',
                    'disk',
                    'memory',
                ],
                'summary' => [
                    'total',
                    'healthy',
                    'warnings',
                    'failed',
                ],
            ]);
    }

    public function test_api_health_detailed_includes_latency(): void
    {
        $response = $this->getJson('/api/health/detailed');

        $data = $response->json();

        // Database check should include latency
        $this->assertArrayHasKey('latency_ms', $data['checks']['database']);
    }

    /*
    |--------------------------------------------------------------------------
    | CLI Health Check Command
    |--------------------------------------------------------------------------
    */

    public function test_health_check_command_runs_successfully(): void
    {
        $exitCode = Artisan::call('health:check');

        // Should return 0 (success) in test environment
        $this->assertEquals(0, $exitCode);
    }

    public function test_health_check_command_json_output(): void
    {
        Artisan::call('health:check', ['--json' => true]);
        $output = Artisan::output();

        $data = json_decode($output, true);

        $this->assertNotNull($data);
        $this->assertArrayHasKey('status', $data);
        $this->assertArrayHasKey('checks', $data);
        $this->assertArrayHasKey('summary', $data);
    }

    public function test_database_health_check_passes(): void
    {
        Artisan::call('health:check', ['--json' => true]);
        $output = Artisan::output();

        $data = json_decode($output, true);

        $this->assertEquals('pass', $data['checks']['database']['status']);
    }
}
