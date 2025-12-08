<x-mail::message>
# Confirmation de paiement

Bonjour {{ $customer->first_name ?? $customer->company_name }},

Nous vous confirmons la bonne reception de votre paiement.

**Details du paiement :**
- Facture : #{{ $invoice->invoice_number }}
- Montant recu : {{ number_format($amount, 2, ',', ' ') }} EUR
- Date : {{ now()->format('d/m/Y H:i') }}

@if($remainingBalance > 0)
**Solde restant du :** {{ number_format($remainingBalance, 2, ',', ' ') }} EUR
@else
Votre facture est maintenant entierement reglee.
@endif

<x-mail::button :url="config('app.url')">
Acceder a mon espace
</x-mail::button>

Merci pour votre confiance !

Cordialement,<br>
L'equipe {{ config('app.name') }}
</x-mail::message>
