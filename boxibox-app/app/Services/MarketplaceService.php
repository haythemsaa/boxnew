<?php

namespace App\Services;

use App\Models\MarketplaceIntegration;
use App\Models\MarketplaceListing;
use App\Models\MarketplaceLead;
use App\Models\MarketplaceAnalytics;
use App\Models\Box;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MarketplaceService
{
    /**
     * Get all integrations for a tenant
     */
    public function getIntegrations(int $tenantId): Collection
    {
        return MarketplaceIntegration::where('tenant_id', $tenantId)
            ->withCount(['listings', 'leads'])
            ->get();
    }

    /**
     * Create or update integration
     */
    public function saveIntegration(int $tenantId, string $platform, array $data): MarketplaceIntegration
    {
        return MarketplaceIntegration::updateOrCreate(
            ['tenant_id' => $tenantId, 'platform' => $platform],
            $data
        );
    }

    /**
     * Test integration connection
     */
    public function testConnection(MarketplaceIntegration $integration): array
    {
        try {
            // Platform-specific connection test
            $result = match ($integration->platform) {
                'sparefoot' => $this->testSparefootConnection($integration),
                'selfstorage' => $this->testSelfStorageConnection($integration),
                'google_business' => $this->testGoogleBusinessConnection($integration),
                default => ['success' => true, 'message' => 'Connection simulated'],
            };

            return $result;
        } catch (\Exception $e) {
            Log::error("Marketplace connection test failed: {$e->getMessage()}", [
                'integration_id' => $integration->id,
                'platform' => $integration->platform,
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync inventory to marketplace
     */
    public function syncInventory(MarketplaceIntegration $integration): array
    {
        $stats = ['created' => 0, 'updated' => 0, 'errors' => 0];

        try {
            $sites = Site::where('tenant_id', $integration->tenant_id)
                ->with(['boxes' => fn($q) => $q->where('status', 'available')])
                ->get();

            foreach ($sites as $site) {
                foreach ($site->boxes as $box) {
                    try {
                        $this->syncBoxToMarketplace($integration, $site, $box);
                        $stats['updated']++;
                    } catch (\Exception $e) {
                        $stats['errors']++;
                        Log::warning("Failed to sync box {$box->id}: {$e->getMessage()}");
                    }
                }
            }

            $integration->update(['last_sync_at' => now()]);

        } catch (\Exception $e) {
            Log::error("Inventory sync failed: {$e->getMessage()}");
            throw $e;
        }

        return $stats;
    }

    /**
     * Sync a single box to marketplace
     */
    protected function syncBoxToMarketplace(MarketplaceIntegration $integration, Site $site, Box $box): MarketplaceListing
    {
        $listing = MarketplaceListing::updateOrCreate(
            [
                'integration_id' => $integration->id,
                'box_id' => $box->id,
            ],
            [
                'tenant_id' => $integration->tenant_id,
                'site_id' => $site->id,
                'listing_type' => 'unit',
                'title' => "Box {$box->number} - {$box->volume}m³",
                'description' => $box->description,
                'unit_type' => $this->getUnitType($box->volume),
                'size_m2' => $box->length * $box->width,
                'listed_price' => $this->calculateListedPrice($box, $integration),
                'original_price' => $box->current_price ?? $box->base_price,
                'features' => $box->features ?? [],
                'is_available' => $box->status === 'available',
                'quantity_available' => 1,
                'status' => 'active',
                'last_synced_at' => now(),
            ]
        );

        return $listing;
    }

    /**
     * Calculate listed price with markup
     */
    protected function calculateListedPrice(Box $box, MarketplaceIntegration $integration): float
    {
        $basePrice = $box->current_price ?? $box->base_price;
        $markup = $integration->price_markup_percent ?? 0;

        return round($basePrice * (1 + $markup / 100), 2);
    }

    /**
     * Get unit type based on volume
     */
    protected function getUnitType(float $volume): string
    {
        return match (true) {
            $volume <= 3 => 'small',
            $volume <= 8 => 'medium',
            $volume <= 15 => 'large',
            default => 'xl',
        };
    }

    /**
     * Process incoming lead from marketplace
     */
    public function processLead(array $data): MarketplaceLead
    {
        return DB::transaction(function () use ($data) {
            $lead = MarketplaceLead::create([
                'tenant_id' => $data['tenant_id'],
                'integration_id' => $data['integration_id'],
                'listing_id' => $data['listing_id'] ?? null,
                'site_id' => $data['site_id'],
                'external_lead_id' => $data['external_lead_id'] ?? null,
                'platform' => $data['platform'],
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'unit_size_requested' => $data['unit_size_requested'] ?? null,
                'move_in_date' => $data['move_in_date'] ?? null,
                'message' => $data['message'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'utm_source' => $data['utm_source'] ?? $data['platform'],
                'utm_medium' => $data['utm_medium'] ?? 'marketplace',
                'utm_campaign' => $data['utm_campaign'] ?? null,
                'status' => 'new',
                'lead_cost' => $data['lead_cost'] ?? null,
                'raw_data' => $data['raw_data'] ?? null,
            ]);

            // Update listing lead count
            if ($lead->listing_id) {
                MarketplaceListing::where('id', $lead->listing_id)->increment('leads_count');
            }

            return $lead;
        });
    }

    /**
     * Get leads with filters
     */
    public function getLeads(int $tenantId, array $filters = []): Collection
    {
        return MarketplaceLead::where('tenant_id', $tenantId)
            ->when($filters['platform'] ?? null, fn($q, $p) => $q->where('platform', $p))
            ->when($filters['status'] ?? null, fn($q, $s) => $q->where('status', $s))
            ->when($filters['site_id'] ?? null, fn($q, $s) => $q->where('site_id', $s))
            ->when($filters['date_from'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($filters['date_to'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->with(['site:id,name', 'integration:id,platform'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Get statistics
     */
    public function getStatistics(int $tenantId, ?string $platform = null, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = MarketplaceLead::where('tenant_id', $tenantId)
            ->when($platform, fn($q, $p) => $q->where('platform', $p))
            ->when($startDate, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($endDate, fn($q, $d) => $q->whereDate('created_at', '<=', $d));

        $totalLeads = $query->count();
        $newLeads = (clone $query)->where('status', 'new')->count();
        $convertedLeads = (clone $query)->where('status', 'converted')->count();
        $lostLeads = (clone $query)->where('status', 'lost')->count();

        $totalCost = (clone $query)->sum('lead_cost');
        $totalRevenue = (clone $query)->where('status', 'converted')->sum('converted_value');
        $avgResponseTime = (clone $query)->whereNotNull('response_time_minutes')->avg('response_time_minutes');

        return [
            'total_leads' => $totalLeads,
            'new_leads' => $newLeads,
            'converted_leads' => $convertedLeads,
            'lost_leads' => $lostLeads,
            'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0,
            'total_cost' => $totalCost ?? 0,
            'total_revenue' => $totalRevenue ?? 0,
            'roi' => $totalCost > 0 ? round((($totalRevenue - $totalCost) / $totalCost) * 100, 2) : 0,
            'avg_response_time' => round($avgResponseTime ?? 0),
            'cost_per_lead' => $totalLeads > 0 ? round($totalCost / $totalLeads, 2) : 0,
            'cost_per_conversion' => $convertedLeads > 0 ? round($totalCost / $convertedLeads, 2) : 0,
        ];
    }

    /**
     * Get statistics by platform
     */
    public function getStatsByPlatform(int $tenantId, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        return MarketplaceLead::where('tenant_id', $tenantId)
            ->when($startDate, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($endDate, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
            ->select('platform')
            ->selectRaw('COUNT(*) as total_leads')
            ->selectRaw('SUM(CASE WHEN status = "converted" THEN 1 ELSE 0 END) as converted')
            ->selectRaw('SUM(lead_cost) as total_cost')
            ->selectRaw('SUM(CASE WHEN status = "converted" THEN converted_value ELSE 0 END) as total_revenue')
            ->groupBy('platform')
            ->get();
    }

    /**
     * Record daily analytics
     */
    public function recordDailyAnalytics(MarketplaceIntegration $integration, Carbon $date, array $data): MarketplaceAnalytics
    {
        return MarketplaceAnalytics::updateOrCreate(
            [
                'integration_id' => $integration->id,
                'date' => $date->toDateString(),
            ],
            array_merge([
                'tenant_id' => $integration->tenant_id,
            ], $data)
        );
    }

    // Platform-specific methods (stubs for now)
    protected function testSparefootConnection(MarketplaceIntegration $integration): array
    {
        // TODO: Implement actual API call
        return ['success' => true, 'message' => 'SpareFoot connection OK'];
    }

    protected function testSelfStorageConnection(MarketplaceIntegration $integration): array
    {
        // TODO: Implement actual API call
        return ['success' => true, 'message' => 'SelfStorage.com connection OK'];
    }

    protected function testGoogleBusinessConnection(MarketplaceIntegration $integration): array
    {
        // TODO: Implement actual API call
        return ['success' => true, 'message' => 'Google Business connection OK'];
    }
}
