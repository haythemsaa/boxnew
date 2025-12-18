<?php

namespace App\Console\Commands;

use App\Services\SmartPaymentRetryService;
use App\Models\PaymentRetryAttempt;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessPaymentRetries extends Command
{
    protected $signature = 'payments:process-retries
                            {--tenant= : Process only for specific tenant}
                            {--dry-run : Preview without processing}
                            {--limit=100 : Maximum retries to process}';

    protected $description = 'Process scheduled payment retry attempts';

    public function handle(SmartPaymentRetryService $retryService): int
    {
        $this->info('Starting payment retry processing...');

        $tenantId = $this->option('tenant');
        $dryRun = $this->option('dry-run');
        $limit = (int) $this->option('limit');

        $query = PaymentRetryAttempt::readyToProcess()
            ->limit($limit);

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $attempts = $query->get();

        $this->info("Found {$attempts->count()} retry attempts to process");

        if ($dryRun) {
            $this->warn('DRY RUN - No payments will be processed');
            foreach ($attempts as $attempt) {
                $this->line("  Would process: Invoice #{$attempt->invoice_id} - {$attempt->formatted_amount} (Attempt {$attempt->attempt_number})");
            }
            return Command::SUCCESS;
        }

        $succeeded = 0;
        $failed = 0;

        foreach ($attempts as $attempt) {
            $this->info("Processing attempt #{$attempt->id} for Invoice #{$attempt->invoice_id}...");

            try {
                $result = $retryService->processRetry($attempt);

                if ($result['success']) {
                    $succeeded++;
                    $this->info("  SUCCESS - Payment recovered!");
                } else {
                    $failed++;
                    $error = $result['error'] ?? 'Unknown error';
                    $this->warn("  FAILED - {$error}");

                    if ($attempt->fresh()->canRetry()) {
                        $nextRetry = $attempt->fresh()->next_retry_at;
                        $this->line("  Next retry scheduled: {$nextRetry}");
                    } else {
                        $this->error("  All retries exhausted");
                    }
                }
            } catch (\Exception $e) {
                $failed++;
                $this->error("  ERROR - {$e->getMessage()}");
                Log::error('Payment retry processing error', [
                    'attempt_id' => $attempt->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info('=== Summary ===');
        $this->info("Processed: {$attempts->count()}");
        $this->info("Succeeded: {$succeeded}");
        $this->info("Failed: {$failed}");

        if ($succeeded > 0) {
            $recoveredAmount = PaymentRetryAttempt::whereIn('id', $attempts->pluck('id'))
                ->where('status', 'succeeded')
                ->sum('amount');
            $this->info("Amount recovered: " . number_format($recoveredAmount, 2) . " EUR");
        }

        return Command::SUCCESS;
    }
}
