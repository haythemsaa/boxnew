<?php

namespace App\Policies;

use App\Models\GdprRequest;
use App\Models\User;

class GdprRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, GdprRequest $gdprRequest): bool
    {
        return $user->tenant_id === $gdprRequest->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, GdprRequest $gdprRequest): bool
    {
        return $user->tenant_id === $gdprRequest->tenant_id;
    }

    public function delete(User $user, GdprRequest $gdprRequest): bool
    {
        return $user->tenant_id === $gdprRequest->tenant_id;
    }

    public function restore(User $user, GdprRequest $gdprRequest): bool
    {
        return $user->tenant_id === $gdprRequest->tenant_id;
    }

    public function forceDelete(User $user, GdprRequest $gdprRequest): bool
    {
        return $user->tenant_id === $gdprRequest->tenant_id;
    }
}
