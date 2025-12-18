<?php

namespace App\Policies;

use App\Models\SustainabilityInitiative;
use App\Models\User;

class SustainabilityInitiativePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, SustainabilityInitiative $initiative): bool
    {
        return $user->tenant_id === $initiative->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, SustainabilityInitiative $initiative): bool
    {
        return $user->tenant_id === $initiative->tenant_id;
    }

    public function delete(User $user, SustainabilityInitiative $initiative): bool
    {
        return $user->tenant_id === $initiative->tenant_id;
    }

    public function restore(User $user, SustainabilityInitiative $initiative): bool
    {
        return $user->tenant_id === $initiative->tenant_id;
    }

    public function forceDelete(User $user, SustainabilityInitiative $initiative): bool
    {
        return $user->tenant_id === $initiative->tenant_id;
    }
}
