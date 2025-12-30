<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BoxAccessShare extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'box_id',
        'contract_id',
        'customer_id',
        'share_code',
        'qr_code_path',
        'guest_name',
        'guest_phone',
        'guest_email',
        'guest_note',
        'valid_from',
        'valid_until',
        'max_uses',
        'used_count',
        'allowed_hours',
        'allowed_days',
        'status',
        'revoked_at',
        'revoke_reason',
        'notify_on_use',
        'sms_sent',
        'email_sent',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'revoked_at' => 'datetime',
        'allowed_hours' => 'array',
        'allowed_days' => 'array',
        'notify_on_use' => 'boolean',
        'sms_sent' => 'boolean',
        'email_sent' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($share) {
            $share->uuid = $share->uuid ?? Str::uuid();
            $share->share_code = $share->share_code ?? self::generateShareCode();
        });
    }

    public static function generateShareCode(): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (self::where('share_code', $code)->exists());

        return $code;
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(BoxAccessLog::class, 'box_access_share_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', 'expired')
                ->orWhere('valid_until', '<', now());
        });
    }

    public function scopeValid($query)
    {
        return $query->where('status', 'active')
            ->where('valid_from', '<=', now())
            ->where('valid_until', '>=', now())
            ->where(function ($q) {
                $q->whereNull('max_uses')
                    ->orWhereColumn('used_count', '<', 'max_uses');
            });
    }

    // Helpers
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isExpired(): bool
    {
        return $this->status === 'expired' || $this->valid_until < now();
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isUsedUp(): bool
    {
        return $this->max_uses !== null && $this->used_count >= $this->max_uses;
    }

    public function isValid(): bool
    {
        return $this->isActive()
            && !$this->isExpired()
            && !$this->isUsedUp()
            && $this->valid_from <= now()
            && $this->valid_until >= now();
    }

    public function canBeUsedNow(): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Check time restrictions
        if ($this->allowed_hours) {
            $now = now();
            $start = $now->copy()->setTimeFromTimeString($this->allowed_hours['start'] ?? '00:00');
            $end = $now->copy()->setTimeFromTimeString($this->allowed_hours['end'] ?? '23:59');

            if ($now < $start || $now > $end) {
                return false;
            }
        }

        // Check day restrictions
        if ($this->allowed_days && !empty($this->allowed_days)) {
            $today = strtolower(now()->format('l'));
            if (!in_array($today, array_map('strtolower', $this->allowed_days))) {
                return false;
            }
        }

        return true;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');

        if ($this->isUsedUp()) {
            $this->update(['status' => 'used_up']);
        }
    }

    public function revoke(string $reason = null): void
    {
        $this->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revoke_reason' => $reason,
        ]);
    }

    public function getRemainingUsesAttribute(): ?int
    {
        if ($this->max_uses === null) {
            return null;
        }
        return max(0, $this->max_uses - $this->used_count);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'active' => 'Actif',
            'expired' => 'Expiré',
            'revoked' => 'Révoqué',
            'used_up' => 'Utilisations épuisées',
            default => 'Inconnu',
        };
    }

    public function getQrCodeUrl(): string
    {
        return route('mobile.access.share.qr', $this->uuid);
    }

    public function getShareUrl(): string
    {
        return route('access.share', $this->share_code);
    }
}
