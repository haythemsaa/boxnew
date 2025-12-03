<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\PaymentReminder;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReminderController extends Controller
{
    /**
     * Display a listing of reminders.
     */
    public function index(Request $request)
    {
        $tenantId = auth()->user()->tenant_id;

        $query = PaymentReminder::where('tenant_id', $tenantId)
            ->with(['invoice.customer']);

        // Filters
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('invoice', function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($q2) use ($search) {
                      $q2->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        $reminders = $query->orderBy('created_at', 'desc')->paginate(15);

        // Stats
        $stats = [
            'total' => PaymentReminder::where('tenant_id', $tenantId)->count(),
            'pending' => PaymentReminder::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'sent' => PaymentReminder::where('tenant_id', $tenantId)->where('status', 'sent')->count(),
            'paid' => PaymentReminder::where('tenant_id', $tenantId)->where('status', 'paid')->count(),
            'overdue_invoices' => Invoice::where('tenant_id', $tenantId)->where('status', 'overdue')->count(),
            'total_overdue' => Invoice::where('tenant_id', $tenantId)->where('status', 'overdue')->selectRaw('SUM(total - paid_amount) as balance')->value('balance') ?? 0,
        ];

        return Inertia::render('Tenant/Reminders/Index', [
            'reminders' => $reminders,
            'stats' => $stats,
            'filters' => $request->only(['search', 'status', 'level']),
        ]);
    }

    /**
     * Show the specified reminder or resource.
     */
    public function show($reminder)
    {
        // If reminder is a string like 'overdue-invoices', show overdue invoices
        if ($reminder === 'overdue-invoices') {
            return $this->overdueInvoices();
        }

        // Otherwise treat it as a reminder ID
        $tenantId = auth()->user()->tenant_id;
        $paymentReminder = PaymentReminder::where('tenant_id', $tenantId)
            ->with(['invoice.customer'])
            ->findOrFail($reminder);

        return Inertia::render('Tenant/Reminders/Show', [
            'reminder' => $paymentReminder,
        ]);
    }

    /**
     * Show the form for creating a new reminder.
     */
    public function create()
    {
        $tenantId = auth()->user()->tenant_id;

        $invoices = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'overdue')
            ->with(['customer'])
            ->orderBy('due_date', 'asc')
            ->get();

        return Inertia::render('Tenant/Reminders/Create', [
            'invoices' => $invoices,
        ]);
    }

    /**
     * Show overdue invoices to create reminders.
     */
    public function overdueInvoices()
    {
        $tenantId = auth()->user()->tenant_id;

        $overdueInvoices = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'overdue')
            ->with(['customer'])
            ->orderBy('due_date', 'asc')
            ->get();

        return Inertia::render('Tenant/Reminders/OverdueInvoices', [
            'invoices' => $overdueInvoices,
        ]);
    }

    /**
     * Create a new reminder for an invoice.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'level' => 'required|integer|min:1|max:4',
            'type' => 'required|in:email,sms,letter',
            'scheduled_at' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $invoice = Invoice::where('tenant_id', $tenantId)->findOrFail($validated['invoice_id']);

        $reminder = PaymentReminder::create([
            'tenant_id' => $tenantId,
            'invoice_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'level' => $validated['level'],
            'type' => $validated['type'],
            'status' => 'pending',
            'scheduled_at' => $validated['scheduled_at'] ?? now(),
            'amount_due' => $invoice->total - $invoice->paid_amount,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('tenant.reminders.index')
            ->with('success', 'Relance créée avec succès.');
    }

    /**
     * Send a reminder.
     */
    public function send(PaymentReminder $reminder)
    {
        $this->authorize('send_notifications');

        if ($reminder->status !== 'pending') {
            return redirect()->back()
                ->with('error', 'Cette relance a déjà été envoyée.');
        }

        // TODO: Integrate with email/SMS provider
        $reminder->markAsSent();

        // Update invoice reminder count
        $reminder->invoice->increment('reminder_count');

        return redirect()->back()
            ->with('success', 'Relance envoyée avec succès.');
    }

    /**
     * Send bulk reminders.
     */
    public function sendBulk(Request $request)
    {
        $validated = $request->validate([
            'reminder_ids' => 'required|array',
            'reminder_ids.*' => 'exists:payment_reminders,id',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $count = 0;

        foreach ($validated['reminder_ids'] as $reminderId) {
            $reminder = PaymentReminder::where('tenant_id', $tenantId)
                ->where('id', $reminderId)
                ->where('status', 'pending')
                ->first();

            if ($reminder) {
                $reminder->markAsSent();
                $reminder->invoice->increment('reminder_count');
                $count++;
            }
        }

        return redirect()->back()
            ->with('success', "{$count} relance(s) envoyée(s) avec succès.");
    }

    /**
     * Remove the specified reminder.
     */
    public function destroy(PaymentReminder $reminder)
    {
        $this->authorize('send_notifications');

        $reminder->delete();

        return redirect()->route('tenant.reminders.index')
            ->with('success', 'Relance supprimée avec succès.');
    }
}
