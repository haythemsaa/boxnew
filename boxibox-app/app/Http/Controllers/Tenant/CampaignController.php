<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SMSCampaign;
use App\Models\Customer;
use App\Services\SMSService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CampaignController extends Controller
{
    protected SMSService $smsService;

    public function __construct(SMSService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Display campaigns list
     */
    public function index()
    {
        $tenantId = auth()->user()->tenant_id;

        $campaigns = SMSCampaign::where('tenant_id', $tenantId)
            ->latest()
            ->get();

        $stats = [
            'total_sent' => $campaigns->sum('sent_count'),
            'success_rate' => $campaigns->avg(function ($campaign) {
                if ($campaign->sent_count == 0) return 0;
                return (($campaign->sent_count - $campaign->failed_count) / $campaign->sent_count) * 100;
            }) ?? 0,
            'total_cost' => $campaigns->sum('cost'),
            'active_campaigns' => $campaigns->whereIn('status', ['scheduled', 'sending'])->count(),
        ];

        return Inertia::render('Tenant/CRM/Campaigns/Index', [
            'campaigns' => $campaigns,
            'stats' => $stats,
        ]);
    }

    /**
     * Show create form
     */
    public function create()
    {
        return Inertia::render('Tenant/CRM/Campaigns/Create');
    }

    /**
     * Store new campaign
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'message' => 'required|string|max:1600',
            'segment' => 'required|in:all,vip,at_risk,new,inactive',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $campaign = SMSCampaign::create([
            'tenant_id' => auth()->user()->tenant_id,
            'name' => $request->name,
            'message' => $request->message,
            'segment' => $request->segment,
            'status' => $request->scheduled_at ? 'scheduled' : 'draft',
            'scheduled_at' => $request->scheduled_at,
        ]);

        return redirect()->route('tenant.crm.campaigns.index')
            ->with('success', 'Campagne créée avec succès');
    }

    /**
     * Show campaign details
     */
    public function show(SMSCampaign $campaign)
    {
        $this->authorize('view', $campaign);

        $campaign->load('logs');

        return Inertia::render('Tenant/CRM/Campaigns/Show', [
            'campaign' => $campaign,
        ]);
    }

    /**
     * Send campaign
     */
    public function send(SMSCampaign $campaign)
    {
        $this->authorize('update', $campaign);

        if ($campaign->status !== 'draft' && $campaign->status !== 'scheduled') {
            return back()->with('error', 'Cette campagne ne peut pas être envoyée');
        }

        // Get recipients based on segment
        $recipients = $this->getRecipients($campaign->segment, $campaign->tenant_id);

        if ($recipients->isEmpty()) {
            return back()->with('error', 'Aucun destinataire trouvé pour ce segment');
        }

        // Update campaign status
        $campaign->update(['status' => 'sending']);

        // Send campaign
        $results = $this->smsService->sendCampaign($campaign, $recipients);

        // Update campaign status
        $campaign->update(['status' => 'sent']);

        return back()->with('success', "Campagne envoyée: {$results['sent']} succès, {$results['failed']} échecs");
    }

    /**
     * Delete campaign
     */
    public function destroy(SMSCampaign $campaign)
    {
        $this->authorize('delete', $campaign);

        if ($campaign->status === 'sending') {
            return back()->with('error', 'Impossible de supprimer une campagne en cours d\'envoi');
        }

        $campaign->delete();

        return redirect()->route('tenant.crm.campaigns.index')
            ->with('success', 'Campagne supprimée');
    }

    /**
     * Get recipients based on segment
     */
    protected function getRecipients(string $segment, int $tenantId)
    {
        $query = Customer::where('tenant_id', $tenantId)
            ->whereNotNull('phone')
            ->where('sms_consent', true);

        switch ($segment) {
            case 'vip':
                // Customers with high LTV
                $query->whereHas('contracts', function ($q) {
                    $q->where('status', 'active');
                })->take(100);
                break;

            case 'at_risk':
                // Customers with expiring contracts
                $query->whereHas('contracts', function ($q) {
                    $q->where('status', 'active')
                      ->whereBetween('end_date', [now(), now()->addDays(30)]);
                });
                break;

            case 'new':
                // Customers created in last 30 days
                $query->where('created_at', '>=', now()->subDays(30));
                break;

            case 'inactive':
                // Customers without active contracts
                $query->whereDoesntHave('contracts', function ($q) {
                    $q->where('status', 'active');
                });
                break;

            case 'all':
            default:
                // All customers
                break;
        }

        return $query->get();
    }
}
