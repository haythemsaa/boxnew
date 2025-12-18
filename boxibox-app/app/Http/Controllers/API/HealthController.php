<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

/**
 * Health Check Controller
 *
 * Provides health check endpoints for:
 * - Load balancers (basic liveness probe)
 * - Kubernetes readiness probes
 * - External monitoring systems (detailed checks)
 */
class HealthController extends Controller
{
    /**
     * Simple liveness probe - returns 200 if app is running.
     * Used by load balancers for quick checks.
     *
     * GET /health
     */
    public function liveness(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Readiness probe - checks if app can handle traffic.
     * Returns 503 if critical services are down.
     *
     * GET /health/ready
     */
    public function readiness(): JsonResponse
    {
        $checks = [];
        $healthy = true;

        // Database check (critical)
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);
            $checks['database'] = [
                'status' => 'healthy',
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            $healthy = false;
            $checks['database'] = [
                'status' => 'unhealthy',
                'error' => 'Connection failed',
            ];
        }

        // Cache check
        try {
            $start = microtime(true);
            $key = 'health_' . uniqid();
            Cache::put($key, true, 5);
            Cache::forget($key);
            $latency = round((microtime(true) - $start) * 1000, 2);
            $checks['cache'] = [
                'status' => 'healthy',
                'driver' => config('cache.default'),
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            // Cache failure is degraded, not critical
            $checks['cache'] = [
                'status' => 'degraded',
                'error' => 'Cache unavailable',
            ];
        }

        $status = $healthy ? 'ready' : 'not_ready';
        $httpCode = $healthy ? 200 : 503;

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'checks' => $checks,
        ], $httpCode);
    }

    /**
     * Detailed health check with all services.
     * For monitoring dashboards and alerting systems.
     *
     * GET /health/detailed
     */
    public function detailed(): JsonResponse
    {
        $checks = [];
        $failedCount = 0;
        $warningCount = 0;

        // Database
        $checks['database'] = $this->checkDatabase();
        if ($checks['database']['status'] === 'unhealthy') $failedCount++;

        // Redis
        $checks['redis'] = $this->checkRedis();
        if ($checks['redis']['status'] === 'unhealthy') $failedCount++;
        elseif ($checks['redis']['status'] === 'degraded') $warningCount++;

        // Cache
        $checks['cache'] = $this->checkCache();
        if ($checks['cache']['status'] === 'unhealthy') $failedCount++;

        // Disk Space
        $checks['disk'] = $this->checkDiskSpace();
        if ($checks['disk']['status'] === 'unhealthy') $failedCount++;
        elseif ($checks['disk']['status'] === 'warning') $warningCount++;

        // Memory
        $checks['memory'] = $this->checkMemory();
        if ($checks['memory']['status'] === 'warning') $warningCount++;

        // Determine overall status
        $status = 'healthy';
        $httpCode = 200;

        if ($failedCount > 0) {
            $status = 'unhealthy';
            $httpCode = 503;
        } elseif ($warningCount > 0) {
            $status = 'degraded';
        }

        return response()->json([
            'status' => $status,
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => $checks,
            'summary' => [
                'total' => count($checks),
                'healthy' => count($checks) - $failedCount - $warningCount,
                'warnings' => $warningCount,
                'failed' => $failedCount,
            ],
        ], $httpCode);
    }

    /**
     * Check database connection and performance.
     */
    protected function checkDatabase(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();

            // Simple query to test read capability
            $tenantCount = DB::table('tenants')->count();

            $latency = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'driver' => config('database.default'),
                'latency_ms' => $latency,
                'tenant_count' => $tenantCount,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => 'Database connection failed',
                'driver' => config('database.default'),
            ];
        }
    }

    /**
     * Check Redis connection.
     */
    protected function checkRedis(): array
    {
        try {
            $start = microtime(true);
            $redis = Redis::connection();
            $redis->ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            $info = $redis->info();

            return [
                'status' => 'healthy',
                'latency_ms' => $latency,
                'memory_used' => $info['used_memory_human'] ?? 'N/A',
                'connected_clients' => $info['connected_clients'] ?? 'N/A',
            ];
        } catch (\Exception $e) {
            // Redis is not critical if we're using database cache
            $isCritical = config('cache.default') === 'redis';

            return [
                'status' => $isCritical ? 'unhealthy' : 'degraded',
                'error' => 'Redis not available',
            ];
        }
    }

    /**
     * Check cache functionality.
     */
    protected function checkCache(): array
    {
        try {
            $start = microtime(true);
            $key = 'health_check_' . uniqid();
            Cache::put($key, 'test', 10);
            $value = Cache::get($key);
            Cache::forget($key);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($value !== 'test') {
                throw new \Exception('Cache read/write mismatch');
            }

            return [
                'status' => 'healthy',
                'driver' => config('cache.default'),
                'latency_ms' => $latency,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'driver' => config('cache.default'),
                'error' => 'Cache read/write failed',
            ];
        }
    }

    /**
     * Check available disk space.
     */
    protected function checkDiskSpace(): array
    {
        try {
            $path = storage_path();
            $free = disk_free_space($path);
            $total = disk_total_space($path);

            $freeGb = round($free / 1024 / 1024 / 1024, 2);
            $totalGb = round($total / 1024 / 1024 / 1024, 2);
            $usedPercent = round((1 - $free / $total) * 100, 1);

            $status = 'healthy';
            if ($usedPercent > 90) {
                $status = 'unhealthy';
            } elseif ($usedPercent > 80) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'free_gb' => $freeGb,
                'total_gb' => $totalGb,
                'used_percent' => $usedPercent,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'warning',
                'error' => 'Unable to check disk space',
            ];
        }
    }

    /**
     * Check PHP memory usage.
     */
    protected function checkMemory(): array
    {
        $used = memory_get_usage(true);
        $limit = $this->getMemoryLimitBytes();

        $usedMb = round($used / 1024 / 1024, 2);
        $limitMb = round($limit / 1024 / 1024, 2);
        $usedPercent = $limit > 0 ? round(($used / $limit) * 100, 1) : 0;

        $status = 'healthy';
        if ($usedPercent > 90) {
            $status = 'warning';
        }

        return [
            'status' => $status,
            'used_mb' => $usedMb,
            'limit_mb' => $limitMb,
            'used_percent' => $usedPercent,
        ];
    }

    /**
     * Get memory limit in bytes.
     */
    protected function getMemoryLimitBytes(): int
    {
        $limit = ini_get('memory_limit');

        if ($limit === '-1') {
            return PHP_INT_MAX;
        }

        $unit = strtolower(substr($limit, -1));
        $value = (int) $limit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
