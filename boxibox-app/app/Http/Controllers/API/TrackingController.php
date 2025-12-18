<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\EmailTrackingService;
use App\Services\SmsTrackingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Controller pour le tracking des emails et SMS
 *
 * - Pixel de tracking (ouverture email)
 * - Redirection des liens trackés (clics email)
 * - Webhooks des providers (Mailgun, Sendinblue, Twilio, etc.)
 */
class TrackingController extends Controller
{
    public function __construct(
        protected EmailTrackingService $emailTracking,
        protected SmsTrackingService $smsTracking
    ) {}

    /**
     * Pixel de tracking d'ouverture email
     * GET /api/track/email/open/{trackingId}
     *
     * Retourne une image 1x1 pixel transparente
     */
    public function trackEmailOpen(string $trackingId): Response
    {
        // Enregistrer l'ouverture
        $this->emailTracking->recordOpen($trackingId);

        // Retourner une image GIF transparente 1x1 pixel
        $pixel = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');

        return response($pixel, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Content-Length', strlen($pixel))
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    /**
     * Tracking de clic sur lien email
     * GET /api/track/email/click/{linkId}
     *
     * Enregistre le clic et redirige vers l'URL originale
     */
    public function trackEmailClick(string $linkId)
    {
        // Enregistrer le clic et obtenir l'URL originale
        $originalUrl = $this->emailTracking->recordClick($linkId);

        if (!$originalUrl) {
            // Si le lien n'existe pas, rediriger vers la page d'accueil
            return redirect()->to(config('app.url'));
        }

        // Rediriger vers l'URL originale
        return redirect()->away($originalUrl);
    }

    /**
     * Webhook générique pour les providers email
     * POST /api/webhooks/email/{provider}/{token}
     */
    public function handleEmailWebhook(Request $request, string $provider, string $token): Response
    {
        Log::info("Email webhook received", [
            'provider' => $provider,
            'token' => substr($token, 0, 8) . '...',
        ]);

        // Valider le token avec le tenant_email_provider correspondant
        $emailProvider = \App\Models\TenantEmailProvider::where('webhook_token', hash('sha256', $token))
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();

        if (!$emailProvider) {
            Log::warning('Invalid email webhook token', [
                'provider' => $provider,
                'token_prefix' => substr($token, 0, 8),
            ]);
            return response()->noContent(401);
        }

        // Vérifier la signature du webhook si configurée
        if (!$this->verifyWebhookSignature($request, $provider, $emailProvider)) {
            Log::warning('Invalid email webhook signature', [
                'provider' => $provider,
                'tenant_id' => $emailProvider->tenant_id,
            ]);
            return response()->noContent(401);
        }

        $payload = $request->all();

        // Gérer selon le provider
        $handled = $this->emailTracking->handleProviderWebhook($provider, $payload);

        return response()->noContent($handled ? 200 : 400);
    }

    /**
     * Vérifier la signature HMAC du webhook
     */
    protected function verifyWebhookSignature(Request $request, string $provider, $providerConfig): bool
    {
        $webhookSecret = $providerConfig->webhook_secret ?? null;

        // Si pas de secret configuré, on accepte (mais on log un warning)
        if (!$webhookSecret) {
            Log::warning("Webhook secret not configured for {$provider}", [
                'tenant_id' => $providerConfig->tenant_id,
            ]);
            return true;
        }

        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Signature')
            ?? $request->header('X-Hub-Signature-256');

        if (!$signature) {
            // Certains providers passent la signature dans le body
            return true;
        }

        // Calculer la signature attendue
        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        // Comparer de manière sécurisée
        return hash_equals($expectedSignature, $signature)
            || hash_equals('sha256=' . $expectedSignature, $signature);
    }

    /**
     * Webhook générique pour les providers SMS
     * POST /api/webhooks/sms/{provider}/{token}
     */
    public function handleSmsWebhook(Request $request, string $provider, string $token): Response
    {
        Log::info("SMS webhook received", [
            'provider' => $provider,
            'token' => substr($token, 0, 8) . '...',
        ]);

        // Valider le token avec le tenant_sms_provider correspondant
        $smsProvider = \App\Models\TenantSmsProvider::where('webhook_token', hash('sha256', $token))
            ->where('provider', $provider)
            ->where('is_active', true)
            ->first();

        if (!$smsProvider) {
            Log::warning('Invalid SMS webhook token', [
                'provider' => $provider,
                'token_prefix' => substr($token, 0, 8),
            ]);
            return response()->noContent(401);
        }

        // Vérifier la signature du webhook si configurée
        if (!$this->verifySmsWebhookSignature($request, $provider, $smsProvider)) {
            Log::warning('Invalid SMS webhook signature', [
                'provider' => $provider,
                'tenant_id' => $smsProvider->tenant_id,
            ]);
            return response()->noContent(401);
        }

        $payload = $request->all();

        $handled = $this->smsTracking->handleProviderWebhook($provider, $payload);

        // Certains providers (Twilio) attendent une réponse TwiML vide
        if ($provider === 'twilio') {
            return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
                ->header('Content-Type', 'application/xml');
        }

        return response()->noContent($handled ? 200 : 400);
    }

    /**
     * Vérifier la signature HMAC du webhook SMS
     */
    protected function verifySmsWebhookSignature(Request $request, string $provider, $providerConfig): bool
    {
        // Twilio utilise une validation spéciale
        if ($provider === 'twilio') {
            return $this->verifyTwilioSignature($request, $providerConfig);
        }

        $webhookSecret = $providerConfig->webhook_secret ?? null;

        if (!$webhookSecret) {
            return true;
        }

        $signature = $request->header('X-Webhook-Signature')
            ?? $request->header('X-Signature');

        if (!$signature) {
            return true;
        }

        $payload = $request->getContent();
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Vérifier la signature Twilio
     */
    protected function verifyTwilioSignature(Request $request, $providerConfig): bool
    {
        $authToken = $providerConfig->auth_token ?? config('services.twilio.auth_token');

        if (!$authToken) {
            return true;
        }

        $signature = $request->header('X-Twilio-Signature');

        if (!$signature) {
            return true;
        }

        // Twilio signature validation
        $url = $request->fullUrl();
        $params = $request->all();
        ksort($params);

        $data = $url;
        foreach ($params as $key => $value) {
            $data .= $key . $value;
        }

        $expectedSignature = base64_encode(hash_hmac('sha1', $data, $authToken, true));

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Webhook Twilio spécifique pour les delivery reports et SMS entrants
     * POST /api/webhooks/twilio
     */
    public function handleTwilioWebhook(Request $request): Response
    {
        $payload = $request->all();

        Log::info("Twilio webhook", [
            'type' => isset($payload['Body']) ? 'inbound' : 'status',
            'from' => $payload['From'] ?? null,
            'status' => $payload['MessageStatus'] ?? $payload['SmsStatus'] ?? null,
        ]);

        $this->smsTracking->handleProviderWebhook('twilio', $payload);

        // Réponse TwiML vide
        return response('<?xml version="1.0" encoding="UTF-8"?><Response></Response>', 200)
            ->header('Content-Type', 'application/xml');
    }

    /**
     * Webhook Mailgun
     * POST /api/webhooks/mailgun
     */
    public function handleMailgunWebhook(Request $request): Response
    {
        // Valider la signature Mailgun si configurée
        $signature = $request->input('signature');
        if ($signature) {
            $timestamp = $signature['timestamp'] ?? '';
            $token = $signature['token'] ?? '';
            $sig = $signature['signature'] ?? '';

            // Vérification de signature (optionnel, dépend de la config)
            $apiKey = config('services.mailgun.secret');
            if ($apiKey) {
                $expectedSig = hash_hmac('sha256', $timestamp . $token, $apiKey);
                if (!hash_equals($expectedSig, $sig)) {
                    Log::warning('Invalid Mailgun webhook signature');
                    return response()->noContent(401);
                }
            }
        }

        $this->emailTracking->handleProviderWebhook('mailgun', $request->all());

        return response()->noContent(200);
    }

    /**
     * Webhook Sendinblue/Brevo
     * POST /api/webhooks/sendinblue
     */
    public function handleSendinblueWebhook(Request $request): Response
    {
        $this->emailTracking->handleProviderWebhook('sendinblue', $request->all());
        return response()->noContent(200);
    }

    /**
     * Webhook Vonage/Nexmo
     * POST /api/webhooks/vonage
     */
    public function handleVonageWebhook(Request $request): Response
    {
        $this->smsTracking->handleProviderWebhook('vonage', $request->all());
        return response()->noContent(200);
    }

    /**
     * Page de désinscription email
     * GET /unsubscribe/{trackingId}
     */
    public function unsubscribe(string $trackingId)
    {
        // TODO: Implémenter la page de désinscription
        // Pour l'instant, on marque juste comme désinscrit
        $tracking = \App\Models\EmailTracking::where('tracking_id', $trackingId)->first();

        if ($tracking) {
            $tracking->markAsUnsubscribed();

            return view('emails.unsubscribed', [
                'email' => $tracking->recipient_email,
            ]);
        }

        return redirect()->to(config('app.url'));
    }
}
