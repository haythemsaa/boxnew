<?php

namespace App\Policies;

use App\Models\MaintenanceTicket;
use App\Models\User;

class MaintenanceTicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, MaintenanceTicket $maintenance): bool
    {
        return $user->tenant_id === $maintenance->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, MaintenanceTicket $maintenance): bool
    {
        return $user->tenant_id === $maintenance->tenant_id;
    }

    public function delete(User $user, MaintenanceTicket $maintenance): bool
    {
        return $user->tenant_id === $maintenance->tenant_id;
    }

    public function restore(User $user, MaintenanceTicket $maintenance): bool
    {
        return $user->tenant_id === $maintenance->tenant_id;
    }

    public function forceDelete(User $user, MaintenanceTicket $maintenance): bool
    {
        return $user->tenant_id === $maintenance->tenant_id;
    }
}
