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
            ->select('id', 'number', 'name', 'site_id', 'building_id', 'floor_id', 'base_price', 'length', 'width', 'height', 'status')
            ->orderBy('number')
            ->get();

        // Pre-select box if box_id is passed in query string
        $selectedBoxId = $request->query('box_id');
        $selectedBox = null;
        $selectedSiteId = null;

        if ($selectedBoxId) {
            $selectedBox = Box::where('tenant_id', $tenantId)
                ->where('id', $selectedBoxId)
                ->with(['site', 'building', 'floor'])
                ->first();

            if ($selectedBox) {
                $selectedSiteId = $selectedBox->site_id;
            }
        }

        return Inertia::render('Tenant/Contracts/Create', [
            'sites' => $sites,
            'customers' => $customers,
            'boxes' => $boxes,
            'selectedBoxId' => $selectedBoxId ? (int) $selectedBoxId : null,
            'selectedSiteId' => $selectedSiteId,
            'selectedBox' => $selectedBox,
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
            ->select('id', 'number', 'site_id', 'building_id', 'floor_id', 'base_price', 'volume', 'length', 'width', 'height', 'status')
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
        $this->authorize('edit_contracts');

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

    /**
     * Show the contract signing page.
     */
    public function sign(Request $request, Contract $contract): Response
    {
        $this->authorize('view_contracts');

        // Ensure tenant can only sign their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Only allow signing if contract is in pending_signature status
        if ($contract->status !== 'pending_signature') {
            return redirect()
                ->route('tenant.contracts.show', $contract->id)
                ->with('warning', 'Ce contrat n\'est pas en attente de signature.');
        }

        $contract->load(['customer', 'box', 'site']);

        return Inertia::render('Tenant/Contracts/Sign', [
            'contract' => $contract,
        ]);
    }

    /**
     * Save contract signatures.
     */
    public function saveSignatures(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('edit_contracts');

        // Ensure tenant can only update their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Validate request
        $validated = $request->validate([
            'customer_signature' => 'required|string',
            'staff_signature' => 'required|string',
            'confirmed' => 'required|boolean|accepted',
        ]);

        // Save signature images to storage
        $customerSignaturePath = null;
        $staffSignaturePath = null;

        if ($validated['customer_signature']) {
            $customerSignaturePath = $this->saveSignatureImage(
                $validated['customer_signature'],
                $contract->id,
                'customer'
            );
        }

        if ($validated['staff_signature']) {
            $staffSignaturePath = $this->saveSignatureImage(
                $validated['staff_signature'],
                $contract->id,
                'staff'
            );
        }

        // Update contract with signature data
        $contract->update([
            'customer_signature' => $customerSignaturePath,
            'staff_signature' => $staffSignaturePath,
            'customer_signed_at' => now(),
            'staff_user_id' => $request->user()->id,
            'staff_signed_at' => now(),
            'status' => 'active',
        ]);

        // Update box status to occupied
        if ($contract->box) {
            $contract->box->update(['status' => 'occupied']);
        }

        // Update customer statistics
        if ($contract->customer) {
            $contract->customer->increment('total_contracts');
        }

        return redirect()
            ->route('tenant.contracts.show', $contract->id)
            ->with('success', 'Signatures enregistrées avec succès. Le contrat est maintenant actif.');
    }

    /**
     * Save a signature image to storage.
     *
     * @param string $signatureData Base64-encoded PNG data
     * @param int $contractId
     * @param string $type customer|staff
     * @return string|null Path to saved file or null if validation fails
     */
    private function saveSignatureImage(string $signatureData, int $contractId, string $type): ?string
    {
        // Validate type parameter
        if (!in_array($type, ['customer', 'staff'])) {
            \Log::warning("Invalid signature type: {$type}");
            return null;
        }

        // Remove data URL prefix if present (support multiple image formats)
        $signatureData = preg_replace('/^data:image\/(png|jpeg|jpg);base64,/', '', $signatureData);

        // Validate base64 string
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $signatureData)) {
            \Log::warning("Invalid base64 signature data for contract {$contractId}");
            return null;
        }

        // Decode base64
        $imageContent = base64_decode($signatureData, true);
        if ($imageContent === false) {
            \Log::warning("Failed to decode base64 signature for contract {$contractId}");
            return null;
        }

        // Validate file size (max 500KB)
        $maxSize = 500 * 1024;
        if (strlen($imageContent) > $maxSize) {
            \Log::warning("Signature too large for contract {$contractId}: " . strlen($imageContent) . " bytes");
            return null;
        }

        // Validate MIME type using finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageContent);
        $allowedMimes = ['image/png', 'image/jpeg', 'image/jpg'];
        if (!in_array($mimeType, $allowedMimes)) {
            \Log::warning("Invalid signature MIME type for contract {$contractId}: {$mimeType}");
            return null;
        }

        // Create secure filename with timestamp
        $extension = $mimeType === 'image/png' ? 'png' : 'jpg';
        $filename = sprintf(
            'signatures/%d/%s_%s_%s.%s',
            $contractId,
            $type,
            date('YmdHis'),
            bin2hex(random_bytes(8)),
            $extension
        );

        try {
            // Store in private disk for security (not publicly accessible)
            \Storage::disk('local')->put($filename, $imageContent);
            return $filename;
        } catch (\Exception $e) {
            \Log::error("Failed to save signature for contract {$contractId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Terminate a contract.
     */
    public function terminate(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('edit_contracts');

        // Ensure tenant can only update their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Only active contracts can be terminated
        if ($contract->status !== 'active') {
            return redirect()
                ->back()
                ->with('error', 'Seuls les contrats actifs peuvent être résiliés.');
        }

        // Validate request
        $validated = $request->validate([
            'termination_reason' => 'required|string|in:customer_request,non_payment,breach,end_of_term,other',
            'termination_notes' => 'nullable|string|max:1000',
            'effective_date' => 'required|date|after_or_equal:today',
        ]);

        // Update contract with termination data
        $contract->update([
            'status' => 'terminated',
            'termination_reason' => $validated['termination_reason'],
            'termination_notes' => $validated['termination_notes'],
            'actual_end_date' => $validated['effective_date'],
        ]);

        // Make box available again
        if ($contract->box) {
            $contract->box->update(['status' => 'available']);
        }

        // Decrement customer's contract count
        if ($contract->customer) {
            $contract->customer->decrement('total_contracts');
        }

        return redirect()
            ->route('tenant.contracts.show', $contract->id)
            ->with('success', 'Le contrat a été résilié avec succès.');
    }

    /**
     * Show renewal options for a contract.
     */
    public function renewalOptions(Request $request, Contract $contract): Response
    {
        $this->authorize('view_contracts');

        // Ensure tenant can only view their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $contract->load(['customer', 'box']);

        return Inertia::render('Tenant/Contracts/RenewalOptions', [
            'contract' => $contract,
        ]);
    }

    /**
     * Renew a contract.
     */
    public function renew(Request $request, Contract $contract): RedirectResponse
    {
        $this->authorize('edit_contracts');

        // Ensure tenant can only update their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Validate request
        $validated = $request->validate([
            'months' => 'required|integer|in:1,3,6,12',
        ]);

        // Renew the contract
        $contract->update([
            'end_date' => $contract->end_date->addMonths($validated['months']),
        ]);

        return redirect()
            ->route('tenant.contracts.show', $contract->id)
            ->with('success', 'Le contrat a été renouvelé avec succès pour ' . $validated['months'] . ' mois.');
    }
}
