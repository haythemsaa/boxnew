<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->when($request->input('type'), function ($query, $type) {
                $query->where('type', $type);
            })
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($request->input('search'), function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('customer_number', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%")
                      ->orWhere('first_name', 'like', "%{$search}%")
                      ->orWhere('last_name', 'like', "%{$search}%")
                      ->orWhere('company_name', 'like', "%{$search}%");
                });
            })
            ->paginate($request->input('per_page', 15));

        return CustomerResource::collection($customers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:individual,company'],

            // Individual fields
            'first_name' => ['required_if:type,individual', 'nullable', 'string', 'max:255'],
            'last_name' => ['required_if:type,individual', 'nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],

            // Company fields
            'company_name' => ['required_if:type,company', 'nullable', 'string', 'max:255'],
            'company_registration' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:50'],

            // Contact information
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],

            // Address
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],

            // Billing information
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_country' => ['nullable', 'string', 'max:100'],

            // Payment information
            'payment_method' => ['nullable', 'in:bank_transfer,credit_card,direct_debit,cash'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:50'],
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],

            // Other
            'status' => ['required', 'in:active,inactive,suspended'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer = Customer::create([
            ...$validated,
            'tenant_id' => $request->user()->tenant_id,
            'customer_number' => Customer::generateCustomerNumber(),
        ]);

        return (new CustomerResource($customer))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Customer $customer): CustomerResource
    {
        // Ensure tenant can only view their own customers
        if ($customer->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $customer->load(['contracts.box', 'invoices', 'payments']);

        return new CustomerResource($customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer): CustomerResource
    {
        // Ensure tenant can only update their own customers
        if ($customer->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'type' => ['sometimes', 'in:individual,company'],

            // Individual fields
            'first_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],

            // Company fields
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_registration' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:50'],

            // Contact information
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],

            // Address
            'address' => ['sometimes', 'string', 'max:255'],
            'city' => ['sometimes', 'string', 'max:100'],
            'postal_code' => ['sometimes', 'string', 'max:20'],
            'country' => ['sometimes', 'string', 'max:100'],

            // Billing information
            'billing_address' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:100'],
            'billing_postal_code' => ['nullable', 'string', 'max:20'],
            'billing_country' => ['nullable', 'string', 'max:100'],

            // Payment information
            'payment_method' => ['nullable', 'in:bank_transfer,credit_card,direct_debit,cash'],
            'bank_account' => ['nullable', 'string', 'max:100'],
            'iban' => ['nullable', 'string', 'max:50'],
            'payment_day' => ['nullable', 'integer', 'min:1', 'max:31'],

            // Other
            'status' => ['sometimes', 'in:active,inactive,suspended'],
            'credit_limit' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return new CustomerResource($customer->fresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        // Ensure tenant can only delete their own customers
        if ($customer->tenant_id !== $request->user()->tenant_id) {
            abort(403);
        }

        // Check if customer has active contracts
        if ($customer->contracts()->where('status', 'active')->exists()) {
            return response()->json([
                'message' => 'Cannot delete customer with active contracts.',
            ], 422);
        }

        $customer->delete();

        return response()->json([
            'message' => 'Customer deleted successfully',
        ]);
    }
}
