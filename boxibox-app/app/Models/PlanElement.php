<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanElement extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'floor_id',
        'element_type',
        'box_id',
        'x',
        'y',
        'width',
        'height',
        'rotation',
        'z_index',
        'fill_color',
        'stroke_color',
        'stroke_width',
        'opacity',
        'font_size',
        'text_color',
        'label',
        'description',
        'properties',
        'is_locked',
        'is_visible',
    ];

    protected $casts = [
        'x' => 'decimal:2',
        'y' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'rotation' => 'decimal:2',
        'opacity' => 'decimal:2',
        'properties' => 'array',
        'is_locked' => 'boolean',
        'is_visible' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function floor(): BelongsTo
    {
        return $this->belongsTo(Floor::class);
    }

    public function box(): BelongsTo
    {
        return $this->belongsTo(Box::class);
    }

    /**
     * Get the status color based on box occupancy
     */
    public function getStatusColorAttribute(): string
    {
        if ($this->element_type !== 'box' || !$this->box) {
            return $this->fill_color ?? '#cccccc';
        }

        $box = $this->box;

        if ($box->status === 'maintenance') {
            return '#ef4444'; // Rouge
        }

        if ($box->status === 'reserved') {
            return '#f59e0b'; // Orange
        }

        // Check if box has active contract
        $hasActiveContract = $box->contracts()
            ->where('status', 'active')
            ->exists();

        return $hasActiveContract ? '#3b82f6' : '#22c55e'; // Bleu si occupé, Vert si libre
    }

    /**
     * Get box info for tooltip
     */
    public function getBoxInfoAttribute(): ?array
    {
        if ($this->element_type !== 'box' || !$this->box) {
            return null;
        }

        $box = $this->box->load(['contracts' => function ($query) {
            $query->where('status', 'active')->with('customer');
        }]);

        $activeContract = $box->contracts->first();

        return [
            'id' => $box->id,
            'code' => $box->code,
            'size' => $box->size_m3 . 'm³',
            'dimensions' => $box->width . 'm x ' . $box->depth . 'm x ' . $box->height . 'm',
            'price' => number_format($box->monthly_price, 2) . '€/mois',
            'status' => $activeContract ? 'occupied' : 'available',
            'contract' => $activeContract ? [
                'id' => $activeContract->id,
                'number' => $activeContract->contract_number,
                'start_date' => $activeContract->start_date->format('d/m/Y'),
                'customer' => $activeContract->customer ? [
                    'id' => $activeContract->customer->id,
                    'name' => $activeContract->customer->full_name,
                    'email' => $activeContract->customer->email,
                    'phone' => $activeContract->customer->phone,
                ] : null,
            ] : null,
        ];
    }
}
