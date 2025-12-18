<?php

namespace App\Policies;

use App\Models\CustomReport;
use App\Models\User;

class CustomReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, CustomReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, CustomReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }

    public function delete(User $user, CustomReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }

    public function restore(User $user, CustomReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }

    public function forceDelete(User $user, CustomReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }
}
