<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_customers') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function view(User $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('view_customers') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_customers') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function update(User $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('edit_customers') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function delete(User $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }
        // Cannot delete customers with active contracts
        if ($customer->contracts()->where('status', 'active')->exists()) {
            return false;
        }
        return $user->hasPermissionTo('delete_customers') || $user->hasRole(['admin', 'super-admin']);
    }

    public function merge(User $user, Customer $customer): bool
    {
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }
        return $user->hasRole(['admin', 'super-admin']);
    }

    public function export(User $user): bool
    {
        return $user->hasPermissionTo('export_customers') || $user->hasRole(['admin', 'super-admin', 'manager']);
    }

    public function restore(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, Customer $customer): bool
    {
        return $user->tenant_id === $customer->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }
}
