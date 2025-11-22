<?php

namespace App\Services;

use App\Models\Box;
use App\Models\SmartLock;
use App\Models\AccessLog;
use App\Models\Contract;
use App\Models\Customer;
use Carbon\Carbon;

class AccessControlService
{
    /**
     * Grant access to a box for a customer
     */
    public function grantAccess(Contract $contract): array
    {
        $box = $contract->box;
        $customer = $contract->customer;

        if (!$box->smartLock) {
            return [
                'success' => false,
                'message' => 'No smart lock configured for this box',
            ];
        }

        $accessCode = $this->generateAccessCode();

        // In production, this would call the actual lock provider API
        // Example for Nokē:
        // $result = $this->nokeProvider->grantAccess($box->smartLock->device_id, $customer->id, $accessCode);

        // Log the access grant
        AccessLog::create([
            'tenant_id' => $contract->tenant_id,
            'box_id' => $box->id,
            'customer_id' => $customer->id,
            'smart_lock_id' => $box->smartLock->id,
            'access_method' => 'code',
            'status' => 'granted',
            'user_identifier' => $accessCode,
            'accessed_at' => now(),
            'metadata' => [
                'contract_id' => $contract->id,
                'action' => 'access_granted',
            ],
        ]);

        // Update contract with access code
        $contract->update([
            'access_code' => $accessCode,
            'access_granted_at' => now(),
        ]);

        return [
            'success' => true,
            'access_code' => $accessCode,
            'message' => 'Access granted successfully',
        ];
    }

    /**
     * Revoke access to a box
     */
    public function revokeAccess(Contract $contract): array
    {
        $box = $contract->box;

        if (!$box->smartLock) {
            return [
                'success' => false,
                'message' => 'No smart lock configured for this box',
            ];
        }

        // In production, call provider API to revoke
        // $this->nokeProvider->revokeAccess($box->smartLock->device_id, $contract->access_code);

        // Log the revocation
        AccessLog::create([
            'tenant_id' => $contract->tenant_id,
            'box_id' => $box->id,
            'customer_id' => $contract->customer_id,
            'smart_lock_id' => $box->smartLock->id,
            'access_method' => 'code',
            'status' => 'denied',
            'user_identifier' => $contract->access_code,
            'reason' => 'Access revoked',
            'accessed_at' => now(),
            'metadata' => [
                'contract_id' => $contract->id,
                'action' => 'access_revoked',
            ],
        ]);

        $contract->update([
            'access_code' => null,
            'access_granted_at' => null,
        ]);

        return [
            'success' => true,
            'message' => 'Access revoked successfully',
        ];
    }

    /**
     * Handle payment status change and update access
     */
    public function handlePaymentStatusChange(Contract $contract, string $status): void
    {
        if ($status === 'paid') {
            // Grant or reactivate access
            if (!$contract->access_code) {
                $this->grantAccess($contract);
            }
        } elseif ($status === 'overdue') {
            // Check if should lock based on days overdue
            $invoice = $contract->invoices()
                ->where('status', 'overdue')
                ->latest()
                ->first();

            if ($invoice) {
                $daysOverdue = now()->diffInDays($invoice->due_date);

                if ($daysOverdue >= 15) { // Lock after 15 days
                    $this->revokeAccess($contract);
                }
            }
        }
    }

    /**
     * Generate secure access code
     */
    protected function generateAccessCode(): string
    {
        // Generate 6-digit code
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get access analytics for a site
     */
    public function getAccessAnalytics($tenantId, $siteId = null, $period = 'week'): array
    {
        $startDate = match($period) {
            'today' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            default => now()->startOfWeek(),
        };

        $logs = AccessLog::where('tenant_id', $tenantId)
            ->whereBetween('accessed_at', [$startDate, now()])
            ->when($siteId, function ($q) use ($siteId) {
                $q->whereHas('box', fn($q2) => $q2->where('site_id', $siteId));
            })
            ->get();

        $totalAccess = $logs->count();
        $granted = $logs->where('status', 'granted')->count();
        $denied = $logs->where('status', 'denied')->count();
        $forced = $logs->where('status', 'forced')->count();

        return [
            'total_access_attempts' => $totalAccess,
            'granted' => $granted,
            'denied' => $denied,
            'forced' => $forced,
            'success_rate' => $totalAccess > 0 ? ($granted / $totalAccess) * 100 : 0,
            'by_method' => $logs->groupBy('access_method')->map->count(),
            'by_hour' => $this->getAccessByHour($logs),
            'suspicious_activity' => $forced + ($denied > 10 ? $denied : 0),
        ];
    }

    /**
     * Get access distribution by hour
     */
    protected function getAccessByHour($logs): array
    {
        $byHour = [];

        for ($hour = 0; $hour < 24; $hour++) {
            $count = $logs->filter(function ($log) use ($hour) {
                return $log->accessed_at->hour === $hour;
            })->count();

            $byHour[] = [
                'hour' => $hour,
                'count' => $count,
            ];
        }

        return $byHour;
    }

    /**
     * Get locks status
     */
    public function getLocksStatus($tenantId, $siteId = null): array
    {
        $locks = SmartLock::where('tenant_id', $tenantId)
            ->when($siteId, function ($q) use ($siteId) {
                $q->whereHas('box', fn($q2) => $q2->where('site_id', $siteId));
            })
            ->get();

        return [
            'total' => $locks->count(),
            'active' => $locks->where('status', 'active')->count(),
            'offline' => $locks->filter->isOnline()->count(),
            'low_battery' => $locks->filter->needsBatteryReplacement()->count(),
            'locks' => $locks->map(function ($lock) {
                return [
                    'id' => $lock->id,
                    'box' => $lock->box->number ?? 'N/A',
                    'status' => $lock->status,
                    'battery_level' => $lock->battery_level,
                    'last_seen' => $lock->last_seen_at?->diffForHumans(),
                    'needs_attention' => $lock->needsBatteryReplacement() || !$lock->isOnline(),
                ];
            }),
        ];
    }

    /**
     * Get recent suspicious activity
     */
    public function getSuspiciousActivity($tenantId, $hours = 24): array
    {
        $logs = AccessLog::where('tenant_id', $tenantId)
            ->where('accessed_at', '>=', now()->subHours($hours))
            ->suspicious()
            ->with(['box', 'customer'])
            ->get();

        return $logs->map(function ($log) {
            return [
                'id' => $log->id,
                'box' => $log->box->number ?? 'N/A',
                'customer' => $log->customer?->full_name ?? 'Unknown',
                'status' => $log->status,
                'reason' => $log->reason,
                'time' => $log->accessed_at->format('Y-m-d H:i:s'),
                'time_ago' => $log->accessed_at->diffForHumans(),
                'severity' => $log->status === 'forced' ? 'high' : 'medium',
            ];
        })->toArray();
    }
}
