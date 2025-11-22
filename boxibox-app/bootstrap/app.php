<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Check for expiring contracts daily at 9:00 AM
        $schedule->command('contracts:check-expiring --days=30')
            ->dailyAt('09:00')
            ->description('Check for contracts expiring in the next 30 days');

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
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
