<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ValetSettings extends Model
{
    use HasFactory;

    protected $table = 'valet_settings';

    protected $fillable = [
        'tenant_id',
        'site_id',
        'valet_enabled',
        'allow_same_day',
        'min_lead_time_hours',
        'max_items_per_order',
        'earliest_time',
        'latest_time',
        'available_days',
        'time_slots',
        'free_delivery_threshold',
        'terms_conditions',
        'pickup_instructions',
        'delivery_instructions',
    ];

    protected $casts = [
        'valet_enabled' => 'boolean',
        'allow_same_day' => 'boolean',
        'min_lead_time_hours' => 'integer',
        'max_items_per_order' => 'integer',
        'earliest_time' => 'datetime:H:i',
        'latest_time' => 'datetime:H:i',
        'available_days' => 'array',
        'time_slots' => 'array',
        'free_delivery_threshold' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public static function getForTenant(int $tenantId, ?int $siteId = null): self
    {
        $settings = static::where('tenant_id', $tenantId)
            ->where('site_id', $siteId)
            ->first();

        if (!$settings) {
            $settings = static::where('tenant_id', $tenantId)
                ->whereNull('site_id')
                ->first();
        }

        if (!$settings) {
            $settings = new static([
                'tenant_id' => $tenantId,
                'site_id' => $siteId,
                'valet_enabled' => true,
                'allow_same_day' => false,
                'min_lead_time_hours' => 24,
                'max_items_per_order' => 50,
                'available_days' => [1, 2, 3, 4, 5],
                'time_slots' => [
                    ['label' => 'Matin', 'value' => 'morning', 'start' => '08:00', 'end' => '12:00'],
                    ['label' => 'Après-midi', 'value' => 'afternoon', 'start' => '12:00', 'end' => '18:00'],
                    ['label' => 'Soir', 'value' => 'evening', 'start' => '18:00', 'end' => '20:00'],
                ],
            ]);
        }

        return $settings;
    }

    public function isDayAvailable(int $dayOfWeek): bool
    {
        return in_array($dayOfWeek, $this->available_days ?? []);
    }

    public function getAvailableDaysLabels(): array
    {
        $days = [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];

        return collect($this->available_days ?? [])
            ->map(fn($day) => $days[$day] ?? '')
            ->filter()
            ->values()
            ->toArray();
    }
}
