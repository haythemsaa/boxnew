<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupportTicket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'subscription_id',
        'created_by',
        'ticket_number',
        'type',
        'subject',
        'description',
        'priority',
        'category',
        'status',
        'assigned_to',
        'assigned_team',
        'first_response_at',
        'resolved_at',
        'closed_at',
        'last_message_at',
        'satisfaction_rating',
        'satisfaction_feedback',
        'tags',
    ];

    protected $casts = [
        'first_response_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'last_message_at' => 'datetime',
        'tags' => 'array',
    ];

    const PRIORITIES = [
        'low' => 'Basse',
        'medium' => 'Moyenne',
        'high' => 'Haute',
        'critical' => 'Critique',
        'urgent' => 'Urgente',
    ];

    const CATEGORIES = [
        'general' => 'General',
        'technical' => 'Technique',
        'billing' => 'Facturation',
        'contract' => 'Contrat',
        'access' => 'Acces',
        'feature_request' => 'Demande de fonctionnalite',
        'bug' => 'Bug',
        'question' => 'Question',
        'complaint' => 'Reclamation',
        'other' => 'Autre',
    ];

    const STATUSES = [
        'open' => 'Ouvert',
        'pending' => 'En attente',
        'in_progress' => 'En cours',
        'waiting_customer' => 'Attente client',
        'waiting_tenant' => 'Attente tenant',
        'waiting_internal' => 'Attente interne',
        'resolved' => 'Resolu',
        'closed' => 'Ferme',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (!$ticket->ticket_number) {
                $prefix = $ticket->type === 'admin_tenant' ? 'ADM' : 'TKT';
                $ticket->ticket_number = $prefix . '-' . strtoupper(substr(uniqid(), -8));
            }
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

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class, 'ticket_id');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', ['resolved', 'closed']);
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeTenantCustomer($query)
    {
        return $query->where('type', 'tenant_customer');
    }

    public function scopeAdminTenant($query)
    {
        return $query->where('type', 'admin_tenant');
    }

    // Helpers
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getUnreadCountAttribute(): int
    {
        return $this->messages()->where('is_read', false)->count();
    }

    public function markAsRead($forUser = true): void
    {
        $query = $this->messages()->where('is_read', false);

        if ($forUser) {
            $query->where('sender_type', '!=', 'user');
        } else {
            $query->where('sender_type', 'user');
        }

        $query->update(['is_read' => true, 'read_at' => now()]);
    }
}
