<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\KioskService;
use App\Models\KioskDevice;
use App\Models\KioskSession;
use App\Models\KioskIssue;
use App\Models\Site;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KioskController extends Controller
{
    public function __construct(
        protected KioskService $service
    ) {}

    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $kiosks = $this->service->getKiosks($tenantId);

        $startDate = Carbon::parse($request->input('start_date', now()->startOfMonth()));
        $endDate = Carbon::parse($request->input('end_date', now()));

        $statistics = $this->service->getStatistics($tenantId, null, $startDate, $endDate);
        $funnel = $this->service->getSessionFunnel($tenantId, $startDate, $endDate);
        $openIssues = $this->service->getOpenIssues($tenantId);

        return Inertia::render('Tenant/Kiosks/Index', [
            'kiosks' => $kiosks,
            'statistics' => $statistics,
            'funnel' => $funnel,
            'openIssues' => $openIssues,
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }

    public function show(KioskDevice $kiosk): Response
    {
        $kiosk->load('site');

        $statistics = $this->service->getStatistics($kiosk->tenant_id, $kiosk->id, now()->subDays(30), now());
        $recentSessions = KioskSession::where('kiosk_id', $kiosk->id)
            ->orderByDesc('started_at')
            ->limit(20)
            ->get();
        $issues = $kiosk->issues()->orderByDesc('created_at')->limit(10)->get();

        return Inertia::render('Tenant/Kiosks/Show', [
            'kiosk' => $kiosk,
            'statistics' => $statistics,
            'recentSessions' => $recentSessions,
            'issues' => $issues,
            'config' => $this->service->getKioskConfig($kiosk),
        ]);
    }

    public function create(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/Kiosks/Create', [
            'sites' => $sites,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'site_id' => 'required|exists:sites,id',
            'location_description' => 'nullable|string|max:255',
            'language' => 'in:fr,en,nl,de',
            'allow_new_rentals' => 'boolean',
            'allow_payments' => 'boolean',
            'allow_access_code_generation' => 'boolean',
            'show_prices' => 'boolean',
            'require_id_verification' => 'boolean',
            'primary_color' => 'nullable|string',
            'secondary_color' => 'nullable|string',
            'welcome_message' => 'nullable|string|max:500',
            'idle_timeout_seconds' => 'integer|min:30|max:600',
            'enable_screensaver' => 'boolean',
        ]);

        $tenantId = $request->user()->tenant_id;
        $kiosk = $this->service->createKiosk($tenantId, $validated);

        return redirect()->route('tenant.kiosks.show', $kiosk)
            ->with('success', "Kiosque créé. Code d'appairage: {$kiosk->device_code}");
    }

    public function edit(KioskDevice $kiosk): Response
    {
        $sites = Site::where('tenant_id', $kiosk->tenant_id)->get(['id', 'name']);

        return Inertia::render('Tenant/Kiosks/Edit', [
            'kiosk' => $kiosk,
            'sites' => $sites,
        ]);
    }

    public function update(Request $request, KioskDevice $kiosk)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'site_id' => 'required|exists:sites,id',
            'location_description' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'language' => 'in:fr,en,nl,de',
            'allow_new_rentals' => 'boolean',
            'allow_payments' => 'boolean',
            'allow_access_code_generation' => 'boolean',
            'show_prices' => 'boolean',
            'require_id_verification' => 'boolean',
            'primary_color' => 'nullable|string',
            'secondary_color' => 'nullable|string',
            'welcome_message' => 'nullable|string|max:500',
            'idle_timeout_seconds' => 'integer|min:30|max:600',
            'enable_screensaver' => 'boolean',
            'admin_pin' => 'nullable|string|min:4|max:8',
        ]);

        $this->service->updateKiosk($kiosk, $validated);

        return back()->with('success', 'Kiosque mis à jour');
    }

    public function destroy(KioskDevice $kiosk)
    {
        $kiosk->delete();
        return redirect()->route('tenant.kiosks.index')
            ->with('success', 'Kiosque supprimé');
    }

    public function regenerateCode(KioskDevice $kiosk)
    {
        $newCode = $kiosk->generateNewCode();

        return back()->with('success', "Nouveau code d'appairage: {$newCode}");
    }

    public function sessions(Request $request, KioskDevice $kiosk): Response
    {
        $sessions = KioskSession::where('kiosk_id', $kiosk->id)
            ->when($request->input('date'), fn($q, $d) => $q->whereDate('started_at', $d))
            ->when($request->input('purpose'), fn($q, $p) => $q->where('purpose', $p))
            ->when($request->input('outcome'), fn($q, $o) => $q->where('outcome', $o))
            ->orderByDesc('started_at')
            ->paginate(50);

        return Inertia::render('Tenant/Kiosks/Sessions', [
            'kiosk' => $kiosk,
            'sessions' => $sessions,
            'purposeOptions' => [
                'browse' => 'Navigation',
                'new_rental' => 'Nouvelle location',
                'payment' => 'Paiement',
                'access_code' => 'Code d\'accès',
                'support' => 'Support',
                'account' => 'Mon compte',
            ],
        ]);
    }

    public function issues(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $issues = KioskIssue::where('tenant_id', $tenantId)
            ->when($request->input('status'), fn($q, $s) => $q->where('status', $s))
            ->when($request->input('kiosk_id'), fn($q, $k) => $q->where('kiosk_id', $k))
            ->with(['kiosk:id,name,site_id', 'kiosk.site:id,name'])
            ->orderByDesc('created_at')
            ->paginate(20);

        $kiosks = $this->service->getKiosks($tenantId);

        return Inertia::render('Tenant/Kiosks/Issues', [
            'issues' => $issues,
            'kiosks' => $kiosks,
        ]);
    }

    public function storeIssue(Request $request, KioskDevice $kiosk)
    {
        $validated = $request->validate([
            'type' => 'required|in:offline,hardware,software,payment_issue,printer_issue,connectivity,other',
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:2000',
            'severity' => 'in:low,medium,high,critical',
        ]);

        $validated['reported_by'] = $request->user()->id;

        $this->service->reportIssue($kiosk, $validated);

        return back()->with('success', 'Problème signalé');
    }

    public function resolveIssue(Request $request, KioskIssue $issue)
    {
        $validated = $request->validate([
            'resolution_notes' => 'nullable|string|max:2000',
        ]);

        $this->service->resolveIssue($issue, $request->user()->id, $validated['resolution_notes'] ?? null);

        return back()->with('success', 'Problème résolu');
    }

    public function analytics(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $startDate = Carbon::parse($request->input('start_date', now()->subDays(30)));
        $endDate = Carbon::parse($request->input('end_date', now()));

        $kiosks = $this->service->getKiosks($tenantId);

        // Get daily analytics for chart
        $dailyStats = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $stats = $this->service->getStatistics($tenantId, null, $currentDate, $currentDate);
            $dailyStats[] = [
                'date' => $currentDate->toDateString(),
                'sessions' => $stats['total_sessions'],
                'rentals' => $stats['completed_rentals'],
                'revenue' => $stats['total_revenue'],
            ];
            $currentDate->addDay();
        }

        $overallStats = $this->service->getStatistics($tenantId, null, $startDate, $endDate);
        $funnel = $this->service->getSessionFunnel($tenantId, $startDate, $endDate);

        return Inertia::render('Tenant/Kiosks/Analytics', [
            'kiosks' => $kiosks,
            'dailyStats' => $dailyStats,
            'overallStats' => $overallStats,
            'funnel' => $funnel,
            'dateRange' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }
}
