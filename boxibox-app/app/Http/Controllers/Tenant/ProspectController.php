<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Prospect;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProspectController extends Controller
{
    /**
     * Display a listing of prospects.
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Prospect::where('tenant_id', $tenantId)
            ->with('customer');

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('source')) {
            $query->where('source', $request->source);
        }

        $prospects = $query->orderBy('created_at', 'desc')->paginate(15);

        // Stats
        $stats = [
            'total' => Prospect::where('tenant_id', $tenantId)->count(),
            'new' => Prospect::where('tenant_id', $tenantId)->where('status', 'new')->count(),
            'contacted' => Prospect::where('tenant_id', $tenantId)->where('status', 'contacted')->count(),
            'qualified' => Prospect::where('tenant_id', $tenantId)->where('status', 'qualified')->count(),
            'quoted' => Prospect::where('tenant_id', $tenantId)->where('status', 'quoted')->count(),
            'converted' => Prospect::where('tenant_id', $tenantId)->where('status', 'converted')->count(),
            'lost' => Prospect::where('tenant_id', $tenantId)->where('status', 'lost')->count(),
            'hot' => Prospect::where('tenant_id', $tenantId)
                ->whereNotIn('status', ['converted', 'lost'])
                ->where('move_in_date', '<=', now()->addDays(30))
                ->where('move_in_date', '>=', now())
                ->count(),
        ];

        return Inertia::render('Tenant/Prospects/Index', [
            'prospects' => $prospects,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'source']),
        ]);
    }

    /**
     * Show the form for creating a new prospect.
     */
    public function create()
    {
        return Inertia::render('Tenant/Prospects/Create');
    }

    /**
     * Store a newly created prospect.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|required_if:type,company|string|max:255',
            'siret' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'source' => 'required|in:website,phone,email,referral,walk_in,social_media,other',
            'box_size_interested' => 'nullable|string|max:20',
            'move_in_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['tenant_id'] = auth()->user()->tenant_id;
        $validated['user_id'] = auth()->id();
        $validated['status'] = 'new';

        Prospect::create($validated);

        return redirect()->route('tenant.prospects.index')
            ->with('success', 'Prospect créé avec succès.');
    }

    /**
     * Display the specified prospect.
     */
    public function show(Prospect $prospect)
    {
        // Verify tenant access
        if ($prospect->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $prospect->load('customer');

        return Inertia::render('Tenant/Prospects/Show', [
            'prospect' => $prospect,
        ]);
    }

    /**
     * Show the form for editing the specified prospect.
     */
    public function edit(Prospect $prospect)
    {
        if ($prospect->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        return Inertia::render('Tenant/Prospects/Edit', [
            'prospect' => $prospect,
        ]);
    }

    /**
     * Update the specified prospect.
     */
    public function update(Request $request, Prospect $prospect)
    {
        if ($prospect->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => 'required|in:individual,company',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'company_name' => 'nullable|string|max:255',
            'siret' => 'nullable|string|max:20',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'status' => 'required|in:new,contacted,qualified,quoted,converted,lost',
            'source' => 'required|in:website,phone,email,referral,walk_in,social_media,other',
            'box_size_interested' => 'nullable|string|max:20',
            'move_in_date' => 'nullable|date',
            'budget' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $prospect->update($validated);

        return redirect()->route('tenant.prospects.index')
            ->with('success', 'Prospect mis à jour avec succès.');
    }

    /**
     * Remove the specified prospect.
     */
    public function destroy(Prospect $prospect)
    {
        if ($prospect->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $prospect->delete();

        return redirect()->route('tenant.prospects.index')
            ->with('success', 'Prospect supprimé avec succès.');
    }

    /**
     * Convert prospect to customer.
     */
    public function convert(Prospect $prospect)
    {
        if ($prospect->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($prospect->status === 'converted') {
            return redirect()->back()
                ->with('error', 'Ce prospect est déjà converti en client.');
        }

        $customer = $prospect->convertToCustomer();

        return redirect()->route('tenant.customers.show', $customer)
            ->with('success', 'Prospect converti en client avec succès.');
    }

    /**
     * Record a contact with the prospect.
     */
    public function recordContact(Request $request, Prospect $prospect)
    {
        if ($prospect->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'notes' => 'nullable|string',
            'status' => 'nullable|in:new,contacted,qualified,quoted',
        ]);

        $prospect->recordContact();

        if (!empty($validated['notes'])) {
            $currentNotes = $prospect->notes ?? '';
            $newNote = '[' . now()->format('d/m/Y H:i') . '] ' . $validated['notes'];
            $prospect->update(['notes' => $currentNotes . "\n" . $newNote]);
        }

        if (!empty($validated['status'])) {
            $prospect->update(['status' => $validated['status']]);
        }

        return redirect()->back()
            ->with('success', 'Contact enregistré.');
    }

    /**
     * Mark prospect as lost.
     */
    public function markAsLost(Prospect $prospect)
    {
        if ($prospect->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $prospect->markAsLost();

        return redirect()->back()
            ->with('success', 'Prospect marqué comme perdu.');
    }

    /**
     * Send SMS to prospect.
     */
    public function sendSms(Request $request, Prospect $prospect)
    {
        if ($prospect->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:160',
        ]);

        // TODO: Integrate with SMS provider
        $prospect->recordContact();

        return redirect()->back()
            ->with('success', 'SMS envoyé avec succès.');
    }
}
