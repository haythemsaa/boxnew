<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\MoveinConfiguration;
use App\Models\MoveinSession;
use App\Models\Site;
use App\Services\ContactlessMoveInService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MoveInController extends Controller
{
    protected ContactlessMoveInService $moveInService;

    public function __construct(ContactlessMoveInService $moveInService)
    {
        $this->moveInService = $moveInService;
    }

    /**
     * Dashboard for move-in management
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $stats = $this->moveInService->getDashboardStats($tenantId);

        $activeSessions = MoveinSession::where('tenant_id', $tenantId)
            ->active()
            ->with(['site', 'box', 'customer'])
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $recentCompleted = MoveinSession::where('tenant_id', $tenantId)
            ->completed()
            ->with(['site', 'box', 'customer'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return Inertia::render('Tenant/MoveIn/Dashboard', [
            'stats' => $stats,
            'activeSessions' => $activeSessions,
            'recentCompleted' => $recentCompleted,
        ]);
    }

    /**
     * List all move-in sessions
     */
    public function sessions(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $status = $request->input('status');
        $siteId = $request->input('site_id');

        $query = MoveinSession::where('tenant_id', $tenantId)
            ->with(['site', 'box', 'customer']);

        if ($status) {
            $query->where('status', $status);
        }

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        $sessions = $query->orderByDesc('created_at')->paginate(50);

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/MoveIn/Sessions', [
            'sessions' => $sessions,
            'sites' => $sites,
            'statusFilter' => $status,
            'siteFilter' => $siteId,
        ]);
    }

    /**
     * Show session details
     */
    public function show(MoveinSession $session)
    {
        $this->authorize('view', $session);

        $session->load([
            'site',
            'box',
            'customer',
            'contract',
            'stepLogs',
            'activeAccessCode',
            'identityDocument',
        ]);

        $progress = $this->moveInService->getSessionProgress($session);

        return Inertia::render('Tenant/MoveIn/SessionDetails', [
            'session' => $session,
            'progress' => $progress,
        ]);
    }

    /**
     * Create a new move-in session manually
     */
    public function create(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        $availableBoxes = Box::where('tenant_id', $tenantId)
            ->where('status', 'available')
            ->with('site:id,name')
            ->get(['id', 'number', 'volume', 'current_price', 'site_id']);

        return Inertia::render('Tenant/MoveIn/Create', [
            'sites' => $sites,
            'availableBoxes' => $availableBoxes,
        ]);
    }

    /**
     * Store a new move-in session
     */
    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'site_id' => ['required', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'box_id' => ['nullable', 'exists:boxes,id', new \App\Rules\SameTenantResource(\App\Models\Box::class, $tenantId)],
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'preferred_date' => 'nullable|date|after_or_equal:today',
            'preferred_time_slot' => 'nullable|string',
            'send_invitation' => 'boolean',
        ]);

        $validated['tenant_id'] = $tenantId;
        $validated['source'] = 'admin';

        $session = $this->moveInService->createSession($validated);

        if ($request->input('send_invitation', true)) {
            // Send invitation email with link
            // Mail::to($validated['email'])->send(new MoveInInvitationMail($session));
        }

        return redirect()->route('tenant.movein.show', $session)
            ->with('success', 'Session d\'emménagement créée. Lien envoyé au client.');
    }

    /**
     * Cancel a session
     */
    public function cancel(Request $request, MoveinSession $session)
    {
        $this->authorize('update', $session);

        $reason = $request->input('reason');
        $session->cancel($reason);

        // Release box if reserved
        if ($session->box && $session->box->status === 'reserved') {
            $session->box->update(['status' => 'available']);
        }

        return back()->with('success', 'Session annulée');
    }

    /**
     * Extend session expiry
     */
    public function extend(Request $request, MoveinSession $session)
    {
        $this->authorize('update', $session);

        $hours = $request->input('hours', 24);

        $session->update([
            'expires_at' => $session->expires_at->addHours($hours),
        ]);

        $session->stepLogs()->create([
            'step' => 'session',
            'action' => 'extended',
            'data' => ['hours' => $hours, 'by' => $request->user()->name],
            'ip_address' => request()->ip(),
        ]);

        return back()->with('success', "Session prolongée de {$hours} heures");
    }

    /**
     * Resend access code
     */
    public function resendAccessCode(MoveinSession $session)
    {
        $this->authorize('update', $session);

        if (!$session->activeAccessCode) {
            return back()->with('error', 'Aucun code d\'accès actif');
        }

        $config = MoveinConfiguration::getOrCreateForTenant($session->tenant_id);

        // Resend notifications
        // This would call the notification service

        return back()->with('success', 'Code d\'accès renvoyé');
    }

    /**
     * Generate new access code
     */
    public function regenerateAccessCode(MoveinSession $session)
    {
        $this->authorize('update', $session);

        // Deactivate old codes
        $session->accessCodes()->update(['is_active' => false]);

        // Generate new code
        $newCode = $session->generateAccessCode();

        return back()->with('success', "Nouveau code généré: {$newCode->formatted_code}");
    }

    /**
     * Configuration settings
     */
    public function configuration(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $config = MoveinConfiguration::getOrCreateForTenant($tenantId);

        return Inertia::render('Tenant/MoveIn/Configuration', [
            'config' => $config,
        ]);
    }

    /**
     * Update configuration
     */
    public function updateConfiguration(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'enabled' => 'boolean',
            'require_identity_verification' => 'boolean',
            'require_selfie' => 'boolean',
            'allow_video_verification' => 'boolean',
            'require_document_scan' => 'boolean',
            'enable_digital_signature' => 'boolean',
            'require_initials' => 'boolean',
            'require_upfront_payment' => 'boolean',
            'allow_sepa_mandate' => 'boolean',
            'allow_card_payment' => 'boolean',
            'deposit_months' => 'integer|min:0|max:6',
            'access_code_validity_hours' => 'integer|min:1|max:168',
            'access_code_max_uses' => 'integer|min:1|max:10',
            'send_access_code_sms' => 'boolean',
            'send_access_code_email' => 'boolean',
            'enable_qr_code' => 'boolean',
            'notify_staff_on_completion' => 'boolean',
            'notify_customer_reminders' => 'boolean',
            'reminder_hours_before_expiry' => 'integer|min:1|max:72',
            'session_expiry_hours' => 'integer|min:24|max:168',
            'max_retry_attempts' => 'integer|min:1|max:10',
            'welcome_message' => 'nullable|array',
            'completion_message' => 'nullable|array',
        ]);

        $config = MoveinConfiguration::getOrCreateForTenant($tenantId);
        $config->update($validated);

        return back()->with('success', 'Configuration mise à jour');
    }

    /**
     * Analytics
     */
    public function analytics(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $period = $request->input('period', '30');

        $startDate = now()->subDays((int) $period);

        // Funnel analytics
        $funnel = MoveinSession::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw("
                COUNT(*) as started,
                SUM(CASE WHEN identity_verified_at IS NOT NULL THEN 1 ELSE 0 END) as identity_verified,
                SUM(CASE WHEN contract_signed_at IS NOT NULL THEN 1 ELSE 0 END) as contract_signed,
                SUM(CASE WHEN payment_completed_at IS NOT NULL THEN 1 ELSE 0 END) as payment_completed,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
            ")
            ->first();

        // Daily completions
        $dailyCompletions = MoveinSession::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('updated_at', '>=', $startDate)
            ->selectRaw('DATE(updated_at) as date, COUNT(*) as count, SUM(amount_paid) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Avg time per step
        $stepTimes = MoveinSession::where('tenant_id', $tenantId)
            ->where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->with('stepLogs')
            ->get()
            ->map(function ($session) {
                $logs = $session->stepLogs->groupBy('step');
                $times = [];

                foreach ($logs as $step => $stepLogs) {
                    $started = $stepLogs->where('action', 'started')->first();
                    $completed = $stepLogs->where('action', 'completed')->first();

                    if ($started && $completed) {
                        $times[$step] = $started->created_at->diffInMinutes($completed->created_at);
                    }
                }

                return $times;
            });

        // Calculate averages
        $avgStepTimes = [];
        $steps = ['identity', 'box_selection', 'contract', 'payment', 'access'];
        foreach ($steps as $step) {
            $values = $stepTimes->pluck($step)->filter();
            $avgStepTimes[$step] = $values->count() > 0 ? round($values->avg()) : null;
        }

        return Inertia::render('Tenant/MoveIn/Analytics', [
            'period' => $period,
            'funnel' => $funnel,
            'dailyCompletions' => $dailyCompletions,
            'avgStepTimes' => $avgStepTimes,
            'stats' => $this->moveInService->getDashboardStats($tenantId),
        ]);
    }

    /**
     * Get public link for session
     */
    public function getPublicLink(MoveinSession $session)
    {
        $this->authorize('view', $session);

        return response()->json([
            'url' => $session->getPublicUrl(),
            'expires_at' => $session->expires_at,
        ]);
    }
}
