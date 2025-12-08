<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavedPaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'type',
        'provider',
        'last_four',
        'brand',
        'holder_name',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'provider_payment_method_id',
        'metadata',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function isExpired(): bool
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return false;
        }
        $expiry = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1)->endOfMonth();
        return $expiry->isPast();
    }

    public function isExpiringSoon(): bool
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return false;
        }
        $expiry = \Carbon\Carbon::createFromDate($this->expiry_year, $this->expiry_month, 1)->endOfMonth();
        return $expiry->isBetween(now(), now()->addMonths(2));
    }

    public function getDisplayNameAttribute(): string
    {
        $brand = ucfirst($this->brand ?? $this->type);
        return "{$brand} •••• {$this->last_four}";
    }

    public function getExpiryDisplayAttribute(): string
    {
        if (!$this->expiry_month || !$this->expiry_year) {
            return '';
        }
        return sprintf('%02d/%d', $this->expiry_month, $this->expiry_year % 100);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_verified', true)
            ->where(function ($q) {
                $q->whereNull('expiry_year')
                  ->orWhere('expiry_year', '>', now()->year)
                  ->orWhere(function ($q2) {
                      $q2->where('expiry_year', now()->year)
                         ->where('expiry_month', '>=', now()->month);
                  });
            });
    }
}
