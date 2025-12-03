<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Booking extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'site_id',
        'box_id',
        'customer_id',
        'booking_number',
        'customer_first_name',
        'customer_last_name',
        'customer_email',
        'customer_phone',
        'customer_address',
        'customer_city',
        'customer_postal_code',
        'customer_country',
        'customer_company',
        'customer_vat_number',
        'start_date',
        'end_date',
        'rental_type',
        'monthly_price',
        'deposit_amount',
        'total_amount',
        'status',
        'source',
        'source_url',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'notes',
        'internal_notes',
        'promo_code',
        'discount_amount',
        'id_document_path',
        'terms_accepted',
        'terms_accepted_at',
        'ip_address',
        'user_agent',
        'contract_id',
        'converted_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'monthly_price' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($booking) {
            if (empty($booking->uuid)) {
                $booking->uuid = (string) Str::uuid();
            }
            if (empty($booking->booking_number)) {
                $booking->booking_number = static::generateBookingNumber($booking->tenant_id);
            }
        });
    }

    public static function generateBookingNumber($tenantId): string
    {
        $prefix = 'BK';
        $year = date('Y');
        $count = static::where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->count() + 1;

        return sprintf('%s%s%05d', $prefix, $year, $count);
    }

    // Relationships
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

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(BookingStatusHistory::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(BookingPayment::class);
    }

    // Accessors
    public function getCustomerFullNameAttribute(): string
    {
        return trim("{$this->customer_first_name} {$this->customer_last_name}");
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'En attente',
            'confirmed' => 'Confirmé',
            'deposit_paid' => 'Acompte payé',
            'active' => 'Actif',
            'completed' => 'Terminé',
            'cancelled' => 'Annulé',
            'rejected' => 'Refusé',
            'expired' => 'Expiré',
            default => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => '#FF9800',
            'confirmed' => '#2196F3',
            'deposit_paid' => '#9C27B0',
            'active' => '#4CAF50',
            'completed' => '#607D8B',
            'cancelled' => '#f44336',
            'rejected' => '#f44336',
            'expired' => '#9E9E9E',
            default => '#9E9E9E',
        };
    }

    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'website' => 'Site Web',
            'widget' => 'Widget',
            'api' => 'API',
            'manual' => 'Manuel',
            'import' => 'Import',
            default => ucfirst($this->source),
        };
    }

    // Scopes
    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeBySite($query, int $siteId)
    {
        return $query->where('site_id', $siteId);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeConfirmed($query)
    {
        return $query->whereIn('status', ['confirmed', 'deposit_paid', 'active']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Actions
    public function confirm($userId = null, $notes = null): void
    {
        $this->updateStatus('confirmed', $userId, $notes);
    }

    public function reject($userId = null, $notes = null): void
    {
        $this->updateStatus('rejected', $userId, $notes);
        $this->box->makeAvailable();
    }

    public function cancel($userId = null, $notes = null): void
    {
        $this->updateStatus('cancelled', $userId, $notes);
        $this->box->makeAvailable();
    }

    public function markDepositPaid($userId = null, $notes = null): void
    {
        $this->updateStatus('deposit_paid', $userId, $notes);
    }

    public function activate($userId = null, $notes = null): void
    {
        $this->updateStatus('active', $userId, $notes);
        $this->box->makeOccupied();
    }

    public function complete($userId = null, $notes = null): void
    {
        $this->updateStatus('completed', $userId, $notes);
        $this->box->makeAvailable();
    }

    protected function updateStatus(string $newStatus, $userId = null, $notes = null): void
    {
        $oldStatus = $this->status;
        $this->update(['status' => $newStatus]);

        BookingStatusHistory::create([
            'booking_id' => $this->id,
            'from_status' => $oldStatus,
            'to_status' => $newStatus,
            'notes' => $notes,
            'user_id' => $userId,
        ]);
    }

    public function convertToContract(): ?Contract
    {
        if ($this->contract_id) {
            return $this->contract;
        }

        // Create or find customer
        $customer = Customer::firstOrCreate(
            [
                'tenant_id' => $this->tenant_id,
                'email' => $this->customer_email,
            ],
            [
                'first_name' => $this->customer_first_name,
                'last_name' => $this->customer_last_name,
                'phone' => $this->customer_phone,
                'address' => $this->customer_address,
                'city' => $this->customer_city,
                'postal_code' => $this->customer_postal_code,
                'country' => $this->customer_country,
                'company' => $this->customer_company,
                'vat_number' => $this->customer_vat_number,
            ]
        );

        // Create contract
        $contract = Contract::create([
            'tenant_id' => $this->tenant_id,
            'site_id' => $this->site_id,
            'box_id' => $this->box_id,
            'customer_id' => $customer->id,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'monthly_rent' => $this->monthly_price,
            'deposit_amount' => $this->deposit_amount,
            'status' => 'active',
            'notes' => "Créé depuis la réservation #{$this->booking_number}",
        ]);

        $this->update([
            'customer_id' => $customer->id,
            'contract_id' => $contract->id,
            'converted_at' => now(),
        ]);

        $this->activate(null, 'Converti en contrat');

        return $contract;
    }
}
