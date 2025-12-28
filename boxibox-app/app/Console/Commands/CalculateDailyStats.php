<?php

namespace App\Console\Commands;

use App\Models\Box;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Site;
use App\Models\SiteDailyStat;
use App\Models\Tenant;
use App\Models\TenantDailyStat;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateDailyStats extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'stats:calculate-daily
                            {--date= : The date to calculate stats for (defaults to yesterday)}
                            {--tenant= : Calculate for specific tenant ID only}';

    /**
     * The console command description.
     */
    protected $description = 'Calculate and store daily statistics for all tenants and sites';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::yesterday();

        $tenantId = $this->option('tenant');

        $this->info("Calculating daily stats for {$date->format('Y-m-d')}...");

        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        $bar = $this->output->createProgressBar($tenants->count());
        $bar->start();

        foreach ($tenants as $tenant) {
            try {
                $this->calculateTenantStats($tenant, $date);
                $this->calculateSiteStats($tenant, $date);
            } catch (\Exception $e) {
                Log::error("Failed to calculate stats for tenant {$tenant->id}: " . $e->getMessage());
                $this->error("Error for tenant {$tenant->id}: " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Daily stats calculation completed.');

        return Command::SUCCESS;
    }

    /**
     * Calculate statistics for a tenant
     */
    private function calculateTenantStats(Tenant $tenant, Carbon $date): void
    {
        $tenantId = $tenant->id;
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();
        $startOfMonth = $date->copy()->startOfMonth();
        $startOfYear = $date->copy()->startOfYear();

        // Payment stats
        $dailyPayments = Payment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startOfDay, $endOfDay])
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $monthlyRevenue = Payment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startOfMonth, $endOfDay])
            ->where('status', 'completed')
            ->sum('amount');

        $yearlyRevenue = Payment::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startOfYear, $endOfDay])
            ->where('status', 'completed')
            ->sum('amount');

        // Customer stats
        $newCustomers = Customer::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        $activeCustomers = Customer::where('tenant_id', $tenantId)
            ->whereHas('contracts', fn($q) => $q->where('status', 'active'))
            ->count();

        // Contract stats
        $newContracts = Contract::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        $activeContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->count();

        $terminatedContracts = Contract::where('tenant_id', $tenantId)
            ->whereBetween('actual_end_date', [$startOfDay, $endOfDay])
            ->count();

        $avgContractValue = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->avg('monthly_price') ?? 0;

        // Box stats using single optimized query
        $boxStats = Box::where('tenant_id', $tenantId)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                SUM(CASE WHEN status = 'reserved' THEN 1 ELSE 0 END) as reserved,
                SUM(CASE WHEN status = 'maintenance' THEN 1 ELSE 0 END) as maintenance
            ")
            ->first();

        $totalBoxes = $boxStats->total ?? 0;
        $occupationRate = $totalBoxes > 0
            ? round(($boxStats->occupied / $totalBoxes) * 100, 2)
            : 0;

        // Invoice stats
        $invoicesSent = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startOfDay, $endOfDay])
            ->count();

        $invoicesPaid = Invoice::where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [$startOfDay, $endOfDay])
            ->where('status', 'paid')
            ->count();

        $invoicesOverdue = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'overdue')
            ->count();

        $outstandingBalance = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->selectRaw('COALESCE(SUM(total - paid_amount), 0) as balance')
            ->value('balance') ?? 0;

        // Lead/Booking stats
        $newLeads = 0;
        $convertedLeads = 0;
        if (class_exists(Lead::class)) {
            $newLeads = Lead::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count();

            $convertedLeads = Lead::where('tenant_id', $tenantId)
                ->whereBetween('converted_at', [$startOfDay, $endOfDay])
                ->count();
        }

        $newBookings = 0;
        if (class_exists(Booking::class)) {
            $newBookings = Booking::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count();
        }

        // Calculate conversion rate safely
        $conversionRate = $newLeads > 0
            ? round(($convertedLeads / $newLeads) * 100, 2)
            : 0;

        // Upsert the stats
        TenantDailyStat::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'stat_date' => $date->format('Y-m-d'),
            ],
            [
                'daily_revenue' => $dailyPayments->total ?? 0,
                'monthly_revenue' => $monthlyRevenue,
                'yearly_revenue' => $yearlyRevenue,
                'payments_count' => $dailyPayments->count ?? 0,
                'payments_total' => $dailyPayments->total ?? 0,
                'new_customers' => $newCustomers,
                'active_customers' => $activeCustomers,
                'churned_customers' => 0, // TODO: Calculate churn
                'new_contracts' => $newContracts,
                'active_contracts' => $activeContracts,
                'terminated_contracts' => $terminatedContracts,
                'average_contract_value' => $avgContractValue,
                'total_boxes' => $totalBoxes,
                'occupied_boxes' => $boxStats->occupied ?? 0,
                'available_boxes' => $boxStats->available ?? 0,
                'reserved_boxes' => $boxStats->reserved ?? 0,
                'maintenance_boxes' => $boxStats->maintenance ?? 0,
                'occupation_rate' => $occupationRate,
                'invoices_sent' => $invoicesSent,
                'invoices_paid' => $invoicesPaid,
                'invoices_overdue' => $invoicesOverdue,
                'outstanding_balance' => $outstandingBalance,
                'new_leads' => $newLeads,
                'converted_leads' => $convertedLeads,
                'new_bookings' => $newBookings,
                'conversion_rate' => $conversionRate,
            ]
        );
    }

    /**
     * Calculate statistics for each site of a tenant
     */
    private function calculateSiteStats(Tenant $tenant, Carbon $date): void
    {
        $sites = Site::where('tenant_id', $tenant->id)->get();
        $startOfDay = $date->copy()->startOfDay();
        $endOfDay = $date->copy()->endOfDay();

        foreach ($sites as $site) {
            // Box stats for this site
            $boxStats = Box::where('site_id', $site->id)
                ->selectRaw("
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) as occupied,
                    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) as available,
                    SUM(CASE WHEN status = 'occupied' THEN current_price ELSE 0 END) as daily_revenue,
                    SUM(current_price) as potential_revenue
                ")
                ->first();

            $totalBoxes = $boxStats->total ?? 0;
            $occupationRate = $totalBoxes > 0
                ? round((($boxStats->occupied ?? 0) / $totalBoxes) * 100, 2)
                : 0;

            $lostRevenue = ($boxStats->potential_revenue ?? 0) - ($boxStats->daily_revenue ?? 0);

            // Contract activity for this site
            $newContracts = Contract::where('site_id', $site->id)
                ->whereBetween('created_at', [$startOfDay, $endOfDay])
                ->count();

            $terminatedContracts = Contract::where('site_id', $site->id)
                ->whereBetween('actual_end_date', [$startOfDay, $endOfDay])
                ->count();

            $activeContracts = Contract::where('site_id', $site->id)
                ->where('status', 'active')
                ->count();

            SiteDailyStat::updateOrCreate(
                [
                    'site_id' => $site->id,
                    'stat_date' => $date->format('Y-m-d'),
                ],
                [
                    'total_boxes' => $totalBoxes,
                    'occupied_boxes' => $boxStats->occupied ?? 0,
                    'available_boxes' => $boxStats->available ?? 0,
                    'occupation_rate' => $occupationRate,
                    'daily_revenue' => $boxStats->daily_revenue ?? 0,
                    'potential_revenue' => $boxStats->potential_revenue ?? 0,
                    'lost_revenue' => $lostRevenue,
                    'new_contracts' => $newContracts,
                    'terminated_contracts' => $terminatedContracts,
                    'active_contracts' => $activeContracts,
                ]
            );
        }
    }
}
