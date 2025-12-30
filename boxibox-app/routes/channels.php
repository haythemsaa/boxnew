<?php

use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

/**
 * Private channel for a specific chat conversation
 * Both tenant staff and customers can listen
 */
Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    // If user is a tenant staff member
    if ($user instanceof User) {
        $conversation = ChatConversation::where('conversation_id', $conversationId)->first();

        if (!$conversation) {
            return false;
        }

        // Check if user belongs to the same tenant
        return $user->tenant_id === $conversation->tenant_id;
    }

    // If user is a customer (via API token or session)
    if ($user instanceof Customer) {
        $conversation = ChatConversation::where('conversation_id', $conversationId)
            ->where('customer_id', $user->id)
            ->exists();

        return $conversation;
    }

    return false;
});

/**
 * Private channel for tenant to receive all new chats
 * Only tenant staff can listen
 */
Broadcast::channel('tenant.{tenantId}.chats', function (User $user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});

/**
 * Private channel for customer to receive their chat updates
 */
Broadcast::channel('customer.{customerId}.chat', function ($user, $customerId) {
    // For session-based mobile auth
    if ($user instanceof Customer) {
        return $user->id === (int) $customerId;
    }

    return false;
});

/**
 * Presence channel to track who is online in a conversation
 */
Broadcast::channel('presence.chat.{conversationId}', function ($user, $conversationId) {
    $conversation = ChatConversation::where('conversation_id', $conversationId)->first();

    if (!$conversation) {
        return false;
    }

    // User is tenant staff
    if ($user instanceof User && $user->tenant_id === $conversation->tenant_id) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'type' => 'agent',
        ];
    }

    // User is customer
    if ($user instanceof Customer && $conversation->customer_id === $user->id) {
        return [
            'id' => $user->id,
            'name' => $user->first_name . ' ' . $user->last_name,
            'type' => 'customer',
        ];
    }

    return false;
});

/**
 * Private channel for a specific support ticket
 * Both tenant staff and customers can listen
 */
Broadcast::channel('support.ticket.{ticketId}', function ($user, $ticketId) {
    $ticket = \App\Models\SupportTicket::find($ticketId);

    if (!$ticket) {
        return false;
    }

    // If user is a tenant staff member
    if ($user instanceof User) {
        return $user->tenant_id === $ticket->tenant_id;
    }

    // If user is a customer
    if ($user instanceof Customer) {
        return $ticket->customer_id === $user->id;
    }

    return false;
});

/**
 * Private channel for tenant to receive all support ticket updates
 */
Broadcast::channel('tenant.{tenantId}.support', function (User $user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});

/**
 * Private channel for customer to receive their support ticket updates
 */
Broadcast::channel('customer.{customerId}.support', function ($user, $customerId) {
    if ($user instanceof Customer) {
        return $user->id === (int) $customerId;
    }

    return false;
});
