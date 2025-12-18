<?php

namespace App\Policies;

use App\Models\ScheduledReport;
use App\Models\User;

class ScheduledReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function view(User $user, ScheduledReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }

    public function create(User $user): bool
    {
        return $user->tenant_id !== null;
    }

    public function update(User $user, ScheduledReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }

    public function delete(User $user, ScheduledReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }

    public function restore(User $user, ScheduledReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }

    public function forceDelete(User $user, ScheduledReport $report): bool
    {
        return $user->tenant_id === $report->tenant_id;
    }
}
