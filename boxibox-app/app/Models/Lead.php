<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'site_id',
        'assigned_to',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'type',
        'status',
        'source',
        'score',
        'priority',
        'conversion_probability',
        'score_breakdown',
        'score_factors',
        'box_type_interest',
        'budget_min',
        'budget_max',
        'move_in_date',
        'notes',
        'metadata',
        'first_contacted_at',
        'last_contacted_at',
        'last_activity_at',
        'converted_at',
        'converted_to_customer_id',
        'score_calculated_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'score_breakdown' => 'array',
        'score_factors' => 'array',
        'conversion_probability' => 'decimal:2',
        'budget_min' => 'decimal:2',
        'budget_max' => 'decimal:2',
        'score' => 'integer',
        'move_in_date' => 'date',
        'first_contacted_at' => 'datetime',
        'last_contacted_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'converted_at' => 'datetime',
        'score_calculated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'converted_to_customer_id');
    }

    public function interactions(): MorphMany
    {
        return $this->morphMany(CrmInteraction::class, 'interactable');
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function scopeNew($query)
    {
        return $query->where('status', 'new');
    }

    public function scopeHot($query)
    {
        return $query->where('score', '>=', 70);
    }

    public function scopeUnassigned($query)
    {
        return $query->whereNull('assigned_to');
    }

    public function updateScore(): void
    {
        $score = 0;

        // Base score for contact information completeness
        if ($this->phone) $score += 10;
        if ($this->email) $score += 10;

        // Budget clarity
        if ($this->budget_min && $this->budget_max) $score += 15;

        // Move-in date proximity
        if ($this->move_in_date) {
            $daysUntil = now()->diffInDays($this->move_in_date, false);
            if ($daysUntil <= 7) $score += 30;
            elseif ($daysUntil <= 30) $score += 20;
            elseif ($daysUntil <= 90) $score += 10;
        }

        // Recent contact
        if ($this->last_contacted_at) {
            $daysSince = now()->diffInDays($this->last_contacted_at);
            if ($daysSince <= 2) $score += 20;
            elseif ($daysSince <= 7) $score += 10;
        }

        // Source quality
        $score += match($this->source) {
            'referral' => 15,
            'google_ads' => 10,
            'website' => 10,
            'walk-in' => 15,
            default => 5
        };

        $this->score = min(100, $score);
        $this->save();
    }
}
