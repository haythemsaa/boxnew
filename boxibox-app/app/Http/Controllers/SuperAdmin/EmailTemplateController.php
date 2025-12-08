<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = EmailTemplate::query();

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $templates = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'total' => EmailTemplate::count(),
            'active' => EmailTemplate::where('is_active', true)->count(),
            'by_category' => [
                'system' => EmailTemplate::where('category', 'system')->count(),
                'tenant' => EmailTemplate::where('category', 'tenant')->count(),
                'billing' => EmailTemplate::where('category', 'billing')->count(),
                'support' => EmailTemplate::where('category', 'support')->count(),
                'marketing' => EmailTemplate::where('category', 'marketing')->count(),
            ],
        ];

        return Inertia::render('SuperAdmin/EmailTemplates/Index', [
            'templates' => $templates,
            'stats' => $stats,
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function create()
    {
        return Inertia::render('SuperAdmin/EmailTemplates/Create', [
            'availableVariables' => $this->getAvailableVariables(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:email_templates,slug',
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'category' => 'required|in:system,tenant,billing,support,marketing',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        EmailTemplate::create($validated);

        return redirect()->route('superadmin.email-templates.index')
            ->with('success', 'Template créé avec succès.');
    }

    public function show(EmailTemplate $emailTemplate)
    {
        return Inertia::render('SuperAdmin/EmailTemplates/Show', [
            'template' => $emailTemplate,
        ]);
    }

    public function edit(EmailTemplate $emailTemplate)
    {
        return Inertia::render('SuperAdmin/EmailTemplates/Edit', [
            'template' => $emailTemplate,
            'availableVariables' => $this->getAvailableVariables(),
        ]);
    }

    public function update(Request $request, EmailTemplate $emailTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => "required|string|max:255|unique:email_templates,slug,{$emailTemplate->id}",
            'subject' => 'required|string|max:255',
            'body_html' => 'required|string',
            'body_text' => 'nullable|string',
            'category' => 'required|in:system,tenant,billing,support,marketing',
            'variables' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $emailTemplate->update($validated);

        return redirect()->route('superadmin.email-templates.index')
            ->with('success', 'Template mis à jour.');
    }

    public function destroy(EmailTemplate $emailTemplate)
    {
        $emailTemplate->delete();

        return redirect()->route('superadmin.email-templates.index')
            ->with('success', 'Template supprimé.');
    }

    public function toggle(EmailTemplate $emailTemplate)
    {
        $emailTemplate->update(['is_active' => !$emailTemplate->is_active]);

        return back()->with('success', $emailTemplate->is_active ? 'Template activé.' : 'Template désactivé.');
    }

    public function preview(Request $request, EmailTemplate $emailTemplate)
    {
        $sampleData = [
            'tenant_name' => 'Exemple Tenant',
            'user_name' => 'Jean Dupont',
            'user_email' => 'jean@example.com',
            'invoice_number' => 'INV-2024-0001',
            'amount' => '99.00 €',
            'due_date' => now()->addDays(30)->format('d/m/Y'),
            'app_name' => config('app.name'),
            'support_email' => 'support@boxibox.com',
        ];

        $rendered = $emailTemplate->render($sampleData);

        return response()->json($rendered);
    }

    public function duplicate(EmailTemplate $emailTemplate)
    {
        $newTemplate = $emailTemplate->replicate();
        $newTemplate->name = $emailTemplate->name . ' (copie)';
        $newTemplate->slug = $emailTemplate->slug . '-copy-' . time();
        $newTemplate->is_active = false;
        $newTemplate->save();

        return redirect()->route('superadmin.email-templates.edit', $newTemplate)
            ->with('success', 'Template dupliqué.');
    }

    private function getAvailableVariables(): array
    {
        return [
            'Général' => [
                '{{app_name}}' => 'Nom de l\'application',
                '{{support_email}}' => 'Email support',
                '{{current_date}}' => 'Date actuelle',
            ],
            'Utilisateur' => [
                '{{user_name}}' => 'Nom de l\'utilisateur',
                '{{user_email}}' => 'Email de l\'utilisateur',
            ],
            'Tenant' => [
                '{{tenant_name}}' => 'Nom du tenant',
                '{{tenant_email}}' => 'Email du tenant',
                '{{tenant_plan}}' => 'Plan d\'abonnement',
            ],
            'Facturation' => [
                '{{invoice_number}}' => 'Numéro de facture',
                '{{amount}}' => 'Montant',
                '{{due_date}}' => 'Date d\'échéance',
            ],
        ];
    }
}
