<?php

namespace App\Services;

use App\Models\Auction;
use App\Models\AuctionBid;
use App\Models\AuctionNotice;
use App\Models\AuctionSettings;
use App\Models\Contract;
use App\Models\Invoice;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuctionService
{
    /**
     * Check for contracts that should start the auction process
     */
    public function checkOverdueContracts(int $tenantId): array
    {
        $settings = AuctionSettings::getForTenant($tenantId);

        if (!$settings?->is_enabled) {
            return ['processed' => 0, 'auctions_created' => 0];
        }

        $results = [
            'processed' => 0,
            'auctions_created' => 0,
            'notices_sent' => 0,
        ];

        // Get contracts with overdue invoices
        $overdueContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('invoices', function ($q) use ($settings) {
                $q->where('status', 'overdue')
                    ->where('due_date', '<=', now()->subDays($settings->days_before_first_notice));
            })
            ->whereDoesntHave('auctions', function ($q) {
                $q->whereNotIn('status', ['cancelled', 'redeemed']);
            })
            ->get();

        foreach ($overdueContracts as $contract) {
            $results['processed']++;

            // Calculate total debt
            $debt = $this->calculateDebt($contract);

            if ($debt['total'] < $settings->minimum_debt_amount) {
                continue;
            }

            // Create or update auction
            $auction = $this->createOrUpdateAuction($contract, $debt, $settings);
            if ($auction->wasRecentlyCreated) {
                $results['auctions_created']++;
            }

            // Send appropriate notices
            $noticeSent = $this->processAuctionNotices($auction, $settings);
            if ($noticeSent) {
                $results['notices_sent']++;
            }
        }

        return $results;
    }

    /**
     * Calculate total debt for a contract
     */
    public function calculateDebt(Contract $contract): array
    {
        $overdueInvoices = $contract->invoices()
            ->where('status', 'overdue')
            ->get();

        $storageFees = $overdueInvoices->sum('total');
        $lateFees = $overdueInvoices->sum('late_fees') ?? 0;

        // Calculate days overdue from oldest unpaid invoice
        $oldestInvoice = $overdueInvoices->sortBy('due_date')->first();
        $daysOverdue = $oldestInvoice ? $oldestInvoice->due_date->diffInDays(now()) : 0;

        return [
            'storage_fees' => $storageFees,
            'late_fees' => $lateFees,
            'legal_fees' => 0,
            'total' => $storageFees + $lateFees,
            'days_overdue' => $daysOverdue,
            'invoice_count' => $overdueInvoices->count(),
        ];
    }

    /**
     * Create or update auction for a contract
     */
    public function createOrUpdateAuction(Contract $contract, array $debt, AuctionSettings $settings): Auction
    {
        return Auction::updateOrCreate(
            [
                'contract_id' => $contract->id,
                'status' => 'pending',
            ],
            [
                'tenant_id' => $contract->tenant_id,
                'site_id' => $contract->site_id,
                'box_id' => $contract->box_id,
                'customer_id' => $contract->customer_id,
                'total_debt' => $debt['total'],
                'storage_fees' => $debt['storage_fees'],
                'late_fees' => $debt['late_fees'],
                'legal_fees' => $debt['legal_fees'],
                'days_overdue' => $debt['days_overdue'],
                'starting_bid' => $settings->calculateStartingBid($debt['total']),
                'legal_jurisdiction' => $settings->legal_jurisdiction,
            ]
        );
    }

    /**
     * Process notices for an auction based on timeline
     */
    public function processAuctionNotices(Auction $auction, AuctionSettings $settings): bool
    {
        $daysOverdue = $auction->days_overdue;

        // First notice
        if (!$auction->first_notice_date && $daysOverdue >= $settings->days_before_first_notice) {
            $this->sendFirstNotice($auction, $settings);
            return true;
        }

        // Second notice
        if ($auction->first_notice_date && !$auction->second_notice_date &&
            $daysOverdue >= $settings->days_before_second_notice) {
            $this->sendSecondNotice($auction, $settings);
            return true;
        }

        // Final notice
        if ($auction->second_notice_date && !$auction->final_notice_date &&
            $daysOverdue >= $settings->days_before_final_notice) {
            $this->sendFinalNotice($auction, $settings);
            return true;
        }

        // Schedule auction
        if ($auction->final_notice_date && !$auction->auction_start_date &&
            $daysOverdue >= $settings->days_before_auction) {
            $this->scheduleAuction($auction, $settings);
            return true;
        }

        return false;
    }

    /**
     * Send first warning notice
     */
    public function sendFirstNotice(Auction $auction, AuctionSettings $settings): AuctionNotice
    {
        $notice = AuctionNotice::create([
            'auction_id' => $auction->id,
            'notice_type' => 'first_warning',
            'channel' => 'email',
            'status' => 'pending',
            'content' => $this->renderNoticeTemplate($auction, $settings->getDefaultFirstNoticeTemplate()),
        ]);

        try {
            $this->sendNoticeEmail($auction, $notice);
            $notice->markSent();
            $auction->sendFirstNotice();
        } catch (\Exception $e) {
            $notice->markFailed();
            Log::error('Failed to send first auction notice', [
                'auction_id' => $auction->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $notice;
    }

    /**
     * Send second warning notice (registered mail)
     */
    public function sendSecondNotice(Auction $auction, AuctionSettings $settings): AuctionNotice
    {
        $notice = AuctionNotice::create([
            'auction_id' => $auction->id,
            'notice_type' => 'second_warning',
            'channel' => 'registered_mail',
            'status' => 'pending',
            'content' => $this->renderNoticeTemplate($auction, $settings->second_notice_template ?? $settings->getDefaultFirstNoticeTemplate()),
        ]);

        $auction->sendSecondNotice();

        // Registered mail needs to be sent manually or via postal API
        // Mark as pending for manual processing
        $notice->update([
            'metadata' => [
                'requires_manual_send' => true,
                'recipient_address' => $auction->customer?->full_address ?? '',
            ],
        ]);

        return $notice;
    }

    /**
     * Send final notice (mise en demeure)
     */
    public function sendFinalNotice(Auction $auction, AuctionSettings $settings): AuctionNotice
    {
        $notice = AuctionNotice::create([
            'auction_id' => $auction->id,
            'notice_type' => 'final_notice',
            'channel' => 'registered_mail',
            'status' => 'pending',
            'content' => $this->renderNoticeTemplate($auction, $settings->getDefaultFinalNoticeTemplate()),
        ]);

        $auction->sendFinalNotice();

        // Also send email as backup
        try {
            $this->sendNoticeEmail($auction, $notice);
        } catch (\Exception $e) {
            Log::warning('Failed to send final notice email backup', [
                'auction_id' => $auction->id,
            ]);
        }

        return $notice;
    }

    /**
     * Schedule auction after all notices are sent
     */
    public function scheduleAuction(Auction $auction, AuctionSettings $settings): void
    {
        $startDate = now()->addDays(7);
        $endDate = $startDate->copy()->addDays($settings->auction_duration_days);

        $auction->schedule($startDate, $endDate);

        // Send auction scheduled notice
        AuctionNotice::create([
            'auction_id' => $auction->id,
            'notice_type' => 'auction_scheduled',
            'channel' => 'email',
            'status' => 'pending',
        ]);

        // Auto-list on platform if configured
        if ($settings->auto_list_on_platform && $settings->preferred_platform) {
            $this->listOnPlatform($auction, $settings);
        }
    }

    /**
     * Place a bid on an auction
     */
    public function placeBid(Auction $auction, array $data): AuctionBid
    {
        if (!$auction->is_active) {
            throw new \Exception('Cette enchère n\'est pas active.');
        }

        $minBid = $auction->current_bid > 0
            ? $auction->current_bid + 1
            : $auction->starting_bid;

        if ($data['amount'] < $minBid) {
            throw new \Exception("L'enchère minimum est de {$minBid} €.");
        }

        if ($auction->reserve_price && $data['amount'] < $auction->reserve_price) {
            // Bid accepted but reserve not met
            $data['metadata'] = ['reserve_not_met' => true];
        }

        return DB::transaction(function () use ($auction, $data) {
            return $auction->placeBid($data);
        });
    }

    /**
     * End auction and determine winner
     */
    public function endAuction(Auction $auction): void
    {
        if ($auction->status !== 'active') {
            throw new \Exception('Cette enchère ne peut pas être terminée.');
        }

        DB::transaction(function () use ($auction) {
            $auction->end();

            // Send result notices
            $this->sendAuctionResultNotices($auction);

            // If sold, process sale
            if ($auction->status === 'sold') {
                $this->processSale($auction);
            }
        });
    }

    /**
     * Process sale after auction ends
     */
    protected function processSale(Auction $auction): void
    {
        // Release box
        $auction->box->update(['status' => 'available']);

        // Close contract
        $auction->contract?->update(['status' => 'terminated']);

        // Create settlement record
        // TODO: Create payment record for winning bid

        Log::info('Auction sold', [
            'auction_id' => $auction->id,
            'winning_bid' => $auction->winning_bid,
            'bidder_id' => $auction->winning_bidder_id,
        ]);
    }

    /**
     * Customer redeems by paying debt
     */
    public function redeemAuction(Auction $auction): void
    {
        if (!in_array($auction->status, ['pending', 'notice_sent', 'scheduled'])) {
            throw new \Exception('Cette enchère ne peut plus être annulée par paiement.');
        }

        DB::transaction(function () use ($auction) {
            $auction->redeem();

            // Cancel all pending notices
            $auction->notices()
                ->where('status', 'pending')
                ->update(['status' => 'cancelled']);

            // Mark related invoices as paid would happen through payment processing
        });
    }

    /**
     * List auction on external platform
     */
    protected function listOnPlatform(Auction $auction, AuctionSettings $settings): void
    {
        // Integration with StorageTreasures, Lockerfox, etc.
        // This is a placeholder for actual API integration

        $platform = $settings->preferred_platform;

        switch ($platform) {
            case 'storage_treasures':
                // TODO: StorageTreasures API integration
                break;
            case 'lockerfox':
                // TODO: Lockerfox API integration
                break;
            default:
                Log::info('No platform integration configured', [
                    'auction_id' => $auction->id,
                    'platform' => $platform,
                ]);
        }
    }

    /**
     * Render notice template with auction data
     */
    protected function renderNoticeTemplate(Auction $auction, string $template): string
    {
        $customer = $auction->customer;
        $site = $auction->site;
        $box = $auction->box;
        $tenant = $auction->tenant;

        $data = [
            'customer_name' => $customer?->full_name ?? 'Client',
            'debt_amount' => number_format($auction->total_debt, 2, ',', ' ') . ' €',
            'box_number' => $box?->display_name ?? '',
            'site_name' => $site?->name ?? '',
            'company_name' => $tenant?->name ?? 'BoxiBox',
            'days_overdue' => $auction->days_overdue,
            'deadline_date' => now()->addDays(15)->format('d/m/Y'),
            'payment_url' => route('customer.invoices'),
            'auction_date' => $auction->auction_start_date?->format('d/m/Y à H:i') ?? '',
        ];

        return preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', function ($matches) use ($data) {
            return $data[$matches[1]] ?? $matches[0];
        }, $template);
    }

    /**
     * Send notice email
     */
    protected function sendNoticeEmail(Auction $auction, AuctionNotice $notice): void
    {
        $customer = $auction->customer;

        if (!$customer?->email) {
            throw new \Exception('No customer email available');
        }

        Mail::html($notice->content, function ($message) use ($auction, $customer, $notice) {
            $message->to($customer->email, $customer->full_name)
                ->subject($this->getNoticeSubject($notice->notice_type))
                ->from(config('mail.from.address'), $auction->tenant->name ?? 'BoxiBox');
        });
    }

    /**
     * Get email subject for notice type
     */
    protected function getNoticeSubject(string $noticeType): string
    {
        return match ($noticeType) {
            'first_warning' => 'Rappel de paiement - Action requise',
            'second_warning' => 'Deuxième avis - Paiement en retard',
            'final_notice' => 'MISE EN DEMEURE - Dernier avis avant vente aux enchères',
            'auction_scheduled' => 'Avis de vente aux enchères programmée',
            'auction_reminder' => 'Rappel - Vente aux enchères demain',
            'auction_result' => 'Résultat de la vente aux enchères',
            default => 'Notification importante concernant votre stockage',
        };
    }

    /**
     * Send auction result notices
     */
    protected function sendAuctionResultNotices(Auction $auction): void
    {
        // Notify original customer
        AuctionNotice::create([
            'auction_id' => $auction->id,
            'notice_type' => 'auction_result',
            'channel' => 'email',
            'status' => 'pending',
            'content' => $auction->status === 'sold'
                ? "Votre box a été vendu aux enchères pour {$auction->winning_bid} €."
                : "La vente aux enchères de votre box s'est terminée sans enchérisseur.",
        ]);

        // Notify winner if sold
        if ($auction->status === 'sold' && $auction->winning_bidder_id) {
            // TODO: Send winner notification with payment instructions
        }
    }

    /**
     * Get auction statistics
     */
    public function getStats(int $tenantId): array
    {
        $query = Auction::where('tenant_id', $tenantId);

        return [
            'total_pending' => (clone $query)->where('status', 'pending')->count(),
            'total_active' => (clone $query)->where('status', 'active')->count(),
            'total_sold' => (clone $query)->where('status', 'sold')->count(),
            'total_redeemed' => (clone $query)->where('status', 'redeemed')->count(),
            'total_debt_pending' => (clone $query)->whereIn('status', ['pending', 'notice_sent', 'scheduled'])->sum('total_debt'),
            'total_recovered' => (clone $query)->where('status', 'sold')->sum('winning_bid'),
            'redemption_rate' => $this->calculateRedemptionRate($tenantId),
        ];
    }

    protected function calculateRedemptionRate(int $tenantId): float
    {
        $total = Auction::where('tenant_id', $tenantId)
            ->whereIn('status', ['sold', 'redeemed', 'unsold'])
            ->count();

        $redeemed = Auction::where('tenant_id', $tenantId)
            ->where('status', 'redeemed')
            ->count();

        return $total > 0 ? round(($redeemed / $total) * 100, 2) : 0;
    }
}
