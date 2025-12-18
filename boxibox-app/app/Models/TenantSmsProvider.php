<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

class TenantSmsProvider extends Model
{
    protected $fillable = [
        'tenant_id',
        'provider',
        'name',
        'is_active',
        'is_default',
        'config',
        'from_number',
        'from_name',
        'webhook_signing_key',
        'webhook_url',
        'sms_sent_today',
        'sms_sent_month',
        'daily_limit',
        'monthly_limit',
        'balance',
        'balance_currency',
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
        'sms_sent_today' => 'integer',
        'sms_sent_month' => 'integer',
        'daily_limit' => 'integer',
        'monthly_limit' => 'integer',
        'balance' => 'decimal:2',
    ];

    protected $hidden = [
        'config',
        'webhook_signing_key',
    ];

    /**
     * Liste des fournisseurs SMS supportés avec leurs configurations
     */
    public const PROVIDERS = [
        'twilio' => [
            'name' => 'Twilio',
            'description' => 'Leader mondial des communications cloud',
            'logo' => '/img/providers/twilio.svg',
            'countries' => ['worldwide'],
            'fields' => [
                'account_sid' => ['type' => 'text', 'label' => 'Account SID', 'required' => true],
                'auth_token' => ['type' => 'password', 'label' => 'Auth Token', 'required' => true],
            ],
            'sender_type' => 'phone', // Doit avoir un numéro de téléphone
            'webhook_events' => ['delivered', 'failed', 'undelivered', 'sent'],
            'supports_inbound' => true,
        ],
        'vonage' => [
            'name' => 'Vonage (ex-Nexmo)',
            'description' => 'API SMS mondiale avec excellente couverture',
            'logo' => '/img/providers/vonage.svg',
            'countries' => ['worldwide'],
            'fields' => [
                'api_key' => ['type' => 'text', 'label' => 'API Key', 'required' => true],
                'api_secret' => ['type' => 'password', 'label' => 'API Secret', 'required' => true],
            ],
            'sender_type' => 'both', // Numéro ou nom alphanumérique
            'webhook_events' => ['delivered', 'failed', 'rejected', 'expired'],
            'supports_inbound' => true,
        ],
        'ovh' => [
            'name' => 'OVH SMS',
            'description' => 'Solution française économique pour la France',
            'logo' => '/img/providers/ovh.svg',
            'countries' => ['FR', 'BE', 'ES', 'IT', 'DE', 'UK'],
            'fields' => [
                'app_key' => ['type' => 'text', 'label' => 'Application Key', 'required' => true],
                'app_secret' => ['type' => 'password', 'label' => 'Application Secret', 'required' => true],
                'consumer_key' => ['type' => 'password', 'label' => 'Consumer Key', 'required' => true],
                'service_name' => ['type' => 'text', 'label' => 'Nom du service SMS', 'required' => true, 'placeholder' => 'sms-xx12345-1'],
            ],
            'sender_type' => 'alphanumeric', // Nom alphanumérique uniquement en France
            'webhook_events' => ['delivered', 'failed'],
            'supports_inbound' => false,
        ],
        'plivo' => [
            'name' => 'Plivo',
            'description' => 'Alternative économique à Twilio',
            'logo' => '/img/providers/plivo.svg',
            'countries' => ['worldwide'],
            'fields' => [
                'auth_id' => ['type' => 'text', 'label' => 'Auth ID', 'required' => true],
                'auth_token' => ['type' => 'password', 'label' => 'Auth Token', 'required' => true],
            ],
            'sender_type' => 'both',
            'webhook_events' => ['delivered', 'undelivered', 'failed', 'sent'],
            'supports_inbound' => true,
        ],
        'messagebird' => [
            'name' => 'MessageBird',
            'description' => 'Solution européenne multi-canal',
            'logo' => '/img/providers/messagebird.svg',
            'countries' => ['worldwide'],
            'fields' => [
                'api_key' => ['type' => 'password', 'label' => 'API Key', 'required' => true],
            ],
            'sender_type' => 'both',
            'webhook_events' => ['delivered', 'delivery_failed', 'sent'],
            'supports_inbound' => true,
        ],
        'smsenvoi' => [
            'name' => 'SMSEnvoi',
            'description' => 'Solution française simple et économique',
            'logo' => '/img/providers/smsenvoi.svg',
            'countries' => ['FR'],
            'fields' => [
                'api_key' => ['type' => 'password', 'label' => 'Clé API', 'required' => true],
                'user' => ['type' => 'text', 'label' => 'Identifiant', 'required' => true],
            ],
            'sender_type' => 'alphanumeric',
            'webhook_events' => ['delivered', 'failed'],
            'supports_inbound' => false,
        ],
        'clicksend' => [
            'name' => 'ClickSend',
            'description' => 'SMS, Email, Fax, Courrier - tout en un',
            'logo' => '/img/providers/clicksend.svg',
            'countries' => ['worldwide'],
            'fields' => [
                'username' => ['type' => 'text', 'label' => 'Nom d\'utilisateur', 'required' => true],
                'api_key' => ['type' => 'password', 'label' => 'API Key', 'required' => true],
            ],
            'sender_type' => 'both',
            'webhook_events' => ['Delivered', 'Failed'],
            'supports_inbound' => true,
        ],
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->webhook_signing_key)) {
                $model->webhook_signing_key = Str::random(32);
            }
            $model->webhook_url = url("/api/webhooks/sms/{$model->provider}/" . Str::random(16));
        });

        static::saving(function ($model) {
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

    public function setConfigAttribute($value): void
    {
        if (is_array($value)) {
            $this->attributes['config'] = Crypt::encryptString(json_encode($value));
        } else {
            $this->attributes['config'] = $value;
        }
    }

    public function getConfigValue(string $key, $default = null)
    {
        return $this->decrypted_config[$key] ?? $default;
    }

    public function getProviderInfoAttribute(): array
    {
        return self::PROVIDERS[$this->provider] ?? [
            'name' => ucfirst($this->provider),
            'description' => '',
            'logo' => '/img/providers/default.svg',
            'fields' => [],
            'sender_type' => 'both',
            'webhook_events' => [],
            'supports_inbound' => false,
        ];
    }

    /**
     * Obtenir le sender ID (numéro ou nom alphanumérique)
     */
    public function getSenderIdAttribute(): string
    {
        return $this->from_number ?: $this->from_name ?: 'BoxiBox';
    }

    // Methods

    public function incrementSentCount(): void
    {
        $this->increment('sms_sent_today');
        $this->increment('sms_sent_month');
    }

    public function resetDailyCount(): void
    {
        $this->update(['sms_sent_today' => 0]);
    }

    public function resetMonthlyCount(): void
    {
        $this->update(['sms_sent_month' => 0]);
    }

    public function canSend(): bool
    {
        if (!$this->is_active || !$this->is_verified) {
            return false;
        }

        if ($this->daily_limit && $this->sms_sent_today >= $this->daily_limit) {
            return false;
        }

        if ($this->monthly_limit && $this->sms_sent_month >= $this->monthly_limit) {
            return false;
        }

        // Vérifier le solde pour les providers prépayés
        if ($this->balance !== null && $this->balance <= 0) {
            return false;
        }

        return true;
    }

    /**
     * Mettre à jour le solde (pour providers prépayés)
     */
    public function updateBalance(float $newBalance): void
    {
        $this->update(['balance' => $newBalance]);
    }

    /**
     * Déduire du solde
     */
    public function deductFromBalance(float $amount): void
    {
        if ($this->balance !== null) {
            $this->decrement('balance', $amount);
        }
    }

    public function markAsVerified(): void
    {
        $this->update([
            'is_verified' => true,
            'verification_status' => 'verified',
            'last_tested_at' => now(),
            'last_error' => null,
        ]);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'is_verified' => false,
            'verification_status' => 'failed',
            'last_tested_at' => now(),
            'last_error' => $error,
        ]);
    }

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
