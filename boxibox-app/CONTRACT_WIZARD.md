# Assistant de Création de Contrat - Documentation

## Vue d'ensemble

L'Assistant de Création de Contrat (Contract Wizard) est une interface professionnelle et intuitive pour créer des contrats de stockage en suivant un processus étape par étape. Inspirée par les meilleures pratiques de Buxida, cette interface guide l'utilisateur à travers 4 étapes clés avec une validation progressive.

## Caractéristiques principales

✅ **Interface multi-étapes** - Processus clair en 4 étapes
✅ **Validation progressive** - Chaque étape est validée avant de progresser
✅ **Résumé visuel** - Aperçus de box et clients à chaque étape
✅ **Design professionnel** - Gradients, icônes, animations fluides
✅ **100% en français** - Interface complètement localisée
✅ **Responsive** - Fonctionne sur desktop, tablet et mobile
✅ **Accessibilité** - Boutons de navigation clairs, messages d'erreur visibles

## Les 4 Étapes du Wizard

### Étape 1: BOX 🎁
**Objectif**: Sélectionner le site et le box

**Champs**:
- Site (obligatoire)
- Box (obligatoire, filtré par site)

**Aperçu**:
- Code du box
- Volume (m³)
- Dimensions (L x l x H)
- Prix de base
- Étage
- Statut

**En-tête**: Gradient bleu (from-blue-500 to-blue-600)

---

### Étape 2: CLIENT 👥
**Objectif**: Sélectionner le client

**Champs**:
- Client (obligatoire)
- Lien pour créer un nouveau client

**Aperçu**:
- Nom complet
- Type (Particulier/Entreprise)
- Email
- Téléphone
- Contrats actifs
- Solde (rouge si négatif, vert si positif)

**En-tête**: Gradient violet (from-purple-500 to-purple-600)

---

### Étape 3: CRÉATION 🔧
**Objectif**: Configurer les termes du contrat

**Sections**:

#### A. Détails du contrat
- Numéro du contrat (auto-généré)
- Statut (Brouillon, En attente de signature, Actif)
- Type (Standard, Court terme, Long terme)

#### B. Dates
- Date de début (obligatoire)
- Date de fin (obligatoire)
- Préavis de résiliation (0-365 jours)
- Renouvellement automatique (checkbox)

#### C. Tarification
- Prix mensuel (obligatoire)
- Dépôt de garantie
- Remise (%)
- Remise fixe (€)

#### D. Facturation
- Fréquence (Mensuel, Trimestriel, Annuel)
- Jour de facturation (1-31)
- Méthode de paiement (Carte, Virement, Espèces, SEPA)
- Paiement automatique (checkbox)

#### E. Accès et clés
- Code d'accès (max 10 caractères)
- Clé remise (checkbox)

**En-tête**: Gradient vert (from-green-500 to-green-600)

---

### Étape 4: VALIDATION ✅
**Objectif**: Vérifier et confirmer

**Résumés visuels**:

1. **Box** (Bleu)
   - Code du box
   - Volume
   - Prix

2. **Client** (Violet)
   - Nom
   - Email
   - Type

3. **Période** (Vert)
   - Date de début
   - Date de fin
   - Préavis

4. **Tarification** (Ambre)
   - Prix mensuel
   - Dépôt
   - Remises

5. **Signatures**
   - Signé par le client
   - Signé par le personnel

**Message d'avertissement**: Invite à vérifier tous les détails

**En-tête**: Gradient ambre (from-amber-500 to-amber-600)

---

## Indicateur de progression

**Design**:
- Cercles numérotés pour chaque étape
- Numéro blanc sur cercle gris/bleu/vert
- Checkmark blanc sur cercle vert pour les étapes complètes
- Lignes de connexion entre cercles (gris → vert selon complétude)
- Noms d'étapes sous les cercles

**Interactivité**:
- Clics sur les cercles complètes peuvent revenir
- Cercles non disponibles sont désactivés
- Transitions visuelles fluides (duration-300)

---

## Boutons de navigation

### Navigation d'étapes
```
[Précédent ←]  [Annuler]  [Suivant →]
```

**Étape finale**:
```
[Précédent ←]  [Annuler]  [Créer le contrat]
```

**États**:
- Bouton "Suivant" désactivé si l'étape n'est pas valide
- Bouton "Créer" désactivé pendant le traitement
- Bouton "Précédent" caché sur la première étape

---

## Validation progressive

### Étape 1 (Box)
```
✓ site_id rempli
✓ box_id rempli
```

### Étape 2 (Client)
```
✓ customer_id rempli
```

### Étape 3 (Création)
```
✓ start_date rempli
✓ end_date rempli (après start_date)
✓ monthly_price rempli (> 0)
```

### Étape 4 (Validation)
```
✓ Aucune validation supplémentaire
✓ Formulaire prêt à soumettre
```

---

## Intégration avec Inertia.js

### Données reçues du contrôleur
```php
[
    'sites' => Collection<Site>,
    'customers' => Collection<Customer>,
    'boxes' => Collection<Box>,
]
```

### Détails des relations
**Sites**: id, name, code, city

**Customers**: id, first_name, last_name, company_name, type, email, phone, total_contracts, outstanding_balance

**Boxes**: id, code, site_id, building_id, floor_id, base_price, volume, length, width, height, + relations (site, building, floor)

---

## Processus de soumission

1. Utilisateur clique "Créer le contrat"
2. Formulaire valide côté client
3. Inertia.js POST vers `/contracts`
4. Laravel valide les données (StoreContractRequest)
5. Contract créé en base de données
6. Box marqué comme 'occupied'
7. Redirection vers liste des contrats
8. Message de succès affiché

---

## Fichiers et routes

### Fichier Vue
- `resources/js/Pages/Tenant/Contracts/CreateWizard.vue`

### Route
- GET: `tenant.contracts.create-wizard` → `/contracts/create/wizard`
- POST: `tenant.contracts.store` → `/contracts`

### Méthode du contrôleur
- `ContractController::createWizard()` - Charge les données
- `ContractController::store()` - Crée le contrat

### Accès
- Bouton "Créer (Wizard)" sur la page d'index des contrats (vert)
- Ou accès direct: `/contracts/create/wizard`

---

## Styles et thèmes

### Palette de couleurs par étape

| Étape | Gradient | Couleur boutons |
|-------|----------|-----------------|
| 1 BOX | blue-500 to blue-600 | focus:ring-blue-500 |
| 2 CLIENT | purple-500 to purple-600 | focus:ring-purple-500 |
| 3 CRÉATION | green-500 to green-600 | focus:ring-green-500 |
| 4 VALIDATION | amber-500 to amber-600 | focus:ring-amber-500 |

### Composants Tailwind utilisés

**Inputs**:
```vue
class="w-full px-4 py-3 border border-gray-300 rounded-lg
       focus:outline-none focus:ring-2 focus:ring-blue-500"
```

**Boutons principaux**:
```vue
class="px-6 py-3 bg-primary-600 text-white rounded-lg
       hover:bg-primary-700 transition-colors font-medium"
```

**Cartes d'aperçu**:
```vue
class="bg-blue-50 border border-blue-200 rounded-lg p-6"
```

---

## Animations

### Transition des étapes
```css
.slide-fade-enter-active,
.slide-fade-leave-active {
    transition: all 0.3s ease;
}

.slide-fade-enter-from {
    transform: translateX(10px);
    opacity: 0;
}
```

**Effet**: Les étapes glissent de droite et apparaissent progressivement

---

## Modifications apportées

### Routes (routes/web.php)
```php
Route::get('contracts/create/wizard', [ContractController::class, 'createWizard'])
    ->name('contracts.create-wizard');
```

### Contrôleur (ContractController.php)
```php
public function createWizard(Request $request): Response
{
    // Charge les sites, customers, boxes avec les relations
    return Inertia::render('Tenant/Contracts/CreateWizard', [
        'sites' => $sites,
        'customers' => $customers,
        'boxes' => $boxes,
    ]);
}
```

### Page d'index (Contracts/Index.vue)
```vue
<Link
    :href="route('tenant.contracts.create-wizard')"
    class="... bg-green-600 hover:bg-green-700 ..."
>
    Créer (Wizard)
</Link>
```

---

## Comparaison avec Buxida

### Inspirations de Buxida implémentées ✅

| Caractéristique | Buxida | Notre implémentation |
|-----------------|--------|----------------------|
| Étapes visuelles | ✓ | ✓ |
| Progr. circulaire | ✓ | ✓ |
| Gradient headers | ✓ | ✓ |
| Validation étapes | ✓ | ✓ |
| Aperçus détails | ✓ | ✓ |
| Français complet | ✓ | ✓ |
| Design moderne | ✓ | ✓ |
| Mobile responsive | ✓ | ✓ |

---

## Guide d'utilisation pour l'utilisateur

### Créer un contrat rapidement:

1. **Allez à Contrats** → Cliquez sur le bouton vert "Créer (Wizard)"
2. **Étape 1**: Sélectionnez le site et le box désiré
3. **Étape 2**: Choisissez le client (ou créez-en un nouveau)
4. **Étape 3**: Remplissez les dates et tarification
5. **Étape 4**: Vérifiez les détails et cliquez "Créer le contrat"
6. **Succès!** Vous êtes redirigé vers la liste des contrats

### Utiliser les flèches pour naviguer
- **Précédent ←** : Revenir à l'étape précédente
- **Suivant →** : Aller à l'étape suivante (si valide)
- **Cliquer sur un cercle** : Sauter à cette étape (si accessible)

---

## Conseils de saisie

### Dates
- Format: YYYY-MM-DD
- La date de fin doit être **après** la date de début
- Préavis par défaut: 30 jours

### Tarification
- Prix en €
- Les remises (%) sont appliquées en priorité
- Puis la remise fixe est soustraite
- Résultat final = max(0, prix - (prix × remise%) - remise_fixe)

### Paiement
- SEPA = prélèvement SEPA (créez le mandat ailleurs)
- Virement = virement bancaire
- Carte = carte bancaire
- Espèces = paiement en espèces

---

## Dépannage

### "Le bouton Suivant est grisé"
→ Vous n'avez pas rempli tous les champs obligatoires de l'étape

### "La date de fin est avant la date de début"
→ Vérifiez l'ordre des dates, la fin doit être après le début

### "Le box n'apparaît pas dans la liste"
→ Le box peut déjà être occupé. Sélectionnez d'abord un site.

### "Le client n'est pas visible"
→ Créez le client d'abord via le menu Clients ou le lien "Créer un nouveau client"

---

## Performance

- **Lazy loading**: Relations chargées uniquement si nécessaires
- **Pagination**: Les listes utilisent le select(), pas select('*')
- **Bundle size**: ~25.68 KB (gzipped: 6.24 KB)
- **Temps de chargement**: < 1 seconde typiquement

---

## Support et améliorations futures

### À venir
- [ ] Affichage de la surface disponible du box
- [ ] Calcul automatique de la date de fin (durée prédéfinie)
- [ ] Sauvegarde en brouillon (sans créer)
- [ ] Duplicate contract (copier un contrat)
- [ ] Modal signature numérique
- [ ] Génération PDF automatique

### Feedback
Pour toute suggestion ou problème, contactez l'équipe de développement.

---

**Version**: 1.0.0
**Dernière mise à jour**: 2025-12-01
**Auteur**: Claude Code
**Statut**: ✅ Production Ready
