<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id;
    }
}
