<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SMSCampaign extends Model
{
    use HasFactory;

    protected $table = 'sms_campaigns';

    protected $fillable = [
        'tenant_id',
        'name',
        'message',
        'segment',
        'status',
        'scheduled_at',
        'sent_at',
        'sent_count',
        'failed_count',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get SMS logs
     */
    public function logs(): HasMany
    {
        return $this->hasMany(SMSLog::class, 'campaign_id');
    }

    /**
     * Scope: Active campaigns
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['scheduled', 'sending']);
    }

    /**
     * Scope: Sent campaigns
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }
}
