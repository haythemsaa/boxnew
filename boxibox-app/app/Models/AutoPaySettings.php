<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutoPaySettings extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'contract_id',
        'is_enabled',
        'max_amount',
        'last_charged_at',
        'next_charge_at',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'max_amount' => 'decimal:2',
        'last_charged_at' => 'datetime',
        'next_charge_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function isActive(): bool
    {
        return $this->is_enabled;
    }

    public function recordSuccess(): void
    {
        $this->last_charged_at = now();
        $this->save();
    }

    public function scopeActive($query)
    {
        return $query->where('is_enabled', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_charge_at', '<=', now());
    }
}
