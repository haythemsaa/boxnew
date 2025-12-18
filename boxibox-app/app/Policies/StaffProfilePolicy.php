<?php

namespace App\Policies;

use App\Models\StaffProfile;
use App\Models\User;

class StaffProfilePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, StaffProfile $staff): bool
    {
        return $user->tenant_id === $staff->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, StaffProfile $staff): bool
    {
        return $user->tenant_id === $staff->tenant_id;
    }

    public function delete(User $user, StaffProfile $staff): bool
    {
        return $user->tenant_id === $staff->tenant_id;
    }

    public function restore(User $user, StaffProfile $staff): bool
    {
        return $user->tenant_id === $staff->tenant_id;
    }

    public function forceDelete(User $user, StaffProfile $staff): bool
    {
        return $user->tenant_id === $staff->tenant_id;
    }
}
