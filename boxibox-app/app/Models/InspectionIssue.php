<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionIssue extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_id',
        'location',
        'description',
        'severity',
        'photos',
        'status',
        'maintenance_ticket_id',
        'resolved_at',
        'resolution_notes',
    ];

    protected $casts = [
        'photos' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function inspection(): BelongsTo
    {
        return $this->belongsTo(Inspection::class);
    }

    public function maintenanceTicket(): BelongsTo
    {
        return $this->belongsTo(MaintenanceTicket::class);
    }

    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }

    public function getSeverityColorAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'red',
            'high' => 'orange',
            'medium' => 'yellow',
            'low' => 'green',
            default => 'gray',
        };
    }

    public function getSeverityLabelAttribute(): string
    {
        return match ($this->severity) {
            'critical' => 'Critique',
            'high' => 'Élevée',
            'medium' => 'Moyenne',
            'low' => 'Faible',
            default => $this->severity,
        };
    }
}
