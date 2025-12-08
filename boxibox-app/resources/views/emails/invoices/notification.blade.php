<x-mail::message>
# @if($type === 'sent')
Nouvelle facture
@elseif($type === 'reminder')
Rappel de facture
@elseif($type === 'overdue')
Facture en retard
@else
Votre facture
@endif

Bonjour {{ $customer->first_name ?? $customer->company_name }},

@if($type === 'sent')
Nous avons le plaisir de vous transmettre votre facture pour votre box de stockage.
@elseif($type === 'reminder')
Nous vous rappelons que la facture ci-dessous reste en attente de paiement.
@elseif($type === 'overdue')
La facture ci-dessous est actuellement en retard de paiement. Merci de bien vouloir proceder au reglement dans les meilleurs delais.
@endif

**Details de la facture :**
- Numero : {{ $invoice->invoice_number }}
- Date : {{ $invoice->invoice_date?->format('d/m/Y') }}
- Echeance : {{ $invoice->due_date?->format('d/m/Y') }}
- Montant : {{ number_format($invoice->total, 2, ',', ' ') }} EUR

@if($contract && $contract->box)
**Box :** {{ $contract->box->reference }} - {{ $contract->box->name }}
@endif

<x-mail::button :url="config('app.url')">
Acceder a mon espace client
</x-mail::button>

Cordialement,<br>
L'equipe {{ config('app.name') }}
</x-mail::message>
