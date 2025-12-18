<?php

namespace App\Policies;

use App\Models\Patrol;
use App\Models\User;

class PatrolPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Patrol $patrol): bool
    {
        return $user->tenant_id === $patrol->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Patrol $patrol): bool
    {
        return $user->tenant_id === $patrol->tenant_id;
    }

    public function delete(User $user, Patrol $patrol): bool
    {
        return $user->tenant_id === $patrol->tenant_id;
    }

    public function restore(User $user, Patrol $patrol): bool
    {
        return $user->tenant_id === $patrol->tenant_id;
    }

    public function forceDelete(User $user, Patrol $patrol): bool
    {
        return $user->tenant_id === $patrol->tenant_id;
    }
}
