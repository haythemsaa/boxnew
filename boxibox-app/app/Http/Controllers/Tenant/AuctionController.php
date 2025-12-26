<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Auction;
use App\Models\AuctionSettings;
use App\Services\AuctionService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuctionController extends Controller
{
    public function __construct(
        protected AuctionService $auctionService
    ) {}

    /**
     * Display auctions dashboard
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Auction::where('tenant_id', $tenantId)
            ->with(['site', 'box', 'customer', 'contract'])
            ->orderBy('created_at', 'desc');

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        if ($request->get('site_id')) {
            $query->where('site_id', $request->get('site_id'));
        }

        $auctions = $query->paginate(20);
        $stats = $this->auctionService->getStats($tenantId);

        return Inertia::render('Tenant/Auctions/Index', [
            'auctions' => $auctions,
            'stats' => $stats,
            'filters' => $request->only(['status', 'site_id']),
        ]);
    }

    /**
     * Show auction details
     */
    public function show(Auction $auction)
    {
        $this->authorize('view', $auction);

        $auction->load([
            'site',
            'box',
            'customer',
            'contract',
            'bids' => fn($q) => $q->orderBy('amount', 'desc')->limit(10),
            'notices' => fn($q) => $q->orderBy('created_at', 'desc'),
        ]);

        return Inertia::render('Tenant/Auctions/Show', [
            'auction' => $auction,
        ]);
    }

    /**
     * Manually create an auction
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'contents_description' => 'nullable|string|max:5000',
            'starting_bid' => 'nullable|numeric|min:0',
            'reserve_price' => 'nullable|numeric|min:0',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $contract = \App\Models\Contract::findOrFail($validated['contract_id']);

        // Calculate debt
        $debt = $this->auctionService->calculateDebt($contract);

        $settings = AuctionSettings::getForTenant($tenantId);

        $auction = Auction::create([
            'tenant_id' => $tenantId,
            'site_id' => $contract->site_id,
            'box_id' => $contract->box_id,
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer_id,
            'total_debt' => $debt['total'],
            'storage_fees' => $debt['storage_fees'],
            'late_fees' => $debt['late_fees'],
            'days_overdue' => $debt['days_overdue'],
            'contents_description' => $validated['contents_description'] ?? null,
            'starting_bid' => $validated['starting_bid'] ?? $settings?->calculateStartingBid($debt['total']) ?? $debt['total'] * 0.1,
            'reserve_price' => $validated['reserve_price'] ?? null,
            'status' => 'pending',
            'legal_jurisdiction' => $settings?->legal_jurisdiction ?? 'FR',
        ]);

        return redirect()->route('tenant.auctions.show', $auction)
            ->with('success', 'Enchère créée.');
    }

    /**
     * Update auction
     */
    public function update(Request $request, Auction $auction)
    {
        $this->authorize('update', $auction);

        $validated = $request->validate([
            'contents_description' => 'nullable|string|max:5000',
            'starting_bid' => 'nullable|numeric|min:0',
            'reserve_price' => 'nullable|numeric|min:0',
            'legal_notes' => 'nullable|string|max:5000',
        ]);

        $auction->update($validated);

        return back()->with('success', 'Enchère mise à jour.');
    }

    /**
     * Upload contents photos
     */
    public function uploadPhotos(Request $request, Auction $auction)
    {
        $this->authorize('update', $auction);

        $request->validate([
            'photos' => 'required|array|max:10',
            'photos.*' => 'image|max:5120',
        ]);

        $photos = $auction->contents_photos ?? [];

        foreach ($request->file('photos') as $photo) {
            $path = $photo->store("auctions/{$auction->id}", 'public');
            $photos[] = $path;
        }

        $auction->update(['contents_photos' => $photos]);

        return back()->with('success', 'Photos ajoutées.');
    }

    /**
     * Send notice manually
     */
    public function sendNotice(Request $request, Auction $auction)
    {
        $this->authorize('update', $auction);

        $validated = $request->validate([
            'notice_type' => 'required|in:first_warning,second_warning,final_notice',
        ]);

        $settings = AuctionSettings::getForTenant($auction->tenant_id);

        try {
            match ($validated['notice_type']) {
                'first_warning' => $this->auctionService->sendFirstNotice($auction, $settings),
                'second_warning' => $this->auctionService->sendSecondNotice($auction, $settings),
                'final_notice' => $this->auctionService->sendFinalNotice($auction, $settings),
            };

            return back()->with('success', 'Avis envoyé.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Schedule auction
     */
    public function schedule(Request $request, Auction $auction)
    {
        $this->authorize('update', $auction);

        $validated = $request->validate([
            'start_date' => 'required|date|after:now',
            'end_date' => 'required|date|after:start_date',
        ]);

        $auction->schedule(
            new \DateTime($validated['start_date']),
            new \DateTime($validated['end_date'])
        );

        return back()->with('success', 'Enchère programmée.');
    }

    /**
     * Start auction manually
     */
    public function start(Auction $auction)
    {
        $this->authorize('update', $auction);

        if ($auction->status !== 'scheduled') {
            return back()->withErrors(['error' => 'L\'enchère doit être programmée pour être démarrée.']);
        }

        $auction->start();

        return back()->with('success', 'Enchère démarrée.');
    }

    /**
     * End auction manually
     */
    public function end(Auction $auction)
    {
        $this->authorize('update', $auction);

        try {
            $this->auctionService->endAuction($auction);

            return back()->with('success', 'Enchère terminée.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel auction
     */
    public function cancel(Request $request, Auction $auction)
    {
        $this->authorize('update', $auction);

        $validated = $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $auction->cancel($validated['reason'] ?? null);

        return back()->with('success', 'Enchère annulée.');
    }

    /**
     * Mark as redeemed (customer paid debt)
     */
    public function redeem(Auction $auction)
    {
        $this->authorize('update', $auction);

        try {
            $this->auctionService->redeemAuction($auction);

            return back()->with('success', 'Dette remboursée, enchère annulée.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Auction settings page
     */
    public function settings()
    {
        $tenantId = auth()->user()->tenant_id;

        $settings = AuctionSettings::getForTenant($tenantId)
            ?? new AuctionSettings(['tenant_id' => $tenantId]);

        return Inertia::render('Tenant/Auctions/Settings', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update auction settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'is_enabled' => 'boolean',
            'days_before_first_notice' => 'integer|min:7|max:90',
            'days_before_second_notice' => 'integer|min:14|max:120',
            'days_before_final_notice' => 'integer|min:21|max:150',
            'days_before_auction' => 'integer|min:30|max:180',
            'minimum_debt_amount' => 'numeric|min:0',
            'auction_duration_days' => 'integer|min:1|max:30',
            'starting_bid_percentage' => 'numeric|min:1|max:100',
            'require_reserve_price' => 'boolean',
            'allow_proxy_bidding' => 'boolean',
            'preferred_platform' => 'nullable|string|max:50',
            'auto_list_on_platform' => 'boolean',
            'legal_jurisdiction' => 'string|max:10',
            'first_notice_template' => 'nullable|string|max:10000',
            'second_notice_template' => 'nullable|string|max:10000',
            'final_notice_template' => 'nullable|string|max:10000',
            'platform_fee_percentage' => 'numeric|min:0|max:50',
            'admin_fee' => 'numeric|min:0',
        ]);

        $tenantId = auth()->user()->tenant_id;

        AuctionSettings::updateOrCreate(
            ['tenant_id' => $tenantId],
            $validated
        );

        return back()->with('success', 'Paramètres mis à jour.');
    }
}
