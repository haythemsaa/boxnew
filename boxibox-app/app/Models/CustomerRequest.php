<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'type',
        'subject',
        'description',
        'status',
        'handled_by',
        'staff_notes',
        'response',
        'responded_at',
        'attachments',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
        'attachments' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_review']);
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['completed', 'cancelled', 'rejected']);
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'termination' => 'Résiliation',
            'box_upgrade' => 'Changement box (plus grand)',
            'box_downgrade' => 'Changement box (plus petit)',
            'payment_plan' => 'Échéancier de paiement',
            'maintenance' => 'Demande de maintenance',
            'access_issue' => 'Problème d\'accès',
            'billing_question' => 'Question facturation',
            'general_inquiry' => 'Demande générale',
            'document_request' => 'Demande de document',
            'other' => 'Autre',
            default => $this->type,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'yellow',
            'in_review' => 'blue',
            'approved' => 'green',
            'rejected' => 'red',
            'completed' => 'gray',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
