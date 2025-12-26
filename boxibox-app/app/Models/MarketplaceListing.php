<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceListing extends Model
{
    protected $table = 'marketplace_listings';

    protected $fillable = [
        'tenant_id',
        'integration_id',
        'box_id',
        'site_id',
        'external_id',
        'title',
        'description',
        'price',
        'size_sqm',
        'features',
        'images',
        'is_active',
        'is_synced',
        'last_synced_at',
        'views_count',
        'inquiries_count',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'size_sqm' => 'decimal:2',
        'features' => 'array',
        'images' => 'array',
        'is_active' => 'boolean',
        'is_synced' => 'boolean',
        'last_synced_at' => 'datetime',
        'views_count' => 'integer',
        'inquiries_count' => 'integer',
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

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
