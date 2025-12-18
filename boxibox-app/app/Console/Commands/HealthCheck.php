<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class HealthCheck extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check
                            {--json : Output results as JSON}
                            {--detailed : Show detailed information}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health status of all application services';

    /**
     * Health check results.
     */
    protected array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running health checks...');
        $this->newLine();

        // Run all health checks
        $this->checkDatabase();
        $this->checkRedis();
        $this->checkCache();
        $this->checkQueue();
        $this->checkStorage();
        $this->checkDiskSpace();
        $this->checkMemory();

        // Calculate overall status
        $failedCount = collect($this->results)->where('status', 'fail')->count();
        $warningCount = collect($this->results)->where('status', 'warning')->count();

        // Output results
        if ($this->option('json')) {
            $this->outputJson($failedCount, $warningCount);
        } else {
            $this->outputTable();
            $this->outputSummary($failedCount, $warningCount);
        }

        // Return appropriate exit code
        if ($failedCount > 0) {
            return Command::FAILURE;
        }

        if ($warningCount > 0) {
            return 2; // Warning exit code
        }

        return Command::SUCCESS;
    }

    /**
     * Check database connection.
     */
    protected function checkDatabase(): void
    {
        $start = microtime(true);
        try {
            DB::connection()->getPdo();
            $latency = round((microtime(true) - $start) * 1000, 2);

            // Check if we can query
            $count = DB::table('tenants')->count();

            $this->results['database'] = [
                'name' => 'Database (MySQL)',
                'status' => 'pass',
                'message' => "Connected, {$count} tenants",
                'latency' => "{$latency}ms",
            ];
        } catch (\Exception $e) {
            $this->results['database'] = [
                'name' => 'Database (MySQL)',
                'status' => 'fail',
                'message' => $e->getMessage(),
                'latency' => '-',
            ];
        }
    }

    /**
     * Check Redis connection.
     */
    protected function checkRedis(): void
    {
        $start = microtime(true);
        try {
            $redis = Redis::connection();
            $redis->ping();
            $latency = round((microtime(true) - $start) * 1000, 2);

            $info = $redis->info();
            $memory = $info['used_memory_human'] ?? 'N/A';

            $this->results['redis'] = [
                'name' => 'Redis',
                'status' => 'pass',
                'message' => "Connected, memory: {$memory}",
                'latency' => "{$latency}ms",
            ];
        } catch (\Exception $e) {
            $this->results['redis'] = [
                'name' => 'Redis',
                'status' => config('cache.default') === 'redis' ? 'fail' : 'warning',
                'message' => 'Not available: ' . $e->getMessage(),
                'latency' => '-',
            ];
        }
    }

    /**
     * Check cache functionality.
     */
    protected function checkCache(): void
    {
        $start = microtime(true);
        try {
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 10);
            $value = Cache::get($testKey);
            Cache::forget($testKey);
            $latency = round((microtime(true) - $start) * 1000, 2);

            if ($value === 'test') {
                $this->results['cache'] = [
                    'name' => 'Cache (' . config('cache.default') . ')',
                    'status' => 'pass',
                    'message' => 'Read/write OK',
                    'latency' => "{$latency}ms",
                ];
            } else {
                throw new \Exception('Cache read/write failed');
            }
        } catch (\Exception $e) {
            $this->results['cache'] = [
                'name' => 'Cache',
                'status' => 'fail',
                'message' => $e->getMessage(),
                'latency' => '-',
            ];
        }
    }

    /**
     * Check queue connection.
     */
    protected function checkQueue(): void
    {
        try {
            $connection = config('queue.default');

            if ($connection === 'sync') {
                $this->results['queue'] = [
                    'name' => 'Queue',
                    'status' => 'warning',
                    'message' => 'Using sync driver (not recommended for production)',
                    'latency' => '-',
                ];
                return;
            }

            // Check queue size
            $size = Queue::size();

            $status = 'pass';
            $message = "Connected ({$connection}), {$size} jobs pending";

            if ($size > 1000) {
                $status = 'warning';
                $message .= ' (queue backup detected)';
            }

            $this->results['queue'] = [
                'name' => 'Queue',
                'status' => $status,
                'message' => $message,
                'latency' => '-',
            ];
        } catch (\Exception $e) {
            $this->results['queue'] = [
                'name' => 'Queue',
                'status' => 'fail',
                'message' => $e->getMessage(),
                'latency' => '-',
            ];
        }
    }

    /**
     * Check storage accessibility.
     */
    protected function checkStorage(): void
    {
        try {
            $disk = Storage::disk('local');
            $testFile = 'health_check_' . time() . '.txt';

            $disk->put($testFile, 'test');
            $content = $disk->get($testFile);
            $disk->delete($testFile);

            if ($content === 'test') {
                $this->results['storage'] = [
                    'name' => 'Storage (local)',
                    'status' => 'pass',
                    'message' => 'Read/write OK',
                    'latency' => '-',
                ];
            } else {
                throw new \Exception('Storage read/write failed');
            }
        } catch (\Exception $e) {
            $this->results['storage'] = [
                'name' => 'Storage',
                'status' => 'fail',
                'message' => $e->getMessage(),
                'latency' => '-',
            ];
        }
    }

    /**
     * Check available disk space.
     */
    protected function checkDiskSpace(): void
    {
        try {
            $path = storage_path();
            $freeBytes = disk_free_space($path);
            $totalBytes = disk_total_space($path);

            $freeGb = round($freeBytes / 1024 / 1024 / 1024, 2);
            $totalGb = round($totalBytes / 1024 / 1024 / 1024, 2);
            $usedPercent = round((1 - $freeBytes / $totalBytes) * 100, 1);

            $status = 'pass';
            if ($usedPercent > 90) {
                $status = 'fail';
            } elseif ($usedPercent > 80) {
                $status = 'warning';
            }

            $this->results['disk'] = [
                'name' => 'Disk Space',
                'status' => $status,
                'message' => "{$freeGb}GB free of {$totalGb}GB ({$usedPercent}% used)",
                'latency' => '-',
            ];
        } catch (\Exception $e) {
            $this->results['disk'] = [
                'name' => 'Disk Space',
                'status' => 'warning',
                'message' => 'Unable to check: ' . $e->getMessage(),
                'latency' => '-',
            ];
        }
    }

    /**
     * Check PHP memory usage.
     */
    protected function checkMemory(): void
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimitBytes();

        $usedMb = round($memoryUsage / 1024 / 1024, 2);
        $limitMb = round($memoryLimit / 1024 / 1024, 2);
        $usedPercent = $memoryLimit > 0 ? round(($memoryUsage / $memoryLimit) * 100, 1) : 0;

        $status = 'pass';
        if ($usedPercent > 90) {
            $status = 'warning';
        }

        $this->results['memory'] = [
            'name' => 'PHP Memory',
            'status' => $status,
            'message' => "{$usedMb}MB / {$limitMb}MB ({$usedPercent}%)",
            'latency' => '-',
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

    /**
     * Output results as a table.
     */
    protected function outputTable(): void
    {
        $rows = [];
        foreach ($this->results as $result) {
            $statusIcon = match ($result['status']) {
                'pass' => '<fg=green>✓ PASS</>',
                'warning' => '<fg=yellow>⚠ WARN</>',
                'fail' => '<fg=red>✗ FAIL</>',
                default => '? UNKNOWN',
            };

            $rows[] = [
                $result['name'],
                $statusIcon,
                $result['message'],
                $result['latency'],
            ];
        }

        $this->table(
            ['Service', 'Status', 'Message', 'Latency'],
            $rows
        );
    }

    /**
     * Output results as JSON.
     */
    protected function outputJson(int $failedCount, int $warningCount): void
    {
        $output = [
            'status' => $failedCount > 0 ? 'unhealthy' : ($warningCount > 0 ? 'degraded' : 'healthy'),
            'timestamp' => now()->toIso8601String(),
            'checks' => $this->results,
            'summary' => [
                'total' => count($this->results),
                'passed' => count($this->results) - $failedCount - $warningCount,
                'warnings' => $warningCount,
                'failed' => $failedCount,
            ],
        ];

        $this->line(json_encode($output, JSON_PRETTY_PRINT));
    }

    /**
     * Output summary.
     */
    protected function outputSummary(int $failedCount, int $warningCount): void
    {
        $this->newLine();

        if ($failedCount > 0) {
            $this->error("Health check failed! {$failedCount} service(s) are down.");
        } elseif ($warningCount > 0) {
            $this->warn("Health check passed with warnings. {$warningCount} service(s) need attention.");
        } else {
            $this->info('All health checks passed! System is healthy.');
        }

        $this->newLine();
    }
}
