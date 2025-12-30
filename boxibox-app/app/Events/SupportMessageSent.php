<?php

namespace App\Events;

use App\Models\SupportMessage;
use App\Models\SupportTicket;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SupportMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public SupportMessage $message;
    public SupportTicket $ticket;
    public string $senderType;
    public ?string $senderName;

    /**
     * Create a new event instance.
     */
    public function __construct(
        SupportMessage $message,
        SupportTicket $ticket,
        string $senderType = 'user',
        ?string $senderName = null
    ) {
        $this->message = $message;
        $this->ticket = $ticket;
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
            // Broadcast to the specific ticket channel
            new PrivateChannel('support.ticket.' . $this->ticket->id),

            // Also broadcast to tenant's support channel for notifications
            new PrivateChannel('tenant.' . $this->ticket->tenant_id . '.support'),
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
        // Load relations if needed
        $this->message->loadMissing(['user', 'customer']);

        return [
            'message' => [
                'id' => $this->message->id,
                'ticket_id' => $this->message->ticket_id,
                'message' => $this->message->message,
                'sender_type' => $this->message->sender_type,
                'sender_name' => $this->senderName ?? $this->message->sender_name,
                'is_internal' => $this->message->is_internal,
                'is_read' => $this->message->is_read,
                'created_at' => $this->message->created_at->format('d/m/Y H:i'),
            ],
            'ticket' => [
                'id' => $this->ticket->id,
                'ticket_number' => $this->ticket->ticket_number,
                'status' => $this->ticket->status,
            ],
            'sender_type' => $this->senderType,
            'sender_name' => $this->senderName,
        ];
    }
}
