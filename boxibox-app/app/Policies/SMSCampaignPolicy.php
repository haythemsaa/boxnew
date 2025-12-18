<?php

namespace App\Policies;

use App\Models\SMSCampaign;
use App\Models\User;

class SMSCampaignPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, SMSCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, SMSCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id;
    }

    public function delete(User $user, SMSCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id;
    }

    public function restore(User $user, SMSCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id;
    }

    public function forceDelete(User $user, SMSCampaign $campaign): bool
    {
        return $user->tenant_id === $campaign->tenant_id;
    }
}
