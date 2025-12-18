<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GocardlessEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'event_id',
        'action',
        'resource_type',
        'resource_id',
        'links',
        'details',
        'metadata',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'links' => 'array',
        'details' => 'array',
        'metadata' => 'array',
        'processed' => 'boolean',
        'processed_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function scopeUnprocessed($query)
    {
        return $query->where('processed', false);
    }

    public function scopeForResource($query, string $type, string $id)
    {
        return $query->where('resource_type', $type)
            ->where('resource_id', $id);
    }

    public function markAsProcessed(): void
    {
        $this->update([
            'processed' => true,
            'processed_at' => now(),
        ]);
    }
}
