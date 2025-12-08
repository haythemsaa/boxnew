<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaintenanceTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'box_id',
        'customer_id',
        'created_by',
        'assigned_to',
        'ticket_number',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'estimated_cost',
        'actual_cost',
        'estimated_hours',
        'actual_hours',
        'scheduled_date',
        'started_at',
        'completed_at',
        'resolution_notes',
        'photos_before',
        'photos_after',
    ];

    protected $casts = [
        'photos_before' => 'array',
        'photos_after' => 'array',
        'scheduled_date' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'estimated_cost' => 'decimal:2',
        'actual_cost' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    public static function generateTicketNumber(): string
    {
        $prefix = 'TKT';
        $date = now()->format('Ymd');
        $lastTicket = static::whereDate('created_at', today())->latest()->first();
        $sequence = $lastTicket ? (int) substr($lastTicket->ticket_number, -4) + 1 : 1;
        return $prefix . $date . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
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

    public function comments(): HasMany
    {
        return $this->hasMany(MaintenanceTicketComment::class, 'ticket_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(MaintenanceTicketHistory::class, 'ticket_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeUrgent($query)
    {
        return $query->where('priority', 'urgent');
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', ['high', 'urgent']);
    }

    public function scopeOverdue($query)
    {
        return $query->whereIn('status', ['open', 'in_progress'])
            ->where('scheduled_date', '<', now());
    }

    public function getPriorityColorAttribute(): string
    {
        return match ($this->priority) {
            'urgent' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'open' => 'blue',
            'in_progress' => 'yellow',
            'pending_parts' => 'orange',
            'completed' => 'green',
            'cancelled' => 'gray',
            default => 'gray',
        };
    }
}
