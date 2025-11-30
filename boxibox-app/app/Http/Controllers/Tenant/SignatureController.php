<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Signature;
use App\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SignatureController extends Controller
{
    /**
     * Display a listing of signatures.
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = Signature::where('tenant_id', $tenantId)
            ->with(['contract', 'customer']);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })->orWhereHas('contract', function ($q) use ($search) {
                $q->where('contract_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $signatures = $query->orderBy('created_at', 'desc')->paginate(15);

        // Stats
        $stats = [
            'total' => Signature::where('tenant_id', $tenantId)->count(),
            'pending' => Signature::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'sent' => Signature::where('tenant_id', $tenantId)->where('status', 'sent')->count(),
            'viewed' => Signature::where('tenant_id', $tenantId)->where('status', 'viewed')->count(),
            'signed' => Signature::where('tenant_id', $tenantId)->where('status', 'signed')->count(),
            'refused' => Signature::where('tenant_id', $tenantId)->where('status', 'refused')->count(),
            'expired' => Signature::where('tenant_id', $tenantId)->where('status', 'expired')->count(),
        ];

        return Inertia::render('Tenant/Signatures/Index', [
            'signatures' => $signatures,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'type']),
        ]);
    }

    /**
     * Show the form for creating a new signature request.
     */
    public function create()
    {
        $tenantId = auth()->user()->tenant_id;

        // Get contracts pending signature
        $contracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'pending_signature')
            ->with(['customer', 'box'])
            ->get();

        return Inertia::render('Tenant/Signatures/Create', [
            'contracts' => $contracts,
        ]);
    }

    /**
     * Store a newly created signature request.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'type' => 'required|in:contract,mandate',
            'email' => 'required|email',
            'expires_in_days' => 'nullable|integer|min:1|max:90',
            'notes' => 'nullable|string',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $contract = Contract::where('tenant_id', $tenantId)->findOrFail($validated['contract_id']);

        $signature = Signature::create([
            'tenant_id' => $tenantId,
            'contract_id' => $contract->id,
            'customer_id' => $contract->customer_id,
            'type' => $validated['type'],
            'status' => 'pending',
            'email_sent_to' => $validated['email'],
            'expires_at' => now()->addDays($validated['expires_in_days'] ?? 30),
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('tenant.signatures.index')
            ->with('success', 'Demande de signature créée avec succès.');
    }

    /**
     * Display the specified signature.
     */
    public function show(Signature $signature)
    {
        if ($signature->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $signature->load(['contract.box', 'customer']);

        return Inertia::render('Tenant/Signatures/Show', [
            'signature' => $signature,
        ]);
    }

    /**
     * Send or resend signature request email.
     */
    public function send(Signature $signature)
    {
        if ($signature->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if (in_array($signature->status, ['signed', 'refused'])) {
            return redirect()->back()
                ->with('error', 'Cette signature ne peut pas être renvoyée.');
        }

        // TODO: Send email with signing link
        $signature->markAsSent($signature->email_sent_to);

        return redirect()->back()
            ->with('success', 'Email de signature envoyé avec succès.');
    }

    /**
     * Send a reminder for pending signature.
     */
    public function remind(Signature $signature)
    {
        if ($signature->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if (!in_array($signature->status, ['sent', 'viewed'])) {
            return redirect()->back()
                ->with('error', 'Une relance ne peut être envoyée que pour une signature en attente.');
        }

        $signature->sendReminder();

        return redirect()->back()
            ->with('success', 'Relance envoyée avec succès.');
    }

    /**
     * Cancel a pending signature.
     */
    public function cancel(Signature $signature)
    {
        if ($signature->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($signature->status === 'signed') {
            return redirect()->back()
                ->with('error', 'Une signature déjà effectuée ne peut pas être annulée.');
        }

        $signature->update(['status' => 'expired']);

        return redirect()->back()
            ->with('success', 'Demande de signature annulée.');
    }

    /**
     * Remove the specified signature.
     */
    public function destroy(Signature $signature)
    {
        if ($signature->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $signature->delete();

        return redirect()->route('tenant.signatures.index')
            ->with('success', 'Signature supprimée avec succès.');
    }

    /**
     * Download signed document.
     */
    public function downloadSigned(Signature $signature)
    {
        if ($signature->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if ($signature->status !== 'signed' || !$signature->signed_document_path) {
            return redirect()->back()
                ->with('error', 'Document signé non disponible.');
        }

        return response()->download(storage_path('app/' . $signature->signed_document_path));
    }

    /**
     * Download original document (proof).
     */
    public function downloadProof(Signature $signature)
    {
        if ($signature->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if (!$signature->document_path) {
            return redirect()->back()
                ->with('error', 'Document original non disponible.');
        }

        return response()->download(storage_path('app/' . $signature->document_path));
    }
}
