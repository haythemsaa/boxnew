<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConditionReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'box_id',
        'customer_id',
        'conducted_by',
        'type',
        'status',
        'conducted_at',
        'checklist',
        'general_condition',
        'cleanliness',
        'walls_condition',
        'floor_condition',
        'door_condition',
        'lock_condition',
        'lighting_condition',
        'photos',
        'videos',
        'damages_noted',
        'damage_cost',
        'customer_comments',
        'staff_comments',
        'customer_signature',
        'customer_signed_at',
        'staff_signature',
        'staff_signed_at',
    ];

    protected $casts = [
        'conducted_at' => 'datetime',
        'customer_signed_at' => 'datetime',
        'staff_signed_at' => 'datetime',
        'checklist' => 'array',
        'photos' => 'array',
        'videos' => 'array',
        'damage_cost' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function conductor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conducted_by');
    }

    public function scopeCheckIn($query)
    {
        return $query->where('type', 'check_in');
    }

    public function scopeCheckOut($query)
    {
        return $query->where('type', 'check_out');
    }

    public function isFullySigned(): bool
    {
        return $this->customer_signature && $this->staff_signature;
    }
}
