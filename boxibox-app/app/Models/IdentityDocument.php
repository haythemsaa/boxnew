<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Identity Document Model
 * Stores identity verification documents for customers during move-in
 */
class IdentityDocument extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'movein_session_id',
        'document_type',
        'document_number',
        'issuing_country',
        'issuing_authority',
        'issue_date',
        'expiry_date',
        'first_name',
        'last_name',
        'date_of_birth',
        'file_path',
        'file_hash',
        'verified_at',
        'verified_by',
        'verification_method',
        'verification_status',
        'rejection_reason',
        'metadata',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'date_of_birth' => 'date',
        'verified_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'document_number',
        'file_path',
        'file_hash',
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

    public function moveinSession(): BelongsTo
    {
        return $this->belongsTo(MoveinSession::class);
    }

    public function verifiedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('verification_status', 'verified');
    }

    public function scopePending($query)
    {
        return $query->where('verification_status', 'pending');
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<', now());
    }

    // Accessors
    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsVerifiedAttribute(): bool
    {
        return $this->verification_status === 'verified';
    }

    public function getMaskedDocumentNumberAttribute(): string
    {
        if (empty($this->document_number)) {
            return '-';
        }
        $length = strlen($this->document_number);
        return str_repeat('*', $length - 4) . substr($this->document_number, -4);
    }
}
