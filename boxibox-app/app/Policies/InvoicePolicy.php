<?php

namespace App\Policies;

use App\Models\Invoice;
use App\Models\User;

class InvoicePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('view_invoices') || $user->hasRole(['admin', 'super-admin', 'manager', 'accountant']);
    }

    public function view(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('view_invoices') || $user->hasRole(['admin', 'super-admin', 'manager', 'accountant']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_invoices') || $user->hasRole(['admin', 'super-admin', 'manager', 'accountant']);
    }

    public function update(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }
        // Cannot update paid or cancelled invoices
        if (in_array($invoice->status, ['paid', 'cancelled'])) {
            return $user->hasRole(['admin', 'super-admin']);
        }
        return $user->hasPermissionTo('edit_invoices') || $user->hasRole(['admin', 'super-admin', 'manager', 'accountant']);
    }

    public function delete(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }
        // Cannot delete paid invoices
        if ($invoice->status === 'paid') {
            return false;
        }
        return $user->hasPermissionTo('delete_invoices') || $user->hasRole(['admin', 'super-admin']);
    }

    public function sendReminder(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('send_reminders') || $user->hasRole(['admin', 'super-admin', 'manager', 'accountant']);
    }

    public function recordPayment(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('record_payments') || $user->hasRole(['admin', 'super-admin', 'manager', 'accountant']);
    }

    public function downloadPdf(User $user, Invoice $invoice): bool
    {
        if ($user->tenant_id !== $invoice->tenant_id) {
            return false;
        }
        return $user->hasPermissionTo('view_invoices') || $user->hasRole(['admin', 'super-admin', 'manager', 'accountant']);
    }

    public function restore(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }

    public function forceDelete(User $user, Invoice $invoice): bool
    {
        return $user->tenant_id === $invoice->tenant_id && $user->hasRole(['admin', 'super-admin']);
    }
}
