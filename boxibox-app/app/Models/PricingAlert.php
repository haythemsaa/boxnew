<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PricingAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'alert_type',
        'severity',
        'title',
        'message',
        'data',
        'potential_revenue_impact',
        'recommended_action',
        'is_read',
        'is_actioned',
        'actioned_by',
        'actioned_at',
    ];

    protected $casts = [
        'data' => 'array',
        'potential_revenue_impact' => 'decimal:2',
        'is_read' => 'boolean',
        'is_actioned' => 'boolean',
        'actioned_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function actionedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actioned_by');
    }
}
