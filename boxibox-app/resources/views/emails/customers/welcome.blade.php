<x-mail::message>
# Bienvenue chez {{ config('app.name') }} !

Bonjour {{ $customer->first_name ?? $customer->company_name }},

Nous sommes ravis de vous accueillir parmi nos clients !

Votre compte a ete cree avec succes. Vous pouvez desormais acceder a votre espace client pour :

- Consulter vos contrats et factures
- Effectuer vos paiements en ligne
- Contacter notre service client
- Gerer vos informations personnelles

<x-mail::button :url="config('app.url')">
Acceder a mon espace client
</x-mail::button>

Notre equipe reste a votre disposition pour toute question.

Cordialement,<br>
L'equipe {{ config('app.name') }}
</x-mail::message>
