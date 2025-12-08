<x-mail::message>
# Rappel de paiement

Bonjour {{ $customer->first_name ?? $customer->company_name }},

Nous vous rappelons que la facture **#{{ $invoice->invoice_number }}** reste impayee.

@if($daysOverdue > 0)
Cette facture est en retard de **{{ $daysOverdue }} jour(s)**.
@endif

**Details :**
- Numero de facture : {{ $invoice->invoice_number }}
- Date d'echeance : {{ $invoice->due_date?->format('d/m/Y') }}
- Montant du : {{ number_format($amountDue, 2, ',', ' ') }} EUR

Merci de bien vouloir proceder au reglement dans les meilleurs delais afin d'eviter d'eventuels frais de retard.

<x-mail::button :url="config('app.url')">
Payer maintenant
</x-mail::button>

Si vous avez deja effectue ce paiement, veuillez ignorer ce message.

Cordialement,<br>
L'equipe {{ config('app.name') }}
</x-mail::message>
