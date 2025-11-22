<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\ContractResource;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Barryvdh\DomPDF\Facade\Pdf;

class ContractController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $contracts = Contract::where('tenant_id', $tenantId)
            ->with(['customer', 'box', 'site'])
            ->when($request->input('customer_id'), function ($query, $customerId) {
                $query->where('customer_id', $customerId);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where('contract_number', 'like', "%{$search}%");
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return ContractResource::collection($contracts);
    }

    /**
     * Get contracts by customer.
     */
    public function byCustomer(Request $request, Customer $customer): AnonymousResourceCollection
    {
        // Ensure tenant can only view their own customers
        if ($customer->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $contracts = $customer->contracts()
            ->with(['box', 'site'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return ContractResource::collection($contracts);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'site_id' => ['required', 'exists:sites,id'],
            'box_id' => ['required', 'exists:boxes,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after:start_date'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_status' => ['required', 'in:pending,paid,refunded'],
            'billing_frequency' => ['required', 'in:monthly,quarterly,yearly'],
            'billing_day' => ['required', 'integer', 'min:1', 'max:31'],
            'payment_method' => ['required', 'in:bank_transfer,credit_card,direct_debit,cash'],
            'auto_renew' => ['boolean'],
            'renewal_period' => ['nullable', 'integer', 'min:1'],
            'insurance_required' => ['boolean'],
            'insurance_amount' => ['nullable', 'numeric', 'min:0'],
            'insurance_provider' => ['nullable', 'string', 'max:255'],
            'access_code' => ['nullable', 'string', 'max:50'],
            'special_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $contract = Contract::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
            'contract_number' => Contract::generateContractNumber(),
            'status' => 'draft',
        ]);

        return (new ContractResource($contract->load(['customer', 'box', 'site'])))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Contract $contract): ContractResource
    {
        // Ensure tenant can only view their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $contract->load(['customer', 'box', 'site', 'invoices']);

        return new ContractResource($contract);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Contract $contract): ContractResource
    {
        // Ensure tenant can only update their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'customer_id' => ['sometimes', 'exists:customers,id'],
            'site_id' => ['sometimes', 'exists:sites,id'],
            'box_id' => ['sometimes', 'exists:boxes,id'],
            'status' => ['sometimes', 'in:draft,active,expired,cancelled'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['nullable', 'date'],
            'monthly_price' => ['sometimes', 'numeric', 'min:0'],
            'deposit_amount' => ['nullable', 'numeric', 'min:0'],
            'deposit_status' => ['sometimes', 'in:pending,paid,refunded'],
            'billing_frequency' => ['sometimes', 'in:monthly,quarterly,yearly'],
            'billing_day' => ['sometimes', 'integer', 'min:1', 'max:31'],
            'payment_method' => ['sometimes', 'in:bank_transfer,credit_card,direct_debit,cash'],
            'auto_renew' => ['boolean'],
            'renewal_period' => ['nullable', 'integer', 'min:1'],
            'insurance_required' => ['boolean'],
            'insurance_amount' => ['nullable', 'numeric', 'min:0'],
            'insurance_provider' => ['nullable', 'string', 'max:255'],
            'access_code' => ['nullable', 'string', 'max:50'],
            'special_conditions' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'cancellation_reason' => ['nullable', 'string'],
        ]);

        $contract->update($validated);

        return new ContractResource($contract->fresh()->load(['customer', 'box', 'site']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Contract $contract): JsonResponse
    {
        // Ensure tenant can only delete their own contracts
        if ($contract->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Only allow deletion of draft or cancelled contracts
        if (!in_array($contract->status, ['draft', 'cancelled'])) {
            return response()->json([
                'message' => 'Only draft or cancelled contracts can be deleted.',
            ], 422);
        }

        $contract->delete();

        return response()->json([
            'message' => 'Contract deleted successfully',
        ]);
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

        $contract->load(['customer', 'box', 'site']);
        $tenant = $request->user()->tenant;

        $pdf = Pdf::loadView('pdf.contract', [
            'contract' => $contract,
            'tenant' => $tenant,
        ]);

        $pdf->setPaper('a4', 'portrait');
        return $pdf->download("contract-{$contract->contract_number}.pdf");
    }
}
