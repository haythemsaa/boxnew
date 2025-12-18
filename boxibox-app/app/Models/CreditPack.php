<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Packs de crédits Email/SMS disponibles à l'achat
 */
class CreditPack extends Model
{
    protected $fillable = [
        'name',
        'type',
        'credits',
        'price',
        'currency',
        'price_per_unit',
        'validity_days',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'credits' => 'integer',
        'price' => 'decimal:2',
        'price_per_unit' => 'decimal:4',
        'validity_days' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            // Calculer le prix unitaire automatiquement
            if ($model->credits > 0) {
                $model->price_per_unit = $model->price / $model->credits;
            }
        });
    }

    // Accessors
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2, ',', ' ') . ' ' . $this->currency;
    }

    public function getFormattedPricePerUnitAttribute(): string
    {
        return number_format($this->price_per_unit, 4, ',', ' ') . ' ' . $this->currency;
    }

    public function getExpiresInTextAttribute(): string
    {
        if (!$this->validity_days) {
            return 'Sans expiration';
        }
        return $this->validity_days . ' jours';
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeEmail($query)
    {
        return $query->where('type', 'email');
    }

    public function scopeSms($query)
    {
        return $query->where('type', 'sms');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('credits');
    }

    // Static methods

    /**
     * Créer les packs par défaut
     */
    public static function seedDefaults(): void
    {
        $packs = [
            // Packs Email
            ['name' => 'Pack 1 000 Emails', 'type' => 'email', 'credits' => 1000, 'price' => 5, 'sort_order' => 1],
            ['name' => 'Pack 5 000 Emails', 'type' => 'email', 'credits' => 5000, 'price' => 20, 'sort_order' => 2],
            ['name' => 'Pack 10 000 Emails', 'type' => 'email', 'credits' => 10000, 'price' => 35, 'sort_order' => 3],
            ['name' => 'Pack 50 000 Emails', 'type' => 'email', 'credits' => 50000, 'price' => 150, 'sort_order' => 4],

            // Packs SMS
            ['name' => 'Pack 50 SMS', 'type' => 'sms', 'credits' => 50, 'price' => 5, 'sort_order' => 10],
            ['name' => 'Pack 100 SMS', 'type' => 'sms', 'credits' => 100, 'price' => 9, 'sort_order' => 11],
            ['name' => 'Pack 500 SMS', 'type' => 'sms', 'credits' => 500, 'price' => 40, 'sort_order' => 12],
            ['name' => 'Pack 1 000 SMS', 'type' => 'sms', 'credits' => 1000, 'price' => 75, 'sort_order' => 13],
        ];

        foreach ($packs as $pack) {
            static::updateOrCreate(
                ['name' => $pack['name'], 'type' => $pack['type']],
                array_merge($pack, [
                    'currency' => 'EUR',
                    'validity_days' => 365, // 1 an de validité
                    'is_active' => true,
                ])
            );
        }
    }
}
