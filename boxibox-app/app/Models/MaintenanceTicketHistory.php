<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MaintenanceTicketHistory extends Model
{
    use HasFactory;

    protected $table = 'maintenance_ticket_history';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'old_value',
        'new_value',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class, 'ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getActionLabelAttribute(): string
    {
        return match ($this->action) {
            'created' => 'Ticket créé',
            'status_changed' => 'Statut modifié',
            'assigned' => 'Assigné',
            'priority_changed' => 'Priorité modifiée',
            'comment_added' => 'Commentaire ajouté',
            'attachment_added' => 'Pièce jointe ajoutée',
            'resolved' => 'Résolu',
            'closed' => 'Fermé',
            'reopened' => 'Réouvert',
            default => $this->action,
        };
    }
}
