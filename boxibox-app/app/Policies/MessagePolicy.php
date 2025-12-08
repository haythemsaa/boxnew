<?php

namespace App\Policies;

use App\Models\Message;
use App\Models\User;

class MessagePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_messages') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function view(User $user, Message $message): bool
    {
        if ($user->tenant_id !== $message->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('view_messages') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('send_messages') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function update(User $user, Message $message): bool
    {
        if ($user->tenant_id !== $message->tenant_id) {
            return false;
        }
        // Can only update draft messages
        if ($message->status !== 'draft') {
            return false;
        }
        return $user->hasPermissionTo('edit_messages') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function delete(User $user, Message $message): bool
    {
        if ($user->tenant_id !== $message->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('delete_messages') || $user->hasRole(['admin', 'super-admin']);
    }

    public function send(User $user, Message $message): bool
    {
        if ($user->tenant_id !== $message->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('send_messages') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function sendBulk(User $user): bool
    {
        return $user->hasPermissionTo('send_bulk_messages') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function restore(User $user, Message $message): bool
    {
        return $user->tenant_id === $message->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, Message $message): bool
    {
        return $user->tenant_id === $message->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }
}
