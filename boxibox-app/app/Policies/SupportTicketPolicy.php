<?php

namespace App\Policies;

use App\Models\SupportTicket;
use App\Models\User;

class SupportTicketPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        // Les utilisateurs du tenant peuvent voir tous les tickets de leur tenant
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, SupportTicket $ticket): bool
    {
        // Les utilisateurs peuvent voir les tickets de leur tenant
        return $user->tenant_id === $ticket->tenant_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        // Tous les utilisateurs du tenant peuvent créer des tickets
        return $user->tenant_id !== null;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, SupportTicket $ticket): bool
    {
        // Les utilisateurs peuvent mettre à jour les tickets de leur tenant
        return $user->tenant_id === $ticket->tenant_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, SupportTicket $ticket): bool
    {
        // Les utilisateurs peuvent supprimer les tickets de leur tenant
        return $user->tenant_id === $ticket->tenant_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, SupportTicket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, SupportTicket $ticket): bool
    {
        return $user->tenant_id === $ticket->tenant_id;
    }
}
