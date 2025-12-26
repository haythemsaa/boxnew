<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'key',
        'secret',
        'permissions',
        'ip_whitelist',
        'is_active',
        'last_used_at',
        'total_requests',
        'expires_at',
    ];

    protected $casts = [
        'permissions' => 'array',
        'ip_whitelist' => 'array',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'total_requests' => 'integer',
        'expires_at' => 'datetime',
    ];

    protected $hidden = ['secret'];

    // Available permissions
    public const PERMISSIONS = [
        // Read
        'read:customers' => 'Read Customers',
        'read:contracts' => 'Read Contracts',
        'read:invoices' => 'Read Invoices',
        'read:payments' => 'Read Payments',
        'read:boxes' => 'Read Boxes',
        'read:sites' => 'Read Sites',
        'read:bookings' => 'Read Bookings',
        'read:analytics' => 'Read Analytics',

        // Write
        'write:customers' => 'Create/Update Customers',
        'write:contracts' => 'Create/Update Contracts',
        'write:invoices' => 'Create/Update Invoices',
        'write:payments' => 'Record Payments',
        'write:boxes' => 'Update Boxes',
        'write:bookings' => 'Create/Update Bookings',

        // Delete
        'delete:customers' => 'Delete Customers',
        'delete:contracts' => 'Cancel Contracts',
        'delete:bookings' => 'Cancel Bookings',
    ];

    /**
     * Store the plain secret temporarily for returning to user on creation.
     * This is NOT persisted - only available immediately after creation.
     */
    protected ?string $plainSecret = null;

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($apiKey) {
            if (empty($apiKey->key)) {
                $apiKey->key = 'bxb_' . Str::random(32);
            }
            if (empty($apiKey->secret)) {
                // Generate and store plain secret for one-time display
                $plainSecret = Str::random(40);
                $apiKey->plainSecret = $plainSecret;
                // Hash before storing (SECURITY)
                $apiKey->secret = hash('sha256', $plainSecret);
            }
        });
    }

    /**
     * Get the plain secret (only available once, immediately after creation).
     */
    public function getPlainSecret(): ?string
    {
        return $this->plainSecret;
    }

    /**
     * Verify a provided secret against the stored hash.
     * SECURITY: Uses hash_equals to prevent timing attacks.
     */
    public function verifySecret(string $secret): bool
    {
        return hash_equals($this->secret, hash('sha256', $secret));
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    // Methods
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions ?? []);
    }

    public function hasAnyPermission(array $permissions): bool
    {
        return count(array_intersect($this->permissions ?? [], $permissions)) > 0;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isAllowedFrom(?string $ip): bool
    {
        if (empty($this->ip_whitelist)) {
            return true;
        }
        return in_array($ip, $this->ip_whitelist);
    }

    public function recordUsage(): void
    {
        $this->increment('total_requests');
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Regenerate the secret and return the new plain secret.
     * SECURITY: The secret is hashed before storing.
     */
    public function regenerateSecret(): string
    {
        $newSecret = Str::random(40);
        $this->update(['secret' => hash('sha256', $newSecret)]);
        return $newSecret; // Return plain secret for one-time display to user
    }

    public function getMaskedKeyAttribute(): string
    {
        return substr($this->key, 0, 12) . '...' . substr($this->key, -4);
    }
}
