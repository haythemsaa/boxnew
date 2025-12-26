<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewOptOut extends Model
{
    protected $table = 'review_opt_outs';

    protected $fillable = [
        'tenant_id',
        'customer_email',
        'customer_id',
        'opted_out_at',
        'reason',
    ];

    protected $casts = [
        'opted_out_at' => 'datetime',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Static helpers
    public static function isOptedOut(int $tenantId, string $email): bool
    {
        return static::where('tenant_id', $tenantId)
            ->where('customer_email', $email)
            ->exists();
    }
}
