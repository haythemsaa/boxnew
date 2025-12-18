<?php

namespace App\Policies;

use App\Models\Box;
use App\Models\User;

class BoxPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Box $box): bool
    {
        return $user->tenant_id === $box->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Box $box): bool
    {
        return $user->tenant_id === $box->tenant_id;
    }

    public function delete(User $user, Box $box): bool
    {
        return $user->tenant_id === $box->tenant_id;
    }

    public function restore(User $user, Box $box): bool
    {
        return $user->tenant_id === $box->tenant_id;
    }

    public function forceDelete(User $user, Box $box): bool
    {
        return $user->tenant_id === $box->tenant_id;
    }
}
