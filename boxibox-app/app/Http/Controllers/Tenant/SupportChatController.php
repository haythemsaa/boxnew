<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportChatController extends Controller
{
    /**
     * List all customer support tickets
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;
        $status = $request->input('status');
        $priority = $request->input('priority');

        $query = SupportTicket::where('tenant_id', $tenantId)
            ->tenantCustomer()
            ->with(['customer', 'assignee', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn($q) => $q->where('is_read', false)->where('sender_type', 'customer')]);

        if ($status) {
            $query->where('status', $status);
        }
        if ($priority) {
            $query->where('priority', $priority);
        }

        $tickets = $query->latest('last_message_at')->paginate(20);

        // Stats
        $stats = [
            'total' => SupportTicket::where('tenant_id', $tenantId)->tenantCustomer()->count(),
            'open' => SupportTicket::where('tenant_id', $tenantId)->tenantCustomer()->open()->count(),
            'unread' => SupportTicket::where('tenant_id', $tenantId)->tenantCustomer()
                ->whereHas('messages', fn($q) => $q->where('is_read', false)->where('sender_type', 'customer'))
                ->count(),
            'resolved_today' => SupportTicket::where('tenant_id', $tenantId)->tenantCustomer()
                ->whereDate('resolved_at', today())
                ->count(),
        ];

        return Inertia::render('Tenant/Support/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'filters' => [
                'status' => $status,
                'priority' => $priority,
            ],
            'statuses' => SupportTicket::STATUSES,
            'priorities' => SupportTicket::PRIORITIES,
        ]);
    }

    /**
     * Show chat conversation
     */
    public function show(SupportTicket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load(['customer', 'assignee', 'creator']);

        // Mark messages as read
        $ticket->markAsRead(true);

        $messages = $ticket->messages()
            ->with(['user', 'customer'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_name,
                'is_internal' => $m->is_internal,
                'attachments' => $m->attachments,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'is_read' => $m->is_read,
            ]);

        return Inertia::render('Tenant/Support/Chat', [
            'ticket' => [
                'id' => $ticket->id,
                'ticket_number' => $ticket->ticket_number,
                'subject' => $ticket->subject,
                'description' => $ticket->description,
                'status' => $ticket->status,
                'status_label' => $ticket->status_label,
                'priority' => $ticket->priority,
                'priority_label' => $ticket->priority_label,
                'category' => $ticket->category,
                'category_label' => $ticket->category_label,
                'customer' => $ticket->customer ? [
                    'id' => $ticket->customer->id,
                    'name' => $ticket->customer->full_name,
                    'email' => $ticket->customer->email,
                    'phone' => $ticket->customer->phone,
                ] : null,
                'assignee' => $ticket->assignee ? [
                    'id' => $ticket->assignee->id,
                    'name' => $ticket->assignee->name,
                ] : null,
                'created_at' => $ticket->created_at->format('d/m/Y H:i'),
            ],
            'messages' => $messages,
            'statuses' => SupportTicket::STATUSES,
            'priorities' => SupportTicket::PRIORITIES,
        ]);
    }

    /**
     * Send a message
     */
    public function sendMessage(Request $request, SupportTicket $ticket)
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'is_internal' => 'boolean',
        ]);

        $message = $ticket->messages()->create([
            'sender_type' => 'user',
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
            'is_internal' => $validated['is_internal'] ?? false,
        ]);

        // Update ticket status if needed
        if ($ticket->status === 'open') {
            $ticket->update([
                'status' => 'in_progress',
                'first_response_at' => $ticket->first_response_at ?? now(),
            ]);
        } elseif ($ticket->status === 'waiting_internal') {
            $ticket->update(['status' => 'waiting_customer']);
        }

        return back()->with('success', 'Message envoye.');
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, SupportTicket $ticket)
    {
        $this->authorize('update', $ticket);

        $validated = $request->validate([
            'status' => 'required|in:' . implode(',', array_keys(SupportTicket::STATUSES)),
        ]);

        $oldStatus = $ticket->status;
        $ticket->update([
            'status' => $validated['status'],
            'resolved_at' => $validated['status'] === 'resolved' ? now() : $ticket->resolved_at,
            'closed_at' => $validated['status'] === 'closed' ? now() : $ticket->closed_at,
        ]);

        // Add system message
        $ticket->messages()->create([
            'sender_type' => 'system',
            'message' => "Statut change de \"{$oldStatus}\" a \"{$validated['status']}\"",
        ]);

        return back()->with('success', 'Statut mis a jour.');
    }

    /**
     * Assign ticket to user
     */
    public function assign(Request $request, SupportTicket $ticket)
    {
        $this->authorize('update', $ticket);
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id', new \App\Rules\SameTenantUser($tenantId)],
        ]);

        $ticket->update(['assigned_to' => $validated['assigned_to']]);

        return back()->with('success', 'Ticket assigne.');
    }

    /**
     * Show create ticket form
     */
    public function create(): Response
    {
        return Inertia::render('Tenant/Support/Create', [
            'categories' => SupportTicket::CATEGORIES,
            'priorities' => SupportTicket::PRIORITIES,
        ]);
    }

    /**
     * Create new ticket (for customer)
     */
    public function store(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'customer_id' => ['required', 'exists:customers,id', new \App\Rules\SameTenantResource(\App\Models\Customer::class, $tenantId)],
            'subject' => 'required|string|max:255',
            'description' => 'required|string|max:5000',
            'category' => 'required|in:' . implode(',', array_keys(SupportTicket::CATEGORIES)),
            'priority' => 'nullable|in:' . implode(',', array_keys(SupportTicket::PRIORITIES)),
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => $request->user()->tenant_id,
            'customer_id' => $validated['customer_id'],
            'created_by' => $request->user()->id,
            'type' => 'tenant_customer',
            'subject' => $validated['subject'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ]);

        // Add initial message
        $ticket->messages()->create([
            'sender_type' => 'user',
            'user_id' => $request->user()->id,
            'message' => $validated['description'],
        ]);

        return redirect()->route('tenant.support.show', $ticket)
            ->with('success', 'Ticket cree avec succes.');
    }

    /**
     * Get customers for autocomplete
     */
    public function customers(Request $request)
    {
        $search = $request->input('search');
        $tenantId = $request->user()->tenant_id;

        $customers = Customer::where('tenant_id', $tenantId)
            ->when($search, fn($q) => $q->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            }))
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'email']);

        return response()->json($customers);
    }
}
