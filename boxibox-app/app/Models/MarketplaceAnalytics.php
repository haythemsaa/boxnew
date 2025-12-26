<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAnalytics extends Model
{
    protected $table = 'marketplace_analytics';

    protected $fillable = [
        'tenant_id',
        'integration_id',
        'site_id',
        'date',
        'impressions',
        'views',
        'clicks',
        'inquiries',
        'leads',
        'conversions',
        'revenue',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'date' => 'date',
        'impressions' => 'integer',
        'views' => 'integer',
        'clicks' => 'integer',
        'inquiries' => 'integer',
        'leads' => 'integer',
        'conversions' => 'integer',
        'revenue' => 'decimal:2',
        'cost' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(MarketplaceIntegration::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function getRoiAttribute(): float
    {
        if ($this->cost <= 0) {
            return 0;
        }
        return (($this->revenue - $this->cost) / $this->cost) * 100;
    }
}
