<?php

namespace App\Policies;

use App\Models\OverdueAction;
use App\Models\User;

class OverdueActionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, OverdueAction $action): bool
    {
        return $user->tenant_id === $action->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, OverdueAction $action): bool
    {
        return $user->tenant_id === $action->tenant_id;
    }

    public function delete(User $user, OverdueAction $action): bool
    {
        return $user->tenant_id === $action->tenant_id;
    }

    public function restore(User $user, OverdueAction $action): bool
    {
        return $user->tenant_id === $action->tenant_id;
    }

    public function forceDelete(User $user, OverdueAction $action): bool
    {
        return $user->tenant_id === $action->tenant_id;
    }
}
