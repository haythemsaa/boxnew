<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreContractRequest;
use App\Http\Requests\UpdateContractRequest;
use App\Models\Contract;
use App\Models\Site;
use App\Models\Customer;
use App\Models\Box;
use App\Services\ExcelExportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContractController extends Controller
{
    /**
     * Display a listing of the contracts.
     */
    public function index(Request $request): Response
    {
        $this->authorize('view_contracts');

        $tenantId = $request->user()->tenant_id;

        $contracts = Contract::where('tenant_id', $tenantId)
            ->with(['customer', 'box', 'site'])
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('contract_number', 'like', "%{$search}%")
                        ->orWhereHas('customer', function ($q) use ($search) {
                            $q->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")
                                ->orWhere('company_name', 'like', "%{$search}%");
                        })
                        ->orWhereHas('box', function ($q) use ($search) {
                            $q->where('number', 'like', "%{$search}%");
                        });
                });
            })
            ->when($request->status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->site_id, function ($query, $siteId) {
                $query->where('site_id', $siteId);
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        $stats = [
            'total' => Contract::where('tenant_id', $tenantId)->count(),
            'active' => Contract::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'pending' => Contract::where('tenant_id', $tenantId)->where('status', 'pending_signature')->count(),
            'expired' => Contract::where('tenant_id', $tenantId)->where('status', 'expired')->count(),
            'total_revenue' => Contract::where('tenant_id', $tenantId)->where('status', 'active')->sum('monthly_price'),
        ];

        $sites = Site::where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Tenant/Contracts/Index', [
            'contracts' => $contracts,
            'stats' => $stats,
            'sites' => $sites,
            'filters' => $request->only(['search', 'status', 'type', 'site_id']),
        ]);
    }

    /**
     * Show the form for creating a new contract.
     */
    public function create(Request $request): Response
    {
        $this->authorize('create_contracts');

        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type')
            ->orderBy('first_name')
            ->get();

        $boxes = Box::where('tenant_id', $tenantId)
            ->where('status', 'available')
            ->with(['site', 'building', 'floor'])
            ->select('id', 'number', 'site_id', 'building_id', 'floor_id', 'base_price')
            ->orderBy('number')
            ->get();

        return Inertia::render('Tenant/Contracts/Create', [
            'sites' => $sites,
            'customers' => $customers,
            'boxes' => $boxes,
        ]);
    }

    /**
     * Show the wizard form for creating a new contract.
     */
    public function createWizard(Request $request): Response
    {
        $this->authorize('create_contracts');

        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->select('id', 'name', 'code', 'city')
            ->orderBy('name')
            ->get();

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type', 'email', 'phone', 'total_contracts', 'outstanding_balance')
            ->orderBy('first_name')
            ->get();

        $boxes = Box::where('tenant_id', $tenantId)
            ->where('status', 'available')
            ->with(['site:id,name', 'building:id,name', 'floor:id,name,floor_number'])
            ->select('id', 'number', 'site_id', 'building_id', 'floor_id', 'base_price', 'volume', 'length', 'width', 'height')
            ->orderBy('number')
            ->get();

        return Inertia::render('Tenant/Contracts/CreateWizard', [
            'sites' => $sites,
            'customers' => $customers,
            'boxes' => $boxes,
        ]);
    }

    /**
     * Store a newly created contract in storage.
     */
    public function store(StoreContractRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['tenant_id'] = $request->user()->tenant_id;

        // Auto-generate contract number if not provided
        if (empty($data['contract_number'])) {
            $data['contract_number'] = 'CNT-' . strtoupper(substr(uniqid(), -8));
        }

        // Set staff_user_id to current user if signed by staff
        if ($data['signed_by_staff'] && empty($data['staff_user_id'])) {
            $data['staff_user_id'] = $request->user()->id;
        }

        // Set customer_signed_at if signed by customer
        if ($data['signed_by_customer'] && empty($data['customer_signed_at'])) {
            $data['customer_signed_at'] = now();
        }

        $contract = Contract::create($data);

        // Update box status if contract is active
        if ($contract->status === 'active') {
            $contract->box->update(['status' => 'occupied']);
        }

        // Update customer statistics
        if ($contract->customer) {
            $contract->customer->increment('total_contracts');
        }

        return redirect()
            ->route('tenant.contracts.index')
            ->with('success', 'Contract created successfully.');
    }

    /**
     * Display the specified contract.
     */
    public function show(Contract $contract): Response
    {
        $this->authorize('view_contracts');

        // Ensure tenant can only view their own contracts
        if ($contract->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $contract->load(['tenant', 'site', 'customer', 'box', 'staffUser', 'invoices']);

        return Inertia::render('Tenant/Contracts/Show', [
            'contract' => $contract,
        ]);
    }

    /**
     * Show the form for editing the specified contract.
     */
    public function edit(Request $request, Contract $contract): Response
    {
        $this->authorize('update_contracts');

        // Ensure tenant can only edit their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $tenantId = $request->user()->tenant_id;

        $sites = Site::where('tenant_id', $tenantId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $customers = Customer::where('tenant_id', $tenantId)
            ->select('id', 'first_name', 'last_name', 'company_name', 'type')
            ->orderBy('first_name')
            ->get();

        $boxes = Box::where('tenant_id', $tenantId)
            ->where(function ($query) use ($contract) {
                $query->where('status', 'available')
                    ->orWhere('id', $contract->box_id);
            })
            ->with(['site', 'building', 'floor'])
            ->select('id', 'number', 'site_id', 'building_id', 'floor_id', 'base_price', 'status')
            ->orderBy('number')
            ->get();

        return Inertia::render('Tenant/Contracts/Edit', [
            'contract' => $contract,
            'sites' => $sites,
            'customers' => $customers,
            'boxes' => $boxes,
        ]);
    }

    /**
     * Update the specified contract in storage.
     */
    public function update(UpdateContractRequest $request, Contract $contract): RedirectResponse
    {
        // Ensure tenant can only update their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $data = $request->validated();

        // Store old status and box_id to detect changes
        $oldStatus = $contract->status;
        $oldBoxId = $contract->box_id;

        // Update contract
        $contract->update($data);

        // Handle box status changes
        if ($oldBoxId !== $data['box_id']) {
            // Old box becomes available if contract was active
            if ($oldStatus === 'active') {
                Box::find($oldBoxId)?->update(['status' => 'available']);
            }
            // New box becomes occupied if contract is active
            if ($data['status'] === 'active') {
                $contract->box->update(['status' => 'occupied']);
            }
        } elseif ($oldStatus !== $data['status']) {
            // Status changed for same box
            if ($data['status'] === 'active') {
                $contract->box->update(['status' => 'occupied']);
            } elseif (in_array($data['status'], ['terminated', 'expired', 'cancelled'])) {
                $contract->box->update(['status' => 'available']);
            }
        }

        return redirect()
            ->route('tenant.contracts.index')
            ->with('success', 'Contract updated successfully.');
    }

    /**
     * Remove the specified contract from storage.
     */
    public function destroy(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('delete_contracts');

        // Ensure tenant can only delete their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Make box available if contract was active
        if ($contract->status === 'active') {
            $contract->box->update(['status' => 'available']);
        }

        // Update customer statistics
        if ($contract->customer) {
            $contract->customer->decrement('total_contracts');
        }

        $contract->delete();

        return redirect()
            ->route('tenant.contracts.index')
            ->with('success', 'Contract deleted successfully.');
    }

    /**
     * Download the contract as PDF.
     */
    public function downloadPdf(Request $request, Contract $contract)
    {
        // Ensure tenant can only download their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Load relationships
        $contract->load(['customer', 'box', 'site']);

        // Get tenant information
        $tenant = $request->user()->tenant;

        // Generate PDF
        $pdf = Pdf::loadView('pdf.contract', [
            'contract' => $contract,
            'tenant' => $tenant,
        ]);

        // Set paper size and orientation
        $pdf->setPaper('a4', 'portrait');

        // Return PDF for download
        return $pdf->download("contract-{$contract->contract_number}.pdf");
    }

    /**
     * Export contracts to Excel (CSV).
     */
    public function export(Request $request, ExcelExportService $exportService)
    {
        $this->authorize('view_contracts');

        $tenantId = $request->user()->tenant_id;
        $status = $request->query('status');

        $result = $exportService->exportContracts($tenantId, $status);
        $csv = $exportService->generateCSV($result['data']);

        return response($csv, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $result['filename'] . '"',
        ]);
    }
}
