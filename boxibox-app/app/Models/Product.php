<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    // Catégories disponibles
    public const CATEGORIES = [
        'lock' => 'Cadenas & Sécurité',
        'packaging' => 'Emballage',
        'supplies' => 'Fournitures',
        'electricity' => 'Électricité',
        'wifi' => 'Wi-Fi',
        'security' => 'Sécurité Avancée',
        'cleaning' => 'Nettoyage',
        'moving' => 'Déménagement',
        'insurance' => 'Assurance',
        'other' => 'Autre',
    ];

    // Types de produits
    public const TYPES = [
        'one_time' => 'Achat unique',
        'recurring' => 'Récurrent',
    ];

    // Périodes de facturation
    public const BILLING_PERIODS = [
        'monthly' => 'Mensuel',
        'quarterly' => 'Trimestriel',
        'yearly' => 'Annuel',
    ];

    // Unités de mesure
    public const UNITS = [
        'piece' => 'Pièce',
        'kg' => 'Kilogramme',
        'm2' => 'Mètre carré',
        'm3' => 'Mètre cube',
        'hour' => 'Heure',
        'day' => 'Jour',
        'month' => 'Mois',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sku',
        'type',
        'category',
        'price',
        'cost_price',
        'tax_rate',
        'billing_period',
        'unit',
        'stock_quantity',
        'min_quantity',
        'max_quantity',
        'track_inventory',
        'requires_contract',
        'image_path',
        'display_order',
        'is_featured',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'tax_rate' => 'decimal:2',
        'stock_quantity' => 'integer',
        'min_quantity' => 'integer',
        'max_quantity' => 'integer',
        'display_order' => 'integer',
        'track_inventory' => 'boolean',
        'requires_contract' => 'boolean',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'metadata' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_price',
        'category_label',
        'type_label',
    ];

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(ProductSaleItem::class);
    }

    public function contractAddons(): HasMany
    {
        return $this->hasMany(ContractAddon::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeOneTime($query)
    {
        return $query->where('type', 'one_time');
    }

    public function scopeRecurring($query)
    {
        return $query->where('type', 'recurring');
    }

    public function scopeInStock($query)
    {
        return $query->where(function ($q) {
            $q->where('track_inventory', false)
                ->orWhere('stock_quantity', '>', 0);
        });
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%");
        });
    }

    // Stock Management
    public function isInStock(): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        return $this->stock_quantity > 0;
    }

    public function hasStock(int $quantity): bool
    {
        if (!$this->track_inventory) {
            return true;
        }

        return $this->stock_quantity >= $quantity;
    }

    public function decrementStock(int $quantity = 1): void
    {
        if ($this->track_inventory) {
            $this->decrement('stock_quantity', $quantity);
        }
    }

    public function incrementStock(int $quantity = 1): void
    {
        if ($this->track_inventory) {
            $this->increment('stock_quantity', $quantity);
        }
    }

    public function setStock(int $quantity): void
    {
        if ($this->track_inventory) {
            $this->update(['stock_quantity' => $quantity]);
        }
    }

    // Helpers
    public function isOneTime(): bool
    {
        return $this->type === 'one_time';
    }

    public function isRecurring(): bool
    {
        return $this->type === 'recurring';
    }

    public function requiresActiveContract(): bool
    {
        return $this->requires_contract;
    }

    public function isLowStock(int $threshold = 5): bool
    {
        if (!$this->track_inventory) {
            return false;
        }

        return $this->stock_quantity <= $threshold;
    }

    public function isOutOfStock(): bool
    {
        if (!$this->track_inventory) {
            return false;
        }

        return $this->stock_quantity <= 0;
    }

    public function getMargin(): ?float
    {
        if (!$this->cost_price || $this->cost_price <= 0) {
            return null;
        }

        return (($this->price - $this->cost_price) / $this->price) * 100;
    }

    public function getPriceWithTax(): float
    {
        return $this->price * (1 + ($this->tax_rate / 100));
    }

    public function validateQuantity(int $quantity): bool
    {
        if ($quantity < $this->min_quantity) {
            return false;
        }

        if ($this->max_quantity && $quantity > $this->max_quantity) {
            return false;
        }

        return true;
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        $suffix = '';
        if ($this->isRecurring() && $this->billing_period) {
            $suffix = match ($this->billing_period) {
                'monthly' => '/mois',
                'quarterly' => '/trim.',
                'yearly' => '/an',
                default => '',
            };
        }

        return number_format($this->price, 2, ',', ' ') . ' €' . $suffix;
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getUnitLabelAttribute(): string
    {
        return self::UNITS[$this->unit ?? 'piece'] ?? $this->unit;
    }

    public function getBillingPeriodLabelAttribute(): ?string
    {
        if (!$this->billing_period) {
            return null;
        }

        return self::BILLING_PERIODS[$this->billing_period] ?? $this->billing_period;
    }

    public function getStockStatusAttribute(): string
    {
        if (!$this->track_inventory) {
            return 'unlimited';
        }

        if ($this->stock_quantity <= 0) {
            return 'out_of_stock';
        }

        if ($this->stock_quantity <= 5) {
            return 'low_stock';
        }

        return 'in_stock';
    }

    public function getStockStatusLabelAttribute(): string
    {
        return match ($this->stock_status) {
            'unlimited' => 'Illimité',
            'out_of_stock' => 'Rupture',
            'low_stock' => 'Stock faible',
            'in_stock' => 'En stock',
            default => 'Inconnu',
        };
    }

    // For Invoice Generation
    public function toInvoiceItem(int $quantity = 1): array
    {
        $subtotal = $this->price * $quantity;
        $taxAmount = $subtotal * ($this->tax_rate / 100);

        return [
            'type' => 'product',
            'product_id' => $this->id,
            'description' => $this->name,
            'quantity' => $quantity,
            'unit_price' => $this->price,
            'tax_rate' => $this->tax_rate,
            'tax_amount' => $taxAmount,
            'discount' => 0,
            'total' => $subtotal + $taxAmount,
        ];
    }
}
