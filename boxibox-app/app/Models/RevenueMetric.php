<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'metric_date',
        'total_units',
        'occupied_units',
        'reserved_units',
        'physical_occupancy_rate',
        'economic_occupancy_rate',
        'gross_revenue',
        'net_revenue',
        'revpau',
        'revpou',
        'average_rent',
        'collection_rate',
        'delinquency_rate',
        'new_contracts',
        'terminated_contracts',
        'churn_rate',
        'revpau_change_vs_previous',
        'revpau_change_vs_year',
    ];

    protected $casts = [
        'metric_date' => 'date',
        'physical_occupancy_rate' => 'decimal:2',
        'economic_occupancy_rate' => 'decimal:2',
        'gross_revenue' => 'decimal:2',
        'net_revenue' => 'decimal:2',
        'revpau' => 'decimal:2',
        'revpou' => 'decimal:2',
        'average_rent' => 'decimal:2',
        'collection_rate' => 'decimal:2',
        'delinquency_rate' => 'decimal:2',
        'churn_rate' => 'decimal:2',
        'revpau_change_vs_previous' => 'decimal:2',
        'revpau_change_vs_year' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
