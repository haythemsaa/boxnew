<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class EmailTracking extends Model
{
    protected $table = 'email_tracking';

    protected $fillable = [
        'tenant_id',
        'tracking_id',
        'recipient_email',
        'recipient_type',
        'recipient_id',
        'email_type',
        'subject',
        'status',
        'sent_at',
        'delivered_at',
        'opened_at',
        'open_count',
        'first_clicked_at',
        'click_count',
        'bounced_at',
        'bounce_type',
        'complained_at',
        'unsubscribed_at',
        'clicks',
        'metadata',
        'provider',
        'provider_message_id',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'opened_at' => 'datetime',
        'first_clicked_at' => 'datetime',
        'bounced_at' => 'datetime',
        'complained_at' => 'datetime',
        'unsubscribed_at' => 'datetime',
        'clicks' => 'array',
        'metadata' => 'array',
        'open_count' => 'integer',
        'click_count' => 'integer',
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

    public function links(): HasMany
    {
        return $this->hasMany(EmailLinkTracking::class);
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
    public function markAsOpened(): void
    {
        $this->increment('open_count');

        if (!$this->opened_at) {
            $this->update([
                'opened_at' => now(),
                'status' => 'opened',
            ]);
        }

        // Update lead/prospect metadata for AI scoring
        $this->updateRecipientEngagement('email_opened');
    }

    public function markAsClicked(string $url): void
    {
        $this->increment('click_count');

        $clicks = $this->clicks ?? [];
        $clicks[] = [
            'url' => $url,
            'clicked_at' => now()->toISOString(),
        ];

        $update = ['clicks' => $clicks];

        if (!$this->first_clicked_at) {
            $update['first_clicked_at'] = now();
            $update['status'] = 'clicked';
        }

        $this->update($update);

        // Update lead/prospect metadata for AI scoring
        $this->updateRecipientEngagement('email_clicked');
    }

    public function markAsBounced(string $type = 'hard'): void
    {
        $this->update([
            'bounced_at' => now(),
            'bounce_type' => $type,
            'status' => 'bounced',
        ]);

        // Update lead metadata
        $this->updateRecipientEngagement('email_bounced');
    }

    public function markAsComplained(): void
    {
        $this->update([
            'complained_at' => now(),
            'status' => 'complained',
        ]);
    }

    public function markAsUnsubscribed(): void
    {
        $this->update([
            'unsubscribed_at' => now(),
        ]);

        $this->updateRecipientEngagement('email_unsubscribed');
    }

    /**
     * Update recipient's engagement metadata for AI scoring
     */
    protected function updateRecipientEngagement(string $event): void
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

        switch ($event) {
            case 'email_opened':
                $metadata['emails_opened'] = ($metadata['emails_opened'] ?? 0) + 1;
                $metadata['last_email_opened_at'] = now()->toISOString();
                break;
            case 'email_clicked':
                $metadata['emails_clicked'] = ($metadata['emails_clicked'] ?? 0) + 1;
                $metadata['last_email_clicked_at'] = now()->toISOString();
                break;
            case 'email_bounced':
                $metadata['email_bounced'] = true;
                $metadata['bounced_at'] = now()->toISOString();
                break;
            case 'email_unsubscribed':
                $metadata['unsubscribed'] = true;
                $metadata['unsubscribed_at'] = now()->toISOString();
                break;
        }

        $model->update([
            'metadata' => $metadata,
            'last_activity_at' => now(),
        ]);
    }

    // Scopes
    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeClicked($query)
    {
        return $query->whereNotNull('first_clicked_at');
    }

    public function scopeBounced($query)
    {
        return $query->whereNotNull('bounced_at');
    }

    public function scopeForRecipient($query, string $type, int $id)
    {
        return $query->where('recipient_type', $type)->where('recipient_id', $id);
    }
}
