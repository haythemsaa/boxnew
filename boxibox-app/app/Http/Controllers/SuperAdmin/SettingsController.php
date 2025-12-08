<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = [
            'app_name' => config('app.name'),
            'app_url' => config('app.url'),
            'app_env' => config('app.env'),
            'app_debug' => config('app.debug'),
            'mail_driver' => config('mail.default'),
            'queue_driver' => config('queue.default'),
            'cache_driver' => config('cache.default'),
            'session_driver' => config('session.driver'),
        ];

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'database' => config('database.default'),
            'timezone' => config('app.timezone'),
        ];

        $healthChecks = $this->runHealthChecks();

        return Inertia::render('SuperAdmin/Settings/Index', [
            'settings' => $settings,
            'systemInfo' => $systemInfo,
            'healthChecks' => $healthChecks,
        ]);
    }

    public function clearCache()
    {
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');

        return back()->with('success', 'Cache vidé avec succès.');
    }

    public function optimizeApplication()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');

        return back()->with('success', 'Application optimisée avec succès.');
    }

    public function runMigrations()
    {
        try {
            Artisan::call('migrate', ['--force' => true]);
            return back()->with('success', 'Migrations exécutées avec succès.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors des migrations: ' . $e->getMessage());
        }
    }

    public function maintenance(Request $request)
    {
        $action = $request->input('action');

        if ($action === 'down') {
            Artisan::call('down', ['--secret' => 'superadmin-access']);
            return back()->with('success', 'Mode maintenance activé.');
        } else {
            Artisan::call('up');
            return back()->with('success', 'Mode maintenance désactivé.');
        }
    }

    private function runHealthChecks(): array
    {
        $checks = [];

        // Database connection
        try {
            DB::connection()->getPdo();
            $checks['database'] = ['status' => 'ok', 'message' => 'Connecté'];
        } catch (\Exception $e) {
            $checks['database'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Cache
        try {
            Cache::put('health_check', true, 10);
            $checks['cache'] = ['status' => 'ok', 'message' => 'Fonctionnel'];
        } catch (\Exception $e) {
            $checks['cache'] = ['status' => 'error', 'message' => $e->getMessage()];
        }

        // Storage
        $storagePath = storage_path('app');
        if (is_writable($storagePath)) {
            $checks['storage'] = ['status' => 'ok', 'message' => 'Accessible en écriture'];
        } else {
            $checks['storage'] = ['status' => 'error', 'message' => 'Non accessible en écriture'];
        }

        // Disk space
        $freeSpace = disk_free_space('/');
        $totalSpace = disk_total_space('/');
        $usedPercent = round((1 - ($freeSpace / $totalSpace)) * 100, 1);
        $checks['disk'] = [
            'status' => $usedPercent < 90 ? 'ok' : 'warning',
            'message' => "{$usedPercent}% utilisé (" . $this->formatBytes($freeSpace) . " libre)",
        ];

        return $checks;
    }

    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        return round($bytes / (1024 ** $pow), $precision) . ' ' . $units[$pow];
    }
}
