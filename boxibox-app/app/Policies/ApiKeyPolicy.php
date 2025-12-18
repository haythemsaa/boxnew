<?php

namespace App\Policies;

use App\Models\ApiKey;
use App\Models\User;

class ApiKeyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, ApiKey $apiKey): bool
    {
        return $user->tenant_id === $apiKey->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, ApiKey $apiKey): bool
    {
        return $user->tenant_id === $apiKey->tenant_id;
    }

    public function delete(User $user, ApiKey $apiKey): bool
    {
        return $user->tenant_id === $apiKey->tenant_id;
    }

    public function restore(User $user, ApiKey $apiKey): bool
    {
        return $user->tenant_id === $apiKey->tenant_id;
    }

    public function forceDelete(User $user, ApiKey $apiKey): bool
    {
        return $user->tenant_id === $apiKey->tenant_id;
    }
}
