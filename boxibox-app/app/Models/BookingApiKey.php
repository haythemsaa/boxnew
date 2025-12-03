<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookingApiKey extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'api_key',
        'api_secret',
        'permissions',
        'ip_whitelist',
        'is_active',
        'last_used_at',
        'requests_count',
    ];

    protected $casts = [
        'permissions' => 'array',
        'ip_whitelist' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'api_secret',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apiKey) {
            if (empty($apiKey->api_key)) {
                $apiKey->api_key = 'bk_' . Str::random(32);
            }
            if (empty($apiKey->api_secret)) {
                $apiKey->api_secret = hash('sha256', Str::random(64));
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Helpers
    public function hasPermission(string $permission): bool
    {
        if (!$this->permissions) {
            return true; // If no permissions set, allow all
        }

        return in_array($permission, $this->permissions) || in_array('*', $this->permissions);
    }

    public function isIpAllowed(?string $ip): bool
    {
        if (!$this->ip_whitelist || empty($this->ip_whitelist)) {
            return true;
        }

        return in_array($ip, $this->ip_whitelist);
    }

    public function recordUsage(): void
    {
        $this->update([
            'last_used_at' => now(),
            'requests_count' => $this->requests_count + 1,
        ]);
    }

    public function regenerateSecret(): string
    {
        $newSecret = hash('sha256', Str::random(64));
        $this->update(['api_secret' => $newSecret]);
        return $newSecret;
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function findByApiKey(string $key): ?self
    {
        return static::where('api_key', $key)
            ->active()
            ->first();
    }

    public static function validateCredentials(string $apiKey, string $apiSecret): ?self
    {
        $key = static::findByApiKey($apiKey);

        if (!$key || $key->api_secret !== $apiSecret) {
            return null;
        }

        return $key;
    }
}
