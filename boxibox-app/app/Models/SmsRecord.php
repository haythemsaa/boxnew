<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsRecord extends Model
{
    protected $table = 'sms_records';

    protected $fillable = [
        'tenant_id',
        'tracking_number_id',
        'site_id',
        'customer_id',
        'direction',
        'from_number',
        'to_number',
        'message',
        'status',
        'sent_at',
        'delivered_at',
        'error_message',
        'is_autoresponse',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'is_autoresponse' => 'boolean',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function trackingNumber(): BelongsTo
    {
        return $this->belongsTo(TrackingNumber::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
