<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerReview extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'customer_id',
        'contract_id',
        'source',
        'external_id',
        'rating',
        'comment',
        'response',
        'responded_by',
        'responded_at',
        'status',
        'is_verified',
        'is_public',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'is_verified' => 'boolean',
        'is_public' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function responder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responded_by');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopePositive($query)
    {
        return $query->where('rating', '>=', 4);
    }

    public function scopeNegative($query)
    {
        return $query->where('rating', '<=', 2);
    }

    public static function getAverageRating(int $tenantId, ?int $siteId = null): float
    {
        $query = static::where('tenant_id', $tenantId)
            ->where('status', 'approved');

        if ($siteId) {
            $query->where('site_id', $siteId);
        }

        return round($query->avg('rating') ?? 0, 1);
    }
}
