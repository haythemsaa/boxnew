<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;

class Customer extends Model
{
    use SoftDeletes, Notifiable;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'type',
        'civility',
        'first_name',
        'last_name',
        'email',
        'phone',
        'mobile',
        'birth_date',
        'company_name',
        'vat_number',
        'id_type',
        'id_number',
        'address',
        'city',
        'postal_code',
        'country',
        'billing_address',
        'credit_score',
        'notes',
        'status',
        'outstanding_balance',
        'total_contracts',
        'total_revenue',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'credit_score' => 'integer',
        'outstanding_balance' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'total_contracts' => 'integer',
        'id_expiry' => 'date',
        'same_billing_address' => 'boolean',
        'gdpr_consent_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'id_number',
        'id_document_path',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function valetItems(): HasMany
    {
        return $this->hasMany(ValetItem::class);
    }

    public function valetOrders(): HasMany
    {
        return $this->hasMany(ValetOrder::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        if ($this->type === 'company') {
            return $this->company_name;
        }
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getDisplayNameAttribute(): string
    {
        $civility = $this->civility ? ucfirst($this->civility) . ' ' : '';
        return $civility . $this->full_name;
    }

    // Helper Methods
    public function updateStatistics(): void
    {
        $outstandingBalance = $this->invoices()
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->sum('total');

        $totalRevenue = $this->payments()
            ->where('status', 'completed')
            ->sum('amount');

        $this->update([
            'total_contracts' => $this->contracts()->count(),
            'outstanding_balance' => $outstandingBalance,
            'total_revenue' => $totalRevenue,
            'last_payment_date' => $this->payments()
                ->where('status', 'completed')
                ->latest()
                ->value('created_at'),
        ]);
    }

    public function hasOutstandingBalance(): bool
    {
        return $this->outstanding_balance > 0;
    }
}
