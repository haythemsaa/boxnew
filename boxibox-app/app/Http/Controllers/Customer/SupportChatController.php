<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SupportChatController extends Controller
{
    /**
     * Get authenticated customer from session
     */
    protected function getAuthenticatedCustomer(): Customer
    {
        $customerId = session('customer_portal_id');

        if ($customerId) {
            return Customer::findOrFail($customerId);
        }

        // Demo fallback
        return Customer::first() ?? abort(403, 'No customer authenticated');
    }

    /**
     * List customer's support tickets
     */
    public function index(Request $request): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        $tickets = SupportTicket::where('customer_id', $customer->id)
            ->where('tenant_id', $customer->tenant_id)
            ->with(['messages' => fn($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn($q) => $q->where('is_read', false)->where('sender_type', 'user')])
            ->latest('last_message_at')
            ->paginate(10);

        return Inertia::render('Customer/Portal/Support/Index', [
            'tickets' => $tickets,
            'statuses' => SupportTicket::STATUSES,
            'categories' => SupportTicket::CATEGORIES,
        ]);
    }

    /**
     * Show chat conversation
     */
    public function show(SupportTicket $ticket): Response
    {
        $customer = $this->getAuthenticatedCustomer();

        // Verify ownership
        if ($ticket->customer_id !== $customer->id) {
            abort(403, 'Acces non autorise.');
        }

        // Mark messages from staff as read
        $ticket->messages()
            ->where('sender_type', 'user')
            ->where('is_internal', false)
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $messages = $ticket->messages()
            ->where('is_internal', false) // Don't show internal notes to customer
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'sender_type' => $m->sender_type,
                'is_mine' => $m->sender_type === 'customer',
                'attachments' => $m->attachments,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'is_read' => $m->is_read,
            ]);

        return Inertia::render('Customer/Portal/Support/Chat', [
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'status_label' => $ticket->status_label,
                'category' => $ticket->category,
                'category_label' => $ticket->category_label,
                'created_at' => $ticket->created_at->format('d/m/Y H:i'),
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, SupportTicket $ticket): RedirectResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        // Verify ownership
        if ($ticket->customer_id !== $customer->id) {
            abort(403, 'Acces non autorise.');
        }

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
        ]);

        $ticket->messages()->create([
            'sender_type' => 'customer',
            'customer_id' => $customer->id,
            'message' => $validated['message'],
        ]);

        // Reopen ticket if it was waiting for customer
        if (in_array($ticket->status, ['waiting_customer', 'resolved'])) {
            $ticket->update(['status' => 'open']);
        }

        return back()->with('success', 'Message envoye.');
    }

    /**
     * Create new support ticket
     */
    public function store(Request $request): RedirectResponse
    {
        $customer = $this->getAuthenticatedCustomer();

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'category' => 'required|in:' . implode(',', array_keys(SupportTicket::CATEGORIES)),
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'type' => 'tenant_customer',
            'subject' => $validated['subject'],
            'description' => $validated['message'],
            'category' => $validated['category'],
            'priority' => 'medium',
            'status' => 'open',
        ]);

        // Add initial message
        $ticket->messages()->create([
            'sender_type' => 'customer',
            'customer_id' => $customer->id,
            'message' => $validated['message'],
        ]);

        return redirect()->route('customer.portal.support.show', $ticket)
            ->with('success', 'Ticket cree avec succes.');
    }

    /**
     * Create form
     */
    public function create(): Response
    {
        return Inertia::render('Customer/Portal/Support/Create', [
            'categories' => SupportTicket::CATEGORIES,
        ]);
    }
}
