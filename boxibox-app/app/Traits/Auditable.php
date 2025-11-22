<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

trait Auditable
{
    /**
     * Boot the auditable trait for a model.
     */
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            $model->auditEvent('created');
        });

        static::updated(function (Model $model) {
            $model->auditEvent('updated');
        });

        static::deleted(function (Model $model) {
            $model->auditEvent('deleted');
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model) {
                $model->auditEvent('restored');
            });
        }
    }

    /**
     * Create an audit log entry for an event.
     */
    protected function auditEvent(string $event): void
    {
        $user = Auth::user();
        $changes = $this->getAuditableChanges($event);

        // Skip if no significant changes
        if ($event === 'updated' && empty($changes['new_values'])) {
            return;
        }

        AuditLog::create([
            'tenant_id' => $this->tenant_id ?? ($user->tenant_id ?? null),
            'user_id' => $user?->id,
            'user_name' => $user ? $user->name : 'System',
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'event' => $event,
            'old_values' => $changes['old_values'] ?? null,
            'new_values' => $changes['new_values'] ?? null,
            'description' => $this->getAuditDescription($event),
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Get the changes that should be audited.
     */
    protected function getAuditableChanges(string $event): array
    {
        $changes = [
            'old_values' => [],
            'new_values' => [],
        ];

        if ($event === 'created') {
            $changes['new_values'] = $this->getAuditableAttributes();
        } elseif ($event === 'updated') {
            $dirty = $this->getDirty();
            $auditable = $this->getAuditableAttributes();

            // Only include attributes that are in the auditable list
            foreach ($dirty as $key => $value) {
                if (isset($auditable[$key])) {
                    $changes['old_values'][$key] = $this->getOriginal($key);
                    $changes['new_values'][$key] = $value;
                }
            }
        } elseif ($event === 'deleted') {
            $changes['old_values'] = $this->getAuditableAttributes();
        }

        return $changes;
    }

    /**
     * Get the attributes that should be audited.
     */
    protected function getAuditableAttributes(): array
    {
        // Get all attributes except excluded ones
        $excluded = $this->auditExclude ?? ['created_at', 'updated_at', 'deleted_at', 'password', 'remember_token'];

        // If specific attributes are defined, only use those
        if (isset($this->auditInclude) && is_array($this->auditInclude)) {
            return array_intersect_key($this->attributes, array_flip($this->auditInclude));
        }

        // Otherwise, return all except excluded
        return array_diff_key($this->attributes, array_flip($excluded));
    }

    /**
     * Get a human-readable description of the audit event.
     */
    protected function getAuditDescription(string $event): string
    {
        $modelName = class_basename($this);
        $identifier = $this->getAuditIdentifier();

        return match ($event) {
            'created' => "{$modelName} {$identifier} was created",
            'updated' => "{$modelName} {$identifier} was updated",
            'deleted' => "{$modelName} {$identifier} was deleted",
            'restored' => "{$modelName} {$identifier} was restored",
            default => "{$modelName} {$identifier}: {$event}",
        };
    }

    /**
     * Get a human-readable identifier for the model.
     */
    protected function getAuditIdentifier(): string
    {
        // Try common identifier attributes
        foreach (['name', 'title', 'code', 'number', 'email', 'id'] as $attribute) {
            if (isset($this->attributes[$attribute])) {
                return "'{$this->attributes[$attribute]}'";
            }
        }

        return "#{$this->id}";
    }

    /**
     * Get all audit logs for this model.
     */
    public function auditLogs()
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest();
    }
}
