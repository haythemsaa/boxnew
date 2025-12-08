<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SmartLock extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'box_id',
        'provider',
        'device_id',
        'device_name',
        'status',
        'battery_level',
        'last_seen_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_seen_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class);
    }

    /**
     * Get configuration through the box's site
     */
    public function configuration(): ?SmartLockConfiguration
    {
        if (!$this->box || !$this->box->site_id) {
            return null;
        }

        return SmartLockConfiguration::where('tenant_id', $this->tenant_id)
            ->where('site_id', $this->box->site_id)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Accessor for configuration attribute
     */
    public function getConfigurationAttribute(): ?SmartLockConfiguration
    {
        return $this->configuration();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeLowBattery($query)
    {
        return $query->where('battery_level', '<=', 20);
    }

    public function scopeOffline($query)
    {
        return $query->where('status', 'offline')
            ->orWhere('last_seen_at', '<', now()->subHours(24));
    }

    public function isOnline(): bool
    {
        return $this->status === 'active'
            && $this->last_seen_at
            && $this->last_seen_at->greaterThan(now()->subHours(1));
    }

    public function needsBatteryReplacement(): bool
    {
        return $this->battery_level !== null && $this->battery_level <= 20;
    }
}
