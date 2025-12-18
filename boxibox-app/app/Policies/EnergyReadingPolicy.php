<?php

namespace App\Policies;

use App\Models\EnergyReading;
use App\Models\User;

class EnergyReadingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, EnergyReading $reading): bool
    {
        return $user->tenant_id === $reading->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, EnergyReading $reading): bool
    {
        return $user->tenant_id === $reading->tenant_id;
    }

    public function delete(User $user, EnergyReading $reading): bool
    {
        return $user->tenant_id === $reading->tenant_id;
    }

    public function restore(User $user, EnergyReading $reading): bool
    {
        return $user->tenant_id === $reading->tenant_id;
    }

    public function forceDelete(User $user, EnergyReading $reading): bool
    {
        return $user->tenant_id === $reading->tenant_id;
    }
}
