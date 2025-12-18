<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SmsTracking extends Model
{
    protected $table = 'sms_tracking';

    protected $fillable = [
        'tenant_id',
        'tracking_id',
        'recipient_phone',
        'recipient_type',
        'recipient_id',
        'sms_type',
        'message',
        'status',
        'sent_at',
        'delivered_at',
        'failed_at',
        'failure_reason',
        'replied_at',
        'reply_message',
        'provider',
        'provider_message_id',
        'cost',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'failed_at' => 'datetime',
        'replied_at' => 'datetime',
        'metadata' => 'array',
        'cost' => 'decimal:4',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->tracking_id)) {
                $model->tracking_id = Str::uuid()->toString();
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the recipient model (Lead, Prospect, or Customer)
     */
    public function recipient()
    {
        return match ($this->recipient_type) {
            'lead' => $this->belongsTo(Lead::class, 'recipient_id'),
            'prospect' => $this->belongsTo(Prospect::class, 'recipient_id'),
            'customer' => $this->belongsTo(Customer::class, 'recipient_id'),
            default => null,
        };
    }

    // Methods
    public function markAsDelivered(): void
    {
        $this->update([
            'delivered_at' => now(),
            'status' => 'delivered',
        ]);
    }

    public function markAsFailed(string $reason): void
    {
        $this->update([
            'failed_at' => now(),
            'failure_reason' => $reason,
            'status' => 'failed',
        ]);
    }

    public function markAsReplied(string $replyMessage): void
    {
        $this->update([
            'replied_at' => now(),
            'reply_message' => $replyMessage,
            'status' => 'replied',
        ]);

        // Update lead/prospect metadata for AI scoring
        $this->updateRecipientEngagement();
    }

    /**
     * Update recipient's engagement metadata for AI scoring
     */
    protected function updateRecipientEngagement(): void
    {
        if (!$this->recipient_id || !$this->recipient_type) {
            return;
        }

        $model = match ($this->recipient_type) {
            'lead' => Lead::find($this->recipient_id),
            'prospect' => Prospect::find($this->recipient_id),
            default => null,
        };

        if (!$model) {
            return;
        }

        $metadata = $model->metadata ?? [];
        $metadata['sms_responded'] = true;
        $metadata['sms_responses'] = ($metadata['sms_responses'] ?? 0) + 1;
        $metadata['last_sms_response_at'] = now()->toISOString();

        $model->update([
            'metadata' => $metadata,
            'last_activity_at' => now(),
        ]);
    }

    // Scopes
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    public function scopeReplied($query)
    {
        return $query->where('status', 'replied');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeForRecipient($query, string $type, int $id)
    {
        return $query->where('recipient_type', $type)->where('recipient_id', $id);
    }
}
