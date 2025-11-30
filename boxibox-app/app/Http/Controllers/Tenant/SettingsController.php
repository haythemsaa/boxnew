<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(Request $request): Response
    {
        $tenant = $request->user()->tenant;
        $tenantId = $request->user()->tenant_id;

        // Get users for this tenant
        $users = User::where('tenant_id', $tenantId)
            ->with('roles')
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->roles->first()?->name ?? 'User',
                    'status' => $user->status,
                ];
            });

        return Inertia::render('Tenant/Settings', [
            'tenant' => $tenant,
            'users' => $users,
            'settings' => [
                'company_name' => $tenant->name ?? '',
                'email' => $tenant->email ?? '',
                'phone' => $tenant->phone ?? '',
                'siret' => $tenant->siret ?? '',
                'address' => $tenant->address ?? '',
                'postal_code' => $tenant->postal_code ?? '',
                'city' => $tenant->city ?? '',
            ],
            'billing' => [
                'invoice_prefix' => $tenant->invoice_prefix ?? 'FAC-',
                'next_invoice_number' => $tenant->next_invoice_number ?? 1,
                'default_tax_rate' => $tenant->default_tax_rate ?? 20,
                'payment_due_days' => $tenant->payment_due_days ?? 30,
                'invoice_footer' => $tenant->invoice_footer ?? '',
            ],
            'notifications' => [
                'new_contract' => $tenant->notify_new_contract ?? true,
                'payment_received' => $tenant->notify_payment ?? true,
                'overdue_invoice' => $tenant->notify_overdue ?? true,
                'contract_ending' => $tenant->notify_contract_ending ?? true,
                'weekly_report' => $tenant->notify_weekly_report ?? false,
            ],
            'sepa' => [
                'ics' => $tenant->sepa_ics ?? '',
                'iban' => $tenant->sepa_iban ?? '',
                'bic' => $tenant->sepa_bic ?? '',
                'creditor_name' => $tenant->sepa_creditor_name ?? '',
            ],
        ]);
    }

    /**
     * Update general settings.
     */
    public function updateGeneral(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:50',
            'siret' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
        ]);

        $tenant = $request->user()->tenant;
        $tenant->update([
            'name' => $validated['company_name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'siret' => $validated['siret'],
            'address' => $validated['address'],
            'postal_code' => $validated['postal_code'],
            'city' => $validated['city'],
        ]);

        return redirect()->back()->with('success', 'Paramètres généraux mis à jour.');
    }

    /**
     * Update billing settings.
     */
    public function updateBilling(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'invoice_prefix' => 'required|string|max:20',
            'next_invoice_number' => 'required|integer|min:1',
            'default_tax_rate' => 'required|numeric|min:0|max:100',
            'payment_due_days' => 'required|integer|min:0',
            'invoice_footer' => 'nullable|string',
        ]);

        $tenant = $request->user()->tenant;
        $tenant->update($validated);

        return redirect()->back()->with('success', 'Paramètres de facturation mis à jour.');
    }

    /**
     * Update notification settings.
     */
    public function updateNotifications(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'new_contract' => 'boolean',
            'payment_received' => 'boolean',
            'overdue_invoice' => 'boolean',
            'contract_ending' => 'boolean',
            'weekly_report' => 'boolean',
        ]);

        $tenant = $request->user()->tenant;
        $tenant->update([
            'notify_new_contract' => $validated['new_contract'] ?? false,
            'notify_payment' => $validated['payment_received'] ?? false,
            'notify_overdue' => $validated['overdue_invoice'] ?? false,
            'notify_contract_ending' => $validated['contract_ending'] ?? false,
            'notify_weekly_report' => $validated['weekly_report'] ?? false,
        ]);

        return redirect()->back()->with('success', 'Paramètres de notification mis à jour.');
    }

    /**
     * Update SEPA settings.
     */
    public function updateSepa(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ics' => 'required|string|max:35',
            'iban' => 'required|string|max:50',
            'bic' => 'nullable|string|max:20',
            'creditor_name' => 'required|string|max:255',
        ]);

        $tenant = $request->user()->tenant;
        $tenant->update([
            'sepa_ics' => $validated['ics'],
            'sepa_iban' => $validated['iban'],
            'sepa_bic' => $validated['bic'],
            'sepa_creditor_name' => $validated['creditor_name'],
        ]);

        return redirect()->back()->with('success', 'Paramètres SEPA mis à jour.');
    }
}
