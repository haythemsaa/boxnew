<?php

namespace App\Events;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatMessage $message;
    public ChatConversation $conversation;
    public string $senderType;
    public ?string $senderName;

    /**
     * Create a new event instance.
     */
    public function __construct(
        ChatMessage $message,
        ChatConversation $conversation,
        string $senderType = 'customer',
        ?string $senderName = null
    ) {
        $this->message = $message;
        $this->conversation = $conversation;
        $this->senderType = $senderType;
        $this->senderName = $senderName;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            // Broadcast to the specific conversation channel
            new PrivateChannel('chat.' . $this->conversation->conversation_id),

            // Also broadcast to tenant's all-chats channel for new message notifications
            new PrivateChannel('tenant.' . $this->conversation->tenant_id . '.chats'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'role' => $this->message->role,
                'content' => $this->message->content,
                'metadata' => $this->message->metadata,
                'created_at' => $this->message->created_at->toISOString(),
            ],
            'conversation' => [
                'id' => $this->conversation->id,
                'conversation_id' => $this->conversation->conversation_id,
                'customer_id' => $this->conversation->customer_id,
                'status' => $this->conversation->status,
                'last_message_at' => $this->conversation->last_message_at?->toISOString(),
            ],
            'sender_type' => $this->senderType,
            'sender_name' => $this->senderName,
        ];
    }
}
