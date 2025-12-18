<?php

namespace App\Console\Commands;

use App\Services\EmailAutomationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessEmailAutomations extends Command
{
    protected $signature = 'emails:process-automations {--limit=100 : Maximum emails to process}';

    protected $description = 'Process due email automation enrollments';

    public function __construct(protected EmailAutomationService $automationService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Processing email automations...');

        try {
            $result = $this->automationService->processDueEmails();

            $this->info("Processed: {$result['processed']}, Errors: {$result['errors']}");

            if ($result['errors'] > 0) {
                Log::warning("Email automation had {$result['errors']} errors during processing.");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Failed: {$e->getMessage()}");
            Log::error("Email automation processing failed: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return self::FAILURE;
        }
    }
}
