<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TenantDailyStat extends Model
{
    protected $fillable = [
        'tenant_id',
        'stat_date',
        'daily_revenue',
        'monthly_revenue',
        'yearly_revenue',
        'payments_count',
        'payments_total',
        'new_customers',
        'active_customers',
        'churned_customers',
        'new_contracts',
        'active_contracts',
        'terminated_contracts',
        'average_contract_value',
        'total_boxes',
        'occupied_boxes',
        'available_boxes',
        'reserved_boxes',
        'maintenance_boxes',
        'occupation_rate',
        'invoices_sent',
        'invoices_paid',
        'invoices_overdue',
        'outstanding_balance',
        'new_leads',
        'converted_leads',
        'new_bookings',
        'conversion_rate',
    ];

    protected $casts = [
        'stat_date' => 'date',
        'daily_revenue' => 'decimal:2',
        'monthly_revenue' => 'decimal:2',
        'yearly_revenue' => 'decimal:2',
        'payments_total' => 'decimal:2',
        'average_contract_value' => 'decimal:2',
        'occupation_rate' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'conversion_rate' => 'decimal:2',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get or create stats for a specific date
     */
    public static function forDate(int $tenantId, $date): self
    {
        return self::firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'stat_date' => $date,
            ],
            [
                'daily_revenue' => 0,
                'monthly_revenue' => 0,
                'yearly_revenue' => 0,
            ]
        );
    }

    /**
     * Get stats for a date range
     */
    public static function forDateRange(int $tenantId, $startDate, $endDate)
    {
        return self::where('tenant_id', $tenantId)
            ->whereBetween('stat_date', [$startDate, $endDate])
            ->orderBy('stat_date')
            ->get();
    }

    /**
     * Get the latest stats for a tenant
     */
    public static function latest(int $tenantId): ?self
    {
        return self::where('tenant_id', $tenantId)
            ->orderByDesc('stat_date')
            ->first();
    }
}
