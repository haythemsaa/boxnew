<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\CustomerAccessCode;
use App\Models\GuestAccessCode;
use App\Models\AccessLog;
use App\Models\Customer;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class SelfServiceController extends Controller
{
    /**
     * Dashboard self-service
     */
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;
        $tenant = Auth::user()->tenant;

        // Stats globales
        $stats = [
            'total_access_codes' => CustomerAccessCode::where('tenant_id', $tenantId)->count(),
            'active_access_codes' => CustomerAccessCode::where('tenant_id', $tenantId)->active()->count(),
            'guest_codes_today' => GuestAccessCode::where('tenant_id', $tenantId)
                ->whereDate('created_at', today())
                ->count(),
            'entries_today' => AccessLog::where('tenant_id', $tenantId)
                ->whereDate('accessed_at', today())
                ->where('status', 'granted')
                ->count(),
            'denied_today' => AccessLog::where('tenant_id', $tenantId)
                ->whereDate('accessed_at', today())
                ->where('status', 'denied')
                ->count(),
        ];

        // Derniers accès
        $recentAccess = AccessLog::where('tenant_id', $tenantId)
            ->with(['customer', 'box.site'])
            ->latest('accessed_at')
            ->limit(20)
            ->get();

        // Sites avec self-service activé
        $selfServiceSites = Site::where('tenant_id', $tenantId)
            ->where('self_service_enabled', true)
            ->get(['id', 'name', 'code', 'gate_system_type']);

        return Inertia::render('Tenant/SelfService/Index', [
            'stats' => $stats,
            'recentAccess' => $recentAccess,
            'selfServiceSites' => $selfServiceSites,
            'tenantSettings' => [
                'self_service_enabled' => $tenant->self_service_enabled,
                'settings' => $tenant->self_service_settings ?? [],
            ],
        ]);
    }

    /**
     * Page des paramètres self-service
     */
    public function settings()
    {
        $tenant = Auth::user()->tenant;
        $sites = Site::where('tenant_id', $tenant->id)
            ->get(['id', 'name', 'code', 'self_service_enabled', 'gate_system_type', 'access_hours']);

        return Inertia::render('Tenant/SelfService/Settings', [
            'tenant' => [
                'self_service_enabled' => $tenant->self_service_enabled,
                'self_service_settings' => $tenant->self_service_settings ?? $this->defaultSettings(),
            ],
            'sites' => $sites,
        ]);
    }

    /**
     * Mettre à jour les paramètres self-service
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'self_service_enabled' => 'boolean',
            'settings' => 'nullable|array',
            'settings.access_hours' => 'nullable|array',
            'settings.access_hours.start' => 'nullable|string',
            'settings.access_hours.end' => 'nullable|string',
            'settings.24h_access' => 'nullable|boolean',
            'settings.require_pin' => 'nullable|boolean',
            'settings.require_qr' => 'nullable|boolean',
            'settings.auto_generate_access_code' => 'nullable|boolean',
            'settings.access_code_validity_hours' => 'nullable|integer|min:1',
            'settings.allow_guest_access' => 'nullable|boolean',
            'settings.max_guests_per_customer' => 'nullable|integer|min:0|max:10',
            'settings.notification_on_entry' => 'nullable|boolean',
            'settings.notification_on_exit' => 'nullable|boolean',
        ]);

        $tenant = Auth::user()->tenant;
        $tenant->update([
            'self_service_enabled' => $validated['self_service_enabled'] ?? false,
            'self_service_settings' => array_merge(
                $this->defaultSettings(),
                $validated['settings'] ?? []
            ),
        ]);

        return back()->with('success', 'Paramètres self-service mis à jour.');
    }

    /**
     * Mettre à jour les paramètres d'un site
     */
    public function updateSiteSettings(Request $request, Site $site)
    {
        $this->authorize('update', $site);

        $validated = $request->validate([
            'self_service_enabled' => 'boolean',
            'gate_system_type' => 'nullable|string|in:manual,keypad,qr_scanner,smart_lock,rfid',
            'gate_api_endpoint' => 'nullable|url',
            'gate_api_key' => 'nullable|string',
            'access_hours' => 'nullable|array',
        ]);

        $site->update($validated);

        return back()->with('success', 'Paramètres du site mis à jour.');
    }

    /**
     * Liste des codes d'accès
     */
    public function accessCodes(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = CustomerAccessCode::where('tenant_id', $tenantId)
            ->with(['customer', 'site', 'contract']);

        // Filtres
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('access_code', 'like', "%{$search}%")
                  ->orWhere('qr_code', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('first_name', 'like', "%{$search}%")
                         ->orWhere('last_name', 'like', "%{$search}%");
                  });
            });
        }

        $accessCodes = $query->latest()->paginate(20)->withQueryString();
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/SelfService/AccessCodes', [
            'accessCodes' => $accessCodes,
            'sites' => $sites,
            'filters' => $request->only(['status', 'site_id', 'search']),
        ]);
    }

    /**
     * Créer un code d'accès pour un client
     */
    public function createAccessCode(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'site_id' => 'required|exists:sites,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'is_permanent' => 'boolean',
            'is_master' => 'boolean',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after:valid_from',
            'max_uses' => 'nullable|integer|min:1',
        ]);

        $customer = Customer::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($validated['customer_id']);

        $site = Site::where('tenant_id', Auth::user()->tenant_id)
            ->findOrFail($validated['site_id']);

        $accessCode = CustomerAccessCode::createForCustomer(
            $customer,
            $site,
            isset($validated['contract_id']) ? Contract::find($validated['contract_id']) : null,
            [
                'is_permanent' => $validated['is_permanent'] ?? false,
                'is_master' => $validated['is_master'] ?? false,
                'valid_from' => $validated['valid_from'] ?? now(),
                'valid_until' => $validated['valid_until'] ?? null,
                'max_uses' => $validated['max_uses'] ?? null,
            ]
        );

        return back()->with('success', "Code d'accès créé: {$accessCode->access_code}");
    }

    /**
     * Révoquer un code d'accès
     */
    public function revokeAccessCode(CustomerAccessCode $accessCode)
    {
        $this->authorize('manage', $accessCode);

        $accessCode->revoke();

        return back()->with('success', "Code d'accès révoqué.");
    }

    /**
     * Suspendre/Activer un code d'accès
     */
    public function toggleAccessCode(CustomerAccessCode $accessCode)
    {
        $this->authorize('manage', $accessCode);

        if ($accessCode->status === 'active') {
            $accessCode->suspend();
            $message = "Code d'accès suspendu.";
        } else {
            $accessCode->activate();
            $message = "Code d'accès activé.";
        }

        return back()->with('success', $message);
    }

    /**
     * Régénérer un code d'accès
     */
    public function regenerateAccessCode(CustomerAccessCode $accessCode)
    {
        $this->authorize('manage', $accessCode);

        $accessCode->update([
            'access_code' => CustomerAccessCode::generateAccessCode(),
            'qr_code' => CustomerAccessCode::generateQrCode(),
            'use_count' => 0,
        ]);

        return back()->with('success', "Nouveau code généré: {$accessCode->access_code}");
    }

    /**
     * Liste des accès invités
     */
    public function guestCodes(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = GuestAccessCode::where('tenant_id', $tenantId)
            ->with(['customer', 'site']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        $guestCodes = $query->latest()->paginate(20)->withQueryString();
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        return Inertia::render('Tenant/SelfService/GuestCodes', [
            'guestCodes' => $guestCodes,
            'sites' => $sites,
            'filters' => $request->only(['status', 'site_id']),
        ]);
    }

    /**
     * Logs d'accès
     */
    public function accessLogs(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = AccessLog::where('tenant_id', $tenantId)
            ->with(['customer', 'box.site']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('box', fn($q) => $q->where('site_id', $request->site_id));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('accessed_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('accessed_at', '<=', $request->date_to);
        }

        $logs = $query->latest('accessed_at')->paginate(50)->withQueryString();
        $sites = Site::where('tenant_id', $tenantId)->get(['id', 'name']);

        // Stats pour la période
        $stats = [
            'total_granted' => (clone $query)->where('status', 'granted')->count(),
            'total_denied' => (clone $query)->where('status', 'denied')->count(),
            'total_pending' => (clone $query)->where('status', 'pending')->count(),
        ];

        return Inertia::render('Tenant/SelfService/AccessLogs', [
            'logs' => $logs,
            'sites' => $sites,
            'stats' => $stats,
            'filters' => $request->only(['status', 'site_id', 'date_from', 'date_to']),
        ]);
    }

    /**
     * API: Valider un code d'accès (pour systèmes de portail)
     */
    public function validateAccess(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'site_id' => 'required|exists:sites,id',
            'gate_id' => 'nullable|string',
            'access_method' => 'required|in:pin,qr,rfid,app',
        ]);

        // Chercher d'abord dans les codes clients
        $accessCode = CustomerAccessCode::where('access_code', $validated['code'])
            ->orWhere('qr_code', $validated['code'])
            ->orWhere('rfid_tag', $validated['code'])
            ->where('site_id', $validated['site_id'])
            ->first();

        if ($accessCode && $accessCode->isValid()) {
            $this->logAccess($accessCode, $validated, true);
            $accessCode->recordUsage();

            return response()->json([
                'success' => true,
                'access_granted' => true,
                'customer_name' => $accessCode->customer->full_name,
                'message' => 'Accès autorisé',
            ]);
        }

        // Chercher dans les codes invités
        $guestCode = GuestAccessCode::where('access_code', $validated['code'])
            ->orWhere('qr_code', $validated['code'])
            ->where('site_id', $validated['site_id'])
            ->first();

        if ($guestCode && $guestCode->isValid()) {
            $this->logGuestAccess($guestCode, $validated, true);
            $guestCode->recordUsage();

            return response()->json([
                'success' => true,
                'access_granted' => true,
                'guest_name' => $guestCode->guest_name,
                'message' => 'Accès invité autorisé',
            ]);
        }

        // Accès refusé
        $this->logDeniedAccess($validated);

        return response()->json([
            'success' => true,
            'access_granted' => false,
            'message' => 'Code invalide ou expiré',
        ], 200);
    }

    // Helper methods
    private function defaultSettings(): array
    {
        return [
            'access_hours' => ['start' => '06:00', 'end' => '22:00'],
            '24h_access' => false,
            'require_pin' => true,
            'require_qr' => true,
            'auto_generate_access_code' => true,
            'access_code_validity_hours' => 24,
            'allow_guest_access' => false,
            'max_guests_per_customer' => 2,
            'notification_on_entry' => true,
            'notification_on_exit' => true,
        ];
    }

    private function logAccess(CustomerAccessCode $accessCode, array $data, bool $success): void
    {
        // Get the customer's primary box for this site
        $box = \App\Models\Box::where('site_id', $data['site_id'])
            ->whereHas('contracts', fn($q) => $q->where('customer_id', $accessCode->customer_id)->where('status', 'active'))
            ->first();

        if ($box) {
            AccessLog::create([
                'tenant_id' => $accessCode->tenant_id,
                'box_id' => $box->id,
                'customer_id' => $accessCode->customer_id,
                'access_code_id' => $accessCode->id,
                'access_method' => $data['access_method'],
                'status' => $success ? 'granted' : 'denied',
                'gate_id' => $data['gate_id'] ?? null,
                'accessed_at' => now(),
            ]);
        }
    }

    private function logGuestAccess(GuestAccessCode $guestCode, array $data, bool $success): void
    {
        // Get the customer's primary box for this site
        $box = \App\Models\Box::where('site_id', $data['site_id'])
            ->whereHas('contracts', fn($q) => $q->where('customer_id', $guestCode->customer_id)->where('status', 'active'))
            ->first();

        if ($box) {
            AccessLog::create([
                'tenant_id' => $guestCode->tenant_id,
                'box_id' => $box->id,
                'customer_id' => $guestCode->customer_id,
                'access_method' => 'guest',
                'status' => $success ? 'granted' : 'denied',
                'gate_id' => $data['gate_id'] ?? null,
                'accessed_at' => now(),
            ]);
        }
    }

    private function logDeniedAccess(array $data): void
    {
        $site = Site::find($data['site_id']);

        // Get any box from this site for logging
        $box = \App\Models\Box::where('site_id', $data['site_id'])->first();

        if ($box) {
            AccessLog::create([
                'tenant_id' => $site?->tenant_id,
                'box_id' => $box->id,
                'access_method' => $data['access_method'],
                'status' => 'denied',
                'reason' => 'Code invalide ou expiré',
                'gate_id' => $data['gate_id'] ?? null,
                'accessed_at' => now(),
            ]);
        }
    }
}
