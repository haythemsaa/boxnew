<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalculatorSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'session_id',
        'visitor_email',
        'visitor_phone',
        'visitor_name',
        'items_selected',
        'total_volume',
        'recommended_size',
        'recommended_box_id',
        'converted_to_lead',
        'lead_id',
        'converted_to_booking',
        'booking_id',
        'utm_source',
        'utm_medium',
        'utm_campaign',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'items_selected' => 'array',
        'total_volume' => 'decimal:2',
        'recommended_size' => 'decimal:2',
        'converted_to_lead' => 'boolean',
        'converted_to_booking' => 'boolean',
    ];

    protected static function booted()
    {
        static::creating(function ($session) {
            if (!$session->session_id) {
                $session->session_id = bin2hex(random_bytes(32));
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function recommendedBox(): BelongsTo
    {
        return $this->belongsTo(Box::class, 'recommended_box_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function calculateRecommendedSize(): float
    {
        // Formule: volume / hauteur moyenne (2.5m) avec marge de 20%
        $heightAvg = 2.5;
        $margin = 1.2;
        return round(($this->total_volume / $heightAvg) * $margin, 1);
    }

    public function getItemsCountAttribute(): int
    {
        return collect($this->items_selected ?? [])->sum('quantity');
    }

    public function scopeConverted($query)
    {
        return $query->where('converted_to_lead', true);
    }

    public function scopeWithContact($query)
    {
        return $query->whereNotNull('visitor_email');
    }
}
