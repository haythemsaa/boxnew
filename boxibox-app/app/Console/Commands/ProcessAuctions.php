<?php

namespace App\Console\Commands;

use App\Models\Auction;
use App\Models\AuctionSettings;
use App\Models\Tenant;
use App\Services\AuctionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessAuctions extends Command
{
    protected $signature = 'auctions:process
                            {--tenant= : Process for specific tenant ID}
                            {--check-overdue : Check overdue contracts and create auctions}
                            {--process-active : End expired active auctions}
                            {--send-reminders : Send auction reminders}';

    protected $description = 'Process auctions: check overdue contracts, send notices, and manage active auctions';

    public function __construct(
        protected AuctionService $auctionService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Processing auctions...');

        $tenantId = $this->option('tenant');
        $checkOverdue = $this->option('check-overdue');
        $processActive = $this->option('process-active');
        $sendReminders = $this->option('send-reminders');

        // Default: do all if no option specified
        if (!$checkOverdue && !$processActive && !$sendReminders) {
            $checkOverdue = $processActive = $sendReminders = true;
        }

        $results = [
            'auctions_created' => 0,
            'notices_sent' => 0,
            'auctions_ended' => 0,
            'reminders_sent' => 0,
        ];

        // Get tenants to process
        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        foreach ($tenants as $tenant) {
            $settings = AuctionSettings::getForTenant($tenant->id);

            if (!$settings?->is_enabled) {
                continue;
            }

            $this->line("Processing tenant: {$tenant->name}");

            // Check overdue contracts and create/update auctions
            if ($checkOverdue) {
                try {
                    $overdueResults = $this->auctionService->checkOverdueContracts($tenant->id);
                    $results['auctions_created'] += $overdueResults['auctions_created'];
                    $results['notices_sent'] += $overdueResults['notices_sent'];

                    if ($overdueResults['auctions_created'] > 0) {
                        $this->line("  - Created {$overdueResults['auctions_created']} auctions");
                    }
                    if ($overdueResults['notices_sent'] > 0) {
                        $this->line("  - Sent {$overdueResults['notices_sent']} notices");
                    }
                } catch (\Exception $e) {
                    Log::error('Auction processing failed', [
                        'tenant_id' => $tenant->id,
                        'error' => $e->getMessage(),
                    ]);
                    $this->error("Error: {$e->getMessage()}");
                }
            }

            // Process active auctions that should end
            if ($processActive) {
                $endedAuctions = Auction::where('tenant_id', $tenant->id)
                    ->where('status', 'active')
                    ->where('auction_end_date', '<=', now())
                    ->get();

                foreach ($endedAuctions as $auction) {
                    try {
                        $this->auctionService->endAuction($auction);
                        $results['auctions_ended']++;
                        $this->line("  - Ended auction #{$auction->auction_number}");
                    } catch (\Exception $e) {
                        Log::error('Failed to end auction', [
                            'auction_id' => $auction->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Start scheduled auctions
            $scheduledAuctions = Auction::where('tenant_id', $tenant->id)
                ->where('status', 'scheduled')
                ->where('auction_start_date', '<=', now())
                ->get();

            foreach ($scheduledAuctions as $auction) {
                $auction->start();
                $this->line("  - Started auction #{$auction->auction_number}");
            }

            // Send reminders for auctions ending soon
            if ($sendReminders) {
                $endingSoon = Auction::where('tenant_id', $tenant->id)
                    ->ending(24)
                    ->get();

                foreach ($endingSoon as $auction) {
                    // Send reminder to participants
                    // TODO: Implement reminder notifications
                    $results['reminders_sent']++;
                }
            }
        }

        $this->info('Summary:');
        $this->line("  - Auctions created: {$results['auctions_created']}");
        $this->line("  - Notices sent: {$results['notices_sent']}");
        $this->line("  - Auctions ended: {$results['auctions_ended']}");
        $this->line("  - Reminders sent: {$results['reminders_sent']}");

        $this->info('Auction processing complete.');

        return self::SUCCESS;
    }
}
