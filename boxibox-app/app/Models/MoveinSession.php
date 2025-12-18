<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class MoveinSession extends Model
{
    protected $fillable = [
        'tenant_id',
        'site_id',
        'box_id',
        'customer_id',
        'contract_id',
        'reservation_id',
        'session_token',
        'status',
        'email',
        'phone',
        'first_name',
        'last_name',
        'completed_steps',
        'current_step',
        'identity_method',
        'identity_data',
        'identity_verified_at',
        'contract_sent_at',
        'contract_signed_at',
        'signature_ip',
        'signature_user_agent',
        'payment_method',
        'payment_intent_id',
        'amount_paid',
        'payment_completed_at',
        'access_code',
        'access_qr_code',
        'access_granted_at',
        'first_access_at',
        'preferred_movein_date',
        'preferred_time_slot',
        'scheduled_at',
        'device_type',
        'browser',
        'ip_address',
        'language',
        'expires_at',
        'reminder_sent_at',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'identity_data' => 'array',
        'identity_verified_at' => 'datetime',
        'contract_sent_at' => 'datetime',
        'contract_signed_at' => 'datetime',
        'payment_completed_at' => 'datetime',
        'access_granted_at' => 'datetime',
        'first_access_at' => 'datetime',
        'preferred_movein_date' => 'date',
        'scheduled_at' => 'datetime',
        'expires_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'amount_paid' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->session_token)) {
                $session->session_token = Str::random(64);
            }
            if (empty($session->expires_at)) {
                $config = MoveinConfiguration::where('tenant_id', $session->tenant_id)->first();
                $hours = $config?->session_expiry_hours ?? 72;
                $session->expires_at = now()->addHours($hours);
            }
        });
    }

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_IDENTITY_VERIFIED = 'identity_verified';
    const STATUS_CONTRACT_SIGNED = 'contract_signed';
    const STATUS_PAYMENT_COMPLETED = 'payment_completed';
    const STATUS_ACCESS_GRANTED = 'access_granted';
    const STATUS_COMPLETED = 'completed';
    const STATUS_EXPIRED = 'expired';
    const STATUS_CANCELLED = 'cancelled';

    // Step constants
    const STEP_IDENTITY = 'identity';
    const STEP_BOX_SELECTION = 'box_selection';
    const STEP_CONTRACT = 'contract';
    const STEP_PAYMENT = 'payment';
    const STEP_ACCESS = 'access';
    const STEP_CONFIRMATION = 'confirmation';

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function stepLogs(): HasMany
    {
        return $this->hasMany(MoveinStepLog::class, 'movein_session_id');
    }

    public function identityDocument(): HasOne
    {
        return $this->hasOne(IdentityDocument::class, 'movein_session_id');
    }

    public function accessCodes(): HasMany
    {
        return $this->hasMany(MoveinAccessCode::class, 'movein_session_id');
    }

    public function activeAccessCode(): HasOne
    {
        return $this->hasOne(MoveinAccessCode::class, 'movein_session_id')
            ->where('is_active', true)
            ->where('valid_until', '>', now());
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', '!=', self::STATUS_EXPIRED)
            ->where('status', '!=', self::STATUS_CANCELLED)
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('status', self::STATUS_EXPIRED)
                ->orWhere('expires_at', '<', now());
        });
    }

    public function scopePending($query)
    {
        return $query->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_EXPIRED, self::STATUS_CANCELLED]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Helpers
    public function isExpired(): bool
    {
        return $this->status === self::STATUS_EXPIRED || $this->expires_at < now();
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function canProceed(): bool
    {
        return !$this->isExpired() && !$this->isCompleted() && $this->status !== self::STATUS_CANCELLED;
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getProgressPercentageAttribute(): int
    {
        $steps = [
            self::STEP_IDENTITY,
            self::STEP_BOX_SELECTION,
            self::STEP_CONTRACT,
            self::STEP_PAYMENT,
            self::STEP_ACCESS,
        ];

        $completedSteps = $this->completed_steps ?? [];
        $completed = count(array_intersect($completedSteps, $steps));

        return (int) round(($completed / count($steps)) * 100);
    }

    public function markStepCompleted(string $step, array $data = []): void
    {
        $completedSteps = $this->completed_steps ?? [];
        if (!in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
            $this->completed_steps = $completedSteps;
        }

        // Log the step
        $this->stepLogs()->create([
            'step' => $step,
            'action' => 'completed',
            'data' => $data,
            'ip_address' => request()->ip(),
        ]);

        $this->advanceToNextStep();
        $this->save();
    }

    public function advanceToNextStep(): void
    {
        $steps = [
            self::STEP_IDENTITY,
            self::STEP_BOX_SELECTION,
            self::STEP_CONTRACT,
            self::STEP_PAYMENT,
            self::STEP_ACCESS,
            self::STEP_CONFIRMATION,
        ];

        $currentIndex = array_search($this->current_step, $steps);
        if ($currentIndex !== false && $currentIndex < count($steps) - 1) {
            $this->current_step = $steps[$currentIndex + 1];
        }

        $this->updateStatusFromStep();
    }

    protected function updateStatusFromStep(): void
    {
        $completedSteps = $this->completed_steps ?? [];

        if (in_array(self::STEP_ACCESS, $completedSteps)) {
            $this->status = self::STATUS_COMPLETED;
        } elseif (in_array(self::STEP_PAYMENT, $completedSteps)) {
            $this->status = self::STATUS_ACCESS_GRANTED;
        } elseif (in_array(self::STEP_CONTRACT, $completedSteps)) {
            $this->status = self::STATUS_PAYMENT_COMPLETED;
        } elseif (in_array(self::STEP_BOX_SELECTION, $completedSteps)) {
            $this->status = self::STATUS_CONTRACT_SIGNED;
        } elseif (in_array(self::STEP_IDENTITY, $completedSteps)) {
            $this->status = self::STATUS_IDENTITY_VERIFIED;
        }
    }

    public function expire(): void
    {
        $this->status = self::STATUS_EXPIRED;
        $this->save();
    }

    public function cancel(string $reason = null): void
    {
        $this->status = self::STATUS_CANCELLED;
        $this->save();

        $this->stepLogs()->create([
            'step' => $this->current_step,
            'action' => 'cancelled',
            'data' => ['reason' => $reason],
            'ip_address' => request()->ip(),
        ]);
    }

    public function generateAccessCode(): MoveinAccessCode
    {
        $config = MoveinConfiguration::where('tenant_id', $this->tenant_id)->first();

        $code = strtoupper(Str::random(8));

        return $this->accessCodes()->create([
            'tenant_id' => $this->tenant_id,
            'site_id' => $this->site_id,
            'customer_id' => $this->customer_id,
            'code' => $code,
            'code_type' => 'temporary',
            'valid_from' => now(),
            'valid_until' => now()->addHours($config?->access_code_validity_hours ?? 48),
            'max_uses' => $config?->access_code_max_uses ?? 3,
        ]);
    }

    public function getPublicUrl(): string
    {
        return route('movein.public.start', ['token' => $this->session_token]);
    }
}
