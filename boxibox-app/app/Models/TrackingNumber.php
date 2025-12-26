<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingNumber extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'phone_number',
        'friendly_name',
        'forward_to',
        'is_active',
        'source',
        'medium',
        'campaign',
        'number_type',
        'sms_enabled',
        'mms_enabled',
        'provider_number_sid',
        'monthly_cost',
        'total_calls',
        'total_sms',
        'last_call_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sms_enabled' => 'boolean',
        'mms_enabled' => 'boolean',
        'monthly_cost' => 'decimal:2',
        'last_call_at' => 'datetime',
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

    public function calls(): HasMany
    {
        return $this->hasMany(CallRecord::class, 'tracking_number_id');
    }

    public function smsRecords(): HasMany
    {
        return $this->hasMany(SmsRecord::class, 'tracking_number_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    // Helpers
    public function getFormattedNumberAttribute(): string
    {
        $number = preg_replace('/[^0-9]/', '', $this->phone_number);
        if (strlen($number) === 10) {
            return sprintf('%s %s %s %s %s',
                substr($number, 0, 2),
                substr($number, 2, 2),
                substr($number, 4, 2),
                substr($number, 6, 2),
                substr($number, 8, 2)
            );
        }
        return $this->phone_number;
    }

    public function incrementCallCount(): void
    {
        $this->increment('total_calls');
        $this->update(['last_call_at' => now()]);
    }

    public function incrementSmsCount(): void
    {
        $this->increment('total_sms');
    }

    public static function getSourceOptions(): array
    {
        return [
            'website' => 'Site Web',
            'google_ads' => 'Google Ads',
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'google_business' => 'Google Business',
            'billboard' => 'Affichage',
            'flyer' => 'Flyer',
            'referral' => 'Parrainage',
            'marketplace' => 'Marketplace',
            'other' => 'Autre',
        ];
    }
}
