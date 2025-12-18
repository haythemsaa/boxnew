<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class SepaMandate extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'contract_id',
        'rum',
        'ics',
        'type',
        'status',
        'iban',
        'bic',
        'account_holder',
        'signature_date',
        'signature_place',
        'signed_document_path',
        'activated_at',
        'first_collection_date',
        'last_collection_date',
        'last_collection_at',
        'collection_count',
        'total_collected',
        'notes',
        // GoCardless fields
        'gocardless_mandate_id',
        'gocardless_customer_id',
        'gocardless_bank_account_id',
        'scheme',
    ];

    protected $casts = [
        'signature_date' => 'date',
        'activated_at' => 'datetime',
        'first_collection_date' => 'date',
        'last_collection_date' => 'date',
        'last_collection_at' => 'datetime',
        'collection_count' => 'integer',
        'total_collected' => 'decimal:2',
        'deleted_at' => 'datetime',
    ];

    protected $hidden = [
        'iban', // Hide by default for security
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mandate) {
            if (empty($mandate->rum)) {
                // Generate RUM: PREFIX + DATE + RANDOM
                $mandate->rum = 'SEPA-' . date('Ymd') . '-' . strtoupper(Str::random(8));
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

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeSuspended($query)
    {
        return $query->where('status', 'suspended');
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    // Helper Methods
    public function activate(): void
    {
        $this->update(['status' => 'active']);
    }

    public function suspend(): void
    {
        $this->update(['status' => 'suspended']);
    }

    public function cancel(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function recordCollection(float $amount): void
    {
        $this->increment('collection_count');
        $this->increment('total_collected', $amount);
        $this->update(['last_collection_date' => now()]);

        if (empty($this->first_collection_date)) {
            $this->update(['first_collection_date' => now()]);
        }
    }

    // Accessors
    public function getMaskedIbanAttribute(): string
    {
        if (empty($this->iban)) {
            return '-';
        }
        // Show only last 4 characters
        return str_repeat('*', strlen($this->iban) - 4) . substr($this->iban, -4);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === 'active';
    }

    public function getCanCollectAttribute(): bool
    {
        return $this->status === 'active' && !empty($this->iban);
    }

    // IBAN validation helper
    public static function validateIban(string $iban): bool
    {
        $iban = strtoupper(str_replace(' ', '', $iban));

        // Basic length check
        if (strlen($iban) < 15 || strlen($iban) > 34) {
            return false;
        }

        // Move first 4 characters to end
        $rearranged = substr($iban, 4) . substr($iban, 0, 4);

        // Replace letters with numbers (A=10, B=11, ..., Z=35)
        $numericIban = '';
        foreach (str_split($rearranged) as $char) {
            if (ctype_alpha($char)) {
                $numericIban .= (ord($char) - 55);
            } else {
                $numericIban .= $char;
            }
        }

        // Modulo 97 check
        return bcmod($numericIban, '97') === '1';
    }
}
