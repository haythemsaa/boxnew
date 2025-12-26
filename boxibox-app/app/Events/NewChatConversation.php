<?php

namespace App\Events;

use App\Models\ChatConversation;
use App\Models\Customer;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewChatConversation implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public ChatConversation $conversation;
    public ?Customer $customer;

    /**
     * Create a new event instance.
     */
    public function __construct(ChatConversation $conversation, ?Customer $customer = null)
    {
        $this->conversation = $conversation;
        $this->customer = $customer ?? $conversation->customer;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.' . $this->conversation->tenant_id . '.chats'),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'conversation.new';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'conversation_id' => $this->conversation->conversation_id,
                'customer_id' => $this->conversation->customer_id,
                'status' => $this->conversation->status,
                'channel' => $this->conversation->channel,
                'created_at' => $this->conversation->created_at->toISOString(),
            ],
            'customer' => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->first_name . ' ' . $this->customer->last_name,
                'email' => $this->customer->email,
            ] : null,
        ];
    }
}
