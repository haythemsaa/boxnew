<?php

namespace App\Policies;

use App\Models\SustainabilityGoal;
use App\Models\User;

class SustainabilityGoalPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, SustainabilityGoal $goal): bool
    {
        return $user->tenant_id === $goal->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, SustainabilityGoal $goal): bool
    {
        return $user->tenant_id === $goal->tenant_id;
    }

    public function delete(User $user, SustainabilityGoal $goal): bool
    {
        return $user->tenant_id === $goal->tenant_id;
    }

    public function restore(User $user, SustainabilityGoal $goal): bool
    {
        return $user->tenant_id === $goal->tenant_id;
    }

    public function forceDelete(User $user, SustainabilityGoal $goal): bool
    {
        return $user->tenant_id === $goal->tenant_id;
    }
}
