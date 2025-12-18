<?php

namespace App\Policies;

use App\Models\SustainabilityCertification;
use App\Models\User;

class SustainabilityCertificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, SustainabilityCertification $certification): bool
    {
        return $user->tenant_id === $certification->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, SustainabilityCertification $certification): bool
    {
        return $user->tenant_id === $certification->tenant_id;
    }

    public function delete(User $user, SustainabilityCertification $certification): bool
    {
        return $user->tenant_id === $certification->tenant_id;
    }

    public function restore(User $user, SustainabilityCertification $certification): bool
    {
        return $user->tenant_id === $certification->tenant_id;
    }

    public function forceDelete(User $user, SustainabilityCertification $certification): bool
    {
        return $user->tenant_id === $certification->tenant_id;
    }
}
