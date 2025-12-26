<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PayPalService
{
    protected string $baseUrl;
    protected ?string $clientId;
    protected ?string $clientSecret;
    protected ?string $accessToken = null;

    public function __construct()
    {
        $mode = config('services.paypal.mode', 'sandbox');
        $this->baseUrl = $mode === 'live'
            ? 'https://api-m.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->clientId = config('services.paypal.client_id') ?? '';
        $this->clientSecret = config('services.paypal.client_secret') ?? '';
    }

    /**
     * Check if PayPal is configured
     */
    public function isConfigured(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Get OAuth access token from PayPal
     */
    protected function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::withBasicAuth($this->clientId, $this->clientSecret)
            ->asForm()
            ->post("{$this->baseUrl}/v1/oauth2/token", [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            Log::error('PayPal OAuth failed', ['response' => $response->json()]);
            throw new \Exception('Failed to authenticate with PayPal');
        }

        $this->accessToken = $response->json('access_token');
        return $this->accessToken;
    }

    /**
     * Create a PayPal order for payment
     */
    public function createOrder(
        float $amount,
        string $currency = 'EUR',
        string $description = 'Paiement BoxiBox',
        string $returnUrl = '',
        string $cancelUrl = '',
        array $metadata = []
    ): array {
        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->baseUrl}/v2/checkout/orders", [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => strtoupper($currency),
                            'value' => number_format($amount, 2, '.', ''),
                        ],
                        'description' => $description,
                        'custom_id' => json_encode($metadata),
                    ],
                ],
                'application_context' => [
                    'brand_name' => 'BoxiBox',
                    'locale' => 'fr-FR',
                    'landing_page' => 'LOGIN',
                    'shipping_preference' => 'NO_SHIPPING',
                    'user_action' => 'PAY_NOW',
                    'return_url' => $returnUrl,
                    'cancel_url' => $cancelUrl,
                ],
            ]);

        if (!$response->successful()) {
            Log::error('PayPal create order failed', ['response' => $response->json()]);
            throw new \Exception('Failed to create PayPal order: ' . ($response->json('message') ?? 'Unknown error'));
        }

        $order = $response->json();

        // Find approval URL
        $approvalUrl = collect($order['links'] ?? [])
            ->firstWhere('rel', 'approve')['href'] ?? null;

        return [
            'order_id' => $order['id'],
            'status' => $order['status'],
            'approval_url' => $approvalUrl,
        ];
    }

    /**
     * Capture an approved PayPal order
     */
    public function captureOrder(string $orderId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->baseUrl}/v2/checkout/orders/{$orderId}/capture", []);

        if (!$response->successful()) {
            Log::error('PayPal capture failed', ['order_id' => $orderId, 'response' => $response->json()]);
            throw new \Exception('Failed to capture PayPal payment');
        }

        $order = $response->json();
        $capture = $order['purchase_units'][0]['payments']['captures'][0] ?? null;

        return [
            'order_id' => $order['id'],
            'status' => $order['status'],
            'capture_id' => $capture['id'] ?? null,
            'amount' => $capture['amount']['value'] ?? null,
            'currency' => $capture['amount']['currency_code'] ?? null,
            'payer_email' => $order['payer']['email_address'] ?? null,
            'payer_id' => $order['payer']['payer_id'] ?? null,
        ];
    }

    /**
     * Get order details
     */
    public function getOrderDetails(string $orderId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->get("{$this->baseUrl}/v2/checkout/orders/{$orderId}");

        if (!$response->successful()) {
            throw new \Exception('Failed to get PayPal order details');
        }

        return $response->json();
    }

    /**
     * Refund a captured payment
     */
    public function refundPayment(string $captureId, ?float $amount = null, string $currency = 'EUR'): array
    {
        $body = [];
        if ($amount !== null) {
            $body['amount'] = [
                'value' => number_format($amount, 2, '.', ''),
                'currency_code' => strtoupper($currency),
            ];
        }

        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->baseUrl}/v2/payments/captures/{$captureId}/refund", $body);

        if (!$response->successful()) {
            Log::error('PayPal refund failed', ['capture_id' => $captureId, 'response' => $response->json()]);
            throw new \Exception('Failed to refund PayPal payment');
        }

        $refund = $response->json();

        return [
            'refund_id' => $refund['id'],
            'status' => $refund['status'],
            'amount' => $refund['amount']['value'] ?? null,
        ];
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhook(array $headers, string $body): bool
    {
        $webhookId = config('services.paypal.webhook_id');

        if (!$webhookId) {
            Log::warning('PayPal webhook ID not configured');
            return false;
        }

        $response = Http::withToken($this->getAccessToken())
            ->post("{$this->baseUrl}/v1/notifications/verify-webhook-signature", [
                'auth_algo' => $headers['paypal-auth-algo'] ?? '',
                'cert_url' => $headers['paypal-cert-url'] ?? '',
                'transmission_id' => $headers['paypal-transmission-id'] ?? '',
                'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
                'transmission_time' => $headers['paypal-transmission-time'] ?? '',
                'webhook_id' => $webhookId,
                'webhook_event' => json_decode($body, true),
            ]);

        return $response->successful() && $response->json('verification_status') === 'SUCCESS';
    }
}
