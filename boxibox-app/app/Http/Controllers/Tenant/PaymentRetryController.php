<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\SmartPaymentRetryService;
use App\Models\PaymentRetryAttempt;
use App\Models\PaymentRetryConfig;
use App\Models\PaymentFailureAnalytics;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PaymentRetryController extends Controller
{
    protected SmartPaymentRetryService $retryService;

    public function __construct(SmartPaymentRetryService $retryService)
    {
        $this->retryService = $retryService;
    }

    /**
     * Dashboard for payment retries
     */
    public function index(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $stats = $this->retryService->getDashboardStats($tenantId);

        $pendingAttempts = PaymentRetryAttempt::where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'scheduled'])
            ->with(['customer', 'invoice'])
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        $recentAttempts = PaymentRetryAttempt::where('tenant_id', $tenantId)
            ->whereIn('status', ['succeeded', 'failed'])
            ->with(['customer', 'invoice'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        return Inertia::render('Tenant/Payments/RetryDashboard', [
            'stats' => $stats,
            'pendingAttempts' => $pendingAttempts,
            'recentAttempts' => $recentAttempts,
        ]);
    }

    /**
     * List all retry attempts
     */
    public function attempts(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $status = $request->input('status');

        $query = PaymentRetryAttempt::where('tenant_id', $tenantId)
            ->with(['customer', 'invoice']);

        if ($status) {
            $query->where('status', $status);
        }

        $attempts = $query->orderByDesc('created_at')->paginate(50);

        return Inertia::render('Tenant/Payments/RetryAttempts', [
            'attempts' => $attempts,
            'statusFilter' => $status,
        ]);
    }

    /**
     * Show retry attempt details
     */
    public function show(PaymentRetryAttempt $attempt)
    {
        $this->authorize('view', $attempt);

        $attempt->load(['customer', 'invoice', 'analytics']);

        return Inertia::render('Tenant/Payments/RetryAttemptDetails', [
            'attempt' => $attempt,
        ]);
    }

    /**
     * Manually trigger a retry
     */
    public function manualRetry(PaymentRetryAttempt $attempt)
    {
        $this->authorize('update', $attempt);

        if (!$attempt->canRetry() && $attempt->status !== 'failed') {
            return back()->with('error', 'Cette tentative ne peut pas etre relancee');
        }

        // Reset to pending and schedule immediately
        $attempt->update([
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        $result = $this->retryService->processRetry($attempt);

        if ($result['success']) {
            return back()->with('success', 'Paiement recupere avec succes!');
        }

        return back()->with('error', 'Echec du paiement: ' . ($result['error'] ?? 'Erreur inconnue'));
    }

    /**
     * Cancel a retry attempt
     */
    public function cancel(PaymentRetryAttempt $attempt)
    {
        $this->authorize('update', $attempt);

        if (!in_array($attempt->status, ['pending', 'scheduled'])) {
            return back()->with('error', 'Cette tentative ne peut pas etre annulee');
        }

        $attempt->cancel();

        return back()->with('success', 'Tentative annulee');
    }

    /**
     * Reschedule a retry
     */
    public function reschedule(Request $request, PaymentRetryAttempt $attempt)
    {
        $this->authorize('update', $attempt);

        $validated = $request->validate([
            'scheduled_at' => 'required|date|after:now',
        ]);

        $attempt->markAsScheduled(new \DateTime($validated['scheduled_at']));

        return back()->with('success', 'Tentative replanifiee');
    }

    /**
     * Configuration settings
     */
    public function config(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $config = PaymentRetryConfig::getOrCreateForTenant($tenantId);

        return Inertia::render('Tenant/Payments/RetryConfig', [
            'config' => $config,
        ]);
    }

    /**
     * Update configuration
     */
    public function updateConfig(Request $request)
    {
        $tenantId = $request->user()->tenant_id;

        $validated = $request->validate([
            'max_retries' => 'required|integer|min:1|max:10',
            'retry_intervals' => 'required|array|min:1',
            'retry_intervals.*' => 'integer|min:1|max:30',
            'retry_times' => 'required|array|min:1',
            'retry_times.*' => 'date_format:H:i',
            'use_smart_timing' => 'boolean',
            'avoid_weekends' => 'boolean',
            'avoid_holidays' => 'boolean',
            'notify_customer_before' => 'boolean',
            'notify_hours_before' => 'integer|min:1|max:72',
            'notify_customer_after_failure' => 'boolean',
            'notify_customer_after_success' => 'boolean',
            'notify_admin_after_all_failures' => 'boolean',
            'allow_card_update' => 'boolean',
            'card_update_link_expiry_hours' => 'integer|min:1|max:168',
            'final_failure_action' => 'required|in:suspend,downgrade,none',
            'grace_period_days' => 'integer|min:0|max:30',
            'escalation_messages' => 'nullable|array',
        ]);

        $config = PaymentRetryConfig::getOrCreateForTenant($tenantId);
        $config->update($validated);

        return back()->with('success', 'Configuration mise a jour');
    }

    /**
     * Analytics page
     */
    public function analytics(Request $request)
    {
        $tenantId = $request->user()->tenant_id;
        $period = $request->input('period', '30');

        $startDate = now()->subDays((int) $period);

        // Recovery rate over time
        $recoveryByDay = PaymentRetryAttempt::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, status, COUNT(*) as count')
            ->groupBy('date', 'status')
            ->orderBy('date')
            ->get()
            ->groupBy('date')
            ->map(function ($group) {
                $total = $group->sum('count');
                $succeeded = $group->where('status', 'succeeded')->sum('count');
                return [
                    'total' => $total,
                    'succeeded' => $succeeded,
                    'rate' => $total > 0 ? round($succeeded / $total * 100, 1) : 0,
                ];
            });

        // Failure reasons distribution
        $failureReasons = PaymentFailureAnalytics::where('tenant_id', $tenantId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('failure_reason, COUNT(*) as count, SUM(eventually_recovered) as recovered')
            ->groupBy('failure_reason')
            ->get()
            ->map(function ($item) {
                return [
                    'reason' => $item->failure_reason,
                    'count' => $item->count,
                    'recovered' => $item->recovered,
                    'rate' => $item->count > 0 ? round($item->recovered / $item->count * 100, 1) : 0,
                ];
            });

        // Best recovery times heatmap
        $heatmap = PaymentFailureAnalytics::where('tenant_id', $tenantId)
            ->where('eventually_recovered', true)
            ->selectRaw('day_of_week, hour_of_day, COUNT(*) as count')
            ->groupBy('day_of_week', 'hour_of_day')
            ->get();

        // Amount recovered
        $amountRecovered = PaymentRetryAttempt::where('tenant_id', $tenantId)
            ->where('status', 'succeeded')
            ->where('succeeded_at', '>=', $startDate)
            ->sum('amount');

        return Inertia::render('Tenant/Payments/RetryAnalytics', [
            'period' => $period,
            'recoveryByDay' => $recoveryByDay,
            'failureReasons' => $failureReasons,
            'heatmap' => $heatmap,
            'amountRecovered' => $amountRecovered,
            'stats' => $this->retryService->getDashboardStats($tenantId),
        ]);
    }

    /**
     * Handle card update from customer
     */
    public function cardUpdateForm(string $token)
    {
        $attempt = PaymentRetryAttempt::where('card_update_token', $token)->first();

        if (!$attempt || !$attempt->isCardUpdateTokenValid()) {
            return Inertia::render('Public/CardUpdateExpired');
        }

        return Inertia::render('Public/CardUpdate', [
            'token' => $token,
            'amount' => $attempt->formatted_amount,
            'invoiceNumber' => $attempt->invoice->invoice_number,
        ]);
    }

    /**
     * Process card update
     */
    public function processCardUpdate(Request $request, string $token)
    {
        $attempt = PaymentRetryAttempt::where('card_update_token', $token)->first();

        if (!$attempt || !$attempt->isCardUpdateTokenValid()) {
            return response()->json(['error' => 'Lien expire'], 400);
        }

        $validated = $request->validate([
            'payment_method_id' => 'required|string',
        ]);

        // Update customer's payment method
        $customer = $attempt->customer;
        $customer->update([
            'stripe_payment_method_id' => $validated['payment_method_id'],
        ]);

        // Mark card as updated and schedule immediate retry
        $attempt->markCardAsUpdated();
        $attempt->update([
            'payment_method_id' => $validated['payment_method_id'],
            'status' => 'scheduled',
            'scheduled_at' => now(),
        ]);

        // Process the retry immediately
        $result = $this->retryService->processRetry($attempt->fresh());

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success']
                ? 'Paiement effectue avec succes!'
                : 'Carte mise a jour, nouvelle tentative programmee',
        ]);
    }
}
