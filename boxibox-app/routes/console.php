<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Generate alert notifications for all tenants every morning at 8:00 AM
Schedule::command('notifications:generate-alerts --all')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/alerts.log'));

// Also run a check at 2:00 PM for afternoon reminders
Schedule::command('notifications:generate-alerts --all')
    ->dailyAt('14:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/alerts.log'));

/*
|--------------------------------------------------------------------------
| AI Copilot Scheduled Agents
|--------------------------------------------------------------------------
*/

// Revenue Guardian Agent - Run every 2 hours during business hours
Schedule::command('copilot:run-agents --agent=revenue')
    ->everyTwoHours()
    ->between('07:00', '20:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ai-agents.log'));

// Churn Predictor Agent - Run daily at 6:00 AM
Schedule::command('copilot:run-agents --agent=churn')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ai-agents.log'));

// Collection Agent - Run daily at 9:00 AM (after business hours start)
Schedule::command('copilot:run-agents --agent=collection')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ai-agents.log'));

// Daily Briefing - Generate and cache briefings for all tenants at 7:00 AM
Schedule::command('copilot:generate-briefings')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/ai-agents.log'));

/*
|--------------------------------------------------------------------------
| Billing & Invoicing Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Generate recurring invoices - Run daily at 6:00 AM
// Checks all active contracts and generates invoices on their billing day
Schedule::command('invoices:generate-recurring')
    ->dailyAt('06:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/invoicing.log'));

// Send payment reminders - Run twice daily at 9:00 AM and 3:00 PM
Schedule::command('reminders:send')
    ->dailyAt('09:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/reminders.log'));

Schedule::command('reminders:send')
    ->dailyAt('15:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/reminders.log'));

// Check for overdue invoices and update status - Run daily at 1:00 AM
Schedule::command('invoices:check-overdue')
    ->dailyAt('01:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/invoicing.log'));

// Check expiring contracts and send alerts - Run daily at 8:00 AM
Schedule::command('contracts:check-expiring')
    ->dailyAt('08:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/contracts.log'));

/*
|--------------------------------------------------------------------------
| Marketing & Recovery Scheduled Tasks
|--------------------------------------------------------------------------
*/

// Recover abandoned bookings - Run every 30 minutes
Schedule::command('bookings:recover-abandoned')
    ->everyThirtyMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/marketing.log'));

// Score leads automatically - Run every hour during business hours
Schedule::command('leads:calculate-scores')
    ->hourly()
    ->between('08:00', '20:00')
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/crm.log'));

/*
|--------------------------------------------------------------------------
| Scheduled Reports
|--------------------------------------------------------------------------
*/

// Send scheduled reports - Run every 15 minutes to catch all due reports
Schedule::command('reports:send-scheduled')
    ->everyFifteenMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/scheduled-reports.log'));

// Process email automations - Run every 5 minutes
Schedule::command('emails:process-automations')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground()
    ->appendOutputTo(storage_path('logs/email-automation.log'));
