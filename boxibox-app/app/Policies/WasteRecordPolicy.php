<?php

namespace App\Policies;

use App\Models\WasteRecord;
use App\Models\User;

class WasteRecordPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, WasteRecord $record): bool
    {
        return $user->tenant_id === $record->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, WasteRecord $record): bool
    {
        return $user->tenant_id === $record->tenant_id;
    }

    public function delete(User $user, WasteRecord $record): bool
    {
        return $user->tenant_id === $record->tenant_id;
    }

    public function restore(User $user, WasteRecord $record): bool
    {
        return $user->tenant_id === $record->tenant_id;
    }

    public function forceDelete(User $user, WasteRecord $record): bool
    {
        return $user->tenant_id === $record->tenant_id;
    }
}
