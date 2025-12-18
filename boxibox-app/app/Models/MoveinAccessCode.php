<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MoveinAccessCode extends Model
{
    protected $fillable = [
        'tenant_id',
        'movein_session_id',
        'site_id',
        'customer_id',
        'code',
        'qr_code_path',
        'code_type',
        'valid_from',
        'valid_until',
        'max_uses',
        'use_count',
        'allowed_areas',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'last_used_at' => 'datetime',
        'allowed_areas' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(MoveinSession::class, 'movein_session_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function accessLogs(): HasMany
    {
        return $this->hasMany(MoveinAccessLog::class, 'access_code_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('valid_until', '>', now())
            ->where('valid_from', '<=', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now());
    }

    public function isValid(): bool
    {
        return $this->is_active
            && $this->valid_from <= now()
            && $this->valid_until > now()
            && $this->use_count < $this->max_uses;
    }

    public function canUse(): array
    {
        if (!$this->is_active) {
            return ['allowed' => false, 'reason' => 'Code désactivé'];
        }

        if ($this->valid_from > now()) {
            return ['allowed' => false, 'reason' => 'Code pas encore valide'];
        }

        if ($this->valid_until < now()) {
            return ['allowed' => false, 'reason' => 'Code expiré'];
        }

        if ($this->use_count >= $this->max_uses) {
            return ['allowed' => false, 'reason' => 'Nombre maximum d\'utilisations atteint'];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function recordUse(string $status, ?string $doorId = null, ?string $accessPoint = null, ?string $method = null): MoveinAccessLog
    {
        $log = $this->accessLogs()->create([
            'site_id' => $this->site_id,
            'door_id' => $doorId,
            'access_point' => $accessPoint,
            'status' => $status,
            'method' => $method,
        ]);

        if ($status === 'granted') {
            $this->increment('use_count');
            $this->update(['last_used_at' => now()]);

            // Update session first access if applicable
            if ($this->session && !$this->session->first_access_at) {
                $this->session->update(['first_access_at' => now()]);
            }
        }

        return $log;
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    public function extend(int $hours): void
    {
        $this->update([
            'valid_until' => $this->valid_until->addHours($hours),
        ]);
    }

    public function getRemainingUsesAttribute(): int
    {
        return max(0, $this->max_uses - $this->use_count);
    }

    public function getFormattedCodeAttribute(): string
    {
        return chunk_split($this->code, 4, '-');
    }
}
