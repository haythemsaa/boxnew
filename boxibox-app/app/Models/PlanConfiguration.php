<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'site_id',
        'canvas_width',
        'canvas_height',
        'show_grid',
        'grid_size',
        'snap_to_grid',
        'default_box_available_color',
        'default_box_occupied_color',
        'default_box_reserved_color',
        'default_box_maintenance_color',
        'default_wall_color',
        'default_door_color',
        'background_image',
        'background_opacity',
        'initial_zoom',
        'initial_x',
        'initial_y',
        'show_box_labels',
        'show_box_sizes',
        'show_legend',
        'show_statistics',
    ];

    protected $casts = [
        'show_grid' => 'boolean',
        'snap_to_grid' => 'boolean',
        'background_opacity' => 'decimal:2',
        'initial_zoom' => 'decimal:2',
        'initial_x' => 'decimal:2',
        'initial_y' => 'decimal:2',
        'show_box_labels' => 'boolean',
        'show_box_sizes' => 'boolean',
        'show_legend' => 'boolean',
        'show_statistics' => 'boolean',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }
}
