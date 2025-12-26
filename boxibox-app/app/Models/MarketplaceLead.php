<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class MarketplaceLead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'integration_id',
        'listing_id',
        'site_id',
        'customer_id',
        'external_lead_id',
        'platform',
        'customer_name',
        'customer_email',
        'customer_phone',
        'unit_size_requested',
        'move_in_date',
        'message',
        'source_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'status',
        'first_contacted_at',
        'converted_at',
        'lost_reason',
        'converted_contract_id',
        'converted_value',
        'lead_cost',
        'cost_charged',
        'response_time_minutes',
        'raw_data',
    ];

    protected $casts = [
        'move_in_date' => 'date',
        'first_contacted_at' => 'datetime',
        'converted_at' => 'datetime',
        'converted_value' => 'decimal:2',
        'lead_cost' => 'decimal:2',
        'cost_charged' => 'boolean',
        'raw_data' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(MarketplaceIntegration::class, 'integration_id');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(MarketplaceListing::class, 'listing_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function convertedContract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'converted_contract_id');
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeByPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }

    // Status transitions
    public function markContacted(): void
    {
        if ($this->status === 'new') {
            $responseTime = $this->created_at->diffInMinutes(now());
            $this->update([
                'status' => 'contacted',
                'first_contacted_at' => now(),
                'response_time_minutes' => $responseTime,
            ]);
        }
    }

    public function markQualified(): void
    {
        $this->update(['status' => 'qualified']);
    }

    public function scheduleTour(): void
    {
        $this->update(['status' => 'tour_scheduled']);
    }

    public function markConverted(int $contractId, float $value): void
    {
        $this->update([
            'status' => 'converted',
            'converted_at' => now(),
            'converted_contract_id' => $contractId,
            'converted_value' => $value,
        ]);
    }

    public function markLost(string $reason = null): void
    {
        $this->update([
            'status' => 'lost',
            'lost_reason' => $reason,
        ]);
    }
}
