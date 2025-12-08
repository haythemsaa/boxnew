<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverdueAction extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'contract_id',
        'invoice_id',
        'workflow_step_id',
        'action_type',
        'scheduled_at',
        'executed_at',
        'result',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'result' => 'array',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function workflowStep(): BelongsTo
    {
        return $this->belongsTo(OverdueWorkflowStep::class, 'workflow_step_id');
    }

    public function isExecuted(): bool
    {
        return $this->executed_at !== null;
    }

    public function isPending(): bool
    {
        return !$this->executed_at && $this->scheduled_at;
    }

    public function scopePending($query)
    {
        return $query->whereNull('executed_at')->whereNotNull('scheduled_at');
    }

    public function scopeDue($query)
    {
        return $query->pending()->where('scheduled_at', '<=', now());
    }

    public function getActionTypeLabelAttribute(): string
    {
        return match ($this->action_type) {
            'email_reminder' => 'Email de relance',
            'sms_reminder' => 'SMS de relance',
            'late_fee' => 'Frais de retard',
            'access_restriction' => 'Restriction d\'accès',
            'access_suspension' => 'Suspension d\'accès',
            'formal_notice' => 'Mise en demeure',
            'collection_agency' => 'Agence de recouvrement',
            'lien_procedure' => 'Procédure de saisie',
            default => $this->action_type,
        };
    }
}
