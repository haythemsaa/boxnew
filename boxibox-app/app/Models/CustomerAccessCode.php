<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class CustomerAccessCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'site_id',
        'access_code',
        'qr_code',
        'rfid_tag',
        'status',
        'valid_from',
        'valid_until',
        'is_permanent',
        'is_master',
        'max_uses',
        'use_count',
        'last_used_at',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'last_used_at' => 'datetime',
        'is_permanent' => 'boolean',
        'is_master' => 'boolean',
    ];

    // Relations
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(AccessLog::class, 'access_code_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeValid($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('valid_until')
              ->orWhere('valid_until', '>', now());
        })->where(function ($q) {
            $q->whereNull('valid_from')
              ->orWhere('valid_from', '<=', now());
        });
    }

    public function scopeForSite($query, $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    // Methods
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->valid_from && $this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->max_uses && $this->use_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function recordUsage(): void
    {
        $this->increment('use_count');
        $this->update(['last_used_at' => now()]);
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function revoke(): void
    {
        $this->update(['status' => 'revoked']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    // Generate unique codes
    public static function generateAccessCode(int $length = 6): string
    {
        do {
            $code = str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
        } while (self::where('access_code', $code)->exists());

        return $code;
    }

    public static function generateQrCode(): string
    {
        do {
            $code = 'QR-' . Str::upper(Str::random(12));
        } while (self::where('qr_code', $code)->exists());

        return $code;
    }

    // Create access code for customer
    public static function createForCustomer(
        Customer $customer,
        Site $site,
        ?Contract $contract = null,
        array $options = []
    ): self {
        return self::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'contract_id' => $contract?->id,
            'site_id' => $site->id,
            'access_code' => self::generateAccessCode(),
            'qr_code' => self::generateQrCode(),
            'status' => 'active',
            'valid_from' => $options['valid_from'] ?? now(),
            'valid_until' => $options['valid_until'] ?? null,
            'is_permanent' => $options['is_permanent'] ?? false,
            'is_master' => $options['is_master'] ?? false,
            'max_uses' => $options['max_uses'] ?? null,
        ]);
    }
}
