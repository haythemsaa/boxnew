<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Global security headers middleware
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // Global input sanitization middleware
        $middleware->append(\App\Http\Middleware\SanitizeInput::class);

        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);

        // Register Spatie Permission middleware aliases
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            '2fa' => \App\Http\Middleware\EnforceTwoFactorAuth::class,
            'api.key' => \App\Http\Middleware\ValidateExternalApiKey::class,
            'mobile.customer' => \App\Http\Middleware\MobileCustomerAuth::class,
        ]);

        // Exclude public booking API routes and webhooks from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'book/api/*',
            'api/v1/booking/*',
            'api/iot/*',
            'api/webhooks/*',
            'widget/booking/*',
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // =====================================================
        // BACKUP TASKS (Critical - Run daily)
        // =====================================================

        // Daily full backup at 2:00 AM
        $schedule->command('backup:run')
            ->dailyAt('02:00')
            ->onOneServer()
            ->description('Daily full backup');

        // Clean old backups daily at 3:00 AM
        $schedule->command('backup:clean')
            ->dailyAt('03:00')
            ->onOneServer()
            ->description('Clean old backups');

        // Monitor backup health daily at 4:00 AM
        $schedule->command('backup:monitor')
            ->dailyAt('04:00')
            ->onOneServer()
            ->description('Monitor backup health');

        // =====================================================
        // CONTRACT MANAGEMENT
        // =====================================================

        // Check for expiring contracts daily at 9:00 AM
        $schedule->command('contracts:check-expiring --days=30')
            ->dailyAt('09:00')
            ->description('Check for contracts expiring in the next 30 days');

        // =====================================================
        // INVOICE MANAGEMENT
        // =====================================================

        // Generate recurring invoices daily at 1:00 AM
        $schedule->command('invoices:generate-recurring')
            ->dailyAt('01:00')
            ->description('Generate recurring invoices for active contracts');

        // Check for overdue invoices daily at 10:00 AM
        $schedule->command('invoices:check-overdue')
            ->dailyAt('10:00')
            ->description('Check for overdue invoices and update status');

        // Send overdue invoice reminders weekly on Mondays at 11:00 AM
        $schedule->command('invoices:check-overdue --send-reminders')
            ->weeklyOn(1, '11:00')
            ->description('Send reminders for overdue invoices');

        // =====================================================
        // CACHE & OPTIMIZATION
        // =====================================================

        // Clear expired cache entries daily at 5:00 AM
        $schedule->command('cache:prune-stale-tags')
            ->dailyAt('05:00')
            ->description('Prune stale cache tags');

        // Queue health check every 5 minutes
        $schedule->command('queue:monitor redis:default --max=100')
            ->everyFiveMinutes()
            ->onOneServer()
            ->description('Monitor queue health');

        // =====================================================
        // DATA CLEANUP (Weekly)
        // =====================================================

        // Clean up expired data every Sunday at 4:00 AM
        $schedule->command('cleanup:expired')
            ->weeklyOn(0, '04:00')
            ->onOneServer()
            ->description('Clean up expired data');

        // =====================================================
        // PAYMENT REMINDERS (Daily)
        // =====================================================

        // Send payment reminders daily at 9:00 AM
        $schedule->command('reminders:send')
            ->dailyAt('09:00')
            ->onOneServer()
            ->description('Send payment reminders for upcoming and overdue invoices');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
