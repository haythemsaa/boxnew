<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OverdueWorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'days_overdue',
        'action_type',
        'template_name',
        'fee_amount',
        'fee_percentage',
        'notes',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'fee_amount' => 'decimal:2',
        'fee_percentage' => 'decimal:2',
    ];

    public function workflow(): BelongsTo
    {
        return $this->belongsTo(OverdueWorkflow::class, 'workflow_id');
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action_type) {
            'email' => 'Envoi email',
            'sms' => 'Envoi SMS',
            'letter' => 'Envoi courrier',
            'lock_box' => 'Verrouillage box',
            'late_fee' => 'Frais de retard',
            'legal_notice' => 'Mise en demeure',
            'lien_process' => 'Procédure de vente',
            default => $this->action_type,
        };
    }
}
