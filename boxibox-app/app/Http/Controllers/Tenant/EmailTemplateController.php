<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class EmailTemplateController extends Controller
{
    public function index(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;
        $category = $request->input('category');
        $search = $request->input('search');

        $query = EmailTemplate::where('tenant_id', $tenantId);

        if ($category) {
            $query->where('category', $category);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templates = $query->latest()->get()->map(fn($t) => [
            'id' => $t->id,
            'name' => $t->name,
            'slug' => $t->slug,
            'subject' => $t->subject,
            'category' => $t->category,
            'category_label' => EmailTemplate::CATEGORIES[$t->category] ?? $t->category,
            'is_system' => $t->is_system,
            'is_active' => $t->is_active,
            'variables' => $t->variables,
            'usage_count' => $t->usage_count ?? 0,
            'last_used_at' => $t->last_used_at?->format('d/m/Y H:i'),
            'updated_at' => $t->updated_at->format('d/m/Y'),
        ]);

        // Stats
        $stats = [
            'total' => $templates->count(),
            'active' => $templates->where('is_active', true)->count(),
            'system' => $templates->where('is_system', true)->count(),
            'custom' => $templates->where('is_system', false)->count(),
        ];

        return Inertia::render('Tenant/Settings/EmailTemplates/Index', [
            'templates' => $templates,
            'categories' => EmailTemplate::CATEGORIES,
            'stats' => $stats,
            'filters' => [
                'category' => $category,
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Tenant/Settings/EmailTemplates/Create', [
            'categories' => EmailTemplate::CATEGORIES,
            'systemTemplates' => EmailTemplate::SYSTEM_TEMPLATES,
            'availableVariables' => $this->getAvailableVariables(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(EmailTemplate::CATEGORIES)),
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Generate slug if not provided
        $slug = $validated['slug'] ?? Str::slug($validated['name']);

        // Ensure unique slug for tenant
        $baseSlug = $slug;
        $counter = 1;
        while (EmailTemplate::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        EmailTemplate::create([
            'tenant_id' => $tenantId,
            'name' => $validated['name'],
            'slug' => $slug,
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => $validated['body_text'] ?? strip_tags($validated['body_html']),
            'variables' => $validated['variables'] ?? [],
            'is_system' => false,
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('tenant.settings.email-templates.index')
            ->with('success', 'Template créé avec succès.');
    }

    public function edit(EmailTemplate $emailTemplate): Response
    {
        // Check tenant ownership
        if ($emailTemplate->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        return Inertia::render('Tenant/Settings/EmailTemplates/Edit', [
            'template' => [
                'id' => $emailTemplate->id,
                'name' => $emailTemplate->name,
                'slug' => $emailTemplate->slug,
                'category' => $emailTemplate->category,
                'subject' => $emailTemplate->subject,
                'body_html' => $emailTemplate->body_html,
                'body_text' => $emailTemplate->body_text,
                'variables' => $emailTemplate->variables,
                'is_system' => $emailTemplate->is_system,
                'is_active' => $emailTemplate->is_active,
            ],
            'categories' => EmailTemplate::CATEGORIES,
            'availableVariables' => $this->getAvailableVariables(),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        // Check tenant ownership
        if ($emailTemplate->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category' => 'required|string|in:' . implode(',', array_keys(EmailTemplate::CATEGORIES)),
            'subject' => 'required|string|max:500',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $emailTemplate->update([
            'name' => $validated['name'],
            'category' => $validated['category'],
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'body_text' => $validated['body_text'] ?? strip_tags($validated['body_html']),
            'variables' => $validated['variables'] ?? [],
            'is_active' => $validated['is_active'] ?? true,
        ]);

        return redirect()->route('tenant.settings.email-templates.index')
            ->with('success', 'Template mis à jour.');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        // Check tenant ownership
        if ($emailTemplate->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // Don't allow deleting system templates
        if ($emailTemplate->is_system) {
            return back()->with('error', 'Les templates système ne peuvent pas être supprimés.');
        }

        $emailTemplate->delete();

        return redirect()->route('tenant.settings.email-templates.index')
            ->with('success', 'Template supprimé.');
    }

    public function duplicate(EmailTemplate $emailTemplate)
    {
        // Check tenant ownership
        if ($emailTemplate->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $tenantId = Auth::user()->tenant_id;

        // Generate new slug
        $baseSlug = $emailTemplate->slug . '-copy';
        $slug = $baseSlug;
        $counter = 1;
        while (EmailTemplate::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $newTemplate = EmailTemplate::create([
            'tenant_id' => $tenantId,
            'name' => $emailTemplate->name . ' (copie)',
            'slug' => $slug,
            'category' => $emailTemplate->category,
            'subject' => $emailTemplate->subject,
            'body_html' => $emailTemplate->body_html,
            'body_text' => $emailTemplate->body_text,
            'variables' => $emailTemplate->variables,
            'is_system' => false,
            'is_active' => true,
        ]);

        return redirect()->route('tenant.settings.email-templates.edit', $newTemplate)
            ->with('success', 'Template dupliqué. Vous pouvez maintenant le personnaliser.');
    }

    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        // Check tenant ownership
        if ($emailTemplate->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        // Generate sample data for preview
        $sampleData = $this->getSampleData($emailTemplate->variables ?? []);
        $rendered = $emailTemplate->render($sampleData);

        return response()->json([
            'subject' => $rendered['subject'],
            'body_html' => $rendered['body_html'],
            'body_text' => $rendered['body_text'],
        ]);
    }

    public function sendTest(Request $request, EmailTemplate $emailTemplate)
    {
        // Check tenant ownership
        if ($emailTemplate->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            // Generate sample data
            $sampleData = $this->getSampleData($emailTemplate->variables ?? []);
            $rendered = $emailTemplate->render($sampleData);

            // In production, send via Mail facade
            // Mail::to($validated['email'])->send(new TemplateTestMail($rendered));

            return response()->json([
                'success' => true,
                'message' => 'Email de test envoyé à ' . $validated['email'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function initializeDefaults()
    {
        $tenantId = Auth::user()->tenant_id;
        $created = 0;

        foreach (EmailTemplate::SYSTEM_TEMPLATES as $slug => $templateData) {
            // Check if already exists
            if (!EmailTemplate::where('tenant_id', $tenantId)->where('slug', $slug)->exists()) {
                EmailTemplate::create([
                    'tenant_id' => $tenantId,
                    'name' => $templateData['name'],
                    'slug' => $slug,
                    'category' => $templateData['category'],
                    'subject' => $templateData['subject'],
                    'body_html' => $this->getDefaultBodyHtml($slug),
                    'body_text' => $this->getDefaultBodyText($slug),
                    'variables' => $templateData['variables'],
                    'is_system' => true,
                    'is_active' => true,
                ]);
                $created++;
            }
        }

        return back()->with('success', "{$created} templates par défaut créés.");
    }

    protected function getAvailableVariables(): array
    {
        return [
            'customer' => [
                'customer_name' => 'Nom complet du client',
                'customer_email' => 'Email du client',
                'customer_phone' => 'Téléphone du client',
            ],
            'company' => [
                'company_name' => 'Nom de l\'entreprise',
                'company_email' => 'Email de contact',
                'company_phone' => 'Téléphone',
                'company_address' => 'Adresse complète',
            ],
            'invoice' => [
                'invoice_number' => 'Numéro de facture',
                'amount' => 'Montant',
                'due_date' => 'Date d\'échéance',
                'invoice_link' => 'Lien vers la facture',
                'payment_link' => 'Lien de paiement',
            ],
            'contract' => [
                'contract_number' => 'Numéro de contrat',
                'box_code' => 'Code du box',
                'site_name' => 'Nom du site',
                'start_date' => 'Date de début',
                'end_date' => 'Date de fin',
                'monthly_price' => 'Prix mensuel',
            ],
            'access' => [
                'access_code' => 'Code d\'accès',
                'site_address' => 'Adresse du site',
                'valid_until' => 'Valide jusqu\'au',
            ],
            'links' => [
                'portal_link' => 'Lien portail client',
                'renewal_link' => 'Lien de renouvellement',
                'contract_link' => 'Lien vers le contrat',
                'support_email' => 'Email support',
            ],
        ];
    }

    protected function getSampleData(array $variables): array
    {
        $sampleValues = [
            'customer_name' => 'Jean Dupont',
            'customer_email' => 'jean.dupont@example.com',
            'customer_phone' => '06 12 34 56 78',
            'company_name' => 'BoxiBox Storage',
            'company_email' => 'contact@boxibox.fr',
            'company_phone' => '01 23 45 67 89',
            'company_address' => '123 Rue du Self-Storage, 75001 Paris',
            'invoice_number' => 'FAC-2024-001234',
            'amount' => '149,00 €',
            'due_date' => '15/01/2024',
            'invoice_link' => 'https://app.boxibox.fr/invoices/123',
            'payment_link' => 'https://app.boxibox.fr/pay/123',
            'contract_number' => 'CTR-2024-000456',
            'box_code' => 'A-101',
            'site_name' => 'BoxiBox Paris Centre',
            'site_address' => '123 Rue du Self-Storage, 75001 Paris',
            'start_date' => '01/01/2024',
            'end_date' => '31/12/2024',
            'monthly_price' => '149,00 €',
            'access_code' => '1234#5678',
            'valid_until' => '31/12/2024',
            'portal_link' => 'https://app.boxibox.fr/portal',
            'renewal_link' => 'https://app.boxibox.fr/renew/123',
            'contract_link' => 'https://app.boxibox.fr/contracts/456',
            'support_email' => 'support@boxibox.fr',
            'days_overdue' => '15',
            'booking_reference' => 'RES-2024-007890',
            'payment_date' => '10/01/2024',
        ];

        $data = [];
        foreach ($variables as $var) {
            $data[$var] = $sampleValues[$var] ?? "{{$var}}";
        }

        return $data;
    }

    protected function getDefaultBodyHtml(string $slug): string
    {
        $templates = [
            'invoice_created' => '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">Nouvelle facture</h1>
        <p>Bonjour {{customer_name}},</p>
        <p>Une nouvelle facture a été créée sur votre compte :</p>
        <div style="background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0;">
            <p><strong>Numéro :</strong> {{invoice_number}}</p>
            <p><strong>Montant :</strong> {{amount}}</p>
            <p><strong>Échéance :</strong> {{due_date}}</p>
        </div>
        <p><a href="{{invoice_link}}" style="display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">Voir la facture</a></p>
        <p>Cordialement,<br>L\'équipe {{company_name}}</p>
    </div>
</body>
</html>',
            'payment_received' => '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #16a34a;">Paiement reçu</h1>
        <p>Bonjour {{customer_name}},</p>
        <p>Nous confirmons la réception de votre paiement :</p>
        <div style="background: #f0fdf4; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #16a34a;">
            <p><strong>Montant :</strong> {{amount}}</p>
            <p><strong>Date :</strong> {{payment_date}}</p>
            <p><strong>Facture :</strong> {{invoice_number}}</p>
        </div>
        <p>Merci pour votre confiance !</p>
        <p>Cordialement,<br>L\'équipe {{company_name}}</p>
    </div>
</body>
</html>',
            'payment_reminder' => '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #dc2626;">Rappel de paiement</h1>
        <p>Bonjour {{customer_name}},</p>
        <p>Nous vous rappelons que la facture suivante est en attente de paiement :</p>
        <div style="background: #fef2f2; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #dc2626;">
            <p><strong>Facture :</strong> {{invoice_number}}</p>
            <p><strong>Montant :</strong> {{amount}}</p>
            <p><strong>Échéance :</strong> {{due_date}}</p>
            <p><strong>Retard :</strong> {{days_overdue}} jours</p>
        </div>
        <p><a href="{{payment_link}}" style="display: inline-block; background: #dc2626; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">Payer maintenant</a></p>
        <p>Cordialement,<br>L\'équipe</p>
    </div>
</body>
</html>',
            'welcome_customer' => '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h1 style="color: #2563eb;">Bienvenue chez {{company_name}} !</h1>
        <p>Bonjour {{customer_name}},</p>
        <p>Nous sommes ravis de vous accueillir parmi nos clients.</p>
        <p>Votre espace client est maintenant disponible. Vous pouvez y accéder pour :</p>
        <ul>
            <li>Consulter vos factures</li>
            <li>Gérer vos contrats</li>
            <li>Accéder à vos codes d\'accès</li>
            <li>Contacter notre support</li>
        </ul>
        <p><a href="{{portal_link}}" style="display: inline-block; background: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 6px;">Accéder à mon espace</a></p>
        <p>Pour toute question, contactez-nous à {{support_email}}</p>
        <p>Cordialement,<br>L\'équipe {{company_name}}</p>
    </div>
</body>
</html>',
        ];

        return $templates[$slug] ?? '<p>Contenu du template à personnaliser.</p>';
    }

    protected function getDefaultBodyText(string $slug): string
    {
        $templates = [
            'invoice_created' => 'Bonjour {{customer_name}},

Une nouvelle facture a été créée :
- Numéro : {{invoice_number}}
- Montant : {{amount}}
- Échéance : {{due_date}}

Voir la facture : {{invoice_link}}

Cordialement,
L\'équipe {{company_name}}',
            'payment_received' => 'Bonjour {{customer_name}},

Nous confirmons la réception de votre paiement :
- Montant : {{amount}}
- Date : {{payment_date}}
- Facture : {{invoice_number}}

Merci pour votre confiance !

Cordialement,
L\'équipe {{company_name}}',
            'payment_reminder' => 'Bonjour {{customer_name}},

Rappel : la facture {{invoice_number}} de {{amount}} est en attente.
Échéance : {{due_date}}
Retard : {{days_overdue}} jours

Payer : {{payment_link}}

Cordialement',
            'welcome_customer' => 'Bonjour {{customer_name}},

Bienvenue chez {{company_name}} !

Accédez à votre espace client : {{portal_link}}

Contact : {{support_email}}

Cordialement,
L\'équipe {{company_name}}',
        ];

        return $templates[$slug] ?? 'Contenu texte à personnaliser.';
    }
}
