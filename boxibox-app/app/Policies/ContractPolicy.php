<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_contracts') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function view(User $user, Contract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('view_contracts') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_contracts') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function update(User $user, Contract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('edit_contracts') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function delete(User $user, Contract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }
        // Cannot delete active contracts with invoices
        if ($contract->status === 'active' && $contract->invoices()->exists()) {
            return false;
        }
        return $user->hasPermissionTo('delete_contracts') || $user->hasRole(['admin', 'super-admin']);
    }

    public function terminate(User $user, Contract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('terminate_contracts') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function renew(User $user, Contract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('renew_contracts') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function sign(User $user, Contract $contract): bool
    {
        if ($user->tenant_id !== $contract->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('sign_contracts') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function restore(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }
}
