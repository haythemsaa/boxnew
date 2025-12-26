<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CallRecord extends Model
{
    protected $fillable = [
        'uuid',
        'tenant_id',
        'tracking_number_id',
        'site_id',
        'customer_id',
        'call_sid',
        'from_number',
        'to_number',
        'direction',
        'started_at',
        'answered_at',
        'ended_at',
        'ring_duration_seconds',
        'talk_duration_seconds',
        'total_duration_seconds',
        'status',
        'was_recorded',
        'recording_url',
        'recording_path',
        'recording_duration_seconds',
        'was_transcribed',
        'transcription',
        'transcription_confidence',
        'sentiment',
        'keywords',
        'call_intent',
        'lead_score',
        'source',
        'medium',
        'campaign',
        'caller_city',
        'caller_region',
        'caller_country',
        'answered_by',
        'was_transferred',
        'transferred_to',
        'requires_callback',
        'callback_scheduled_at',
        'callback_completed',
        'notes',
        'converted',
        'converted_booking_id',
        'converted_contract_id',
        'converted_value',
        'raw_data',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'answered_at' => 'datetime',
        'ended_at' => 'datetime',
        'callback_scheduled_at' => 'datetime',
        'was_recorded' => 'boolean',
        'was_transcribed' => 'boolean',
        'was_transferred' => 'boolean',
        'requires_callback' => 'boolean',
        'callback_completed' => 'boolean',
        'converted' => 'boolean',
        'keywords' => 'array',
        'raw_data' => 'array',
        'transcription_confidence' => 'decimal:2',
        'lead_score' => 'decimal:2',
        'converted_value' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid();
            }
        });
    }

    // Relationships
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

    public function answeredByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    // Scopes
    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    public function scopeMissed($query)
    {
        return $query->whereIn('status', ['no_answer', 'busy', 'voicemail']);
    }

    public function scopeAnswered($query)
    {
        return $query->where('status', 'completed')->whereNotNull('answered_at');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('started_at', now()->toDateString());
    }

    public function scopeRequiresCallback($query)
    {
        return $query->where('requires_callback', true)->where('callback_completed', false);
    }

    // Helpers
    public function getFormattedDurationAttribute(): string
    {
        $seconds = $this->talk_duration_seconds ?? $this->total_duration_seconds ?? 0;
        $minutes = floor($seconds / 60);
        $secs = $seconds % 60;
        return sprintf('%d:%02d', $minutes, $secs);
    }

    public function wasAnswered(): bool
    {
        return $this->status === 'completed' && $this->answered_at !== null;
    }

    public function wasMissed(): bool
    {
        return in_array($this->status, ['no_answer', 'busy', 'voicemail']);
    }

    public function markConverted(int $contractId = null, float $value = null): void
    {
        $this->update([
            'converted' => true,
            'converted_contract_id' => $contractId,
            'converted_value' => $value,
        ]);
    }

    public function scheduleCallback(\DateTime $datetime): void
    {
        $this->update([
            'requires_callback' => true,
            'callback_scheduled_at' => $datetime,
        ]);
    }

    public function completeCallback(): void
    {
        $this->update(['callback_completed' => true]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'ringing' => 'En cours',
            'in_progress' => 'En communication',
            'completed' => 'Terminé',
            'busy' => 'Occupé',
            'no_answer' => 'Sans réponse',
            'failed' => 'Échec',
            'cancelled' => 'Annulé',
            'voicemail' => 'Messagerie',
            default => $this->status,
        };
    }
}
