<?php

namespace App\Policies;

use App\Models\OverdueWorkflow;
use App\Models\User;

class OverdueWorkflowPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, OverdueWorkflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, OverdueWorkflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    public function delete(User $user, OverdueWorkflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    public function restore(User $user, OverdueWorkflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }

    public function forceDelete(User $user, OverdueWorkflow $workflow): bool
    {
        return $user->tenant_id === $workflow->tenant_id;
    }
}
