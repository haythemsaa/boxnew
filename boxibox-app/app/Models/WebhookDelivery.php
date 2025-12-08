<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebhookDelivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'webhook_id',
        'event_type',
        'event_id',
        'payload',
        'attempt',
        'status',
        'response_code',
        'response_body',
        'error_message',
        'duration',
        'delivered_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'attempt' => 'integer',
        'response_code' => 'integer',
        'duration' => 'float',
        'delivered_at' => 'datetime',
    ];

    // Relationships
    public function webhook(): BelongsTo
    {
        return $this->belongsTo(Webhook::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuccess($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Methods
    public function markAsSuccess(int $responseCode, ?string $responseBody, float $duration): void
    {
        $this->update([
            'status' => 'success',
            'response_code' => $responseCode,
            'response_body' => substr($responseBody ?? '', 0, 5000),
            'duration' => $duration,
            'delivered_at' => now(),
        ]);
    }

    public function markAsFailed(string $error, ?int $responseCode = null, ?string $responseBody = null): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
            'response_code' => $responseCode,
            'response_body' => substr($responseBody ?? '', 0, 5000),
        ]);
    }

    public function isRetryable(): bool
    {
        return $this->status === 'failed'
            && $this->attempt < ($this->webhook->retry_count ?? 3);
    }
}
