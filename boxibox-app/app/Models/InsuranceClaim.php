<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsuranceClaim extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'policy_id',
        'claim_number',
        'incident_date',
        'incident_type',
        'description',
        'items_damaged',
        'estimated_damage',
        'claimed_amount',
        'approved_amount',
        'status',
        'submitted_at',
        'reviewed_at',
        'resolved_at',
        'reviewer_notes',
        'documents',
        'photos',
    ];

    protected $casts = [
        'incident_date' => 'date',
        'estimated_damage' => 'decimal:2',
        'claimed_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
        'items_damaged' => 'array',
        'documents' => 'array',
        'photos' => 'array',
    ];

    /**
     * Get the tenant.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the policy.
     */
    public function policy(): BelongsTo
    {
        return $this->belongsTo(InsurancePolicy::class);
    }

    /**
     * Generate a unique claim number.
     */
    public static function generateClaimNumber(): string
    {
        $prefix = 'CLM';
        $year = date('Y');
        $lastClaim = self::whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastClaim ? intval(substr($lastClaim->claim_number, -6)) + 1 : 1;

        return sprintf('%s-%s-%06d', $prefix, $year, $sequence);
    }
}
