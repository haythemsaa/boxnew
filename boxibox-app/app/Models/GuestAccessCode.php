<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class GuestAccessCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'site_id',
        'guest_name',
        'guest_phone',
        'guest_email',
        'access_code',
        'qr_code',
        'status',
        'valid_from',
        'valid_until',
        'max_uses',
        'use_count',
        'purpose',
        'last_used_at',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'last_used_at' => 'datetime',
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

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeValid($query)
    {
        return $query->where('valid_from', '<=', now())
                     ->where('valid_until', '>', now())
                     ->whereIn('status', ['pending', 'active']);
    }

    // Methods
    public function isValid(): bool
    {
        if (!in_array($this->status, ['pending', 'active'])) {
            return false;
        }

        if ($this->valid_from->isFuture()) {
            return false;
        }

        if ($this->valid_until->isPast()) {
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
        $this->update([
            'last_used_at' => now(),
            'status' => $this->use_count >= $this->max_uses ? 'used' : 'active',
        ]);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    // Generate unique codes
    public static function generateAccessCode(int $length = 6): string
    {
        do {
            $code = 'G' . str_pad(random_int(0, pow(10, $length - 1) - 1), $length - 1, '0', STR_PAD_LEFT);
        } while (self::where('access_code', $code)->exists());

        return $code;
    }

    public static function generateQrCode(): string
    {
        do {
            $code = 'GQR-' . Str::upper(Str::random(10));
        } while (self::where('qr_code', $code)->exists());

        return $code;
    }

    // Create guest access code
    public static function createForGuest(
        Customer $customer,
        Site $site,
        string $guestName,
        \DateTime $validFrom,
        \DateTime $validUntil,
        array $options = []
    ): self {
        return self::create([
            'tenant_id' => $customer->tenant_id,
            'customer_id' => $customer->id,
            'site_id' => $site->id,
            'guest_name' => $guestName,
            'guest_phone' => $options['guest_phone'] ?? null,
            'guest_email' => $options['guest_email'] ?? null,
            'access_code' => self::generateAccessCode(),
            'qr_code' => self::generateQrCode(),
            'status' => 'pending',
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'max_uses' => $options['max_uses'] ?? 1,
            'purpose' => $options['purpose'] ?? null,
        ]);
    }
}
