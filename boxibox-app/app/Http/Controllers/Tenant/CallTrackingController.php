<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\CallTrackingService;
use App\Models\TrackingNumber;
use App\Models\CallRecord;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CallTrackingController extends Controller
{
    public function __construct(
        protected CallTrackingService $service
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()));
        $source = $request->input('source');

        $statistics = $this->service->getStatistics($tenantId, $startDate, $endDate, $source);
        $statsBySource = $this->service->getStatsBySource($tenantId, $startDate, $endDate);
        $trackingNumbers = $this->service->getTrackingNumbers($tenantId);
        $missedCalls = $this->service->getMissedCalls($tenantId, now()->startOfDay());
        $callbacksRequired = $this->service->getCallbacksRequired($tenantId);

        return Inertia::render('Tenant/CallTracking/Index', [
            'statistics' => $statistics,
            'statsBySource' => $statsBySource,
            'trackingNumbers' => $trackingNumbers,
            'missedCalls' => $missedCalls,
            'callbacksRequired' => $callbacksRequired,
            'sourceOptions' => TrackingNumber::getSourceOptions(),
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    public function calls(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $filters = $request->only(['site_id', 'source', 'status', 'direction', 'date_from', 'date_to']);
        $calls = $this->service->getCallHistory($tenantId, $filters);

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);
        $trackingNumbers = $this->service->getTrackingNumbers($tenantId);

        return Inertia::render('Tenant/CallTracking/Calls', [
            'calls' => $calls,
            'sites' => $sites,
            'trackingNumbers' => $trackingNumbers,
            'filters' => $filters,
            'sourceOptions' => TrackingNumber::getSourceOptions(),
            'statusOptions' => [
                'completed' => 'Terminé',
                'no_answer' => 'Sans réponse',
                'busy' => 'Occupé',
                'voicemail' => 'Messagerie',
                'failed' => 'Échec',
            ],
        ]);
    }

    public function showCall(CallRecord $call): Response
    {
        $call->load(['trackingNumber', 'site', 'customer', 'answeredByUser']);

        return Inertia::render('Tenant/CallTracking/CallShow', [
            'call' => $call,
        ]);
    }

    public function numbers(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $trackingNumbers = $this->service->getTrackingNumbers($tenantId);
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/CallTracking/Numbers', [
            'trackingNumbers' => $trackingNumbers,
            'sites' => $sites,
            'sourceOptions' => TrackingNumber::getSourceOptions(),
        ]);
    }

    public function storeNumber(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string|unique:tracking_numbers,phone_number',
            'friendly_name' => 'nullable|string|max:100',
            'forward_to' => 'required|string',
            'site_id' => 'nullable|exists:sites,id',
            'source' => 'required|string',
            'medium' => 'nullable|string',
            'campaign' => 'nullable|string',
            'number_type' => 'in:local,toll_free,mobile',
            'sms_enabled' => 'boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $this->service->createTrackingNumber($tenantId, $validated);

        return back()->with('success', 'Numéro de tracking ajouté');
    }

    public function updateNumber(Request $request, TrackingNumber $number)
    {
        $validated = $request->validate([
            'friendly_name' => 'nullable|string|max:100',
            'forward_to' => 'required|string',
            'site_id' => 'nullable|exists:sites,id',
            'source' => 'required|string',
            'medium' => 'nullable|string',
            'campaign' => 'nullable|string',
            'is_active' => 'boolean',
            'sms_enabled' => 'boolean',
        ]);

        $number->update($validated);

        return back()->with('success', 'Numéro mis à jour');
    }

    public function deleteNumber(TrackingNumber $number)
    {
        $number->delete();
        return back()->with('success', 'Numéro supprimé');
    }

    public function settings(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $settings = $this->service->getSettings($tenantId);

        return Inertia::render('Tenant/CallTracking/Settings', [
            'settings' => $settings,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'is_enabled' => 'boolean',
            'provider' => 'in:twilio,callrail,ringcentral',
            'api_key' => 'nullable|string',
            'api_secret' => 'nullable|string',
            'account_sid' => 'nullable|string',
            'record_calls' => 'boolean',
            'recording_retention_days' => 'integer|min:7|max:365',
            'transcribe_calls' => 'boolean',
            'notify_missed_calls' => 'boolean',
            'notify_after_hours' => 'boolean',
            'notification_email' => 'nullable|email',
            'notification_phone' => 'nullable|string',
            'business_hours' => 'array',
            'timezone' => 'string',
            'enable_voicemail' => 'boolean',
            'voicemail_greeting' => 'nullable|string',
            'enable_sms_autoresponse' => 'boolean',
            'sms_autoresponse_message' => 'nullable|string|max:160',
        ]);

        $tenantId = $request->user()->tenant_id;
        $this->service->updateSettings($tenantId, $validated);

        return back()->with('success', 'Paramètres mis à jour');
    }

    public function scheduleCallback(Request $request, CallRecord $call)
    {
        $validated = $request->validate([
            'callback_at' => 'required|date|after:now',
        ]);

        $call->scheduleCallback(Carbon::parse($validated['callback_at']));

        return back()->with('success', 'Rappel programmé');
    }

    public function completeCallback(CallRecord $call)
    {
        $call->completeCallback();
        return back()->with('success', 'Rappel marqué comme effectué');
    }

    public function markConverted(Request $request, CallRecord $call)
    {
        $validated = $request->validate([
            'contract_id' => 'nullable|exists:contracts,id',
            'value' => 'nullable|numeric|min:0',
        ]);

        $call->markConverted($validated['contract_id'] ?? null, $validated['value'] ?? null);

        return back()->with('success', 'Appel marqué comme converti');
    }

    public function addNote(Request $request, CallRecord $call)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:2000',
        ]);

        $call->update(['notes' => $validated['notes']]);

        return back()->with('success', 'Note ajoutée');
    }
}
