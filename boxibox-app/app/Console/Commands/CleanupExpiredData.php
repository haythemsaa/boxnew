<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Lead;
use App\Models\Prospect;
use App\Models\PasswordResetToken;
use App\Models\PersonalAccessToken;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CleanupExpiredData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cleanup:expired
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up expired data (abandoned bookings, old tokens, inactive leads)';

    /**
     * Cleanup results.
     */
    protected array $results = [];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info($dryRun ? 'DRY RUN - No data will be deleted' : 'Starting cleanup...');
        $this->newLine();

        // Clean up abandoned bookings (older than 48 hours)
        $this->cleanupAbandonedBookings($dryRun);

        // Clean up expired password reset tokens
        $this->cleanupPasswordResetTokens($dryRun);

        // Clean up expired API tokens
        $this->cleanupExpiredApiTokens($dryRun);

        // Clean up old inactive leads (older than 1 year)
        $this->cleanupOldLeads($dryRun);

        // Clean up old prospects without activity (older than 6 months)
        $this->cleanupOldProspects($dryRun);

        // Clean up old session files
        $this->cleanupOldSessions($dryRun);

        // Clean up old log files
        $this->cleanupOldLogs($dryRun);

        // Display summary
        $this->displaySummary($dryRun);

        return Command::SUCCESS;
    }

    /**
     * Clean up abandoned bookings.
     */
    protected function cleanupAbandonedBookings(bool $dryRun): void
    {
        $query = Booking::where('status', 'pending')
            ->where('created_at', '<', now()->subHours(48));

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            $query->update(['status' => 'expired']);
            Log::info("Cleanup: {$count} abandoned bookings marked as expired");
        }

        $this->results['abandoned_bookings'] = [
            'label' => 'Abandoned bookings (>48h)',
            'count' => $count,
            'action' => 'marked as expired',
        ];
    }

    /**
     * Clean up expired password reset tokens.
     */
    protected function cleanupPasswordResetTokens(bool $dryRun): void
    {
        // Laravel's password reset tokens expire after 60 minutes by default
        $expireMinutes = config('auth.passwords.users.expire', 60);

        $count = DB::table('password_reset_tokens')
            ->where('created_at', '<', now()->subMinutes($expireMinutes))
            ->count();

        if (!$dryRun && $count > 0) {
            DB::table('password_reset_tokens')
                ->where('created_at', '<', now()->subMinutes($expireMinutes))
                ->delete();
            Log::info("Cleanup: {$count} expired password reset tokens deleted");
        }

        $this->results['password_tokens'] = [
            'label' => 'Expired password reset tokens',
            'count' => $count,
            'action' => 'deleted',
        ];
    }

    /**
     * Clean up expired API tokens.
     */
    protected function cleanupExpiredApiTokens(bool $dryRun): void
    {
        // Clean up Sanctum tokens that haven't been used in 30 days
        $count = DB::table('personal_access_tokens')
            ->where(function ($query) {
                $query->whereNull('last_used_at')
                    ->where('created_at', '<', now()->subDays(30));
            })
            ->orWhere('last_used_at', '<', now()->subDays(30))
            ->count();

        if (!$dryRun && $count > 0) {
            DB::table('personal_access_tokens')
                ->where(function ($query) {
                    $query->whereNull('last_used_at')
                        ->where('created_at', '<', now()->subDays(30));
                })
                ->orWhere('last_used_at', '<', now()->subDays(30))
                ->delete();
            Log::info("Cleanup: {$count} expired API tokens deleted");
        }

        $this->results['api_tokens'] = [
            'label' => 'Expired API tokens (>30 days)',
            'count' => $count,
            'action' => 'deleted',
        ];
    }

    /**
     * Clean up old inactive leads.
     */
    protected function cleanupOldLeads(bool $dryRun): void
    {
        if (!class_exists(Lead::class)) {
            $this->results['old_leads'] = [
                'label' => 'Old inactive leads',
                'count' => 0,
                'action' => 'skipped (model not found)',
            ];
            return;
        }

        $query = Lead::where('status', 'lost')
            ->where('updated_at', '<', now()->subYear());

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            // Soft delete or archive instead of hard delete
            $query->update(['status' => 'archived']);
            Log::info("Cleanup: {$count} old leads archived");
        }

        $this->results['old_leads'] = [
            'label' => 'Old inactive leads (>1 year)',
            'count' => $count,
            'action' => 'archived',
        ];
    }

    /**
     * Clean up old prospects without activity.
     */
    protected function cleanupOldProspects(bool $dryRun): void
    {
        if (!class_exists(Prospect::class)) {
            $this->results['old_prospects'] = [
                'label' => 'Old prospects',
                'count' => 0,
                'action' => 'skipped (model not found)',
            ];
            return;
        }

        $query = Prospect::whereIn('status', ['cold', 'lost'])
            ->where('updated_at', '<', now()->subMonths(6));

        $count = $query->count();

        if (!$dryRun && $count > 0) {
            $query->update(['status' => 'archived']);
            Log::info("Cleanup: {$count} old prospects archived");
        }

        $this->results['old_prospects'] = [
            'label' => 'Old inactive prospects (>6 months)',
            'count' => $count,
            'action' => 'archived',
        ];
    }

    /**
     * Clean up old session files.
     */
    protected function cleanupOldSessions(bool $dryRun): void
    {
        $sessionPath = storage_path('framework/sessions');
        $count = 0;

        if (config('session.driver') === 'file' && is_dir($sessionPath)) {
            $files = glob($sessionPath . '/*');
            $expireSeconds = config('session.lifetime', 120) * 60;

            foreach ($files as $file) {
                if (is_file($file) && (time() - filemtime($file)) > $expireSeconds) {
                    $count++;
                    if (!$dryRun) {
                        unlink($file);
                    }
                }
            }

            if (!$dryRun && $count > 0) {
                Log::info("Cleanup: {$count} expired session files deleted");
            }
        }

        $this->results['sessions'] = [
            'label' => 'Expired session files',
            'count' => $count,
            'action' => 'deleted',
        ];
    }

    /**
     * Clean up old log files.
     */
    protected function cleanupOldLogs(bool $dryRun): void
    {
        $logPath = storage_path('logs');
        $count = 0;
        $retentionDays = 30;

        if (is_dir($logPath)) {
            $files = glob($logPath . '/laravel-*.log');

            foreach ($files as $file) {
                if (is_file($file) && (time() - filemtime($file)) > ($retentionDays * 86400)) {
                    $count++;
                    if (!$dryRun) {
                        unlink($file);
                    }
                }
            }

            if (!$dryRun && $count > 0) {
                Log::info("Cleanup: {$count} old log files deleted");
            }
        }

        $this->results['logs'] = [
            'label' => "Old log files (>{$retentionDays} days)",
            'count' => $count,
            'action' => 'deleted',
        ];
    }

    /**
     * Display cleanup summary.
     */
    protected function displaySummary(bool $dryRun): void
    {
        $rows = [];
        $totalCleaned = 0;

        foreach ($this->results as $result) {
            $rows[] = [
                $result['label'],
                $result['count'],
                $dryRun ? "Would be {$result['action']}" : ucfirst($result['action']),
            ];
            $totalCleaned += $result['count'];
        }

        $this->table(['Item', 'Count', 'Action'], $rows);

        $this->newLine();
        if ($dryRun) {
            $this->info("DRY RUN: {$totalCleaned} items would be cleaned up.");
            $this->line('Run without --dry-run to actually clean up.');
        } else {
            $this->info("Cleanup complete: {$totalCleaned} items processed.");
        }
    }
}
