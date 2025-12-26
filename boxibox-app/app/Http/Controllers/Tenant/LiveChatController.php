<?php

namespace App\Http\Controllers\Tenant;

use App\Events\ChatMessageRead;
use App\Events\ChatMessageSent;
use App\Events\ChatTyping;
use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LiveChatController extends Controller
{
    /**
     * Display list of all chat conversations
     */
    public function index(Request $request): Response
    {
        $tenantId = $request->user()->tenant_id;

        $query = ChatConversation::where('tenant_id', $tenantId)
            ->with(['customer:id,first_name,last_name,email,phone'])
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('role', 'customer')
                    ->whereJsonDoesntContain('metadata->is_read', true);
            }])
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('last_message_at', 'desc');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $conversations = $query->paginate(20)->withQueryString();

        // Stats
        $stats = [
            'total' => ChatConversation::where('tenant_id', $tenantId)->count(),
            'active' => ChatConversation::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'unread' => ChatMessage::whereHas('conversation', function ($q) use ($tenantId) {
                $q->where('tenant_id', $tenantId)->where('status', 'active');
            })->where('role', 'customer')
                ->whereJsonDoesntContain('metadata->is_read', true)
                ->count(),
            'closed_today' => ChatConversation::where('tenant_id', $tenantId)
                ->where('status', 'closed')
                ->whereDate('updated_at', today())
                ->count(),
        ];

        return Inertia::render('Tenant/LiveChat/Index', [
            'conversations' => $conversations,
            'stats' => $stats,
            'filters' => $request->only(['status']),
        ]);
    }

    /**
     * Display a specific conversation
     */
    public function show(Request $request, int $id): Response
    {
        $tenantId = $request->user()->tenant_id;

        $conversation = ChatConversation::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with(['customer', 'site'])
            ->firstOrFail();

        $messages = ChatMessage::where('conversation_id', $conversation->conversation_id)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'conversation_id' => $message->conversation_id,
                    'role' => $message->role,
                    'content' => $message->content,
                    'metadata' => $message->metadata,
                    'created_at' => $message->created_at->toISOString(),
                    'is_read' => $message->metadata['is_read'] ?? false,
                ];
            });

        // Get other active conversations for sidebar
        $otherConversations = ChatConversation::where('tenant_id', $tenantId)
            ->where('id', '!=', $id)
            ->where('status', 'active')
            ->with(['customer:id,first_name,last_name'])
            ->withCount(['messages as unread_count' => function ($query) {
                $query->where('role', 'customer')
                    ->whereJsonDoesntContain('metadata->is_read', true);
            }])
            ->orderBy('last_message_at', 'desc')
            ->limit(10)
            ->get();

        // Customer history (contracts, invoices)
        $customer = $conversation->customer;
        $customerHistory = null;

        if ($customer) {
            $customerHistory = [
                'contracts_count' => $customer->contracts()->count(),
                'active_contracts' => $customer->contracts()->where('status', 'active')->count(),
                'total_paid' => $customer->payments()->where('status', 'completed')->sum('amount'),
                'pending_invoices' => $customer->invoices()->whereIn('status', ['sent', 'overdue'])->count(),
            ];
        }

        return Inertia::render('Tenant/LiveChat/Show', [
            'conversation' => [
                'id' => $conversation->id,
                'conversation_id' => $conversation->conversation_id,
                'status' => $conversation->status,
                'channel' => $conversation->channel,
                'message_count' => $conversation->message_count,
                'last_message_at' => $conversation->last_message_at?->toISOString(),
                'created_at' => $conversation->created_at->toISOString(),
                'metadata' => $conversation->metadata,
            ],
            'messages' => $messages,
            'customer' => $customer ? [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'company' => $customer->company_name,
                'created_at' => $customer->created_at->toISOString(),
            ] : null,
            'customerHistory' => $customerHistory,
            'otherConversations' => $otherConversations,
            'site' => $conversation->site,
        ]);
    }

    /**
     * Send a reply message
     */
    public function reply(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $user = $request->user();

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $conversation = ChatConversation::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // Create message
        $message = ChatMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'agent',
            'content' => $validated['content'],
            'metadata' => [
                'agent_id' => $user->id,
                'agent_name' => $user->name,
                'is_read' => false,
            ],
        ]);

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'message_count' => $conversation->message_count + 1,
            'metadata' => array_merge($conversation->metadata ?? [], [
                'last_agent_id' => $user->id,
                'last_agent_name' => $user->name,
            ]),
        ]);

        // Broadcast message
        broadcast(new ChatMessageSent(
            $message,
            $conversation,
            'agent',
            $user->name
        ));

        return response()->json([
            'message' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'role' => $message->role,
                'content' => $message->content,
                'metadata' => $message->metadata,
                'created_at' => $message->created_at->toISOString(),
            ],
        ]);
    }

    /**
     * Mark customer messages as read
     */
    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $conversation = ChatConversation::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // Get unread customer messages
        $unreadMessages = ChatMessage::where('conversation_id', $conversation->conversation_id)
            ->where('role', 'customer')
            ->whereJsonDoesntContain('metadata->is_read', true)
            ->get();

        $messageIds = [];

        foreach ($unreadMessages as $message) {
            $metadata = $message->metadata ?? [];
            $metadata['is_read'] = true;
            $metadata['read_at'] = now()->toISOString();
            $metadata['read_by'] = $request->user()->id;
            $message->update(['metadata' => $metadata]);
            $messageIds[] = $message->id;
        }

        if (!empty($messageIds)) {
            // Broadcast read event
            broadcast(new ChatMessageRead($conversation->conversation_id, $messageIds, 'agent'));
        }

        return response()->json([
            'read_count' => count($messageIds),
            'message_ids' => $messageIds,
        ]);
    }

    /**
     * Send typing indicator
     */
    public function typing(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $user = $request->user();

        $validated = $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $conversation = ChatConversation::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        // Broadcast typing event
        broadcast(new ChatTyping(
            $conversation->conversation_id,
            'agent',
            $user->name,
            $validated['is_typing']
        ));

        return response()->json(['success' => true]);
    }

    /**
     * Close a conversation
     */
    public function close(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $user = $request->user();

        $conversation = ChatConversation::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $conversation->update([
            'status' => 'closed',
            'metadata' => array_merge($conversation->metadata ?? [], [
                'closed_at' => now()->toISOString(),
                'closed_by' => $user->id,
                'closed_by_name' => $user->name,
            ]),
        ]);

        // Add system message
        ChatMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'system',
            'content' => "La conversation a ete fermee par {$user->name}.",
            'metadata' => ['type' => 'closed', 'agent_id' => $user->id],
        ]);

        return response()->json([
            'message' => 'Conversation fermee.',
        ]);
    }

    /**
     * Reopen a closed conversation
     */
    public function reopen(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;
        $user = $request->user();

        $conversation = ChatConversation::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $conversation->update([
            'status' => 'active',
            'metadata' => array_merge($conversation->metadata ?? [], [
                'reopened_at' => now()->toISOString(),
                'reopened_by' => $user->id,
            ]),
        ]);

        // Add system message
        ChatMessage::create([
            'conversation_id' => $conversation->conversation_id,
            'role' => 'system',
            'content' => "La conversation a ete rouverte par {$user->name}.",
            'metadata' => ['type' => 'reopened', 'agent_id' => $user->id],
        ]);

        return response()->json([
            'message' => 'Conversation rouverte.',
        ]);
    }

    /**
     * Assign conversation to an agent
     */
    public function assign(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'agent_id' => 'required|exists:users,id',
        ]);

        $conversation = ChatConversation::where('id', $id)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $conversation->update([
            'metadata' => array_merge($conversation->metadata ?? [], [
                'assigned_to' => $validated['agent_id'],
                'assigned_at' => now()->toISOString(),
                'assigned_by' => $request->user()->id,
            ]),
        ]);

        return response()->json([
            'message' => 'Conversation assignee.',
        ]);
    }

    /**
     * Get unread count for header badge (API)
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $count = ChatMessage::whereHas('conversation', function ($q) use ($tenantId) {
            $q->where('tenant_id', $tenantId)->where('status', 'active');
        })->where('role', 'customer')
            ->whereJsonDoesntContain('metadata->is_read', true)
            ->count();

        $activeCount = ChatConversation::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        return response()->json([
            'unread_messages' => $count,
            'active_conversations' => $activeCount,
        ]);
    }

    /**
     * Use a canned response
     */
    public function cannedResponse(Request $request, int $id): JsonResponse
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'response_id' => 'required|integer',
        ]);

        // Get canned response (placeholder - would come from database)
        $cannedResponses = [
            1 => "Bonjour! Comment puis-je vous aider aujourd'hui?",
            2 => "Merci pour votre message. Je verifie cela pour vous.",
            3 => "Votre demande a bien ete prise en compte. Nous reviendrons vers vous rapidement.",
            4 => "N'hesitez pas a nous contacter si vous avez d'autres questions.",
            5 => "Bonne journee et a bientot!",
        ];

        $content = $cannedResponses[$validated['response_id']] ?? null;

        if (!$content) {
            return response()->json(['error' => 'Reponse non trouvee.'], 404);
        }

        // Use the reply method logic
        $request->merge(['content' => $content]);
        return $this->reply($request, $id);
    }
}
