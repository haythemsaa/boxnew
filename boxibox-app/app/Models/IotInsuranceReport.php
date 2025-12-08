<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IotInsuranceReport extends Model
{
    use HasFactory;

    protected $table = 'iot_insurance_reports';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'period_start',
        'period_end',
        'temperature_summary',
        'humidity_summary',
        'incident_summary',
        'total_alerts',
        'critical_alerts',
        'uptime_percentage',
        'file_path',
        'status',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'temperature_summary' => 'array',
        'humidity_summary' => 'array',
        'incident_summary' => 'array',
        'uptime_percentage' => 'float',
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
