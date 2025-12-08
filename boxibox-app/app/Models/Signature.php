<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Signature extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'customer_id',
        'type',
        'status',
        'email_sent_to',
        'sent_at',
        'viewed_at',
        'signed_at',
        'expires_at',
        'document_path',
        'signed_document_path',
        'signature_token',
        'ip_address',
        'user_agent',
        'reminder_count',
        'last_reminder_at',
        'notes',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'viewed_at' => 'datetime',
        'signed_at' => 'datetime',
        'expires_at' => 'datetime',
        'last_reminder_at' => 'datetime',
        'reminder_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($signature) {
            if (empty($signature->signature_token)) {
                $signature->signature_token = Str::uuid()->toString();
            }
            if (empty($signature->expires_at)) {
                $signature->expires_at = now()->addDays(30);
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'sent', 'viewed']);
    }

    public function scopeSigned($query)
    {
        return $query->where('status', 'signed');
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired')
            ->orWhere(function ($q) {
                $q->whereIn('status', ['pending', 'sent', 'viewed'])
                  ->where('expires_at', '<', now());
            });
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // Helper Methods
    public function markAsSent(string $email): void
    {
        $this->update([
            'status' => 'sent',
            'email_sent_to' => $email,
            'sent_at' => now(),
        ]);
    }

    public function markAsViewed(): void
    {
        if ($this->status === 'sent') {
            $this->update([
                'status' => 'viewed',
                'viewed_at' => now(),
            ]);
        }
    }

    public function markAsSigned(string $ipAddress = null, string $userAgent = null): void
    {
        $this->update([
            'status' => 'signed',
            'signed_at' => now(),
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);

        // Update contract status
        if ($this->contract) {
            $this->contract->update(['status' => 'active']);
        }
    }

    public function markAsRefused(): void
    {
        $this->update(['status' => 'refused']);
    }

    public function sendReminder(): void
    {
        $this->increment('reminder_count');
        $this->update(['last_reminder_at' => now()]);

        // TODO: Send email reminder
    }

    public function checkExpiration(): bool
    {
        if ($this->expires_at && $this->expires_at->isPast() && !in_array($this->status, ['signed', 'refused', 'expired'])) {
            $this->update(['status' => 'expired']);
            return true;
        }
        return false;
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->status === 'expired' || ($this->expires_at && $this->expires_at->isPast());
    }

    public function getSigningUrlAttribute(): string
    {
        return url("/signature/{$this->signature_token}");
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expires_at) {
            return null;
        }
        return max(0, now()->diffInDays($this->expires_at, false));
    }
}
