<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'description',
        'type',
        'value',
        'start_date',
        'end_date',
        'max_uses',
        'used_count',
        'min_rental_amount',
        'is_active',
        'applicable_to',
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'min_rental_amount' => 'decimal:2',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'applicable_to' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        if ($now->lt($this->start_date) || $now->gt($this->end_date)) {
            return false;
        }

        if ($this->max_uses && $this->used_count >= $this->max_uses) {
            return false;
        }

        return true;
    }

    public function calculateDiscount(float $amount): float
    {
        if (!$this->isValid()) {
            return 0;
        }

        if ($this->min_rental_amount && $amount < $this->min_rental_amount) {
            return 0;
        }

        return match ($this->type) {
            'percentage' => $amount * ($this->value / 100),
            'fixed_amount' => min($this->value, $amount),
            'free_month' => $amount,
            default => 0,
        };
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }
}
