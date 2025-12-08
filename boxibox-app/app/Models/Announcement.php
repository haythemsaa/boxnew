<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    protected $fillable = [
        'created_by',
        'title',
        'content',
        'type',
        'target',
        'target_tenant_ids',
        'is_active',
        'is_dismissible',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'target_tenant_ids' => 'array',
        'is_active' => 'boolean',
        'is_dismissible' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where(function ($q) use ($tenantId) {
            $q->where('target', 'all')
                ->orWhere('target', 'tenants')
                ->orWhere(function ($q2) use ($tenantId) {
                    $q2->where('target', 'specific')
                        ->whereJsonContains('target_tenant_ids', $tenantId);
                });
        });
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function isDismissedBy(User $user): bool
    {
        return $this->reads()
            ->where('user_id', $user->id)
            ->where('is_dismissed', true)
            ->exists();
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->type) {
            'info' => 'blue',
            'warning' => 'yellow',
            'maintenance' => 'orange',
            'feature' => 'green',
            'promotion' => 'purple',
            default => 'gray',
        };
    }
}
