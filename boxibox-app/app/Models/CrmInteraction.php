<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmInteraction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'interactable_type',
        'interactable_id',
        'user_id',
        'type',
        'subject',
        'content',
        'outcome',
        'direction',
        'duration_seconds',
        'scheduled_at',
        'completed_at',
        'is_completed',
        'priority',
        'sentiment',
        'related_contract_id',
        'related_invoice_id',
        'related_quote_id',
        'reminder_at',
        'reminder_sent',
        'metadata',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'reminder_at' => 'datetime',
        'is_completed' => 'boolean',
        'reminder_sent' => 'boolean',
        'metadata' => 'array',
    ];

    protected $appends = ['formatted_type', 'icon', 'color'];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(CrmInteractionAttachment::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'related_contract_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'related_invoice_id');
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class, 'related_quote_id');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    public function getFormattedTypeAttribute(): string
    {
        return match ($this->type) {
            'call' => 'Appel téléphonique',
            'email' => 'Email envoyé',
            'email_received' => 'Email reçu',
            'meeting' => 'Rendez-vous',
            'visit' => 'Visite sur site',
            'sms' => 'SMS envoyé',
            'sms_received' => 'SMS reçu',
            'note' => 'Note',
            'task' => 'Tâche',
            'status_change' => 'Changement de statut',
            'quote' => 'Devis',
            'contract' => 'Contrat',
            'payment' => 'Paiement',
            'reminder' => 'Relance',
            'whatsapp' => 'WhatsApp',
            'chat' => 'Chat en ligne',
            'other' => 'Autre',
            default => ucfirst($this->type),
        };
    }

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'call' => 'phone',
            'email', 'email_received' => 'envelope',
            'meeting' => 'calendar',
            'visit' => 'map-marker-alt',
            'sms', 'sms_received' => 'comment-sms',
            'note' => 'sticky-note',
            'task' => 'tasks',
            'status_change' => 'exchange-alt',
            'quote' => 'file-invoice',
            'contract' => 'file-signature',
            'payment' => 'credit-card',
            'reminder' => 'bell',
            'whatsapp' => 'whatsapp',
            'chat' => 'comments',
            default => 'info-circle',
        };
    }

    public function getColorAttribute(): string
    {
        return match ($this->type) {
            'call' => 'blue',
            'email', 'email_received' => 'indigo',
            'meeting' => 'purple',
            'visit' => 'pink',
            'sms', 'sms_received' => 'cyan',
            'note' => 'yellow',
            'task' => 'amber',
            'status_change' => 'gray',
            'quote' => 'orange',
            'contract' => 'emerald',
            'payment' => 'green',
            'reminder' => 'red',
            'whatsapp' => 'green',
            'chat' => 'sky',
            default => 'gray',
        };
    }

    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration_seconds) {
            return null;
        }

        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;

        if ($minutes >= 60) {
            $hours = floor($minutes / 60);
            $minutes = $minutes % 60;
            return sprintf('%dh %02dm', $hours, $minutes);
        }

        return sprintf('%dm %02ds', $minutes, $seconds);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    public function scopeForTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUpcoming($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '>', now())
            ->where('is_completed', false);
    }

    public function scopeOverdue($query)
    {
        return $query->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<', now())
            ->where('is_completed', false);
    }

    public function scopePendingReminders($query)
    {
        return $query->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now())
            ->where('reminder_sent', false);
    }

    public function scopeForEntity($query, string $type, int $id)
    {
        return $query->where('interactable_type', $type)
            ->where('interactable_id', $id);
    }

    // ==========================================
    // METHODS
    // ==========================================

    public function markAsCompleted(): void
    {
        $this->update([
            'is_completed' => true,
            'completed_at' => now(),
        ]);
    }

    public function markReminderSent(): void
    {
        $this->update(['reminder_sent' => true]);
    }

    public static function getTypeOptions(): array
    {
        return [
            'call' => 'Appel téléphonique',
            'email' => 'Email envoyé',
            'email_received' => 'Email reçu',
            'meeting' => 'Rendez-vous',
            'visit' => 'Visite sur site',
            'sms' => 'SMS envoyé',
            'sms_received' => 'SMS reçu',
            'note' => 'Note interne',
            'task' => 'Tâche',
            'reminder' => 'Relance',
            'whatsapp' => 'WhatsApp',
            'chat' => 'Chat en ligne',
            'other' => 'Autre',
        ];
    }

    public static function getSentimentOptions(): array
    {
        return [
            'positive' => 'Positif',
            'neutral' => 'Neutre',
            'negative' => 'Négatif',
        ];
    }

    public static function getPriorityOptions(): array
    {
        return [
            'low' => 'Basse',
            'normal' => 'Normale',
            'high' => 'Haute',
            'urgent' => 'Urgente',
        ];
    }

    public static function getOutcomeOptions(): array
    {
        return [
            'call' => [
                'answered' => 'Répondu',
                'voicemail' => 'Messagerie vocale',
                'no_answer' => 'Pas de réponse',
                'busy' => 'Occupé',
                'callback_scheduled' => 'Rappel programmé',
            ],
            'email' => [
                'sent' => 'Envoyé',
                'opened' => 'Ouvert',
                'clicked' => 'Cliqué',
                'replied' => 'Répondu',
                'bounced' => 'Non délivré',
            ],
            'meeting' => [
                'completed' => 'Effectué',
                'no_show' => 'Absent',
                'rescheduled' => 'Reporté',
                'cancelled' => 'Annulé',
            ],
            'visit' => [
                'visited' => 'Visité',
                'interested' => 'Intéressé',
                'reserved' => 'Box réservé',
                'signed' => 'Contrat signé',
                'not_interested' => 'Pas intéressé',
            ],
        ];
    }
}
