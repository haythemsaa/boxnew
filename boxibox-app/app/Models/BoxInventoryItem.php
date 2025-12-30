<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class BoxInventoryItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'tenant_id',
        'box_id',
        'contract_id',
        'customer_id',
        'name',
        'description',
        'category',
        'quantity',
        'estimated_value',
        'currency',
        'condition',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'length',
        'width',
        'height',
        'weight',
        'photos',
        'location_in_box',
        'tags',
        'is_insured',
        'insurance_policy_number',
        'status',
        'stored_at',
        'removed_at',
        'removal_note',
    ];

    protected $casts = [
        'photos' => 'array',
        'tags' => 'array',
        'purchase_date' => 'date',
        'stored_at' => 'datetime',
        'removed_at' => 'datetime',
        'estimated_value' => 'decimal:2',
        'weight' => 'decimal:2',
        'is_insured' => 'boolean',
    ];

    public const CATEGORIES = [
        'furniture' => 'Meubles',
        'electronics' => 'Électronique',
        'clothing' => 'Vêtements',
        'books' => 'Livres/Documents',
        'sports' => 'Sports/Loisirs',
        'tools' => 'Outils',
        'appliances' => 'Électroménager',
        'decor' => 'Décoration',
        'collectibles' => 'Collections',
        'seasonal' => 'Saisonnier',
        'vehicle' => 'Véhicule/Pièces',
        'other' => 'Autre',
    ];

    public const CONDITIONS = [
        'new' => 'Neuf',
        'excellent' => 'Excellent',
        'good' => 'Bon',
        'fair' => 'Correct',
        'poor' => 'Usé',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            $item->uuid = $item->uuid ?? Str::uuid();
            $item->stored_at = $item->stored_at ?? now();
        });
    }

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

    // Scopes
    public function scopeStored($query)
    {
        return $query->where('status', 'stored');
    }

    public function scopeRemoved($query)
    {
        return $query->where('status', 'removed');
    }

    public function scopeInsured($query)
    {
        return $query->where('is_insured', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('brand', 'like', "%{$search}%")
                ->orWhere('model', 'like', "%{$search}%")
                ->orWhere('serial_number', 'like', "%{$search}%");
        });
    }

    // Helpers
    public function isStored(): bool
    {
        return $this->status === 'stored';
    }

    public function isRemoved(): bool
    {
        return $this->status === 'removed';
    }

    public function markAsRemoved(string $note = null): void
    {
        $this->update([
            'status' => 'removed',
            'removed_at' => now(),
            'removal_note' => $note,
        ]);
    }

    public function markAsStored(): void
    {
        $this->update([
            'status' => 'stored',
            'removed_at' => null,
            'removal_note' => null,
        ]);
    }

    public function addPhoto(string $path): void
    {
        $photos = $this->photos ?? [];
        $photos[] = $path;
        $this->update(['photos' => $photos]);
    }

    public function removePhoto(string $path): void
    {
        $photos = $this->photos ?? [];
        $photos = array_filter($photos, fn($p) => $p !== $path);
        $this->update(['photos' => array_values($photos)]);
    }

    // Accessors
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Autre';
    }

    public function getConditionLabelAttribute(): string
    {
        return self::CONDITIONS[$this->condition] ?? 'Non spécifié';
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'stored' => 'Stocké',
            'removed' => 'Retiré',
            'damaged' => 'Endommagé',
            'lost' => 'Perdu',
            default => 'Inconnu',
        };
    }

    public function getVolumeAttribute(): ?float
    {
        if ($this->length && $this->width && $this->height) {
            return ($this->length * $this->width * $this->height) / 1000000; // m³
        }
        return null;
    }

    public function getDimensionsAttribute(): ?string
    {
        if ($this->length && $this->width && $this->height) {
            return "{$this->length} x {$this->width} x {$this->height} cm";
        }
        return null;
    }

    public function getFormattedValueAttribute(): string
    {
        if ($this->estimated_value) {
            return number_format($this->estimated_value, 2, ',', ' ') . ' ' . $this->currency;
        }
        return 'Non estimé';
    }

    public function getTotalValueAttribute(): float
    {
        return ($this->estimated_value ?? 0) * $this->quantity;
    }

    public function getFirstPhotoAttribute(): ?string
    {
        return $this->photos[0] ?? null;
    }

    public function getPhotosCountAttribute(): int
    {
        return count($this->photos ?? []);
    }
}
