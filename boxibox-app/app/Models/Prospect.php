<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prospect extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'first_name',
        'last_name',
        'company_name',
        'siret',
        'email',
        'phone',
        'address',
        'postal_code',
        'city',
        'country',
        'status',
        'source',
        'box_size_interested',
        'move_in_date',
        'budget',
        'notes',
        'follow_up_count',
        'last_contact_at',
        'converted_at',
        'customer_id',
    ];

    protected $casts = [
        'move_in_date' => 'date',
        'budget' => 'decimal:2',
        'last_contact_at' => 'datetime',
        'converted_at' => 'datetime',
        'follow_up_count' => 'integer',
        'deleted_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeQualified($query)
    {
        return $query->where('status', 'qualified');
    }

    public function scopeConverted($query)
    {
        return $query->where('status', 'converted');
    }

    public function scopeLost($query)
    {
        return $query->where('status', 'lost');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    // Helper Methods
    public function convertToCustomer(): ?Customer
    {
        if ($this->status === 'converted') {
            return $this->customer;
        }

        $customer = Customer::create([
            'tenant_id' => $this->tenant_id,
            'user_id' => $this->user_id,
            'type' => $this->type,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'company_name' => $this->company_name,
            'siret' => $this->siret,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'postal_code' => $this->postal_code,
            'city' => $this->city,
            'country' => $this->country,
            'status' => 'active',
            'outstanding_balance' => 0,
            'total_contracts' => 0,
            'total_revenue' => 0,
        ]);

        $this->update([
            'status' => 'converted',
            'converted_at' => now(),
            'customer_id' => $customer->id,
        ]);

        return $customer;
    }

    public function markAsLost(): void
    {
        $this->update(['status' => 'lost']);
    }

    public function recordContact(): void
    {
        $this->increment('follow_up_count');
        $this->update(['last_contact_at' => now()]);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        if ($this->type === 'company' && $this->company_name) {
            return $this->company_name;
        }
        return $this->first_name . ' ' . $this->last_name;
    }

    public function getIsHotAttribute(): bool
    {
        // Hot prospect if move_in_date is within next 30 days
        return $this->move_in_date && $this->move_in_date->isBefore(now()->addDays(30));
    }
}
