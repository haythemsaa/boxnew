<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\GdprRequest;
use App\Models\GdprConsent;
use App\Models\Customer;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class GdprController extends Controller
{
    public function index()
    {
        $tenantId = Auth::user()->tenant_id;

        $stats = [
            'pending_requests' => GdprRequest::where('tenant_id', $tenantId)->pending()->count(),
            'overdue_requests' => GdprRequest::where('tenant_id', $tenantId)->overdue()->count(),
            'completed_this_month' => GdprRequest::where('tenant_id', $tenantId)
                ->where('status', 'completed')
                ->whereMonth('completed_at', now()->month)
                ->count(),
            'active_consents' => GdprConsent::where('tenant_id', $tenantId)->active()->count(),
        ];

        return Inertia::render('Tenant/Gdpr/Index', [
            'stats' => $stats,
        ]);
    }

    public function requests(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = GdprRequest::where('tenant_id', $tenantId)
            ->with(['customer', 'handler']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $requests = $query->latest()->paginate(20)->withQueryString();

        return Inertia::render('Tenant/Gdpr/Requests', [
            'requests' => $requests,
            'filters' => $request->only(['status', 'type']),
            'requestTypes' => $this->getRequestTypes(),
        ]);
    }

    public function createRequest()
    {
        $tenantId = Auth::user()->tenant_id;

        return Inertia::render('Tenant/Gdpr/RequestCreate', [
            'customers' => Customer::where('tenant_id', $tenantId)->get(['id', 'first_name', 'last_name', 'email']),
            'requestTypes' => $this->getRequestTypes(),
        ]);
    }

    public function storeRequest(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'requester_name' => 'required|string|max:255',
            'requester_email' => 'required|email',
            'type' => 'required|string',
            'description' => 'nullable|string',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Deadline: 30 jours pour les demandes RGPD
        $deadline = now()->addDays(30);

        $gdprRequest = GdprRequest::create([
            'tenant_id' => $tenantId,
            'customer_id' => $validated['customer_id'] ?? null,
            'request_number' => GdprRequest::generateRequestNumber(),
            'requester_name' => $validated['requester_name'],
            'requester_email' => $validated['requester_email'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'status' => 'pending',
            'deadline_at' => $deadline,
        ]);

        // Log d'audit
        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'auditable_type' => GdprRequest::class,
            'auditable_id' => $gdprRequest->id,
            'event' => 'created',
            'description' => "Demande RGPD créée: {$gdprRequest->request_number}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('tenant.gdpr.requests.show', $gdprRequest)
            ->with('success', 'Demande RGPD enregistrée.');
    }

    public function showRequest(GdprRequest $gdprRequest)
    {
        $this->authorize('view', $gdprRequest);

        $gdprRequest->load(['customer', 'handler']);

        // Historique des actions
        $auditLogs = AuditLog::where('auditable_type', GdprRequest::class)
            ->where('auditable_id', $gdprRequest->id)
            ->with('user')
            ->latest()
            ->get();

        return Inertia::render('Tenant/Gdpr/RequestShow', [
            'request' => $gdprRequest,
            'auditLogs' => $auditLogs,
        ]);
    }

    public function processRequest(Request $request, GdprRequest $gdprRequest)
    {
        $this->authorize('update', $gdprRequest);

        $validated = $request->validate([
            'action' => 'required|in:start,complete,reject,cancel',
            'response' => 'nullable|string',
            'data_exported' => 'nullable|array',
            'data_deleted' => 'nullable|array',
        ]);

        switch ($validated['action']) {
            case 'start':
                $gdprRequest->update([
                    'status' => 'in_progress',
                    'handled_by' => Auth::id(),
                ]);
                break;

            case 'complete':
                $gdprRequest->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'response' => $validated['response'] ?? null,
                    'data_exported' => $validated['data_exported'] ?? null,
                    'data_deleted' => $validated['data_deleted'] ?? null,
                ]);
                break;

            case 'reject':
                $gdprRequest->update([
                    'status' => 'rejected',
                    'response' => $validated['response'] ?? null,
                ]);
                break;

            case 'cancel':
                $gdprRequest->update([
                    'status' => 'cancelled',
                    'response' => $validated['response'] ?? null,
                ]);
                break;
        }

        // Log d'audit
        AuditLog::create([
            'tenant_id' => $gdprRequest->tenant_id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'auditable_type' => GdprRequest::class,
            'auditable_id' => $gdprRequest->id,
            'event' => 'status_changed',
            'new_values' => ['status' => $gdprRequest->status],
            'description' => "Statut modifié: {$gdprRequest->status}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Demande mise à jour.');
    }

    public function exportCustomerData(Customer $customer)
    {
        $this->authorize('view', $customer);

        // Collecter toutes les données du client
        $data = [
            'personal_info' => $customer->only([
                'first_name', 'last_name', 'email', 'phone', 'address',
                'city', 'postal_code', 'country', 'created_at'
            ]),
            'contracts' => $customer->contracts->map(fn($c) => $c->only([
                'contract_number', 'start_date', 'end_date', 'status', 'monthly_price'
            ])),
            'invoices' => $customer->invoices->map(fn($i) => $i->only([
                'invoice_number', 'total', 'status', 'due_date', 'paid_at'
            ])),
            'payments' => $customer->payments->map(fn($p) => $p->only([
                'paid_at', 'amount', 'payment_method', 'reference'
            ])),
            'consents' => GdprConsent::where('customer_id', $customer->id)->get()->map(fn($c) => $c->only([
                'consent_type', 'is_granted', 'granted_at', 'withdrawn_at'
            ])),
        ];

        // Log d'audit
        AuditLog::create([
            'tenant_id' => $customer->tenant_id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'auditable_type' => Customer::class,
            'auditable_id' => $customer->id,
            'event' => 'data_exported',
            'description' => "Export des données du client: {$customer->full_name}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="customer_data_' . $customer->id . '.json"');
    }

    // Consentements
    public function consents(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = GdprConsent::where('tenant_id', $tenantId)
            ->with('customer');

        if ($request->filled('type')) {
            $query->where('consent_type', $request->type);
        }
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->active();
            } else {
                $query->withdrawn();
            }
        }

        $consents = $query->latest()->paginate(20)->withQueryString();

        // Stats par type
        $consentStats = GdprConsent::where('tenant_id', $tenantId)
            ->selectRaw('consent_type, COUNT(*) as total, SUM(CASE WHEN withdrawn_at IS NULL THEN 1 ELSE 0 END) as active')
            ->groupBy('consent_type')
            ->get();

        return Inertia::render('Tenant/Gdpr/Consents', [
            'consents' => $consents,
            'consentStats' => $consentStats,
            'filters' => $request->only(['type', 'status']),
            'consentTypes' => $this->getConsentTypes(),
        ]);
    }

    public function recordConsent(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'consent_type' => 'required|string',
            'version' => 'nullable|string',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Retirer le consentement existant si présent
        GdprConsent::where('tenant_id', $tenantId)
            ->where('customer_id', $validated['customer_id'])
            ->where('consent_type', $validated['consent_type'])
            ->whereNull('withdrawn_at')
            ->update(['withdrawn_at' => now()]);

        // Créer le nouveau consentement
        GdprConsent::create([
            'tenant_id' => $tenantId,
            'customer_id' => $validated['customer_id'],
            'consent_type' => $validated['consent_type'],
            'version' => $validated['version'] ?? '1.0',
            'is_granted' => true,
            'granted_at' => now(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Consentement enregistré.');
    }

    public function withdrawConsent(GdprConsent $consent)
    {
        $consent->update(['withdrawn_at' => now()]);

        // Log d'audit
        AuditLog::create([
            'tenant_id' => $consent->tenant_id,
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'auditable_type' => GdprConsent::class,
            'auditable_id' => $consent->id,
            'event' => 'consent_withdrawn',
            'description' => "Consentement retiré: {$consent->consent_type_label}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return back()->with('success', 'Consentement retiré.');
    }

    // Journal d'audit
    public function auditLog(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $query = AuditLog::where('tenant_id', $tenantId)
            ->with('user');

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->latest()->paginate(50)->withQueryString();

        return Inertia::render('Tenant/Gdpr/AuditLog', [
            'logs' => $logs,
            'filters' => $request->only(['event', 'user_id', 'date_from', 'date_to']),
        ]);
    }

    protected function getRequestTypes(): array
    {
        return [
            ['value' => 'access', 'label' => 'Droit d\'accès'],
            ['value' => 'rectification', 'label' => 'Droit de rectification'],
            ['value' => 'erasure', 'label' => 'Droit à l\'effacement'],
            ['value' => 'portability', 'label' => 'Droit à la portabilité'],
            ['value' => 'restriction', 'label' => 'Droit à la limitation'],
            ['value' => 'objection', 'label' => 'Droit d\'opposition'],
        ];
    }

    protected function getConsentTypes(): array
    {
        return [
            ['value' => 'marketing_email', 'label' => 'Emails marketing'],
            ['value' => 'marketing_sms', 'label' => 'SMS marketing'],
            ['value' => 'marketing_phone', 'label' => 'Appels marketing'],
            ['value' => 'data_processing', 'label' => 'Traitement des données'],
            ['value' => 'data_sharing', 'label' => 'Partage des données'],
            ['value' => 'terms_conditions', 'label' => 'CGU/CGV'],
            ['value' => 'privacy_policy', 'label' => 'Politique de confidentialité'],
        ];
    }
}
