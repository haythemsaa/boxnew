<?php

namespace App\Policies;

use App\Models\Inspection;
use App\Models\User;

class InspectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Inspection $inspection): bool
    {
        return $user->tenant_id === $inspection->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Inspection $inspection): bool
    {
        return $user->tenant_id === $inspection->tenant_id;
    }

    public function delete(User $user, Inspection $inspection): bool
    {
        return $user->tenant_id === $inspection->tenant_id;
    }

    public function restore(User $user, Inspection $inspection): bool
    {
        return $user->tenant_id === $inspection->tenant_id;
    }

    public function forceDelete(User $user, Inspection $inspection): bool
    {
        return $user->tenant_id === $inspection->tenant_id;
    }
}
