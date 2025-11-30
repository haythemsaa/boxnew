<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\SepaMandate;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SepaMandateController extends Controller
{
    /**
     * Display a listing of SEPA mandates.
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = SepaMandate::where('tenant_id', $tenantId)
            ->with(['customer', 'contract']);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('rum', 'like', "%{$search}%")
                  ->orWhere('account_holder', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $mandates = $query->orderBy('created_at', 'desc')->paginate(15);

        // Stats
        $stats = [
            'total' => SepaMandate::where('tenant_id', $tenantId)->count(),
            'active' => SepaMandate::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'pending' => SepaMandate::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'suspended' => SepaMandate::where('tenant_id', $tenantId)->where('status', 'suspended')->count(),
            'cancelled' => SepaMandate::where('tenant_id', $tenantId)->where('status', 'cancelled')->count(),
            'total_collected' => SepaMandate::where('tenant_id', $tenantId)->sum('total_collected'),
        ];

        return Inertia::render('Tenant/SepaMandate/Index', [
            'mandates' => $mandates,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Show the form for creating a new mandate.
     */
    public function create()
    {
        $tenantId = auth()->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->orderBy('last_name')
            ->get();

        return Inertia::render('Tenant/SepaMandate/Create', [
            'customers' => $customers,
        ]);
    }

    /**
     * Store a newly created mandate.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'contract_id' => 'nullable|exists:contracts,id',
            'ics' => 'required|string|max:35',
            'type' => 'required|in:recurrent,one_time',
            'iban' => 'required|string|max:34',
            'bic' => 'nullable|string|max:11',
            'account_holder' => 'required|string|max:255',
            'signature_date' => 'required|date',
            'signature_place' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        // Validate IBAN format
        if (!SepaMandate::validateIban($validated['iban'])) {
            return back()->withErrors(['iban' => 'IBAN invalide.']);
        }

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['status'] = 'pending';

        SepaMandate::create($validated);

        return redirect()->route('tenant.sepa-mandates.index')
            ->with('success', 'Mandat SEPA créé avec succès.');
    }

    /**
     * Display the specified mandate.
     */
    public function show(SepaMandate $sepaMandate)
    {
        if ($sepaMandate->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $sepaMandate->load(['customer', 'contract']);

        return Inertia::render('Tenant/SepaMandate/Show', [
            'mandate' => $sepaMandate,
        ]);
    }

    /**
     * Activate a pending mandate.
     */
    public function activate(SepaMandate $sepaMandate)
    {
        if ($sepaMandate->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($sepaMandate->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Seul un mandat en attente peut être activé.');
        }

        $sepaMandate->activate();

        return redirect()->back()
            ->with('success', 'Mandat SEPA activé avec succès.');
    }

    /**
     * Suspend an active mandate.
     */
    public function suspend(SepaMandate $sepaMandate)
    {
        if ($sepaMandate->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($sepaMandate->status !== 'active') {
            return redirect()->back()
                ->with('error', 'Seul un mandat actif peut être suspendu.');
        }

        $sepaMandate->suspend();

        return redirect()->back()
            ->with('success', 'Mandat SEPA suspendu avec succès.');
    }

    /**
     * Reactivate a suspended mandate.
     */
    public function reactivate(SepaMandate $sepaMandate)
    {
        if ($sepaMandate->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($sepaMandate->status !== 'suspended') {
            return redirect()->back()
                ->with('error', 'Seul un mandat suspendu peut être réactivé.');
        }

        $sepaMandate->activate();

        return redirect()->back()
            ->with('success', 'Mandat SEPA réactivé avec succès.');
    }

    /**
     * Cancel a mandate.
     */
    public function cancel(SepaMandate $sepaMandate)
    {
        if ($sepaMandate->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($sepaMandate->status === 'cancelled') {
            return redirect()->back()
                ->with('error', 'Ce mandat est déjà annulé.');
        }

        $sepaMandate->cancel();

        return redirect()->back()
            ->with('success', 'Mandat SEPA annulé avec succès.');
    }

    /**
     * Remove the specified mandate.
     */
    public function destroy(SepaMandate $sepaMandate)
    {
        if ($sepaMandate->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $sepaMandate->delete();

        return redirect()->route('tenant.sepa-mandates.index')
            ->with('success', 'Mandat SEPA supprimé avec succès.');
    }

    /**
     * Download mandate document.
     */
    public function download(SepaMandate $sepaMandate)
    {
        if ($sepaMandate->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if (!$sepaMandate->signed_document_path) {
            return redirect()->back()
                ->with('error', 'Document non disponible.');
        }

        return response()->download(storage_path('app/' . $sepaMandate->signed_document_path));
    }
}
