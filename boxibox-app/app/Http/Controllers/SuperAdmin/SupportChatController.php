<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use App\Models\SupportTicket;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SupportChatController extends Controller
{
    /**
     * List all tenant support tickets (admin_tenant type)
     */
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        $tenantId = $request->input('tenant_id');

        $query = SupportTicket::adminTenant()
            ->with(['tenant', 'assignee', 'messages' => fn($q) => $q->latest()->limit(1)])
            ->withCount(['messages as unread_count' => fn($q) => $q->where('is_read', false)->where('sender_type', '!=', 'user')]);

        if ($status) {
            $query->where('status', $status);
        }
        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        $tickets = $query->latest('last_message_at')->paginate(20);

        // Stats
        $stats = [
            'total' => SupportTicket::adminTenant()->count(),
            'open' => SupportTicket::adminTenant()->open()->count(),
            'unread' => SupportTicket::adminTenant()
                ->whereHas('messages', fn($q) => $q->where('is_read', false)->where('sender_type', '!=', 'user'))
                ->count(),
            'resolved_today' => SupportTicket::adminTenant()
                ->whereDate('resolved_at', today())
                ->count(),
        ];

        // Tenants list for filter
        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/Support/Index', [
            'tickets' => $tickets,
            'stats' => $stats,
            'tenants' => $tenants,
            'filters' => [
                'status' => $status,
                'tenant_id' => $tenantId,
            ],
            'statuses' => SupportTicket::STATUSES,
        ]);
    }

    /**
     * Show chat conversation with tenant
     */
    public function show(SupportTicket $ticket): Response
    {
        $ticket->load(['tenant', 'assignee', 'creator']);

        // Mark messages as read (from tenant)
        $ticket->messages()
            ->where('sender_type', '!=', 'user')
            ->where('is_read', false)
            ->update(['is_read' => true, 'read_at' => now()]);

        $messages = $ticket->messages()
            ->with(['user'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'message' => $m->message,
                'sender_type' => $m->sender_type,
                'sender_name' => $m->sender_type === 'user'
                    ? ($m->user?->name ?? 'Admin')
                    : ($ticket->tenant?->name ?? 'Tenant'),
                'is_admin' => $m->sender_type === 'user',
                'is_internal' => $m->is_internal,
                'attachments' => $m->attachments,
                'created_at' => $m->created_at->format('d/m/Y H:i'),
                'is_read' => $m->is_read,
            ]);

        return Inertia::render('SuperAdmin/Support/Chat', [
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
                'tenant' => $ticket->tenant ? [
                    'id' => $ticket->tenant->id,
                    'name' => $ticket->tenant->name,
                    'email' => $ticket->tenant->email,
                ] : null,
                'assignee' => $ticket->assignee ? [
                    'id' => $ticket->assignee->id,
                    'name' => $ticket->assignee->name,
                ] : null,
                'created_at' => $ticket->created_at->format('d/m/Y H:i'),
            ],
            'messages' => $messages,
            'statuses' => SupportTicket::STATUSES,
        ]);
    }

    /**
     * Send message to tenant
     */
    public function sendMessage(Request $request, SupportTicket $ticket)
    {
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

        // Update ticket status
        if ($ticket->status === 'open') {
            $ticket->update([
                'status' => 'in_progress',
                'first_response_at' => $ticket->first_response_at ?? now(),
            ]);
        }

        return back()->with('success', 'Message envoye.');
    }

    /**
     * Update ticket status
     */
    public function updateStatus(Request $request, SupportTicket $ticket)
    {
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
     * Show create ticket form
     */
    public function create(): Response
    {
        $tenants = Tenant::select('id', 'name')->orderBy('name')->get();

        return Inertia::render('SuperAdmin/Support/Create', [
            'tenants' => $tenants,
            'priorities' => SupportTicket::PRIORITIES,
        ]);
    }

    /**
     * Create new ticket for tenant (admin initiated)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:5000',
            'priority' => 'nullable|in:' . implode(',', array_keys(SupportTicket::PRIORITIES)),
        ]);

        $ticket = SupportTicket::create([
            'tenant_id' => $validated['tenant_id'],
            'created_by' => $request->user()->id,
            'type' => 'admin_tenant',
            'subject' => $validated['subject'],
            'description' => $validated['message'],
            'category' => 'general',
            'priority' => $validated['priority'] ?? 'medium',
            'status' => 'open',
        ]);

        // Add initial message
        $ticket->messages()->create([
            'sender_type' => 'user',
            'user_id' => $request->user()->id,
            'message' => $validated['message'],
        ]);

        return redirect()->route('superadmin.support.show', $ticket)
            ->with('success', 'Ticket cree avec succes.');
    }
}
