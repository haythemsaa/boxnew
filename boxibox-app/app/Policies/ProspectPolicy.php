<?php

namespace App\Policies;

use App\Models\Prospect;
use App\Models\User;

class ProspectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Prospect $prospect): bool
    {
        return $user->tenant_id === $prospect->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Prospect $prospect): bool
    {
        return $user->tenant_id === $prospect->tenant_id;
    }

    public function delete(User $user, Prospect $prospect): bool
    {
        return $user->tenant_id === $prospect->tenant_id;
    }

    public function convert(User $user, Prospect $prospect): bool
    {
        return $user->tenant_id === $prospect->tenant_id;
    }

    public function restore(User $user, Prospect $prospect): bool
    {
        return $user->tenant_id === $prospect->tenant_id;
    }

    public function forceDelete(User $user, Prospect $prospect): bool
    {
        return $user->tenant_id === $prospect->tenant_id;
    }
}
