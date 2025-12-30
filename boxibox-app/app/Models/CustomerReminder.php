<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;

class CustomerReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'customer_id',
        'remindable_type',
        'remindable_id',
        'type',
        'title',
        'message',
        'priority',
        'remind_at',
        'reminded_at',
        'is_recurring',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_end',
        'occurrence_count',
        'max_occurrences',
        'channels',
        'status',
        'snoozed_until',
        'dismissed_at',
        'completed_at',
        'email_sent',
        'sms_sent',
        'push_sent',
        'is_read',
        'read_at',
        'action_url',
        'action_label',
    ];

    protected $casts = [
        'remind_at' => 'datetime',
        'reminded_at' => 'datetime',
        'recurrence_end' => 'datetime',
        'snoozed_until' => 'datetime',
        'dismissed_at' => 'datetime',
        'completed_at' => 'datetime',
        'read_at' => 'datetime',
        'channels' => 'array',
        'is_recurring' => 'boolean',
        'email_sent' => 'boolean',
        'sms_sent' => 'boolean',
        'push_sent' => 'boolean',
        'is_read' => 'boolean',
    ];

    public const TYPES = [
        'contract_expiry' => 'Fin de contrat',
        'invoice_due' => 'Facture à payer',
        'payment_failed' => 'Paiement échoué',
        'booking_confirmation' => 'Réservation à confirmer',
        'visit_scheduled' => 'Visite planifiée',
        'document_required' => 'Document requis',
        'insurance_renewal' => 'Renouvellement assurance',
        'custom' => 'Rappel personnalisé',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reminder) {
            $reminder->uuid = $reminder->uuid ?? Str::uuid();
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function remindable(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('status', 'pending')
            ->where('remind_at', '>=', now())
            ->orderBy('remind_at');
    }

    public function scopeDue($query)
    {
        return $query->where('status', 'pending')
            ->where('remind_at', '<=', now());
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    // Helpers
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSent(): bool
    {
        return $this->status === 'sent';
    }

    public function isDismissed(): bool
    {
        return $this->status === 'dismissed';
    }

    public function isSnoozed(): bool
    {
        return $this->status === 'snoozed' && $this->snoozed_until > now();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function isDue(): bool
    {
        return $this->isPending() && $this->remind_at <= now();
    }

    public function markAsSent(): void
    {
        $this->update([
            'status' => 'sent',
            'reminded_at' => now(),
        ]);

        if ($this->is_recurring) {
            $this->scheduleNextOccurrence();
        }
    }

    public function snooze(int $minutes = 30): void
    {
        $this->update([
            'status' => 'snoozed',
            'snoozed_until' => now()->addMinutes($minutes),
        ]);
    }

    public function unsnooze(): void
    {
        if ($this->isSnoozed()) {
            $this->update([
                'status' => 'pending',
                'snoozed_until' => null,
            ]);
        }
    }

    public function dismiss(): void
    {
        $this->update([
            'status' => 'dismissed',
            'dismissed_at' => now(),
        ]);
    }

    public function complete(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    public function markAsRead(): void
    {
        if (!$this->is_read) {
            $this->update([
                'is_read' => true,
                'read_at' => now(),
            ]);
        }
    }

    protected function scheduleNextOccurrence(): void
    {
        if (!$this->is_recurring) {
            return;
        }

        if ($this->max_occurrences && $this->occurrence_count >= $this->max_occurrences) {
            return;
        }

        if ($this->recurrence_end && $this->recurrence_end < now()) {
            return;
        }

        $nextDate = match($this->recurrence_type) {
            'daily' => now()->addDays($this->recurrence_interval),
            'weekly' => now()->addWeeks($this->recurrence_interval),
            'monthly' => now()->addMonths($this->recurrence_interval),
            'yearly' => now()->addYears($this->recurrence_interval),
            default => null,
        };

        if ($nextDate && (!$this->recurrence_end || $nextDate <= $this->recurrence_end)) {
            self::create([
                'tenant_id' => $this->tenant_id,
                'customer_id' => $this->customer_id,
                'remindable_type' => $this->remindable_type,
                'remindable_id' => $this->remindable_id,
                'type' => $this->type,
                'title' => $this->title,
                'message' => $this->message,
                'priority' => $this->priority,
                'remind_at' => $nextDate,
                'is_recurring' => true,
                'recurrence_type' => $this->recurrence_type,
                'recurrence_interval' => $this->recurrence_interval,
                'recurrence_end' => $this->recurrence_end,
                'occurrence_count' => $this->occurrence_count + 1,
                'max_occurrences' => $this->max_occurrences,
                'channels' => $this->channels,
                'action_url' => $this->action_url,
                'action_label' => $this->action_label,
            ]);
        }
    }

    // Accessors
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? 'Rappel';
    }

    public function getPriorityLabelAttribute(): string
    {
        return match($this->priority) {
            'low' => 'Basse',
            'medium' => 'Moyenne',
            'high' => 'Haute',
            'urgent' => 'Urgente',
            default => 'Moyenne',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'En attente',
            'sent' => 'Envoyé',
            'dismissed' => 'Ignoré',
            'snoozed' => 'Reporté',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            default => 'Inconnu',
        };
    }

    // Factory methods
    public static function createContractExpiryReminder(Contract $contract, int $daysBefore = 30): self
    {
        return self::create([
            'tenant_id' => $contract->tenant_id,
            'customer_id' => $contract->customer_id,
            'remindable_type' => Contract::class,
            'remindable_id' => $contract->id,
            'type' => 'contract_expiry',
            'title' => 'Votre contrat arrive à échéance',
            'message' => "Votre contrat {$contract->contract_number} expire le " . $contract->end_date->format('d/m/Y') . ". Pensez à le renouveler.",
            'priority' => 'high',
            'remind_at' => $contract->end_date->subDays($daysBefore),
            'channels' => ['in_app', 'email', 'push'],
            'action_url' => "/mobile/contracts/{$contract->id}",
            'action_label' => 'Voir le contrat',
        ]);
    }

    public static function createInvoiceDueReminder(Invoice $invoice, int $daysBefore = 7): self
    {
        return self::create([
            'tenant_id' => $invoice->tenant_id,
            'customer_id' => $invoice->customer_id,
            'remindable_type' => Invoice::class,
            'remindable_id' => $invoice->id,
            'type' => 'invoice_due',
            'title' => 'Facture à payer',
            'message' => "La facture {$invoice->invoice_number} de {$invoice->total}€ arrive à échéance le " . $invoice->due_date->format('d/m/Y') . ".",
            'priority' => 'medium',
            'remind_at' => $invoice->due_date->subDays($daysBefore),
            'channels' => ['in_app', 'email'],
            'action_url' => "/mobile/invoices/{$invoice->id}",
            'action_label' => 'Payer maintenant',
        ]);
    }
}
