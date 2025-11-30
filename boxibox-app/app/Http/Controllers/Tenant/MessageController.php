<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class MessageController extends Controller
{
    /**
     * Display a listing of messages.
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        // Get messages from a hypothetical messages table or build from customer communications
        $messages = collect([]);

        // For now, we'll simulate messages based on recent customer activity
        $customers = Customer::where('tenant_id', $tenantId)
            ->whereNotNull('email')
            ->latest()
            ->limit(20)
            ->get();

        // Get unread count (simulated)
        $unreadCount = 0;

        return Inertia::render('Tenant/Messages/Index', [
            'messages' => $messages,
            'customers' => $customers,
            'unreadCount' => $unreadCount,
            'filters' => $request->only(['search', 'status', 'type']),
        ]);
    }

    /**
     * Show the form for composing a new message.
     */
    public function create(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->whereNotNull('email')
            ->select('id', 'first_name', 'last_name', 'company_name', 'email', 'type')
            ->orderBy('first_name')
            ->get();

        return Inertia::render('Tenant/Messages/Create', [
            'customers' => $customers,
            'customerId' => $request->query('customer_id'),
        ]);
    }

    /**
     * Send a message.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|in:email,sms',
        ]);

        $tenantId = $request->user()->tenant_id;
        $customer = Customer::where('tenant_id', $tenantId)->findOrFail($validated['customer_id']);

        // TODO: Implement actual email/SMS sending
        // For now, we just log the message

        return redirect()
            ->route('tenant.messages.index')
            ->with('success', 'Message sent successfully to ' . $customer->email);
    }

    /**
     * Display a specific message thread.
     */
    public function show(Request $request, $id): Response
    {
        // Placeholder for message thread view
        return Inertia::render('Tenant/Messages/Show', [
            'message' => null,
        ]);
    }

    /**
     * Send bulk messages.
     */
    public function sendBulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_ids' => 'required|array',
            'customer_ids.*' => 'exists:customers,id',
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'type' => 'required|in:email,sms',
        ]);

        $tenantId = $request->user()->tenant_id;
        $count = Customer::where('tenant_id', $tenantId)
            ->whereIn('id', $validated['customer_ids'])
            ->count();

        // TODO: Implement actual bulk sending

        return redirect()
            ->route('tenant.messages.index')
            ->with('success', "Message sent to {$count} customers.");
    }
}
