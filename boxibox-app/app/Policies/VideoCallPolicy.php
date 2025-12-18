<?php

namespace App\Policies;

use App\Models\VideoCall;
use App\Models\User;

class VideoCallPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, VideoCall $videoCall): bool
    {
        return $user->tenant_id === $videoCall->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, VideoCall $videoCall): bool
    {
        return $user->tenant_id === $videoCall->tenant_id;
    }

    public function delete(User $user, VideoCall $videoCall): bool
    {
        return $user->tenant_id === $videoCall->tenant_id;
    }

    public function restore(User $user, VideoCall $videoCall): bool
    {
        return $user->tenant_id === $videoCall->tenant_id;
    }

    public function forceDelete(User $user, VideoCall $videoCall): bool
    {
        return $user->tenant_id === $videoCall->tenant_id;
    }
}
