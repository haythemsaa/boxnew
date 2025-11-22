<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'gateway',
        'gateway_method_id',
        'type',
        'brand',
        'last4',
        'exp_month',
        'exp_year',
        'is_default',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_default' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeCards($query)
    {
        return $query->where('type', 'card');
    }

    public function scopeSepa($query)
    {
        return $query->where('type', 'sepa_debit');
    }

    public function isExpired(): bool
    {
        if (!$this->exp_month || !$this->exp_year) {
            return false;
        }

        $expiryDate = now()->setYear($this->exp_year)->setMonth($this->exp_month)->endOfMonth();
        return $expiryDate->isPast();
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->type === 'card') {
            return ($this->brand ? ucfirst($this->brand) . ' ' : '') . '****' . $this->last4;
        }

        if ($this->type === 'sepa_debit') {
            return 'SEPA ****' . $this->last4;
        }

        return ucfirst($this->type);
    }
}
