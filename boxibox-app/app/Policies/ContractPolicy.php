<?php

namespace App\Policies;

use App\Models\Contract;
use App\Models\User;

class ContractPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id;
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
        return true;
    }

    public function terminate(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id;
    }

    public function renew(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id;
    }

    public function sign(User $user, Contract $contract): bool
    {
        return $user->tenant_id === $contract->tenant_id;
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
