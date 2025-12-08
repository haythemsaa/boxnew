<?php

namespace App\Policies;

use App\Models\Prospect;
use App\Models\User;

class ProspectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_prospects') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function view(User $user, Prospect $prospect): bool
    {
        if ($user->tenant_id !== $prospect->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('view_prospects') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_prospects') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function update(User $user, Prospect $prospect): bool
    {
        if ($user->tenant_id !== $prospect->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('edit_prospects') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function delete(User $user, Prospect $prospect): bool
    {
        if ($user->tenant_id !== $prospect->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('delete_prospects') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function convert(User $user, Prospect $prospect): bool
    {
        if ($user->tenant_id !== $prospect->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('convert_prospects') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function restore(User $user, Prospect $prospect): bool
    {
        return $user->tenant_id === $prospect->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, Prospect $prospect): bool
    {
        return $user->tenant_id === $prospect->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }
}
