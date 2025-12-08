<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'consent_type',
        'version',
        'is_granted',
        'granted_at',
        'withdrawn_at',
        'ip_address',
        'user_agent',
        'consent_text',
    ];

    protected $casts = [
        'is_granted' => 'boolean',
        'granted_at' => 'datetime',
        'withdrawn_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isActive(): bool
    {
        return $this->is_granted && !$this->withdrawn_at;
    }

    public function isWithdrawn(): bool
    {
        return $this->withdrawn_at !== null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_granted', true)->whereNull('withdrawn_at');
    }

    public function scopeWithdrawn($query)
    {
        return $query->whereNotNull('withdrawn_at');
    }

    public function getConsentTypeLabelAttribute(): string
    {
        return match ($this->consent_type) {
            'marketing_email' => 'Emails marketing',
            'marketing_sms' => 'SMS marketing',
            'marketing_phone' => 'Appels marketing',
            'data_processing' => 'Traitement des données',
            'data_sharing' => 'Partage des données',
            'terms_conditions' => 'CGU/CGV',
            'privacy_policy' => 'Politique de confidentialité',
            default => $this->consent_type,
        };
    }
}
