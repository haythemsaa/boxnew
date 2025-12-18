<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TenantEmailProvider extends Model
{
    protected $fillable = [
        'tenant_id',
        'provider',
        'name',
        'is_active',
        'is_default',
        'config',
        'from_email',
        'from_name',
        'reply_to_email',
        'webhook_signing_key',
        'webhook_url',
        'emails_sent_today',
        'emails_sent_month',
        'daily_limit',
        'monthly_limit',
        'last_tested_at',
        'is_verified',
        'verification_status',
        'last_error',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'is_verified' => 'boolean',
        'last_tested_at' => 'datetime',
        'emails_sent_today' => 'integer',
        'emails_sent_month' => 'integer',
        'daily_limit' => 'integer',
        'monthly_limit' => 'integer',
    ];

    protected $hidden = [
        'config',
        'webhook_signing_key',
    ];

    /**
     * Liste des fournisseurs supportés avec leurs configurations requises
     */
    public const PROVIDERS = [
        'mailgun' => [
            'name' => 'Mailgun',
            'description' => 'Service email transactionnel professionnel',
            'logo' => '/img/providers/mailgun.svg',
            'fields' => [
                'api_key' => ['type' => 'password', 'label' => 'API Key', 'required' => true],
                'domain' => ['type' => 'text', 'label' => 'Domain', 'required' => true, 'placeholder' => 'mg.votre-domaine.com'],
                'region' => ['type' => 'select', 'label' => 'Région', 'options' => ['us' => 'US', 'eu' => 'Europe'], 'default' => 'eu'],
            ],
            'webhook_events' => ['delivered', 'opened', 'clicked', 'bounced', 'complained', 'unsubscribed'],
        ],
        'sendinblue' => [
            'name' => 'Brevo (Sendinblue)',
            'description' => 'Solution marketing & email tout-en-un française',
            'logo' => '/img/providers/brevo.svg',
            'fields' => [
                'api_key' => ['type' => 'password', 'label' => 'API Key v3', 'required' => true],
            ],
            'webhook_events' => ['delivered', 'opened', 'clicked', 'hard_bounce', 'soft_bounce', 'spam', 'unsubscribed'],
        ],
        'postmark' => [
            'name' => 'Postmark',
            'description' => 'Email transactionnel haute délivrabilité',
            'logo' => '/img/providers/postmark.svg',
            'fields' => [
                'server_token' => ['type' => 'password', 'label' => 'Server API Token', 'required' => true],
            ],
            'webhook_events' => ['Delivery', 'Open', 'Click', 'Bounce', 'SpamComplaint'],
        ],
        'ses' => [
            'name' => 'Amazon SES',
            'description' => 'Service email AWS économique et scalable',
            'logo' => '/img/providers/aws-ses.svg',
            'fields' => [
                'access_key_id' => ['type' => 'password', 'label' => 'AWS Access Key ID', 'required' => true],
                'secret_access_key' => ['type' => 'password', 'label' => 'AWS Secret Access Key', 'required' => true],
                'region' => ['type' => 'select', 'label' => 'Région AWS', 'options' => [
                    'eu-west-1' => 'Europe (Irlande)',
                    'eu-west-2' => 'Europe (Londres)',
                    'eu-central-1' => 'Europe (Francfort)',
                    'us-east-1' => 'US East (N. Virginia)',
                    'us-west-2' => 'US West (Oregon)',
                ], 'default' => 'eu-west-1'],
            ],
            'webhook_events' => ['Delivery', 'Bounce', 'Complaint'],
        ],
        'sendgrid' => [
            'name' => 'SendGrid',
            'description' => 'Plateforme email marketing et transactionnel (Twilio)',
            'logo' => '/img/providers/sendgrid.svg',
            'fields' => [
                'api_key' => ['type' => 'password', 'label' => 'API Key', 'required' => true],
            ],
            'webhook_events' => ['delivered', 'open', 'click', 'bounce', 'spamreport', 'unsubscribe'],
        ],
        'smtp' => [
            'name' => 'SMTP Personnalisé',
            'description' => 'Serveur SMTP de votre choix',
            'logo' => '/img/providers/smtp.svg',
            'fields' => [
                'host' => ['type' => 'text', 'label' => 'Serveur SMTP', 'required' => true, 'placeholder' => 'smtp.example.com'],
                'port' => ['type' => 'number', 'label' => 'Port', 'required' => true, 'default' => 587],
                'encryption' => ['type' => 'select', 'label' => 'Chiffrement', 'options' => ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'Aucun'], 'default' => 'tls'],
                'username' => ['type' => 'text', 'label' => 'Utilisateur', 'required' => true],
                'password' => ['type' => 'password', 'label' => 'Mot de passe', 'required' => true],
            ],
            'webhook_events' => [], // SMTP n'a pas de webhooks
        ],
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->webhook_signing_key)) {
                $model->webhook_signing_key = Str::random(32);
            }
            // Générer l'URL du webhook
            $model->webhook_url = url("/api/webhooks/email/{$model->provider}/" . Str::random(16));
        });

        static::saving(function ($model) {
            // Si on définit comme default, retirer le default des autres
            if ($model->is_default && $model->isDirty('is_default')) {
                static::where('tenant_id', $model->tenant_id)
                    ->where('id', '!=', $model->id)
                    ->update(['is_default' => false]);
            }
        });
    }

    // Relationships
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Accessors & Mutators

    /**
     * Déchiffrer la configuration
     */
    public function getDecryptedConfigAttribute(): array
    {
        if (empty($this->config)) {
            return [];
        }

        try {
            return json_decode(Crypt::decryptString($this->config), true) ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Chiffrer la configuration avant sauvegarde
     */
    public function setConfigAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['config'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['config'] = $value;
        }
    }

    /**
     * Obtenir un champ de configuration spécifique
     */
    public function getConfigValue(string $key, $default = null)
    {
        return $this->decrypted_config[$key] ?? $default;
    }

    /**
     * Obtenir les infos du provider
     */
    public function getProviderInfoAttribute(): array
    {
        return self::PROVIDERS[$this->provider] ?? [
            'name' => ucfirst($this->provider),
            'description' => '',
            'logo' => '/img/providers/default.svg',
            'fields' => [],
            'webhook_events' => [],
        ];
    }

    // Methods

    /**
     * Incrémenter le compteur d'emails envoyés
     */
    public function incrementSentCount(): void
    {
        $this->increment('emails_sent_today');
        $this->increment('emails_sent_month');
    }

    /**
     * Réinitialiser le compteur journalier (appelé par cron)
     */
    public function resetDailyCount(): void
    {
        $this->update(['emails_sent_today' => 0]);
    }

    /**
     * Réinitialiser le compteur mensuel (appelé par cron)
     */
    public function resetMonthlyCount(): void
    {
        $this->update(['emails_sent_month' => 0]);
    }

    /**
     * Vérifier si on peut encore envoyer
     */
    public function canSend(): bool
    {
        if (!$this->is_active || !$this->is_verified) {
            return false;
        }

        if ($this->daily_limit && $this->emails_sent_today >= $this->daily_limit) {
            return false;
        }

        if ($this->monthly_limit && $this->emails_sent_month >= $this->monthly_limit) {
            return false;
        }

        return true;
    }

    /**
     * Marquer comme vérifié
     */
    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verification_status' => 'verified',
            'last_tested_at' => now(),
            'last_error' => null,
        ]);
    }

    /**
     * Marquer comme échoué
     */
    public function markAsFailed(string $error): void
    {
        $this->update([
            'is_verified' => false,
            'verification_status' => 'failed',
            'last_tested_at' => now(),
            'last_error' => $error,
        ]);
    }

    /**
     * Valider la signature d'un webhook
     */
    public function validateWebhookSignature(string $signature, string $payload): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhook_signing_key);
        return hash_equals($expectedSignature, $signature);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
