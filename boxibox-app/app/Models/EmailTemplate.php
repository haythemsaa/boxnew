<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'subject',
        'body_html',
        'body_text',
        'variables',
        'category',
        'is_system',
        'is_active',
        'last_used_at',
        'usage_count',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
        'usage_count' => 'integer',
    ];

    // Categories available
    public const CATEGORIES = [
        'billing' => 'Facturation',
        'contracts' => 'Contrats',
        'reminders' => 'Rappels',
        'notifications' => 'Notifications',
        'marketing' => 'Marketing',
        'welcome' => 'Bienvenue',
        'support' => 'Support',
    ];

    // Default system templates
    public const SYSTEM_TEMPLATES = [
        'invoice_created' => [
            'name' => 'Nouvelle facture',
            'category' => 'billing',
            'subject' => 'Facture {{invoice_number}} - {{company_name}}',
            'variables' => ['invoice_number', 'customer_name', 'amount', 'due_date', 'company_name', 'invoice_link'],
        ],
        'payment_received' => [
            'name' => 'Paiement reçu',
            'category' => 'billing',
            'subject' => 'Confirmation de paiement - {{company_name}}',
            'variables' => ['customer_name', 'amount', 'payment_date', 'invoice_number', 'company_name'],
        ],
        'payment_reminder' => [
            'name' => 'Rappel de paiement',
            'category' => 'reminders',
            'subject' => 'Rappel : Facture {{invoice_number}} en attente',
            'variables' => ['customer_name', 'invoice_number', 'amount', 'due_date', 'days_overdue', 'payment_link'],
        ],
        'contract_created' => [
            'name' => 'Nouveau contrat',
            'category' => 'contracts',
            'subject' => 'Votre contrat de location - {{box_code}}',
            'variables' => ['customer_name', 'box_code', 'site_name', 'start_date', 'monthly_price', 'contract_link'],
        ],
        'contract_ending' => [
            'name' => 'Fin de contrat proche',
            'category' => 'contracts',
            'subject' => 'Votre contrat arrive à échéance',
            'variables' => ['customer_name', 'box_code', 'end_date', 'renewal_link'],
        ],
        'welcome_customer' => [
            'name' => 'Bienvenue nouveau client',
            'category' => 'welcome',
            'subject' => 'Bienvenue chez {{company_name}}!',
            'variables' => ['customer_name', 'company_name', 'portal_link', 'support_email'],
        ],
        'access_code' => [
            'name' => 'Code d\'accès',
            'category' => 'notifications',
            'subject' => 'Votre code d\'accès - {{site_name}}',
            'variables' => ['customer_name', 'access_code', 'site_name', 'site_address', 'valid_until'],
        ],
        'booking_confirmation' => [
            'name' => 'Confirmation de réservation',
            'category' => 'notifications',
            'subject' => 'Confirmation de votre réservation - {{box_code}}',
            'variables' => ['customer_name', 'box_code', 'site_name', 'start_date', 'booking_reference'],
        ],
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public static function findBySlug(string $slug): ?self
    {
        return self::where('slug', $slug)->where('is_active', true)->first();
    }

    public function render(array $data = []): array
    {
        $subject = $this->subject;
        $bodyHtml = $this->body_html;
        $bodyText = $this->body_text;

        foreach ($data as $key => $value) {
            $placeholder = '{{' . $key . '}}';
            $subject = str_replace($placeholder, $value, $subject);
            $bodyHtml = str_replace($placeholder, $value, $bodyHtml);
            $bodyText = str_replace($placeholder, $value, $bodyText ?? '');
        }

        return [
            'subject' => $subject,
            'body_html' => $bodyHtml,
            'body_text' => $bodyText,
        ];
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getCategoryColorAttribute(): string
    {
        return match($this->category) {
            'system' => 'blue',
            'tenant' => 'green',
            'billing' => 'yellow',
            'support' => 'orange',
            'marketing' => 'purple',
            default => 'gray',
        };
    }
}
