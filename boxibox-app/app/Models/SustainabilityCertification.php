<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SustainabilityCertification extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'type',
        'issuing_body',
        'certification_number',
        'issue_date',
        'expiry_date',
        'level',
        'score',
        'document_path',
        'is_active',
        'renewal_reminder_sent',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'score' => 'integer',
        'is_active' => 'boolean',
        'renewal_reminder_sent' => 'boolean',
    ];

    public const TYPES = [
        'iso_14001' => 'ISO 14001',
        'iso_50001' => 'ISO 50001',
        'breeam' => 'BREEAM',
        'leed' => 'LEED',
        'hqe' => 'HQE',
        'energie_plus' => 'Énergie Plus',
        'carbon_neutral' => 'Carbon Neutral',
        'b_corp' => 'B Corp',
        'other' => 'Autre',
    ];

    public const LEVELS = [
        'bronze' => 'Bronze',
        'silver' => 'Argent',
        'gold' => 'Or',
        'platinum' => 'Platine',
        'certified' => 'Certifié',
        'outstanding' => 'Exceptionnel',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            });
    }

    public function scopeExpiringSoon($query, $days = 90)
    {
        return $query->where('is_active', true)
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }

    public function scopeExpired($query)
    {
        return $query->whereNotNull('expiry_date')
            ->where('expiry_date', '<', now());
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getLevelLabelAttribute(): string
    {
        return self::LEVELS[$this->level] ?? $this->level;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsExpiringSoonAttribute(): bool
    {
        if (!$this->expiry_date) return false;
        return $this->expiry_date->isBetween(now(), now()->addDays(90));
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        if (!$this->expiry_date) return null;
        return max(0, now()->diffInDays($this->expiry_date, false));
    }

    public function getStatusAttribute(): string
    {
        if (!$this->is_active) return 'inactive';
        if ($this->is_expired) return 'expired';
        if ($this->is_expiring_soon) return 'expiring';
        return 'valid';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'valid' => 'green',
            'expiring' => 'yellow',
            'expired' => 'red',
            'inactive' => 'gray',
            default => 'gray',
        };
    }
}
