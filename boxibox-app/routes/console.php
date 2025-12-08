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
