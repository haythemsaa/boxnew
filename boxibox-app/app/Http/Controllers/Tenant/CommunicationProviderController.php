<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantEmailProvider;
use App\Models\TenantSmsProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * Contrôleur pour la gestion des fournisseurs Email et SMS par tenant
 *
 * Permet aux tenants de configurer leurs propres API keys
 * pour envoyer des emails et SMS avec leur compte
 */
class CommunicationProviderController extends Controller
{
    /**
     * Liste des providers configurés
     */
    public function index()
    {
        $tenant = auth()->user()->tenant;

        $emailProviders = TenantEmailProvider::where('tenant_id', $tenant->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => $this->formatEmailProvider($p));

        $smsProviders = TenantSmsProvider::where('tenant_id', $tenant->id)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => $this->formatSmsProvider($p));

        return Inertia::render('Tenant/Settings/CommunicationProviders', [
            'emailProviders' => $emailProviders,
            'smsProviders' => $smsProviders,
            'availableEmailProviders' => TenantEmailProvider::PROVIDERS,
            'availableSmsProviders' => TenantSmsProvider::PROVIDERS,
        ]);
    }

    /**
     * Ajouter un provider email
     */
    public function storeEmailProvider(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:' . implode(',', array_keys(TenantEmailProvider::PROVIDERS)),
            'name' => 'nullable|string|max:255',
            'config' => 'required|array',
            'from_email' => 'required|email',
            'from_name' => 'required|string|max:255',
            'reply_to_email' => 'nullable|email',
            'is_default' => 'boolean',
            'daily_limit' => 'nullable|integer|min:1',
            'monthly_limit' => 'nullable|integer|min:1',
        ]);

        $tenant = auth()->user()->tenant;

        // Vérifier les champs requis selon le provider
        $providerInfo = TenantEmailProvider::PROVIDERS[$request->provider];
        foreach ($providerInfo['fields'] as $field => $config) {
            if ($config['required'] ?? false) {
                if (empty($request->config[$field])) {
                    return back()->withErrors(["config.{$field}" => "Le champ {$config['label']} est requis."]);
                }
            }
        }

        $provider = TenantEmailProvider::create([
            'tenant_id' => $tenant->id,
            'provider' => $request->provider,
            'name' => $request->name ?? $providerInfo['name'],
            'config' => $request->config,
            'from_email' => $request->from_email,
            'from_name' => $request->from_name,
            'reply_to_email' => $request->reply_to_email,
            'is_default' => $request->is_default ?? false,
            'daily_limit' => $request->daily_limit,
            'monthly_limit' => $request->monthly_limit,
            'verification_status' => 'pending',
        ]);

        return back()->with('success', 'Fournisseur email ajouté. Cliquez sur "Tester" pour vérifier la configuration.');
    }

    /**
     * Ajouter un provider SMS
     */
    public function storeSmsProvider(Request $request)
    {
        $request->validate([
            'provider' => 'required|string|in:' . implode(',', array_keys(TenantSmsProvider::PROVIDERS)),
            'name' => 'nullable|string|max:255',
            'config' => 'required|array',
            'from_number' => 'nullable|string|max:20',
            'from_name' => 'nullable|string|max:11', // Alphanumeric max 11 chars
            'is_default' => 'boolean',
            'daily_limit' => 'nullable|integer|min:1',
            'monthly_limit' => 'nullable|integer|min:1',
        ]);

        $tenant = auth()->user()->tenant;

        $providerInfo = TenantSmsProvider::PROVIDERS[$request->provider];

        // Vérifier les champs requis
        foreach ($providerInfo['fields'] as $field => $config) {
            if ($config['required'] ?? false) {
                if (empty($request->config[$field])) {
                    return back()->withErrors(["config.{$field}" => "Le champ {$config['label']} est requis."]);
                }
            }
        }

        // Vérifier qu'on a soit un numéro soit un nom selon le type de sender supporté
        $senderType = $providerInfo['sender_type'] ?? 'both';
        if ($senderType === 'phone' && empty($request->from_number)) {
            return back()->withErrors(['from_number' => 'Un numéro de téléphone est requis pour ce provider.']);
        }
        if ($senderType === 'alphanumeric' && empty($request->from_name)) {
            return back()->withErrors(['from_name' => 'Un nom d\'expéditeur est requis pour ce provider.']);
        }

        $provider = TenantSmsProvider::create([
            'tenant_id' => $tenant->id,
            'provider' => $request->provider,
            'name' => $request->name ?? $providerInfo['name'],
            'config' => $request->config,
            'from_number' => $request->from_number,
            'from_name' => $request->from_name,
            'is_default' => $request->is_default ?? false,
            'daily_limit' => $request->daily_limit,
            'monthly_limit' => $request->monthly_limit,
            'verification_status' => 'pending',
        ]);

        return back()->with('success', 'Fournisseur SMS ajouté. Cliquez sur "Tester" pour vérifier la configuration.');
    }

    /**
     * Mettre à jour un provider email
     */
    public function updateEmailProvider(Request $request, TenantEmailProvider $emailProvider)
    {
        $this->authorize('update', $emailProvider);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'config' => 'nullable|array',
            'from_email' => 'required|email',
            'from_name' => 'required|string|max:255',
            'reply_to_email' => 'nullable|email',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'daily_limit' => 'nullable|integer|min:1',
            'monthly_limit' => 'nullable|integer|min:1',
        ]);

        $data = $request->only([
            'name', 'from_email', 'from_name', 'reply_to_email',
            'is_active', 'is_default', 'daily_limit', 'monthly_limit'
        ]);

        // Ne mettre à jour la config que si fournie
        if ($request->has('config') && !empty($request->config)) {
            $data['config'] = $request->config;
            $data['is_verified'] = false;
            $data['verification_status'] = 'pending';
        }

        $emailProvider->update($data);

        return back()->with('success', 'Fournisseur email mis à jour.');
    }

    /**
     * Mettre à jour un provider SMS
     */
    public function updateSmsProvider(Request $request, TenantSmsProvider $smsProvider)
    {
        $this->authorize('update', $smsProvider);

        $request->validate([
            'name' => 'nullable|string|max:255',
            'config' => 'nullable|array',
            'from_number' => 'nullable|string|max:20',
            'from_name' => 'nullable|string|max:11',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'daily_limit' => 'nullable|integer|min:1',
            'monthly_limit' => 'nullable|integer|min:1',
        ]);

        $data = $request->only([
            'name', 'from_number', 'from_name',
            'is_active', 'is_default', 'daily_limit', 'monthly_limit'
        ]);

        if ($request->has('config') && !empty($request->config)) {
            $data['config'] = $request->config;
            $data['is_verified'] = false;
            $data['verification_status'] = 'pending';
        }

        $smsProvider->update($data);

        return back()->with('success', 'Fournisseur SMS mis à jour.');
    }

    /**
     * Supprimer un provider email
     */
    public function destroyEmailProvider(TenantEmailProvider $emailProvider)
    {
        $this->authorize('delete', $emailProvider);

        $emailProvider->delete();

        return back()->with('success', 'Fournisseur email supprimé.');
    }

    /**
     * Supprimer un provider SMS
     */
    public function destroySmsProvider(TenantSmsProvider $smsProvider)
    {
        $this->authorize('delete', $smsProvider);

        $smsProvider->delete();

        return back()->with('success', 'Fournisseur SMS supprimé.');
    }

    /**
     * Tester un provider email
     */
    public function testEmailProvider(Request $request, TenantEmailProvider $emailProvider)
    {
        $this->authorize('update', $emailProvider);

        $request->validate([
            'test_email' => 'required|email',
        ]);

        try {
            $result = $this->sendTestEmail($emailProvider, $request->test_email);

            if ($result['success']) {
                $emailProvider->markAsVerified();
                return back()->with('success', 'Email de test envoyé avec succès ! Le provider est maintenant vérifié.');
            } else {
                $emailProvider->markAsFailed($result['error']);
                return back()->withErrors(['test' => 'Échec: ' . $result['error']]);
            }
        } catch (\Exception $e) {
            $emailProvider->markAsFailed($e->getMessage());
            Log::error('Email provider test failed', [
                'provider_id' => $emailProvider->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['test' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    /**
     * Tester un provider SMS
     */
    public function testSmsProvider(Request $request, TenantSmsProvider $smsProvider)
    {
        $this->authorize('update', $smsProvider);

        $request->validate([
            'test_phone' => 'required|string',
        ]);

        try {
            $result = $this->sendTestSms($smsProvider, $request->test_phone);

            if ($result['success']) {
                $smsProvider->markAsVerified();
                return back()->with('success', 'SMS de test envoyé avec succès ! Le provider est maintenant vérifié.');
            } else {
                $smsProvider->markAsFailed($result['error']);
                return back()->withErrors(['test' => 'Échec: ' . $result['error']]);
            }
        } catch (\Exception $e) {
            $smsProvider->markAsFailed($e->getMessage());
            Log::error('SMS provider test failed', [
                'provider_id' => $smsProvider->id,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors(['test' => 'Erreur: ' . $e->getMessage()]);
        }
    }

    /**
     * Envoyer un email de test
     */
    protected function sendTestEmail(TenantEmailProvider $provider, string $toEmail): array
    {
        $config = $provider->decrypted_config;

        return match ($provider->provider) {
            'mailgun' => $this->testMailgun($config, $provider, $toEmail),
            'sendinblue' => $this->testSendinblue($config, $provider, $toEmail),
            'postmark' => $this->testPostmark($config, $provider, $toEmail),
            'sendgrid' => $this->testSendgrid($config, $provider, $toEmail),
            'ses' => $this->testSes($config, $provider, $toEmail),
            'smtp' => $this->testSmtp($config, $provider, $toEmail),
            default => ['success' => false, 'error' => 'Provider non supporté'],
        };
    }

    /**
     * Envoyer un SMS de test
     */
    protected function sendTestSms(TenantSmsProvider $provider, string $toPhone): array
    {
        $config = $provider->decrypted_config;
        $message = "Test BoxiBox - Votre configuration SMS fonctionne ! " . now()->format('H:i');

        return match ($provider->provider) {
            'twilio' => $this->testTwilio($config, $provider, $toPhone, $message),
            'vonage' => $this->testVonage($config, $provider, $toPhone, $message),
            'plivo' => $this->testPlivo($config, $provider, $toPhone, $message),
            'messagebird' => $this->testMessagebird($config, $provider, $toPhone, $message),
            default => ['success' => false, 'error' => 'Provider non supporté pour les tests'],
        };
    }

    // === TEST METHODS FOR EMAIL PROVIDERS ===

    protected function testMailgun(array $config, TenantEmailProvider $provider, string $toEmail): array
    {
        $domain = $config['domain'] ?? '';
        $apiKey = $config['api_key'] ?? '';
        $region = $config['region'] ?? 'us';

        $baseUrl = $region === 'eu' ? 'https://api.eu.mailgun.net' : 'https://api.mailgun.net';

        $response = Http::withBasicAuth('api', $apiKey)
            ->asForm()
            ->post("{$baseUrl}/v3/{$domain}/messages", [
                'from' => "{$provider->from_name} <{$provider->from_email}>",
                'to' => $toEmail,
                'subject' => 'Test BoxiBox - Configuration Mailgun',
                'html' => $this->getTestEmailHtml('Mailgun'),
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('id')];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function testSendinblue(array $config, TenantEmailProvider $provider, string $toEmail): array
    {
        $apiKey = $config['api_key'] ?? '';

        $response = Http::withHeaders([
            'api-key' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.brevo.com/v3/smtp/email', [
            'sender' => [
                'name' => $provider->from_name,
                'email' => $provider->from_email,
            ],
            'to' => [['email' => $toEmail]],
            'subject' => 'Test BoxiBox - Configuration Brevo',
            'htmlContent' => $this->getTestEmailHtml('Brevo (Sendinblue)'),
        ]);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('messageId')];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function testPostmark(array $config, TenantEmailProvider $provider, string $toEmail): array
    {
        $serverToken = $config['server_token'] ?? '';

        $response = Http::withHeaders([
            'X-Postmark-Server-Token' => $serverToken,
            'Content-Type' => 'application/json',
        ])->post('https://api.postmarkapp.com/email', [
            'From' => "{$provider->from_name} <{$provider->from_email}>",
            'To' => $toEmail,
            'Subject' => 'Test BoxiBox - Configuration Postmark',
            'HtmlBody' => $this->getTestEmailHtml('Postmark'),
        ]);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('MessageID')];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function testSendgrid(array $config, TenantEmailProvider $provider, string $toEmail): array
    {
        $apiKey = $config['api_key'] ?? '';

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.sendgrid.com/v3/mail/send', [
            'personalizations' => [['to' => [['email' => $toEmail]]]],
            'from' => [
                'email' => $provider->from_email,
                'name' => $provider->from_name,
            ],
            'subject' => 'Test BoxiBox - Configuration SendGrid',
            'content' => [
                ['type' => 'text/html', 'value' => $this->getTestEmailHtml('SendGrid')],
            ],
        ]);

        if ($response->successful() || $response->status() === 202) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function testSes(array $config, TenantEmailProvider $provider, string $toEmail): array
    {
        // AWS SES nécessite le SDK AWS - retourner un message informatif
        return ['success' => false, 'error' => 'Le test SES nécessite le SDK AWS. Veuillez installer aws/aws-sdk-php.'];
    }

    protected function testSmtp(array $config, TenantEmailProvider $provider, string $toEmail): array
    {
        try {
            // Utiliser le mailer Laravel avec configuration dynamique
            config([
                'mail.mailers.test_smtp' => [
                    'transport' => 'smtp',
                    'host' => $config['host'],
                    'port' => $config['port'],
                    'encryption' => $config['encryption'] === 'none' ? null : $config['encryption'],
                    'username' => $config['username'],
                    'password' => $config['password'],
                ],
            ]);

            \Mail::mailer('test_smtp')->send([], [], function ($message) use ($provider, $toEmail) {
                $message->from($provider->from_email, $provider->from_name)
                    ->to($toEmail)
                    ->subject('Test BoxiBox - Configuration SMTP')
                    ->html($this->getTestEmailHtml('SMTP'));
            });

            return ['success' => true];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // === TEST METHODS FOR SMS PROVIDERS ===

    protected function testTwilio(array $config, TenantSmsProvider $provider, string $toPhone, string $message): array
    {
        $accountSid = $config['account_sid'] ?? '';
        $authToken = $config['auth_token'] ?? '';

        $response = Http::withBasicAuth($accountSid, $authToken)
            ->asForm()
            ->post("https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json", [
                'From' => $provider->sender_id,
                'To' => $toPhone,
                'Body' => $message,
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('sid')];
        }

        return ['success' => false, 'error' => $response->json('message') ?? $response->body()];
    }

    protected function testVonage(array $config, TenantSmsProvider $provider, string $toPhone, string $message): array
    {
        $apiKey = $config['api_key'] ?? '';
        $apiSecret = $config['api_secret'] ?? '';

        $response = Http::post('https://rest.nexmo.com/sms/json', [
            'api_key' => $apiKey,
            'api_secret' => $apiSecret,
            'from' => $provider->sender_id,
            'to' => preg_replace('/[^0-9]/', '', $toPhone),
            'text' => $message,
        ]);

        if ($response->successful()) {
            $data = $response->json();
            $messageData = $data['messages'][0] ?? [];

            if (($messageData['status'] ?? '1') === '0') {
                return ['success' => true, 'message_id' => $messageData['message-id']];
            }

            return ['success' => false, 'error' => $messageData['error-text'] ?? 'Unknown error'];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function testPlivo(array $config, TenantSmsProvider $provider, string $toPhone, string $message): array
    {
        $authId = $config['auth_id'] ?? '';
        $authToken = $config['auth_token'] ?? '';

        $response = Http::withBasicAuth($authId, $authToken)
            ->post("https://api.plivo.com/v1/Account/{$authId}/Message/", [
                'src' => $provider->sender_id,
                'dst' => preg_replace('/[^0-9]/', '', $toPhone),
                'text' => $message,
            ]);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('message_uuid')[0] ?? null];
        }

        return ['success' => false, 'error' => $response->body()];
    }

    protected function testMessagebird(array $config, TenantSmsProvider $provider, string $toPhone, string $message): array
    {
        $apiKey = $config['api_key'] ?? '';

        $response = Http::withHeaders([
            'Authorization' => 'AccessKey ' . $apiKey,
        ])->post('https://rest.messagebird.com/messages', [
            'originator' => $provider->sender_id,
            'recipients' => [preg_replace('/[^0-9]/', '', $toPhone)],
            'body' => $message,
        ]);

        if ($response->successful()) {
            return ['success' => true, 'message_id' => $response->json('id')];
        }

        return ['success' => false, 'error' => $response->json('errors')[0]['description'] ?? $response->body()];
    }

    // === HELPER METHODS ===

    protected function formatEmailProvider(TenantEmailProvider $provider): array
    {
        return [
            'id' => $provider->id,
            'provider' => $provider->provider,
            'provider_info' => $provider->provider_info,
            'name' => $provider->name,
            'from_email' => $provider->from_email,
            'from_name' => $provider->from_name,
            'reply_to_email' => $provider->reply_to_email,
            'is_active' => $provider->is_active,
            'is_default' => $provider->is_default,
            'is_verified' => $provider->is_verified,
            'verification_status' => $provider->verification_status,
            'last_tested_at' => $provider->last_tested_at?->toISOString(),
            'last_error' => $provider->last_error,
            'emails_sent_today' => $provider->emails_sent_today,
            'emails_sent_month' => $provider->emails_sent_month,
            'daily_limit' => $provider->daily_limit,
            'monthly_limit' => $provider->monthly_limit,
            'webhook_url' => $provider->webhook_url,
            'created_at' => $provider->created_at->toISOString(),
        ];
    }

    protected function formatSmsProvider(TenantSmsProvider $provider): array
    {
        return [
            'id' => $provider->id,
            'provider' => $provider->provider,
            'provider_info' => $provider->provider_info,
            'name' => $provider->name,
            'from_number' => $provider->from_number,
            'from_name' => $provider->from_name,
            'sender_id' => $provider->sender_id,
            'is_active' => $provider->is_active,
            'is_default' => $provider->is_default,
            'is_verified' => $provider->is_verified,
            'verification_status' => $provider->verification_status,
            'last_tested_at' => $provider->last_tested_at?->toISOString(),
            'last_error' => $provider->last_error,
            'sms_sent_today' => $provider->sms_sent_today,
            'sms_sent_month' => $provider->sms_sent_month,
            'daily_limit' => $provider->daily_limit,
            'monthly_limit' => $provider->monthly_limit,
            'balance' => $provider->balance,
            'balance_currency' => $provider->balance_currency,
            'webhook_url' => $provider->webhook_url,
            'created_at' => $provider->created_at->toISOString(),
        ];
    }

    protected function getTestEmailHtml(string $providerName): string
    {
        $date = now()->format('d/m/Y H:i');
        return <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .footer { text-align: center; color: #888; font-size: 12px; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Test de Configuration Email</h1>
        <p>BoxiBox - Self Storage Management</p>
    </div>
    <div class="content">
        <div class="success">
            <strong>Succès !</strong> Votre configuration <strong>{$providerName}</strong> fonctionne correctement.
        </div>
        <p>Cet email a été envoyé automatiquement pour tester votre configuration de fournisseur email.</p>
        <p>Date du test : <strong>{$date}</strong></p>
        <p>Vous pouvez maintenant utiliser ce fournisseur pour envoyer des emails à vos clients.</p>
    </div>
    <div class="footer">
        <p>BoxiBox - Logiciel de gestion de self-storage</p>
    </div>
</body>
</html>
HTML;
    }
}
