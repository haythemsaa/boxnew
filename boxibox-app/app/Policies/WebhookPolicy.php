<?php

namespace App\Policies;

use App\Models\Webhook;
use App\Models\User;

class WebhookPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Webhook $webhook): bool
    {
        return $user->tenant_id === $webhook->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Webhook $webhook): bool
    {
        return $user->tenant_id === $webhook->tenant_id;
    }

    public function delete(User $user, Webhook $webhook): bool
    {
        return $user->tenant_id === $webhook->tenant_id;
    }

    public function restore(User $user, Webhook $webhook): bool
    {
        return $user->tenant_id === $webhook->tenant_id;
    }

    public function forceDelete(User $user, Webhook $webhook): bool
    {
        return $user->tenant_id === $webhook->tenant_id;
    }
}
