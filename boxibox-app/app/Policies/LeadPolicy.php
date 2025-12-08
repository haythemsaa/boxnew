<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_leads') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function view(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('view_leads') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_leads') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function update(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('edit_leads') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function delete(User $user, Lead $lead): bool
    {
        if ($user->tenant_id !== $lead->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('delete_leads') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function restore(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, Lead $lead): bool
    {
        return $user->tenant_id === $lead->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }
}
