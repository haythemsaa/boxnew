<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\MarketplaceService;
use App\Models\MarketplaceIntegration;
use App\Models\MarketplaceLead;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MarketplaceController extends Controller
{
    public function __construct(
        protected MarketplaceService $service
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $integrations = $this->service->getIntegrations($tenantId);

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()));

        $statistics = $this->service->getStatistics($tenantId, null, $startDate, $endDate);
        $statsByPlatform = $this->service->getStatsByPlatform($tenantId, $startDate, $endDate);

        return Inertia::render('Tenant/Marketplaces/Index', [
            'integrations' => $integrations,
            'statistics' => $statistics,
            'statsByPlatform' => $statsByPlatform,
            'platforms' => MarketplaceIntegration::getPlatforms(),
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    public function leads(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $filters = $request->only(['platform', 'status', 'site_id', 'date_from', 'date_to']);
        $leads = $this->service->getLeads($tenantId, $filters);

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);
        $integrations = $this->service->getIntegrations($tenantId);

        return Inertia::render('Tenant/Marketplaces/Leads', [
            'leads' => $leads,
            'sites' => $sites,
            'integrations' => $integrations,
            'filters' => $filters,
            'statusOptions' => [
                'new' => 'Nouveau',
                'contacted' => 'Contacté',
                'qualified' => 'Qualifié',
                'tour_scheduled' => 'Visite planifiée',
                'converted' => 'Converti',
                'lost' => 'Perdu',
            ],
        ]);
    }

    public function showLead(MarketplaceLead $lead): Response
    {
        $lead->load(['site', 'integration', 'listing', 'customer', 'convertedContract']);

        return Inertia::render('Tenant/Marketplaces/LeadShow', [
            'lead' => $lead,
        ]);
    }

    public function updateLeadStatus(Request $request, MarketplaceLead $lead)
    {
        $validated = $request->validate([
            'status' => 'required|in:new,contacted,qualified,tour_scheduled,converted,lost,duplicate',
            'lost_reason' => 'nullable|string|max:500',
            'converted_contract_id' => 'nullable|exists:contracts,id',
            'converted_value' => 'nullable|numeric|min:0',
        ]);

        switch ($validated['status']) {
            case 'contacted':
                $lead->markContacted();
                break;
            case 'qualified':
                $lead->markQualified();
                break;
            case 'tour_scheduled':
                $lead->scheduleTour();
                break;
            case 'converted':
                $lead->markConverted(
                    $validated['converted_contract_id'],
                    $validated['converted_value'] ?? 0
                );
                break;
            case 'lost':
                $lead->markLost($validated['lost_reason'] ?? null);
                break;
            default:
                $lead->update(['status' => $validated['status']]);
        }

        return back()->with('success', 'Statut du lead mis à jour');
    }

    public function settings(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $integrations = $this->service->getIntegrations($tenantId);

        return Inertia::render('Tenant/Marketplaces/Settings', [
            'integrations' => $integrations,
            'platforms' => MarketplaceIntegration::getPlatforms(),
        ]);
    }

    public function saveIntegration(Request $request)
    {
        $validated = $request->validate([
            'platform' => 'required|string',
            'is_active' => 'boolean',
            'platform_account_id' => 'nullable|string',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'auto_sync_inventory' => 'boolean',
            'auto_sync_prices' => 'boolean',
            'auto_accept_leads' => 'boolean',
            'sync_interval_minutes' => 'integer|min:15|max:1440',
            'price_markup_percent' => 'numeric|min:-50|max:100',
            'commission_percent' => 'nullable|numeric|min:0|max:100',
            'lead_cost' => 'nullable|numeric|min:0',
        ]);

        $tenantId = $request->user()->tenant_id;
        $this->service->saveIntegration($tenantId, $validated['platform'], $validated);

        return back()->with('success', 'Intégration sauvegardée');
    }

    public function testConnection(Request $request, MarketplaceIntegration $integration)
    {
        $result = $this->service->testConnection($integration);

        if ($result['success']) {
            return back()->with('success', $result['message']);
        }

        return back()->with('error', $result['message']);
    }

    public function syncInventory(Request $request, MarketplaceIntegration $integration)
    {
        try {
            $stats = $this->service->syncInventory($integration);
            return back()->with('success', "Synchronisation terminée: {$stats['updated']} mis à jour, {$stats['errors']} erreurs");
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur de synchronisation: ' . $e->getMessage());
        }
    }

    public function deleteIntegration(MarketplaceIntegration $integration)
    {
        $integration->delete();
        return back()->with('success', 'Intégration supprimée');
    }
}
