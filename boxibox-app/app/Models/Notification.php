<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Notification extends Model
{
    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    // Priority constants
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_CRITICAL = 'critical';

    // Type constants
    const TYPE_PAYMENT = 'payment';
    const TYPE_CONTRACT = 'contract';
    const TYPE_INVOICE = 'invoice';
    const TYPE_BOOKING = 'booking';
    const TYPE_SYSTEM = 'system';
    const TYPE_ALERT = 'alert';
    const TYPE_REMINDER = 'reminder';
    const TYPE_PROMOTION = 'promotion';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'title',
        'message',
        'channels',
        'data',
        'scheduled_for',
        'status',
        'sent_at',
        'is_read',
        'read_at',
        'priority',
        'related_type',
        'related_id',
        'alert_key',
    ];

    protected $casts = [
        'channels' => 'array',
        'data' => 'array',
        'is_read' => 'boolean',
        'scheduled_for' => 'datetime',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => 'pending',
        'priority' => 'medium',
        'is_read' => false,
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo('related');
    }

    // Scopes
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    public function scopeUnsent($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where(function($q) {
                $q->whereNull('scheduled_for')
                    ->orWhere('scheduled_for', '<=', now());
            });
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereNotNull('scheduled_for')
            ->where('scheduled_for', '>', now());
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeCritical($query)
    {
        return $query->where('priority', self::PRIORITY_CRITICAL);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Helper Methods
    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    public function markAsUnread(): void
    {
        $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => self::STATUS_SENT,
            'sent_at' => now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isRead(): bool
    {
        return $this->is_read;
    }

    public function isCritical(): bool
    {
        return $this->priority === self::PRIORITY_CRITICAL;
    }

    public function isHighPriority(): bool
    {
        return in_array($this->priority, [self::PRIORITY_HIGH, self::PRIORITY_CRITICAL]);
    }

    public function shouldSendViaEmail(): bool
    {
        return is_array($this->channels) && in_array('email', $this->channels);
    }

    public function shouldSendViaSms(): bool
    {
        return is_array($this->channels) && in_array('sms', $this->channels);
    }

    public function shouldSendViaPush(): bool
    {
        return is_array($this->channels) && in_array('push', $this->channels);
    }

    public function shouldSendInApp(): bool
    {
        return is_array($this->channels) && in_array('in_app', $this->channels);
    }

    public function getIconAttribute(): string
    {
        return match($this->type) {
            self::TYPE_PAYMENT => 'currency-euro',
            self::TYPE_INVOICE => 'document-text',
            self::TYPE_CONTRACT => 'document',
            self::TYPE_BOOKING => 'calendar',
            self::TYPE_ALERT => 'exclamation-triangle',
            self::TYPE_REMINDER => 'bell',
            self::TYPE_PROMOTION => 'gift',
            default => 'bell',
        };
    }

    public function getColorAttribute(): string
    {
        return match($this->priority) {
            self::PRIORITY_CRITICAL => 'red',
            self::PRIORITY_HIGH => 'orange',
            self::PRIORITY_MEDIUM => 'yellow',
            self::PRIORITY_LOW => 'green',
            default => 'blue',
        };
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_PAYMENT => 'Paiement',
            self::TYPE_CONTRACT => 'Contrat',
            self::TYPE_INVOICE => 'Facture',
            self::TYPE_BOOKING => 'Réservation',
            self::TYPE_SYSTEM => 'Système',
            self::TYPE_ALERT => 'Alerte',
            self::TYPE_REMINDER => 'Rappel',
            self::TYPE_PROMOTION => 'Promotion',
        ];
    }

    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Basse',
            self::PRIORITY_MEDIUM => 'Moyenne',
            self::PRIORITY_HIGH => 'Haute',
            self::PRIORITY_CRITICAL => 'Critique',
        ];
    }
}
