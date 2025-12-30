<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoxAccessLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'box_id',
        'contract_id',
        'customer_id',
        'access_type',
        'method',
        'access_code_used',
        'shared_by_customer_id',
        'box_access_share_id',
        'device_id',
        'device_name',
        'ip_address',
        'latitude',
        'longitude',
        'status',
        'failure_reason',
        'metadata',
        'accessed_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'accessed_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function sharedByCustomer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'shared_by_customer_id');
    }

    public function accessShare(): BelongsTo
    {
        return $this->belongsTo(BoxAccessShare::class, 'box_access_share_id');
    }

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'denied']);
    }

    public function scopeEntries($query)
    {
        return $query->where('access_type', 'entry');
    }

    public function scopeExits($query)
    {
        return $query->where('access_type', 'exit');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('accessed_at', today());
    }

    public function scopeThisWeek($query)
    {
        return $query->whereBetween('accessed_at', [now()->startOfWeek(), now()->endOfWeek()]);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('accessed_at', now()->month)
            ->whereYear('accessed_at', now()->year);
    }

    // Helpers
    public function isEntry(): bool
    {
        return $this->access_type === 'entry';
    }

    public function isExit(): bool
    {
        return $this->access_type === 'exit';
    }

    public function wasSuccessful(): bool
    {
        return $this->status === 'success';
    }

    public function wasSharedAccess(): bool
    {
        return $this->method === 'shared_access' || $this->box_access_share_id !== null;
    }

    public function getMethodLabelAttribute(): string
    {
        return match($this->method) {
            'code' => 'Code d\'accès',
            'qr_code' => 'QR Code',
            'nfc' => 'Badge NFC',
            'smart_lock' => 'Serrure connectée',
            'manual' => 'Ouverture manuelle',
            'shared_access' => 'Accès partagé',
            default => 'Autre',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'success' => 'Réussi',
            'failed' => 'Échoué',
            'denied' => 'Refusé',
            default => 'Inconnu',
        };
    }
}
