<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class SecurityAuditService
{
    /**
     * Log security event.
     */
    public function logSecurityEvent(string $action, string $resource, string $status = 'success', array $details = []): void
    {
        $user = Auth::user();

        $logData = [
            'action' => $action,
            'resource' => $resource,
            'status' => $status,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now()->toIso8601String(),
            'details' => $details,
        ];

        Log::channel('security')->info(json_encode($logData));
    }

    /**
     * Log login attempt.
     */
    public function logLoginAttempt(string $email, bool $success, ?string $reason = null): void
    {
        $this->logSecurityEvent(
            'login_attempt',
            'authentication',
            $success ? 'success' : 'failure',
            [
                'email' => $email,
                'reason' => $reason,
                'ip_address' => request()->ip(),
            ]
        );
    }

    /**
     * Log unauthorized access attempt.
     */
    public function logUnauthorizedAccess(string $resource, string $action = 'view'): void
    {
        $this->logSecurityEvent(
            'unauthorized_access',
            $resource,
            'failure',
            [
                'action' => $action,
                'ip_address' => request()->ip(),
            ]
        );
    }

    /**
     * Log data modification.
     */
    public function logDataModification(string $model, int $modelId, string $action, array $changes): void
    {
        $this->logSecurityEvent(
            'data_modification',
            "{$model}#{$modelId}",
            'success',
            [
                'model' => $model,
                'action' => $action,
                'changes' => $changes,
            ]
        );
    }

    /**
     * Log API access.
     */
    public function logApiAccess(string $endpoint, string $method, int $status, float $responseTime): void
    {
        $this->logSecurityEvent(
            'api_access',
            $endpoint,
            $status >= 200 && $status < 300 ? 'success' : 'failure',
            [
                'method' => $method,
                'status_code' => $status,
                'response_time_ms' => round($responseTime * 1000, 2),
                'ip_address' => request()->ip(),
            ]
        );
    }

    /**
     * Log file access or download.
     */
    public function logFileAccess(string $filename, string $action = 'download', ?string $reason = null): void
    {
        $this->logSecurityEvent(
            'file_access',
            $filename,
            'success',
            [
                'action' => $action,
                'reason' => $reason,
                'file_size' => filesize($filename),
            ]
        );
    }

    /**
     * Log potential security threat.
     */
    public function logSecurityThreat(string $threat, string $description, array $details = []): void
    {
        $logData = [
            'threat_type' => $threat,
            'description' => $description,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'timestamp' => now()->toIso8601String(),
            'details' => $details,
        ];

        Log::channel('security')->alert(json_encode($logData));
    }

    /**
     * Get audit trail for a user.
     */
    public function getAuditTrail(int $userId, int $limit = 100): array
    {
        // This would query a database table in a full implementation
        // For now, returning an empty array as a placeholder
        return [];
    }

    /**
     * Check if action requires additional verification.
     */
    public function requiresAdditionalVerification(string $action): bool
    {
        $sensitiveActions = [
            'delete_contract',
            'modify_pricing',
            'process_refund',
            'export_data',
            'create_admin',
        ];

        return in_array($action, $sensitiveActions);
    }

    /**
     * Validate action based on user role.
     */
    public function validateActionPermission(string $action, string $userRole): bool
    {
        $rolePermissions = [
            'admin' => [
                'delete_contract',
                'modify_pricing',
                'process_refund',
                'export_data',
                'create_admin',
                'view_audit_logs',
            ],
            'manager' => [
                'create_contract',
                'modify_contract',
                'generate_invoice',
                'process_payment',
                'export_data',
            ],
            'customer' => [
                'view_contract',
                'download_invoice',
                'view_payment_history',
            ],
        ];

        return in_array($action, $rolePermissions[$userRole] ?? []);
    }

    /**
     * Detect suspicious activity patterns.
     */
    public function detectSuspiciousActivity(array $activityLog): array
    {
        $suspicious = [];

        // Check for too many failed login attempts
        $failedLogins = collect($activityLog)
            ->where('action', 'login_attempt')
            ->where('status', 'failure')
            ->count();

        if ($failedLogins > 5) {
            $suspicious[] = [
                'type' => 'too_many_failed_logins',
                'severity' => 'high',
                'count' => $failedLogins,
            ];
        }

        // Check for unusual data access patterns
        $dataAccess = collect($activityLog)
            ->where('action', 'data_modification')
            ->count();

        if ($dataAccess > 100) {
            $suspicious[] = [
                'type' => 'unusual_data_modification',
                'severity' => 'medium',
                'count' => $dataAccess,
            ];
        }

        // Check for API access from different IPs
        $uniqueIps = collect($activityLog)
            ->pluck('details.ip_address')
            ->unique()
            ->count();

        if ($uniqueIps > 5) {
            $suspicious[] = [
                'type' => 'access_from_multiple_locations',
                'severity' => 'medium',
                'locations' => $uniqueIps,
            ];
        }

        return $suspicious;
    }
}
