<?php

namespace App\Console\Commands;

use App\Services\ReviewRequestService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessReviewRequests extends Command
{
    protected $signature = 'reviews:process
                            {--limit=100 : Maximum number of requests to process}';

    protected $description = 'Process pending review requests and send emails/SMS';

    public function __construct(
        protected ReviewRequestService $reviewService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Processing review requests...');

        try {
            $results = $this->reviewService->processPendingRequests();

            $this->info('Summary:');
            $this->line("  - Processed: {$results['processed']}");
            $this->line("  - Sent successfully: {$results['sent']}");
            $this->line("  - Failed: {$results['failed']}");

            if ($results['failed'] > 0) {
                $this->warn('Some review requests failed to send. Check logs for details.');
            }

            $this->info('Review request processing complete.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            Log::error('Review request processing failed', [
                'error' => $e->getMessage(),
            ]);
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
