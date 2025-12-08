<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Webhook extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'name',
        'url',
        'secret_key',
        'events',
        'headers',
        'is_active',
        'verify_ssl',
        'retry_count',
        'timeout',
        'last_triggered_at',
        'last_status',
        'last_error',
        'total_calls',
        'successful_calls',
        'failed_calls',
    ];

    protected $casts = [
        'events' => 'array',
        'headers' => 'array',
        'is_active' => 'boolean',
        'verify_ssl' => 'boolean',
        'retry_count' => 'integer',
        'timeout' => 'integer',
        'last_triggered_at' => 'datetime',
        'total_calls' => 'integer',
        'successful_calls' => 'integer',
        'failed_calls' => 'integer',
    ];

    protected $hidden = ['secret_key'];

    // Available webhook events
    public const EVENTS = [
        // Contracts
        'contract.created' => 'Contract Created',
        'contract.signed' => 'Contract Signed',
        'contract.terminated' => 'Contract Terminated',
        'contract.renewed' => 'Contract Renewed',
        'contract.expiring_soon' => 'Contract Expiring Soon',

        // Customers
        'customer.created' => 'Customer Created',
        'customer.updated' => 'Customer Updated',
        'customer.deleted' => 'Customer Deleted',

        // Invoices
        'invoice.created' => 'Invoice Created',
        'invoice.sent' => 'Invoice Sent',
        'invoice.paid' => 'Invoice Paid',
        'invoice.overdue' => 'Invoice Overdue',
        'invoice.cancelled' => 'Invoice Cancelled',

        // Payments
        'payment.received' => 'Payment Received',
        'payment.failed' => 'Payment Failed',
        'payment.refunded' => 'Payment Refunded',

        // Boxes
        'box.occupied' => 'Box Occupied',
        'box.vacated' => 'Box Vacated',
        'box.maintenance_required' => 'Box Maintenance Required',

        // Access
        'access.granted' => 'Access Granted',
        'access.revoked' => 'Access Revoked',
        'access.unauthorized' => 'Unauthorized Access Attempt',

        // Leads/Prospects
        'lead.created' => 'Lead Created',
        'lead.converted' => 'Lead Converted',
        'prospect.created' => 'Prospect Created',
        'prospect.converted' => 'Prospect Converted',

        // Bookings
        'booking.created' => 'Booking Created',
        'booking.confirmed' => 'Booking Confirmed',
        'booking.cancelled' => 'Booking Cancelled',

        // SEPA
        'sepa_mandate.created' => 'SEPA Mandate Created',
        'sepa_mandate.activated' => 'SEPA Mandate Activated',
        'sepa_mandate.cancelled' => 'SEPA Mandate Cancelled',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($webhook) {
            if (empty($webhook->secret_key)) {
                $webhook->secret_key = Str::random(32);
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class);
    }

    public function recentDeliveries(): HasMany
    {
        return $this->hasMany(WebhookDelivery::class)
            ->orderByDesc('created_at')
            ->limit(50);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForEvent($query, string $event)
    {
        return $query->whereJsonContains('events', $event);
    }

    // Methods
    public function subscribesTo(string $event): bool
    {
        return in_array($event, $this->events ?? []);
    }

    public function generateSignature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->secret_key);
    }

    public function recordSuccess(): void
    {
        $this->increment('total_calls');
        $this->increment('successful_calls');
        $this->update([
            'last_triggered_at' => now(),
            'last_status' => 'success',
            'last_error' => null,
        ]);
    }

    public function recordFailure(string $error): void
    {
        $this->increment('total_calls');
        $this->increment('failed_calls');
        $this->update([
            'last_triggered_at' => now(),
            'last_status' => 'failed',
            'last_error' => $error,
        ]);
    }

    public function getSuccessRateAttribute(): float
    {
        if ($this->total_calls === 0) {
            return 100;
        }
        return round(($this->successful_calls / $this->total_calls) * 100, 2);
    }
}
