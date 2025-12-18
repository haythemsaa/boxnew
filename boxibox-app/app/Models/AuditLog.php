<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_name',
        'action',
        'model_type',
        'model_id',
        'auditable_type',
        'auditable_id',
        'event',
        'old_values',
        'new_values',
        'description',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    /**
     * Get the tenant that owns the audit log.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user that performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the auditable model.
     */
    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by action type.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope: Filter by model type.
     */
    public function scopeForModel($query, string $modelType)
    {
        return $query->where('model_type', $modelType);
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by tenant.
     */
    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    /**
     * Scope: Filter by date range.
     */
    public function scopeBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Security events only.
     */
    public function scopeSecurity($query)
    {
        return $query->where('action', 'like', 'security.%');
    }

    /**
     * Scope: Authentication events only.
     */
    public function scopeAuth($query)
    {
        return $query->where('action', 'like', 'auth.%');
    }

    /**
     * Get a human-readable description of the changes.
     */
    public function getChangesDescriptionAttribute(): string
    {
        if (!$this->old_values || !$this->new_values) {
            return 'No changes recorded';
        }

        $changes = [];
        foreach ($this->new_values as $key => $newValue) {
            $oldValue = $this->old_values[$key] ?? null;
            if ($oldValue != $newValue) {
                $changes[] = "{$key}: {$oldValue} → {$newValue}";
            }
        }

        return implode(', ', $changes);
    }

    /**
     * Get a summary of changes.
     */
    public function getChangesSummaryAttribute(): string
    {
        if (empty($this->old_values) && empty($this->new_values)) {
            return 'No changes recorded';
        }

        $changes = [];
        $newValues = $this->new_values ?? [];
        $oldValues = $this->old_values ?? [];

        foreach ($newValues as $key => $value) {
            if (!isset($oldValues[$key])) {
                $changes[] = "{$key}: added";
            } elseif ($oldValues[$key] !== $value) {
                $changes[] = "{$key}: changed";
            }
        }

        foreach ($oldValues as $key => $value) {
            if (!isset($newValues[$key])) {
                $changes[] = "{$key}: removed";
            }
        }

        return implode(', ', $changes) ?: 'No changes';
    }
}
