<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class VideoCall extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'agent_id',
        'customer_id',
        'prospect_id',
        'room_id',
        'guest_name',
        'guest_email',
        'guest_phone',
        'status',
        'type',
        'scheduled_at',
        'started_at',
        'ended_at',
        'duration_seconds',
        'notes',
        'summary',
        'recording_urls',
        'metadata',
        'rating',
        'feedback',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'recording_urls' => 'array',
        'metadata' => 'array',
        'rating' => 'decimal:1',
    ];

    public const STATUSES = [
        'pending' => 'En attente',
        'waiting' => 'En salle d\'attente',
        'in_progress' => 'En cours',
        'completed' => 'Terminé',
        'cancelled' => 'Annulé',
        'missed' => 'Manqué',
    ];

    public const TYPES = [
        'tour' => 'Visite virtuelle',
        'consultation' => 'Consultation',
        'support' => 'Support client',
        'onboarding' => 'Onboarding',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($call) {
            if (empty($call->room_id)) {
                $call->room_id = Str::uuid()->toString();
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function prospect(): BelongsTo
    {
        return $this->belongsTo(Prospect::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(VideoCallMessage::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(VideoCallInvitation::class);
    }

    // Scopes
    public function scopeScheduled($query)
    {
        return $query->where('status', 'pending')
            ->whereNotNull('scheduled_at');
    }

    public function scopeWaiting($query)
    {
        return $query->where('status', 'waiting');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('scheduled_at', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'pending')
            ->where('scheduled_at', '>=', now())
            ->orderBy('scheduled_at');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getGuestDisplayNameAttribute(): string
    {
        if ($this->guest_name) return $this->guest_name;
        if ($this->customer) return $this->customer->full_name;
        if ($this->prospect) return $this->prospect->full_name;
        return 'Visiteur';
    }

    public function getDurationFormattedAttribute(): string
    {
        if (!$this->duration_seconds) return '-';

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        return sprintf('%d:%02d', $minutes, $seconds);
    }

    public function getJoinUrlAttribute(): string
    {
        return route('video.join', ['room' => $this->room_id]);
    }

    // Methods
    public function start(): bool
    {
        if (!in_array($this->status, ['pending', 'waiting'])) {
            return false;
        }

        return $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function end(): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $duration = $this->started_at
            ? now()->diffInSeconds($this->started_at)
            : 0;

        return $this->update([
            'status' => 'completed',
            'ended_at' => now(),
            'duration_seconds' => $duration,
        ]);
    }

    public function cancel(): bool
    {
        if (in_array($this->status, ['completed', 'cancelled'])) {
            return false;
        }

        return $this->update(['status' => 'cancelled']);
    }

    public function markAsMissed(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update(['status' => 'missed']);
    }

    public function joinWaitingRoom(): bool
    {
        if ($this->status !== 'pending') {
            return false;
        }

        return $this->update(['status' => 'waiting']);
    }

    public function createInvitation(?string $email = null, ?string $phone = null, int $expiresInHours = 24): VideoCallInvitation
    {
        return $this->invitations()->create([
            'token' => Str::random(32),
            'email' => $email,
            'phone' => $phone,
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }
}
