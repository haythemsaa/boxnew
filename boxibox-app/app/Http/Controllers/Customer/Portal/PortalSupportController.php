<?php

namespace App\Http\Controllers\Customer\Portal;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Portal Support Controller
 * Handles support requests and customer service for portal
 */
class PortalSupportController extends Controller
{
    /**
     * Get authenticated customer from session
     */
    protected function getAuthenticatedCustomer(): Customer
    {
        $customerId = session('customer_portal_id');

        if (!$customerId) {
            abort(403, 'No customer authenticated. Please log in.');
        }

        return Customer::findOrFail($customerId);
    }

    /**
     * Display support page
     */
    public function index(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Get recent requests
        $requests = CustomerRequest::where('customer_id', $customer->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Get tenant contact info
        $tenant = $customer->tenant;

        return Inertia::render('Customer/Portal/Support', [
            'customer' => $customer,
            'requests' => $requests,
            'contactInfo' => [
                'phone' => $tenant->phone ?? null,
                'email' => $tenant->support_email ?? $tenant->email ?? null,
                'address' => $tenant->address ?? null,
                'business_hours' => $tenant->business_hours ?? 'Mon-Fri 9:00-18:00',
            ],
        ]);
    }

    /**
     * Display customer requests history
     */
    public function requests(): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $requests = CustomerRequest::where('customer_id', $customer->id)
            ->with(['contract.box'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return Inertia::render('Customer/Portal/Requests', [
            'customer' => $customer,
            'requests' => $requests,
        ]);
    }

    /**
     * Submit new support request
     */
    public function submitRequest(Request $request)
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'type' => 'required|string|in:question,complaint,change_request,technical,billing,other',
            'subject' => 'required|string|max:200',
            'message' => 'required|string|max:5000',
            'contract_id' => 'nullable|exists:contracts,id',
            'priority' => 'nullable|string|in:low,normal,high',
        ]);

        // Verify contract belongs to customer if provided
        if ($validated['contract_id']) {
            $contract = $customer->contracts()->find($validated['contract_id']);
            if (!$contract) {
                return back()->with('error', 'Invalid contract selected.');
            }
        }

        try {
            $customerRequest = CustomerRequest::create([
                'tenant_id' => $customer->tenant_id,
                'customer_id' => $customer->id,
                'contract_id' => $validated['contract_id'] ?? null,
                'request_number' => 'REQ-' . strtoupper(uniqid()),
                'type' => $validated['type'],
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'priority' => $validated['priority'] ?? 'normal',
                'status' => 'open',
                'source' => 'portal',
            ]);

            Log::info('Support request submitted via portal', [
                'request_id' => $customerRequest->id,
                'customer_id' => $customer->id,
                'type' => $validated['type'],
            ]);

            return back()->with('success', 'Your request has been submitted. Reference: ' . $customerRequest->request_number . '. We will respond shortly.');
        } catch (\Exception $e) {
            Log::error('Failed to submit support request', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
            ]);

            return back()->with('error', 'Failed to submit request. Please try again.');
        }
    }

    /**
     * Show single request details
     */
    public function showRequest(CustomerRequest $customerRequest): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        if ($customerRequest->customer_id !== $customer->id) {
            abort(403, 'Unauthorized access to request.');
        }

        return Inertia::render('Customer/Portal/RequestDetail', [
            'customer' => $customer,
            'request' => $customerRequest->load(['contract.box', 'responses']),
        ]);
    }
}
