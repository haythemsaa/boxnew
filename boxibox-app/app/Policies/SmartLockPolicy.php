<?php

namespace App\Policies;

use App\Models\SmartLock;
use App\Models\User;

class SmartLockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, SmartLock $lock): bool
    {
        return $user->tenant_id === $lock->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, SmartLock $lock): bool
    {
        return $user->tenant_id === $lock->tenant_id;
    }

    public function delete(User $user, SmartLock $lock): bool
    {
        return $user->tenant_id === $lock->tenant_id;
    }

    public function restore(User $user, SmartLock $lock): bool
    {
        return $user->tenant_id === $lock->tenant_id;
    }

    public function forceDelete(User $user, SmartLock $lock): bool
    {
        return $user->tenant_id === $lock->tenant_id;
    }
}
