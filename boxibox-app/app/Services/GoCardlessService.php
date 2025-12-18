<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\GocardlessPayment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\SepaMandate;
use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoCardlessService
{
    protected string $baseUrl;
    protected ?string $accessToken;
    protected bool $isLive;

    public function __construct()
    {
        $this->isLive = config('services.gocardless.environment', 'sandbox') === 'live';
        $this->baseUrl = $this->isLive
            ? 'https://api.gocardless.com'
            : 'https://api-sandbox.gocardless.com';
        $this->accessToken = config('services.gocardless.access_token');
    }

    /**
     * Set tenant-specific access token if using OAuth
     */
    public function forTenant(Tenant $tenant): self
    {
        if ($tenant->gocardless_access_token) {
            $this->accessToken = $tenant->gocardless_access_token;
        }
        return $this;
    }

    /**
     * Create a redirect flow for mandate setup
     */
    public function createRedirectFlow(
        Customer $customer,
        string $successUrl,
        string $sessionToken
    ): array {
        $response = $this->request('POST', '/redirect_flows', [
            'redirect_flows' => [
                'description' => 'Prelevement automatique ' . config('app.name'),
                'session_token' => $sessionToken,
                'success_redirect_url' => $successUrl,
                'scheme' => 'sepa_core',
                'prefilled_customer' => [
                    'email' => $customer->email,
                    'given_name' => $customer->first_name,
                    'family_name' => $customer->last_name,
                    'company_name' => $customer->company_name,
                    'address_line1' => $customer->address,
                    'city' => $customer->city,
                    'postal_code' => $customer->postal_code,
                    'country_code' => $customer->country ?? 'FR',
                ],
            ],
        ]);

        return $response['redirect_flows'] ?? $response;
    }

    /**
     * Complete redirect flow and get mandate
     */
    public function completeRedirectFlow(string $redirectFlowId, string $sessionToken): array
    {
        $response = $this->request('POST', "/redirect_flows/{$redirectFlowId}/actions/complete", [
            'data' => [
                'session_token' => $sessionToken,
            ],
        ]);

        return $response['redirect_flows'] ?? $response;
    }

    /**
     * Get mandate details
     */
    public function getMandate(string $mandateId): array
    {
        $response = $this->request('GET', "/mandates/{$mandateId}");
        return $response['mandates'] ?? $response;
    }

    /**
     * Cancel a mandate
     */
    public function cancelMandate(string $mandateId, array $metadata = []): array
    {
        $response = $this->request('POST', "/mandates/{$mandateId}/actions/cancel", [
            'data' => [
                'metadata' => $metadata,
            ],
        ]);
        return $response['mandates'] ?? $response;
    }

    /**
     * Create a payment
     */
    public function createPayment(
        SepaMandate $mandate,
        float $amount,
        string $description,
        ?string $chargeDate = null,
        array $metadata = []
    ): array {
        $data = [
            'payments' => [
                'amount' => (int) ($amount * 100), // Convert to cents
                'currency' => 'EUR',
                'description' => Str::limit($description, 140),
                'metadata' => $metadata,
                'links' => [
                    'mandate' => $mandate->gocardless_mandate_id,
                ],
            ],
        ];

        if ($chargeDate) {
            $data['payments']['charge_date'] = $chargeDate;
        }

        $response = $this->request('POST', '/payments', $data);
        return $response['payments'] ?? $response;
    }

    /**
     * Get payment details
     */
    public function getPayment(string $paymentId): array
    {
        $response = $this->request('GET', "/payments/{$paymentId}");
        return $response['payments'] ?? $response;
    }

    /**
     * Cancel a payment
     */
    public function cancelPayment(string $paymentId, array $metadata = []): array
    {
        $response = $this->request('POST', "/payments/{$paymentId}/actions/cancel", [
            'data' => [
                'metadata' => $metadata,
            ],
        ]);
        return $response['payments'] ?? $response;
    }

    /**
     * Retry a failed payment
     */
    public function retryPayment(string $paymentId, ?string $chargeDate = null, array $metadata = []): array
    {
        $data = ['data' => ['metadata' => $metadata]];

        if ($chargeDate) {
            $data['data']['charge_date'] = $chargeDate;
        }

        $response = $this->request('POST', "/payments/{$paymentId}/actions/retry", $data);
        return $response['payments'] ?? $response;
    }

    /**
     * Create a customer in GoCardless
     */
    public function createCustomer(Customer $customer, array $metadata = []): array
    {
        $response = $this->request('POST', '/customers', [
            'customers' => [
                'email' => $customer->email,
                'given_name' => $customer->first_name,
                'family_name' => $customer->last_name,
                'company_name' => $customer->company_name,
                'address_line1' => $customer->address,
                'city' => $customer->city,
                'postal_code' => $customer->postal_code,
                'country_code' => $customer->country ?? 'FR',
                'metadata' => array_merge([
                    'internal_customer_id' => (string) $customer->id,
                    'tenant_id' => (string) $customer->tenant_id,
                ], $metadata),
            ],
        ]);

        return $response['customers'] ?? $response;
    }

    /**
     * Get customer bank accounts
     */
    public function getCustomerBankAccounts(string $customerId): array
    {
        $response = $this->request('GET', "/customer_bank_accounts?customer={$customerId}");
        return $response['customer_bank_accounts'] ?? [];
    }

    /**
     * Create payment for invoice
     */
    public function chargeInvoice(Invoice $invoice): ?GocardlessPayment
    {
        $customer = $invoice->customer;
        $mandate = $customer->sepaMandates()->active()->first();

        if (!$mandate || !$mandate->gocardless_mandate_id) {
            Log::warning('No active GoCardless mandate for customer', [
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
            ]);
            return null;
        }

        try {
            $gcPayment = $this->createPayment(
                $mandate,
                $invoice->total,
                "Facture {$invoice->invoice_number}",
                null,
                [
                    'invoice_id' => (string) $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'tenant_id' => (string) $invoice->tenant_id,
                ]
            );

            // Create local payment record
            $payment = GocardlessPayment::create([
                'tenant_id' => $invoice->tenant_id,
                'customer_id' => $customer->id,
                'sepa_mandate_id' => $mandate->id,
                'invoice_id' => $invoice->id,
                'gocardless_payment_id' => $gcPayment['id'],
                'amount' => $invoice->total,
                'currency' => 'EUR',
                'description' => "Facture {$invoice->invoice_number}",
                'status' => $gcPayment['status'],
                'charge_date' => $gcPayment['charge_date'] ?? null,
                'metadata' => [
                    'invoice_number' => $invoice->invoice_number,
                ],
            ]);

            return $payment;

        } catch (\Exception $e) {
            Log::error('GoCardless payment creation failed', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Process webhook event
     */
    public function processWebhook(array $event): void
    {
        $resourceType = $event['resource_type'] ?? null;
        $action = $event['action'] ?? null;
        $links = $event['links'] ?? [];

        Log::info('Processing GoCardless webhook', [
            'event_id' => $event['id'] ?? null,
            'resource_type' => $resourceType,
            'action' => $action,
        ]);

        match ($resourceType) {
            'mandates' => $this->handleMandateEvent($action, $links, $event),
            'payments' => $this->handlePaymentEvent($action, $links, $event),
            default => Log::info('Unhandled GoCardless event type', ['type' => $resourceType]),
        };
    }

    /**
     * Handle mandate webhook events
     */
    protected function handleMandateEvent(string $action, array $links, array $event): void
    {
        $mandateId = $links['mandate'] ?? null;
        if (!$mandateId) return;

        $mandate = SepaMandate::where('gocardless_mandate_id', $mandateId)->first();
        if (!$mandate) {
            Log::warning('GoCardless mandate not found locally', ['mandate_id' => $mandateId]);
            return;
        }

        match ($action) {
            'created' => $mandate->update(['status' => 'pending_submission']),
            'submitted' => $mandate->update(['status' => 'submitted']),
            'active' => $mandate->update(['status' => 'active', 'activated_at' => now()]),
            'failed' => $mandate->update(['status' => 'failed', 'failed_at' => now()]),
            'cancelled' => $mandate->update(['status' => 'cancelled', 'cancelled_at' => now()]),
            'expired' => $mandate->update(['status' => 'expired']),
            'reinstated' => $mandate->update(['status' => 'active']),
            'transferred' => Log::info('Mandate transferred', ['mandate_id' => $mandateId]),
            default => null,
        };
    }

    /**
     * Handle payment webhook events
     */
    protected function handlePaymentEvent(string $action, array $links, array $event): void
    {
        $paymentId = $links['payment'] ?? null;
        if (!$paymentId) return;

        $gcPayment = GocardlessPayment::where('gocardless_payment_id', $paymentId)->first();
        if (!$gcPayment) {
            Log::warning('GoCardless payment not found locally', ['payment_id' => $paymentId]);
            return;
        }

        match ($action) {
            'created' => $gcPayment->update(['status' => 'pending_submission']),
            'submitted' => $gcPayment->update(['status' => 'submitted']),
            'confirmed' => $this->handlePaymentConfirmed($gcPayment),
            'paid_out' => $this->handlePaymentPaidOut($gcPayment, $event),
            'failed' => $this->handlePaymentFailed($gcPayment, $event),
            'cancelled' => $gcPayment->update(['status' => 'cancelled', 'cancelled_at' => now()]),
            'customer_approval_denied' => $gcPayment->update(['status' => 'customer_approval_denied']),
            'charged_back' => $this->handleChargeback($gcPayment, $event),
            default => null,
        };
    }

    protected function handlePaymentConfirmed(GocardlessPayment $gcPayment): void
    {
        $gcPayment->update(['status' => 'confirmed']);

        // Update invoice status
        if ($gcPayment->invoice) {
            $gcPayment->invoice->update(['status' => 'pending_payment']);
        }
    }

    protected function handlePaymentPaidOut(GocardlessPayment $gcPayment, array $event): void
    {
        $gcPayment->update([
            'status' => 'paid_out',
            'paid_out_at' => now(),
        ]);

        // Create local payment record and mark invoice as paid
        if ($gcPayment->invoice && !$gcPayment->payment_id) {
            $payment = Payment::create([
                'tenant_id' => $gcPayment->tenant_id,
                'customer_id' => $gcPayment->customer_id,
                'invoice_id' => $gcPayment->invoice_id,
                'amount' => $gcPayment->amount,
                'payment_method' => 'sepa_direct_debit',
                'paid_at' => now(),
                'reference' => $gcPayment->gocardless_payment_id,
                'status' => 'completed',
                'notes' => 'Prelevement SEPA GoCardless',
            ]);

            $gcPayment->update(['payment_id' => $payment->id]);

            // Mark invoice as paid
            $gcPayment->invoice->update(['status' => 'paid', 'paid_at' => now()]);

            // Update mandate collection stats
            $gcPayment->sepaMandate->recordCollection($gcPayment->amount);
        }
    }

    protected function handlePaymentFailed(GocardlessPayment $gcPayment, array $event): void
    {
        $details = $event['details'] ?? [];

        $gcPayment->update([
            'status' => 'failed',
            'failed_at' => now(),
            'failure_reason' => $details['cause'] ?? null,
            'failure_description' => $details['description'] ?? null,
        ]);

        // Update invoice status
        if ($gcPayment->invoice) {
            $gcPayment->invoice->update(['status' => 'payment_failed']);
        }

        // Log for follow-up
        Log::warning('GoCardless payment failed', [
            'payment_id' => $gcPayment->id,
            'gocardless_id' => $gcPayment->gocardless_payment_id,
            'reason' => $details['cause'] ?? 'unknown',
        ]);
    }

    protected function handleChargeback(GocardlessPayment $gcPayment, array $event): void
    {
        $gcPayment->update([
            'status' => 'charged_back',
            'failure_reason' => 'chargeback',
        ]);

        // Reverse the payment if it was recorded
        if ($gcPayment->payment) {
            $gcPayment->payment->update(['status' => 'refunded']);
        }

        // Reopen the invoice
        if ($gcPayment->invoice) {
            $gcPayment->invoice->update([
                'status' => 'overdue',
                'paid_at' => null,
            ]);
        }

        Log::warning('GoCardless payment charged back', [
            'payment_id' => $gcPayment->id,
            'invoice_id' => $gcPayment->invoice_id,
        ]);
    }

    /**
     * Make HTTP request to GoCardless API
     */
    protected function request(string $method, string $endpoint, array $data = []): array
    {
        if (!$this->accessToken) {
            throw new \Exception('GoCardless access token not configured');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'GoCardless-Version' => '2015-07-06',
            'Content-Type' => 'application/json',
        ])->$method($this->baseUrl . $endpoint, $data);

        if ($response->failed()) {
            $error = $response->json();
            Log::error('GoCardless API error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'error' => $error,
            ]);
            throw new \Exception($error['error']['message'] ?? 'GoCardless API error');
        }

        return $response->json();
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(string $body, string $signature): bool
    {
        $secret = config('services.gocardless.webhook_secret');
        if (!$secret) {
            Log::warning('GoCardless webhook secret not configured');
            return false;
        }

        $computedSignature = hash_hmac('sha256', $body, $secret);
        return hash_equals($computedSignature, $signature);
    }
}
