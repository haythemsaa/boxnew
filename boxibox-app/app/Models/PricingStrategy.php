<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingStrategy extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'name',
        'description',
        'strategy_type',
        'rules',
        'is_active',
        'starts_at',
        'ends_at',
        'min_discount_percentage',
        'max_discount_percentage',
        'priority',
    ];

    protected $casts = [
        'rules' => 'array',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'min_discount_percentage' => 'decimal:2',
        'max_discount_percentage' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeForSite($query, $siteId)
    {
        return $query->where(function ($q) use ($siteId) {
            $q->where('site_id', $siteId)
                ->orWhereNull('site_id');
        });
    }
}
