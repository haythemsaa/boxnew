<x-mail::message>
# Votre contrat arrive a echeance

Bonjour {{ $customer->first_name ?? $customer->company_name }},

Nous vous informons que votre contrat de location arrive a echeance dans **{{ $daysUntilExpiry }} jours**.

**Details de votre contrat :**
- Numero : {{ $contract->contract_number }}
- Box : {{ $box->reference ?? 'N/A' }} - {{ $box->name ?? '' }}
- Date de fin : {{ $contract->end_date?->format('d/m/Y') }}

Si vous souhaitez renouveler votre contrat, nous vous invitons a nous contacter ou a vous connecter a votre espace client.

<x-mail::button :url="config('app.url')">
Renouveler mon contrat
</x-mail::button>

Si vous ne souhaitez pas renouveler, nous vous rappelons que le box devra etre vide et les cles restituees avant la date de fin de contrat.

Cordialement,<br>
L'equipe {{ config('app.name') }}
</x-mail::message>
