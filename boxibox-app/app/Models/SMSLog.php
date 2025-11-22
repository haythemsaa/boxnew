<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SMSLog extends Model
{
    use HasFactory;

    protected $table = 'sms_logs';

    protected $fillable = [
        'tenant_id',
        'campaign_id',
        'customer_id',
        'to',
        'message',
        'status', // sent, failed, delivered, undelivered
        'provider', // twilio, vonage, aws-sns
        'provider_id', // SID from provider
        'type', // payment_reminder, promotion, welcome, etc.
        'cost',
        'error_message',
        'metadata',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cost' => 'decimal:4',
        'metadata' => 'array',
    ];

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the campaign
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(SMSCampaign::class, 'campaign_id');
    }

    /**
     * Get the customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Scope: Sent successfully
     */
    public function scopeSent($query)
    {
        return $query->whereIn('status', ['sent', 'delivered']);
    }

    /**
     * Scope: Failed
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'undelivered']);
    }

    /**
     * Scope: By type
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
