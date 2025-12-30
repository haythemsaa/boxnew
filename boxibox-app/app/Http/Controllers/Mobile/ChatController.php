<?php

namespace App\Http\Controllers\Mobile;

use App\Events\ChatMessageRead;
use App\Events\ChatMessageSent;
use App\Events\ChatTyping;
use App\Events\NewChatConversation;
use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    /**
     * Get the authenticated mobile customer
     */
    protected function getCustomer(): Customer
    {
        $customerId = session('mobile_customer_id');
        return Customer::findOrFail($customerId);
    }

    /**
     * Display chat page with conversation history
     */
    public function index(): Response
    {
        $customer = $this->getCustomer();

        // Get active conversation or the latest one
        $conversation = ChatConversation::where('customer_id', $customer->id)
            ->where('tenant_id', $customer->tenant_id)
            ->orderBy('last_message_at', 'desc')
            ->first();

        $messages = [];

        if ($conversation) {
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
        }

        return Inertia::render('Mobile/Chat/Index', [
            'conversation' => $conversation ? [
                'id' => $conversation->id,
                'conversation_id' => $conversation->conversation_id,
                'status' => $conversation->status,
                'channel' => $conversation->channel,
                'last_message_at' => $conversation->last_message_at?->toISOString(),
            ] : null,
            'messages' => $messages,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->first_name . ' ' . $customer->last_name,
            ],
        ]);
    }

    /**
     * Start a new conversation
     */
    public function store(Request $request): JsonResponse
    {
        $customer = $this->getCustomer();

        // Check if there's already an active conversation
        $existingConversation = ChatConversation::where('customer_id', $customer->id)
            ->where('tenant_id', $customer->tenant_id)
            ->where('status', 'active')
            ->first();

        if ($existingConversation) {
            return response()->json([
                'conversation' => [
                    'id' => $existingConversation->id,
                    'conversation_id' => $existingConversation->conversation_id,
                    'status' => $existingConversation->status,
                ],
                'message' => 'Conversation active existante trouvee.',
            ]);
        }

        // Create new conversation
        $conversationId = 'conv_' . Str::uuid();

        $conversation = ChatConversation::create([
            'conversation_id' => $conversationId,
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'status' => 'active',
            'channel' => 'mobile',
            'message_count' => 0,
            'metadata' => [
                'started_at' => now()->toISOString(),
                'customer_name' => $customer->first_name . ' ' . $customer->last_name,
                'customer_email' => $customer->email,
            ],
        ]);

        // Create welcome message from system
        $welcomeMessage = ChatMessage::create([
            'conversation_id' => $conversationId,
            'role' => 'system',
            'content' => 'Bienvenue! Un agent va vous repondre dans quelques instants.',
            'metadata' => ['type' => 'welcome'],
        ]);

        // Broadcast new conversation to tenant
        broadcast(new NewChatConversation($conversation, $customer));

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'conversation_id' => $conversation->conversation_id,
                'status' => $conversation->status,
            ],
            'message' => 'Conversation creee avec succes.',
        ]);
    }

    /**
     * Get conversation details with messages
     */
    public function show(string $conversationId): JsonResponse
    {
        $customer = $this->getCustomer();

        $conversation = ChatConversation::where('conversation_id', $conversationId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $messages = ChatMessage::where('conversation_id', $conversationId)
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

        return response()->json([
            'conversation' => [
                'id' => $conversation->id,
                'conversation_id' => $conversation->conversation_id,
                'status' => $conversation->status,
                'channel' => $conversation->channel,
                'last_message_at' => $conversation->last_message_at?->toISOString(),
            ],
            'messages' => $messages,
        ]);
    }

    /**
     * Send a message in the conversation
     */
    public function sendMessage(Request $request, string $conversationId): JsonResponse
    {
        $customer = $this->getCustomer();

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'attachment' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
        ]);

        $conversation = ChatConversation::where('conversation_id', $conversationId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        // Handle attachment if present
        $attachmentData = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $path = $file->store('chat-attachments/' . $conversationId, 'public');
            $attachmentData = [
                'path' => $path,
                'name' => $file->getClientOriginalName(),
                'mime' => $file->getMimeType(),
                'size' => $file->getSize(),
            ];
        }

        // Create message
        $message = ChatMessage::create([
            'conversation_id' => $conversationId,
            'role' => 'customer',
            'content' => $validated['content'],
            'metadata' => [
                'customer_id' => $customer->id,
                'customer_name' => $customer->first_name . ' ' . $customer->last_name,
                'attachment' => $attachmentData,
                'is_read' => false,
            ],
        ]);

        // Update conversation
        $conversation->update([
            'last_message_at' => now(),
            'message_count' => $conversation->message_count + 1,
        ]);

        // Broadcast message
        broadcast(new ChatMessageSent(
            $message,
            $conversation,
            'customer',
            $customer->first_name . ' ' . $customer->last_name
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
     * Mark messages as read
     */
    public function markAsRead(Request $request, string $conversationId): JsonResponse
    {
        $customer = $this->getCustomer();

        $conversation = ChatConversation::where('conversation_id', $conversationId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        // Get unread agent messages
        $unreadMessages = ChatMessage::where('conversation_id', $conversationId)
            ->where('role', 'agent')
            ->whereJsonDoesntContain('metadata->is_read', true)
            ->get();

        $messageIds = [];

        foreach ($unreadMessages as $message) {
            $metadata = $message->metadata ?? [];
            $metadata['is_read'] = true;
            $metadata['read_at'] = now()->toISOString();
            $message->update(['metadata' => $metadata]);
            $messageIds[] = $message->id;
        }

        if (!empty($messageIds)) {
            // Broadcast read event
            broadcast(new ChatMessageRead($conversationId, $messageIds, 'customer'));
        }

        return response()->json([
            'read_count' => count($messageIds),
            'message_ids' => $messageIds,
        ]);
    }

    /**
     * Send typing indicator
     */
    public function typing(Request $request, string $conversationId): JsonResponse
    {
        $customer = $this->getCustomer();

        $validated = $request->validate([
            'is_typing' => 'required|boolean',
        ]);

        $conversation = ChatConversation::where('conversation_id', $conversationId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        // Broadcast typing event
        broadcast(new ChatTyping(
            $conversationId,
            'customer',
            $customer->first_name,
            $validated['is_typing']
        ));

        return response()->json(['success' => true]);
    }

    /**
     * Close conversation
     */
    public function close(string $conversationId): JsonResponse
    {
        $customer = $this->getCustomer();

        $conversation = ChatConversation::where('conversation_id', $conversationId)
            ->where('customer_id', $customer->id)
            ->firstOrFail();

        $conversation->update([
            'status' => 'closed',
            'metadata' => array_merge($conversation->metadata ?? [], [
                'closed_at' => now()->toISOString(),
                'closed_by' => 'customer',
            ]),
        ]);

        // Add system message
        ChatMessage::create([
            'conversation_id' => $conversationId,
            'role' => 'system',
            'content' => 'Le client a termine la conversation.',
            'metadata' => ['type' => 'closed'],
        ]);

        return response()->json([
            'message' => 'Conversation fermee.',
        ]);
    }

    /**
     * Authenticate broadcasting channel for mobile customer
     */
    public function broadcastAuth(Request $request): JsonResponse
    {
        $customer = $this->getCustomer();
        $channelName = $request->input('channel_name');
        $socketId = $request->input('socket_id');

        // Parse the channel name (e.g., "private-chat.conv_xxx")
        $channel = str_replace('private-', '', $channelName);

        // Check authorization based on channel type
        $authorized = false;

        // Chat conversation channel
        if (preg_match('/^chat\.(.+)$/', $channel, $matches)) {
            $conversationId = $matches[1];
            $authorized = ChatConversation::where('conversation_id', $conversationId)
                ->where('customer_id', $customer->id)
                ->exists();
        }

        // Customer-specific channel
        if (preg_match('/^customer\.(\d+)\./', $channel, $matches)) {
            $authorized = (int) $matches[1] === $customer->id;
        }

        if (!$authorized) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        // Generate Pusher-compatible auth response
        $pusher = new \Pusher\Pusher(
            config('broadcasting.connections.reverb.key'),
            config('broadcasting.connections.reverb.secret'),
            config('broadcasting.connections.reverb.app_id'),
            [
                'cluster' => 'mt1',
                'useTLS' => true,
            ]
        );

        $auth = $pusher->authorizeChannel($channelName, $socketId);

        return response()->json(json_decode($auth, true));
    }
}
