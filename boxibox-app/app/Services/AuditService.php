<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class AuditService
{
    /**
     * Log an action to the audit log.
     */
    public static function log(
        string $action,
        ?Model $model = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): void {
        try {
            $user = Auth::user();

            $data = [
                'user_id' => $user?->id,
                'tenant_id' => $user?->tenant_id,
                'action' => $action,
                'model_type' => $model ? get_class($model) : null,
                'model_id' => $model?->id,
                'old_values' => $oldValues ? self::sanitizeData($oldValues) : null,
                'new_values' => $newValues ? self::sanitizeData($newValues) : null,
                'description' => $description,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
                'url' => Request::fullUrl(),
                'method' => Request::method(),
            ];

            // Use database if AuditLog model exists, otherwise log to file
            if (class_exists(AuditLog::class)) {
                AuditLog::create($data);
            } else {
                Log::channel('audit')->info($action, $data);
            }
        } catch (\Exception $e) {
            // Don't let audit failures break the application
            Log::error('Audit log failed: ' . $e->getMessage(), [
                'action' => $action,
                'model' => $model ? get_class($model) : null,
            ]);
        }
    }

    /**
     * Log a critical security event.
     */
    public static function security(string $event, array $context = []): void
    {
        $user = Auth::user();

        $data = array_merge($context, [
            'user_id' => $user?->id,
            'tenant_id' => $user?->tenant_id,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'timestamp' => now()->toIso8601String(),
        ]);

        Log::channel('security')->warning($event, $data);

        // Also log to audit log
        self::log('security.' . $event, null, null, $data, $event);
    }

    /**
     * Log a failed login attempt.
     */
    public static function failedLogin(string $email, string $reason = 'invalid_credentials'): void
    {
        self::security('failed_login', [
            'email' => $email,
            'reason' => $reason,
        ]);
    }

    /**
     * Log a successful login.
     */
    public static function successfulLogin(): void
    {
        self::log('auth.login', Auth::user(), null, null, 'User logged in');
    }

    /**
     * Log a logout.
     */
    public static function logout(): void
    {
        self::log('auth.logout', Auth::user(), null, null, 'User logged out');
    }

    /**
     * Log model creation.
     */
    public static function created(Model $model, ?string $description = null): void
    {
        self::log(
            'model.created',
            $model,
            null,
            $model->getAttributes(),
            $description ?? class_basename($model) . ' created'
        );
    }

    /**
     * Log model update.
     */
    public static function updated(Model $model, array $originalValues, ?string $description = null): void
    {
        $changes = $model->getChanges();

        // Only log if there are actual changes
        if (empty($changes)) {
            return;
        }

        self::log(
            'model.updated',
            $model,
            array_intersect_key($originalValues, $changes),
            $changes,
            $description ?? class_basename($model) . ' updated'
        );
    }

    /**
     * Log model deletion.
     */
    public static function deleted(Model $model, ?string $description = null): void
    {
        self::log(
            'model.deleted',
            $model,
            $model->getAttributes(),
            null,
            $description ?? class_basename($model) . ' deleted'
        );
    }

    /**
     * Log a payment action.
     */
    public static function payment(string $action, Model $payment, ?array $context = null): void
    {
        self::log(
            'payment.' . $action,
            $payment,
            null,
            $context ?? $payment->only(['amount', 'status', 'method']),
            "Payment {$action}"
        );
    }

    /**
     * Log a contract action.
     */
    public static function contract(string $action, Model $contract, ?array $context = null): void
    {
        self::log(
            'contract.' . $action,
            $contract,
            null,
            $context ?? $contract->only(['contract_number', 'status', 'monthly_price']),
            "Contract {$action}"
        );
    }

    /**
     * Log data export action.
     */
    public static function export(string $type, int $recordCount, ?string $format = 'csv'): void
    {
        self::log(
            'data.export',
            null,
            null,
            [
                'type' => $type,
                'record_count' => $recordCount,
                'format' => $format,
            ],
            "Exported {$recordCount} {$type} records to {$format}"
        );
    }

    /**
     * Sanitize data to remove sensitive information before logging.
     */
    protected static function sanitizeData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'current_password',
            'new_password',
            'secret',
            'token',
            'api_key',
            'stripe_secret',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
            'social_security',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[REDACTED]';
            }
        }

        return $data;
    }
}
