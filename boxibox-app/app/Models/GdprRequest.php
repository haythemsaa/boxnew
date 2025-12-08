<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GdprRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'request_number',
        'requester_email',
        'requester_name',
        'type',
        'description',
        'status',
        'handled_by',
        'deadline_at',
        'completed_at',
        'response',
        'data_exported',
        'data_deleted',
    ];

    protected $casts = [
        'deadline_at' => 'datetime',
        'completed_at' => 'datetime',
        'data_exported' => 'array',
        'data_deleted' => 'array',
    ];

    public static function generateRequestNumber(): string
    {
        $prefix = 'GDPR';
        $year = now()->format('Y');
        $lastRequest = static::whereYear('created_at', $year)->latest()->first();
        $sequence = $lastRequest ? (int) substr($lastRequest->request_number, -5) + 1 : 1;
        return $prefix . $year . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function handler(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function isOverdue(): bool
    {
        return $this->deadline_at && $this->deadline_at->isPast() && $this->status !== 'completed';
    }

    public function getDaysRemainingAttribute(): int
    {
        if (!$this->deadline_at) {
            return 0;
        }
        return max(0, now()->diffInDays($this->deadline_at, false));
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'access' => 'Droit d\'accès',
            'rectification' => 'Droit de rectification',
            'erasure' => 'Droit à l\'effacement',
            'portability' => 'Droit à la portabilité',
            'restriction' => 'Droit à la limitation',
            'objection' => 'Droit d\'opposition',
            default => $this->type,
        };
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', ['pending', 'in_progress']);
    }

    public function scopeOverdue($query)
    {
        return $query->where('deadline_at', '<', now())
            ->whereNotIn('status', ['completed', 'rejected', 'cancelled']);
    }
}
