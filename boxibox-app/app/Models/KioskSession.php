<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KioskSession extends Model
{
    protected $table = 'kiosk_sessions';

    protected $fillable = [
        'tenant_id',
        'kiosk_id',
        'session_token',
        'customer_id',
        'purpose',
        'outcome',
        'started_at',
        'ended_at',
        'duration_seconds',
        'pages_viewed',
        'actions_taken',
        'contract_id',
        'payment_amount',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'duration_seconds' => 'integer',
        'pages_viewed' => 'array',
        'actions_taken' => 'array',
        'payment_amount' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function kiosk(): BelongsTo
    {
        return $this->belongsTo(KioskDevice::class, 'kiosk_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function end(string $outcome = 'abandoned'): void
    {
        $this->update([
            'ended_at' => now(),
            'outcome' => $outcome,
            'duration_seconds' => now()->diffInSeconds($this->started_at),
        ]);
    }
}
