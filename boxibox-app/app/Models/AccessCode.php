<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccessCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'smart_lock_id',
        'customer_id',
        'contract_id',
        'code',
        'code_type',
        'name',
        'valid_from',
        'valid_until',
        'recurring_schedule',
        'max_uses',
        'use_count',
        'is_active',
        'is_revoked',
        'revoke_reason',
        'revoked_by',
        'revoked_at',
        'created_by',
    ];

    protected $casts = [
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'recurring_schedule' => 'array',
        'is_active' => 'boolean',
        'is_revoked' => 'boolean',
        'revoked_at' => 'datetime',
    ];

    public function smartLock(): BelongsTo
    {
        return $this->belongsTo(SmartLock::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active || $this->is_revoked) {
            return false;
        }

        $now = now();
        if ($this->valid_from && $now->lt($this->valid_from)) {
            return false;
        }

        if ($this->valid_until && $now->gt($this->valid_until)) {
            return false;
        }

        if ($this->max_uses && $this->use_count >= $this->max_uses) {
            return false;
        }

        return true;
    }
}
