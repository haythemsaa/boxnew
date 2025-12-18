<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GocardlessPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'sepa_mandate_id',
        'invoice_id',
        'payment_id',
        'gocardless_payment_id',
        'amount',
        'currency',
        'description',
        'status',
        'charge_date',
        'paid_out_at',
        'cancelled_at',
        'failed_at',
        'failure_reason',
        'failure_description',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'charge_date' => 'date',
        'paid_out_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'failed_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = ['status_label', 'status_color'];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sepaMandate(): BelongsTo
    {
        return $this->belongsTo(SepaMandate::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending_customer_approval' => 'En attente approbation client',
            'pending_submission' => 'En attente d\'envoi',
            'submitted' => 'Soumis a la banque',
            'confirmed' => 'Confirme',
            'paid_out' => 'Paye',
            'cancelled' => 'Annule',
            'customer_approval_denied' => 'Refuse par le client',
            'failed' => 'Echoue',
            'charged_back' => 'Rembourse (litige)',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'paid_out' => 'green',
            'confirmed' => 'blue',
            'pending_submission', 'submitted' => 'yellow',
            'pending_customer_approval' => 'orange',
            'failed', 'cancelled', 'customer_approval_denied', 'charged_back' => 'red',
            default => 'gray',
        };
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            'pending_customer_approval',
            'pending_submission',
            'submitted',
            'confirmed',
        ]);
    }

    public function isSuccessful(): bool
    {
        return $this->status === 'paid_out';
    }

    public function hasFailed(): bool
    {
        return in_array($this->status, [
            'failed',
            'cancelled',
            'customer_approval_denied',
            'charged_back',
        ]);
    }

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [
            'pending_customer_approval',
            'pending_submission',
            'submitted',
            'confirmed',
        ]);
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', [
            'failed',
            'cancelled',
            'customer_approval_denied',
            'charged_back',
        ]);
    }
}
