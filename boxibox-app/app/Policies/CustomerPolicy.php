<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;

class CustomerPolicy
{
    public function viewAny(User $user): bool
    {
        // Tous les utilisateurs du tenant peuvent voir les clients
        return $user->tenant_id !== null;
    }

    public function view(User $user, Customer $customer): bool
    {
        // Les utilisateurs peuvent voir les clients de leur tenant
        return $user->tenant_id === $customer->tenant_id;
    }

    public function create(User $user): bool
    {
        // Tous les utilisateurs du tenant peuvent créer des clients
        return $user->tenant_id !== null;
    }

    public function update(User $user, Customer $customer): bool
    {
        // Les utilisateurs peuvent mettre à jour les clients de leur tenant
        return $user->tenant_id === $customer->tenant_id;
    }

    public function delete(User $user, Customer $customer): bool
    {
        // Les utilisateurs peuvent supprimer les clients de leur tenant
        // sauf s'ils ont des contrats actifs
        if ($user->tenant_id !== $customer->tenant_id) {
            return false;
        }
        if ($customer->contracts()->where('status', 'active')->exists()) {
            return false;
        }
        return true;
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
