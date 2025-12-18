<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Customer;
use App\Models\PaymentRetryConfig;
use App\Models\PaymentRetryAttempt;
use App\Models\PaymentFailureAnalytics;
use App\Models\Payment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class SmartPaymentRetryService
{
    protected StripePaymentService $stripeService;

    public function __construct(StripePaymentService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /**
     * Create a new retry attempt for a failed payment
     */
    public function createRetryAttempt(Invoice $invoice, string $failureCode, string $failureMessage, ?string $declineCode = null): PaymentRetryAttempt
    {
        $config = PaymentRetryConfig::getOrCreateForTenant($invoice->tenant_id);

        $attempt = PaymentRetryAttempt::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'amount' => $invoice->total_amount,
            'currency' => 'EUR',
            'payment_method_id' => $invoice->customer->stripe_payment_method_id ?? null,
            'status' => 'pending',
            'attempt_number' => 1,
            'max_attempts' => $config->max_retries,
            'failure_code' => $failureCode,
            'failure_message' => $failureMessage,
            'decline_code' => $declineCode,
        ]);

        // Record analytics
        $this->recordFailureAnalytics($attempt, $declineCode ?? $failureCode);

        // Schedule first retry
        $this->scheduleRetry($attempt, $config);

        // Send notification
        if ($config->notify_customer_after_failure) {
            $this->sendFailureNotification($attempt, $config);
        }

        Log::info('Payment retry attempt created', [
            'attempt_id' => $attempt->id,
            'invoice_id' => $invoice->id,
            'scheduled_at' => $attempt->scheduled_at,
        ]);

        return $attempt;
    }

    /**
     * Process a scheduled retry attempt
     */
    public function processRetry(PaymentRetryAttempt $attempt): array
    {
        $attempt->markAsProcessing();

        $invoice = $attempt->invoice;
        $customer = $attempt->customer;

        // Check if card was updated
        if ($attempt->card_was_updated) {
            Log::info('Processing retry with updated card', ['attempt_id' => $attempt->id]);
        }

        try {
            // Create payment intent
            $result = $this->stripeService->createPaymentIntent(
                $invoice,
                $customer->stripe_customer_id ?? null
            );

            if (!$result) {
                throw new \Exception('Failed to create payment intent');
            }

            // For saved cards, attempt to charge immediately
            if ($customer->stripe_payment_method_id) {
                $chargeResult = $this->chargeWithSavedCard($result['intent_id'], $customer->stripe_payment_method_id);

                if ($chargeResult['success']) {
                    $this->handleSuccessfulPayment($attempt, $chargeResult);
                    return ['success' => true, 'attempt' => $attempt->fresh()];
                } else {
                    $this->handleFailedPayment($attempt, $chargeResult);
                    return ['success' => false, 'attempt' => $attempt->fresh(), 'error' => $chargeResult['error']];
                }
            }

            // No saved card - send payment link
            $this->sendPaymentLinkNotification($attempt, $result['client_secret']);

            return ['success' => false, 'awaiting_payment' => true, 'attempt' => $attempt->fresh()];

        } catch (\Exception $e) {
            Log::error('Payment retry failed', [
                'attempt_id' => $attempt->id,
                'error' => $e->getMessage(),
            ]);

            $this->handleFailedPayment($attempt, [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'processing_error',
            ]);

            return ['success' => false, 'attempt' => $attempt->fresh(), 'error' => $e->getMessage()];
        }
    }

    /**
     * Charge with saved card
     */
    protected function chargeWithSavedCard(string $intentId, string $paymentMethodId): array
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret_key'));

            $intent = \Stripe\PaymentIntent::retrieve($intentId);
            $intent->confirm([
                'payment_method' => $paymentMethodId,
            ]);

            if ($intent->status === 'succeeded') {
                return [
                    'success' => true,
                    'intent_id' => $intent->id,
                    'charge_id' => $intent->charges->data[0]->id ?? null,
                ];
            }

            return [
                'success' => false,
                'error' => 'Payment not completed',
                'code' => $intent->last_payment_error->code ?? 'unknown',
                'decline_code' => $intent->last_payment_error->decline_code ?? null,
            ];

        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getStripeCode(),
                'decline_code' => $e->getDeclineCode(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => 'processing_error',
            ];
        }
    }

    /**
     * Handle successful payment
     */
    protected function handleSuccessfulPayment(PaymentRetryAttempt $attempt, array $result): void
    {
        $attempt->markAsSucceeded($result['intent_id'], $result['charge_id'] ?? null);

        // Update invoice
        $invoice = $attempt->invoice;
        $invoice->update([
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        // Create payment record
        Payment::create([
            'tenant_id' => $attempt->tenant_id,
            'customer_id' => $attempt->customer_id,
            'invoice_id' => $attempt->invoice_id,
            'amount' => $attempt->amount,
            'payment_method' => 'stripe',
            'status' => 'completed',
            'stripe_payment_intent_id' => $result['intent_id'],
            'stripe_charge_id' => $result['charge_id'] ?? null,
            'processed_at' => now(),
        ]);

        // Update analytics
        if ($attempt->analytics) {
            $attempt->analytics->update([
                'eventually_recovered' => true,
                'recovery_attempt_number' => $attempt->attempt_number,
            ]);
        }

        // Send success notification
        $config = PaymentRetryConfig::getOrCreateForTenant($attempt->tenant_id);
        if ($config->notify_customer_after_success) {
            $this->sendSuccessNotification($attempt);
        }

        Log::info('Payment retry succeeded', [
            'attempt_id' => $attempt->id,
            'invoice_id' => $attempt->invoice_id,
        ]);
    }

    /**
     * Handle failed payment
     */
    protected function handleFailedPayment(PaymentRetryAttempt $attempt, array $result): void
    {
        $attempt->markAsFailed(
            $result['code'] ?? 'unknown',
            $result['error'] ?? 'Unknown error',
            $result['decline_code'] ?? null
        );

        // Record analytics
        $this->recordFailureAnalytics($attempt, $result['decline_code'] ?? $result['code'] ?? 'unknown');

        $config = PaymentRetryConfig::getOrCreateForTenant($attempt->tenant_id);

        if ($attempt->canRetry()) {
            // Schedule next retry
            $this->scheduleRetry($attempt, $config);

            if ($config->notify_customer_after_failure) {
                $this->sendFailureNotification($attempt, $config);
            }
        } else {
            // Final failure
            $this->handleFinalFailure($attempt, $config);
        }
    }

    /**
     * Handle final failure after all retries exhausted
     */
    protected function handleFinalFailure(PaymentRetryAttempt $attempt, PaymentRetryConfig $config): void
    {
        Log::warning('All payment retries exhausted', [
            'attempt_id' => $attempt->id,
            'invoice_id' => $attempt->invoice_id,
            'customer_id' => $attempt->customer_id,
        ]);

        // Notify admin
        if ($config->notify_admin_after_all_failures) {
            $this->notifyAdminOfFinalFailure($attempt);
        }

        // Apply final failure action
        switch ($config->final_failure_action) {
            case 'suspend':
                $this->suspendCustomerAccess($attempt, $config->grace_period_days);
                break;
            case 'downgrade':
                $this->downgradeCustomer($attempt);
                break;
        }

        // Update invoice status
        $attempt->invoice->update([
            'status' => 'overdue',
        ]);
    }

    /**
     * Schedule a retry attempt
     */
    protected function scheduleRetry(PaymentRetryAttempt $attempt, PaymentRetryConfig $config): void
    {
        $daysToWait = $config->getRetryIntervalForAttempt($attempt->attempt_number);
        $scheduledAt = $this->calculateOptimalRetryTime($attempt, $config, $daysToWait);

        if ($attempt->attempt_number < $attempt->max_attempts) {
            $attempt->scheduleNextRetry($scheduledAt);
        } else {
            $attempt->update([
                'next_retry_at' => $scheduledAt,
                'scheduled_at' => $scheduledAt,
                'status' => 'scheduled',
            ]);
        }
    }

    /**
     * Calculate optimal retry time based on analytics
     */
    protected function calculateOptimalRetryTime(PaymentRetryAttempt $attempt, PaymentRetryConfig $config, int $daysToWait): Carbon
    {
        $baseDate = now()->addDays($daysToWait);

        if (!$config->use_smart_timing) {
            // Use configured times
            $times = $config->retry_times;
            $time = $times[array_rand($times)];
            return $baseDate->setTimeFromTimeString($time);
        }

        // Get best times from analytics
        $bestTimes = PaymentFailureAnalytics::getBestRecoveryTimes($attempt->tenant_id);

        if (empty($bestTimes)) {
            // Default to 10:00
            return $baseDate->setTime(10, 0);
        }

        // Find the next available optimal time
        foreach ($bestTimes as $slot) {
            $candidateDate = $baseDate->copy()->setTime($slot['hour_of_day'], 0);

            // Adjust day of week if needed
            $targetDow = $slot['day_of_week'];
            $currentDow = $candidateDate->dayOfWeek;

            if ($currentDow !== $targetDow) {
                $daysToAdd = ($targetDow - $currentDow + 7) % 7;
                if ($daysToAdd === 0) $daysToAdd = 7;
                $candidateDate->addDays($daysToAdd);
            }

            // Check weekend avoidance
            if ($config->avoid_weekends && $candidateDate->isWeekend()) {
                continue;
            }

            // Check holiday avoidance
            if ($config->avoid_holidays && $this->isHoliday($candidateDate)) {
                continue;
            }

            return $candidateDate;
        }

        // Fallback
        $result = $baseDate->setTime(10, 0);

        // Avoid weekends
        if ($config->avoid_weekends) {
            while ($result->isWeekend()) {
                $result->addDay();
            }
        }

        return $result;
    }

    /**
     * Record failure analytics
     */
    protected function recordFailureAnalytics(PaymentRetryAttempt $attempt, string $failureReason): void
    {
        $customer = $attempt->customer;
        $now = now();

        // Calculate customer metrics
        $customerTenure = $customer->created_at ? $now->diffInDays($customer->created_at) : null;
        $successfulPayments = Payment::where('customer_id', $customer->id)
            ->where('status', 'completed')
            ->count();
        $failedPayments = PaymentRetryAttempt::where('customer_id', $customer->id)
            ->where('status', 'failed')
            ->count();

        PaymentFailureAnalytics::create([
            'tenant_id' => $attempt->tenant_id,
            'retry_attempt_id' => $attempt->id,
            'failure_reason' => $failureReason,
            'card_brand' => null, // Could be extracted from Stripe
            'card_last4' => null,
            'day_of_week' => $now->dayOfWeek,
            'hour_of_day' => $now->hour,
            'date' => $now->toDateString(),
            'is_first_of_month' => $now->day <= 5,
            'is_end_of_month' => $now->day >= 25,
            'customer_tenure_days' => $customerTenure,
            'previous_successful_payments' => $successfulPayments,
            'previous_failed_payments' => $failedPayments,
        ]);
    }

    /**
     * Send failure notification to customer
     */
    protected function sendFailureNotification(PaymentRetryAttempt $attempt, PaymentRetryConfig $config): void
    {
        $message = $config->getEscalationMessageForAttempt($attempt->attempt_number);
        if (!$message) return;

        $customer = $attempt->customer;
        $invoice = $attempt->invoice;

        // Generate card update link if allowed
        $cardUpdateUrl = null;
        if ($config->allow_card_update) {
            $token = $attempt->generateCardUpdateToken($config->card_update_link_expiry_hours);
            $cardUpdateUrl = route('payment.update-card', ['token' => $token]);
        }

        try {
            // Send email (simplified - would use a proper Mailable)
            Mail::raw(
                $message['body'] . ($cardUpdateUrl ? "\n\nMettez a jour votre carte: $cardUpdateUrl" : ''),
                function ($mail) use ($customer, $message) {
                    $mail->to($customer->email)
                        ->subject($message['subject']);
                }
            );

            $attempt->recordNotificationSent('failure_' . $attempt->attempt_number, 'email');

            Log::info('Payment failure notification sent', [
                'attempt_id' => $attempt->id,
                'customer_email' => $customer->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send payment failure notification', [
                'attempt_id' => $attempt->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send success notification
     */
    protected function sendSuccessNotification(PaymentRetryAttempt $attempt): void
    {
        $customer = $attempt->customer;

        try {
            Mail::raw(
                "Bonne nouvelle ! Votre paiement de {$attempt->formatted_amount} a ete traite avec succes.",
                function ($mail) use ($customer) {
                    $mail->to($customer->email)
                        ->subject('Paiement recu - Merci !');
                }
            );

            $attempt->recordNotificationSent('success', 'email');
        } catch (\Exception $e) {
            Log::error('Failed to send success notification', [
                'attempt_id' => $attempt->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send payment link notification
     */
    protected function sendPaymentLinkNotification(PaymentRetryAttempt $attempt, string $clientSecret): void
    {
        $customer = $attempt->customer;
        $paymentUrl = route('payment.checkout', ['invoice' => $attempt->invoice_id]);

        try {
            Mail::raw(
                "Veuillez completer votre paiement de {$attempt->formatted_amount}: $paymentUrl",
                function ($mail) use ($customer) {
                    $mail->to($customer->email)
                        ->subject('Action requise - Paiement en attente');
                }
            );

            $attempt->recordNotificationSent('payment_link', 'email');
        } catch (\Exception $e) {
            Log::error('Failed to send payment link notification', [
                'attempt_id' => $attempt->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Notify admin of final failure
     */
    protected function notifyAdminOfFinalFailure(PaymentRetryAttempt $attempt): void
    {
        // Would send to tenant admin email
        Log::warning('Payment final failure - admin notification needed', [
            'attempt_id' => $attempt->id,
            'customer_id' => $attempt->customer_id,
            'invoice_id' => $attempt->invoice_id,
            'amount' => $attempt->amount,
        ]);
    }

    /**
     * Suspend customer access
     */
    protected function suspendCustomerAccess(PaymentRetryAttempt $attempt, int $gracePeriodDays): void
    {
        $customer = $attempt->customer;
        $suspendAt = now()->addDays($gracePeriodDays);

        // Mark contracts for suspension
        $customer->contracts()
            ->where('status', 'active')
            ->update([
                'payment_suspended' => true,
                'suspension_scheduled_at' => $suspendAt,
            ]);

        Log::info('Customer access scheduled for suspension', [
            'customer_id' => $customer->id,
            'suspend_at' => $suspendAt,
        ]);
    }

    /**
     * Downgrade customer
     */
    protected function downgradeCustomer(PaymentRetryAttempt $attempt): void
    {
        // Implementation depends on your tier/plan system
        Log::info('Customer flagged for downgrade', [
            'customer_id' => $attempt->customer_id,
        ]);
    }

    /**
     * Check if date is a French holiday
     */
    protected function isHoliday(Carbon $date): bool
    {
        $holidays = [
            '01-01', // Nouvel An
            '05-01', // Fete du Travail
            '05-08', // Victoire 1945
            '07-14', // Fete Nationale
            '08-15', // Assomption
            '11-01', // Toussaint
            '11-11', // Armistice
            '12-25', // Noel
        ];

        return in_array($date->format('m-d'), $holidays);
    }

    /**
     * Get retry dashboard statistics
     */
    public function getDashboardStats(int $tenantId): array
    {
        $now = now();
        $startOfMonth = $now->copy()->startOfMonth();

        $attempts = PaymentRetryAttempt::where('tenant_id', $tenantId);

        return [
            'total_pending' => (clone $attempts)->whereIn('status', ['pending', 'scheduled'])->count(),
            'recovered_this_month' => (clone $attempts)
                ->where('status', 'succeeded')
                ->where('succeeded_at', '>=', $startOfMonth)
                ->count(),
            'amount_recovered_this_month' => (clone $attempts)
                ->where('status', 'succeeded')
                ->where('succeeded_at', '>=', $startOfMonth)
                ->sum('amount'),
            'failed_permanently' => (clone $attempts)
                ->where('status', 'failed')
                ->whereColumn('attempt_number', '>=', 'max_attempts')
                ->count(),
            'recovery_rate' => $this->calculateRecoveryRate($tenantId),
            'average_recovery_attempt' => $this->calculateAverageRecoveryAttempt($tenantId),
            'failure_reasons' => PaymentFailureAnalytics::getRecoveryRateByReason($tenantId),
            'best_times' => PaymentFailureAnalytics::getBestRecoveryTimes($tenantId),
        ];
    }

    /**
     * Calculate overall recovery rate
     */
    protected function calculateRecoveryRate(int $tenantId): float
    {
        $total = PaymentRetryAttempt::where('tenant_id', $tenantId)
            ->whereIn('status', ['succeeded', 'failed'])
            ->count();

        if ($total === 0) return 0;

        $recovered = PaymentRetryAttempt::where('tenant_id', $tenantId)
            ->where('status', 'succeeded')
            ->count();

        return round($recovered / $total * 100, 1);
    }

    /**
     * Calculate average attempt number for recovery
     */
    protected function calculateAverageRecoveryAttempt(int $tenantId): float
    {
        $avg = PaymentRetryAttempt::where('tenant_id', $tenantId)
            ->where('status', 'succeeded')
            ->avg('attempt_number');

        return round($avg ?? 0, 1);
    }
}
