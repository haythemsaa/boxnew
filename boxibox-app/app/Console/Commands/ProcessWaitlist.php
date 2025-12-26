<?php

namespace App\Console\Commands;

use App\Models\Box;
use App\Models\WaitlistEntry;
use App\Services\WaitlistService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessWaitlist extends Command
{
    protected $signature = 'waitlist:process
                            {--expire : Only expire old notifications}
                            {--notify : Process new notifications for available boxes}';

    protected $description = 'Process waitlist: expire old notifications and notify for available boxes';

    public function __construct(
        protected WaitlistService $waitlistService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Processing waitlist...');

        $expireOnly = $this->option('expire');
        $notifyOnly = $this->option('notify');

        // Default: do both if no option specified
        if (!$expireOnly && !$notifyOnly) {
            $expireOnly = $notifyOnly = true;
        }

        $results = [
            'expired' => 0,
            'notified' => 0,
        ];

        // Expire old notifications
        if ($expireOnly) {
            $this->info('Expiring old notifications...');
            $results['expired'] = $this->waitlistService->expireOldNotifications();
            $this->info("Expired {$results['expired']} entries.");
        }

        // Process notifications for newly available boxes
        if ($notifyOnly) {
            $this->info('Checking for available boxes...');

            // Find boxes that became available recently
            $availableBoxes = Box::where('status', 'available')
                ->where('updated_at', '>=', now()->subHours(1))
                ->get();

            foreach ($availableBoxes as $box) {
                try {
                    $boxResults = $this->waitlistService->processBoxAvailable($box);
                    $results['notified'] += $boxResults['notified'];

                    if ($boxResults['notified'] > 0) {
                        $this->line("  - Box {$box->display_name}: {$boxResults['notified']} notifications sent");
                    }
                } catch (\Exception $e) {
                    Log::error('Waitlist processing failed for box', [
                        'box_id' => $box->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("Error processing box {$box->id}: {$e->getMessage()}");
                }
            }

            $this->info("Sent {$results['notified']} notifications.");
        }

        $this->info('Waitlist processing complete.');

        return self::SUCCESS;
    }
}
