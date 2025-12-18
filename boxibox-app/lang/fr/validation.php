<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | These validation rules are used by custom validation rules in BoxiBox.
    |
    */

    'safe_string' => 'Le champ :attribute contient des caractères potentiellement dangereux.',
    'safe_string_sql' => 'Le champ :attribute contient des motifs SQL non autorisés.',
    'safe_string_html' => 'Le champ :attribute ne doit pas contenir de balises HTML.',
    'phone' => 'Le champ :attribute doit être un numéro de téléphone valide.',
    'phone_country' => 'Le champ :attribute doit être un numéro de téléphone d\'un pays autorisé.',
    'phone_mobile' => 'Le champ :attribute doit être un numéro de téléphone mobile.',

    'password' => [
        'letters' => 'Le :attribute doit contenir au moins une lettre.',
        'mixed' => 'Le :attribute doit contenir au moins une majuscule et une minuscule.',
        'numbers' => 'Le :attribute doit contenir au moins un chiffre.',
        'symbols' => 'Le :attribute doit contenir au moins un caractère spécial.',
        'uncompromised' => 'Le :attribute fourni est trop courant ou trop faible. Veuillez choisir un mot de passe plus sécurisé.',
    ],

];
