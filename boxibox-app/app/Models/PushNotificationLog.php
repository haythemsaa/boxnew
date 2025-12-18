<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'body',
        'data',
        'status',
        'recipients_count',
        'delivered_count',
        'clicked_count',
        'sent_at',
    ];

    protected $casts = [
        'data' => 'array',
        'sent_at' => 'datetime',
    ];

    // Notification types
    const TYPE_ALERT = 'alert';
    const TYPE_REMINDER = 'reminder';
    const TYPE_PAYMENT = 'payment';
    const TYPE_CONTRACT = 'contract';
    const TYPE_SYSTEM = 'system';
    const TYPE_MARKETING = 'marketing';
    const TYPE_IOT = 'iot';

    // Statuses
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_CLICKED = 'clicked';

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope by status.
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Mark as sent.
     */
    public function markAsSent(int $deliveredCount = 0): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
            'delivered_count' => $deliveredCount,
        ]);
    }

    /**
     * Mark as failed.
     */
    public function markAsFailed(): void
    {
        $this->update(['status' => self::STATUS_FAILED]);
    }

    /**
     * Increment click count.
     */
    public function incrementClicks(): void
    {
        $this->increment('clicked_count');
        if ($this->status !== self::STATUS_CLICKED) {
            $this->update(['status' => self::STATUS_CLICKED]);
        }
    }
}
