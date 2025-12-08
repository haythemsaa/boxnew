<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'trigger',
        'is_active',
        'steps',
        'total_enrolled',
        'total_completed',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(EmailSequenceEnrollment::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForTrigger($query, string $trigger)
    {
        return $query->where('trigger', $trigger);
    }
}

class EmailSequenceEnrollment extends Model
{
    protected $fillable = [
        'email_sequence_id',
        'customer_id',
        'lead_id',
        'status',
        'current_step',
        'next_send_at',
        'enrolled_at',
        'completed_at',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_active' => 'boolean',
    ];

    public function emailSequence(): BelongsTo
    {
        return $this->belongsTo(EmailSequence::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeDueForSend($query)
    {
        return $query->where('status', 'active')
            ->where('next_send_at', '<=', now());
    }
}
