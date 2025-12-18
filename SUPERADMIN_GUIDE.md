# Guide SuperAdmin - BoxiBox SaaS

## Vue d'ensemble

Le système SuperAdmin permet une gestion complète de la plateforme BoxiBox SaaS. Le SuperAdmin a un contrôle total sur tous les tenants, modules, plans d'abonnement, et peut effectuer toutes les opérations sans restriction.

---

## Fonctionnalités Implémentées

### 1. Gestion des Modules

**Contrôleur**: `App\Http\Controllers\SuperAdmin\ModuleController`
**Routes**: `superadmin.modules.*`
**Pages Vue**: `resources/js/Pages/SuperAdmin/Modules/`

#### Fonctionnalités:
- ✅ Créer/modifier/supprimer des modules
- ✅ Activer/désactiver des modules globalement
- ✅ Définir le prix mensuel/annuel par module
- ✅ Définir les dépendances entre modules
- ✅ Catégoriser les modules (core, marketing, operations, integrations, analytics, premium)
- ✅ Activer/désactiver des modules pour des tenants spécifiques
- ✅ Démarrer des démos de modules (avec période d'essai)
- ✅ Gérer l'historique des démos et les conversions

#### Routes principales:
```php
GET  /superadmin/modules                    // Liste des modules
POST /superadmin/modules                    // Créer un module
GET  /superadmin/modules/{module}/edit      // Modifier un module
POST /superadmin/modules/tenant/{tenant}/enable    // Activer un module pour un tenant
DELETE /superadmin/modules/tenant/{tenant}/module/{module} // Désactiver un module
GET  /superadmin/modules/demos              // Historique des démos
```

---

### 2. Gestion des Plans d'Abonnement

**Contrôleur**: `App\Http\Controllers\SuperAdmin\SubscriptionPlanController`
**Routes**: `superadmin.plans.*`
**Pages Vue**: `resources/js/Pages/SuperAdmin/Plans/`

#### Fonctionnalités:
- ✅ Créer/modifier/supprimer des plans
- ✅ Définir les prix mensuel/annuel
- ✅ Définir les limites (sites, boxes, utilisateurs, clients)
- ✅ Inclure des modules dans les plans
- ✅ Définir le niveau de support (none, email, priority, dedicated)
- ✅ Marquer un plan comme "populaire"
- ✅ Dupliquer un plan existant
- ✅ Activer/désactiver un plan

#### Plans par défaut:
1. **Starter** (49€/mois) - 1 site, 100 boxes, 3 utilisateurs, modules core
2. **Professional** (99€/mois) - 3 sites, 500 boxes, 10 utilisateurs, + CRM + Booking + Maintenance
3. **Business** (199€/mois) - 10 sites, 2000 boxes, 50 utilisateurs, + Analytics + IoT + Dynamic Pricing
4. **Enterprise** (399€/mois) - Illimité, tous les modules, support dédié

#### Routes principales:
```php
GET  /superadmin/plans                // Liste des plans
POST /superadmin/plans                // Créer un plan
GET  /superadmin/plans/{plan}/edit    // Modifier un plan
POST /superadmin/plans/{plan}/duplicate  // Dupliquer un plan
POST /superadmin/plans/{plan}/toggle  // Activer/désactiver
```

---

### 3. Gestion Complète des Tenants

**Contrôleur**: `App\Http\Controllers\SuperAdmin\TenantManagementController`
**Routes**: `superadmin.tenant-management.*`
**Pages Vue**: `resources/js/Pages/SuperAdmin/Tenants/`

#### Fonctionnalités:

##### Gestion des Clients
- ✅ Créer des clients pour n'importe quel tenant
- ✅ Voir la liste des clients d'un tenant
- ✅ Accès complet aux données clients

##### Gestion des Boxes
- ✅ Créer des boxes pour n'importe quel tenant
- ✅ Voir la liste des boxes d'un tenant
- ✅ Modifier le statut des boxes

##### Gestion des Contrats
- ✅ Créer des contrats pour n'importe quel tenant
- ✅ Voir la liste des contrats d'un tenant
- ✅ Générer les numéros de contrat automatiquement

##### Gestion des Abonnements
- ✅ Voir l'abonnement actuel d'un tenant
- ✅ Changer le plan d'un tenant
- ✅ Définir une période d'essai
- ✅ Suspendre/réactiver un abonnement
- ✅ Voir l'historique des abonnements

##### Gestion Financière
- ✅ Créer des factures plateforme pour les tenants
- ✅ Voir les factures payées/en attente/en retard
- ✅ Statistiques financières par tenant

##### Gestion des Limites
- ✅ Modifier les limites max_sites, max_boxes, max_users
- ✅ Outrepasser les limites d'un plan si nécessaire

#### Routes principales:
```php
GET  /superadmin/tenant-management/{tenant}/customers    // Clients du tenant
POST /superadmin/tenant-management/{tenant}/customers    // Créer un client
GET  /superadmin/tenant-management/{tenant}/boxes        // Boxes du tenant
POST /superadmin/tenant-management/{tenant}/boxes        // Créer un box
GET  /superadmin/tenant-management/{tenant}/contracts    // Contrats du tenant
POST /superadmin/tenant-management/{tenant}/contracts    // Créer un contrat
GET  /superadmin/tenant-management/{tenant}/subscription // Gérer l'abonnement
POST /superadmin/tenant-management/{tenant}/subscription/change // Changer de plan
POST /superadmin/tenant-management/{tenant}/subscription/suspend // Suspendre
POST /superadmin/tenant-management/{tenant}/limits       // Modifier les limites
```

---

### 4. Facturation Plateforme

**Contrôleur**: `App\Http\Controllers\SuperAdmin\PlatformBillingController`
**Routes**: `superadmin.billing.*`
**Pages Vue**: `resources/js/Pages/SuperAdmin/Billing/`

#### Fonctionnalités:
- ✅ Créer des factures manuellement pour les tenants
- ✅ Générer automatiquement les factures mensuelles
- ✅ Marquer une facture comme payée
- ✅ Annuler une facture
- ✅ Envoyer des rappels de paiement
- ✅ Voir les factures en retard
- ✅ Statistiques de revenus plateforme

#### Numérotation:
Format: `PLAT-YYYYMM-0001`
Exemple: `PLAT-202512-0042`

#### Routes principales:
```php
GET  /superadmin/billing              // Liste des factures
POST /superadmin/billing              // Créer une facture
GET  /superadmin/billing/{invoice}    // Détails facture
POST /superadmin/billing/{invoice}/mark-paid  // Marquer comme payée
POST /superadmin/billing/generate-monthly     // Générer factures mensuelles
```

---

### 5. Dashboard SuperAdmin Amélioré

**Contrôleur**: `App\Http\Controllers\SuperAdmin\ImprovedDashboardController`
**Route**: `superadmin.dashboard`
**Page Vue**: `resources/js/Pages/SuperAdmin/Dashboard.vue`

#### Statistiques affichées:

##### Tenants
- Total tenants
- Tenants actifs
- Tenants en essai
- Tenants suspendus

##### Revenus
- Revenus totaux plateforme (factures payées)
- Revenus mensuels plateforme
- Montant en attente
- Montant en retard

##### Abonnements
- Abonnements actifs
- Abonnements en essai
- Abonnements en retard de paiement

##### Modules
- Total modules
- Modules actifs
- Souscriptions modules

#### Graphiques:
- Tendance des revenus plateforme (12 derniers mois)
- Croissance des tenants (12 derniers mois)
- Répartition par plan
- Modules les plus utilisés

#### Alertes système:
- 🟡 Tenants avec paiements en retard
- 🔵 Abonnements expirant dans 30 jours
- 🔵 Essais se terminant dans 7 jours

#### Top Tenants:
- Classement par revenus plateforme
- Montants en attente et en retard
- Nombre de contrats, clients, utilisateurs

---

## Tables de Base de Données

### `modules`
```sql
- id
- code (unique)
- name
- description
- icon
- color
- category (core, marketing, operations, integrations, analytics, premium)
- monthly_price
- yearly_price
- features (JSON)
- routes (JSON)
- dependencies (JSON)
- is_core (boolean)
- is_active (boolean)
- sort_order
```

### `subscription_plans`
```sql
- id
- code (unique)
- name
- description
- badge_color
- monthly_price
- yearly_price
- yearly_discount
- max_sites (nullable)
- max_boxes (nullable)
- max_users (nullable)
- max_customers (nullable)
- includes_support
- support_level (none, email, priority, dedicated)
- included_modules (JSON array of module IDs)
- features (JSON)
- is_popular
- is_active
- sort_order
```

### `tenant_subscriptions`
```sql
- id
- tenant_id
- plan_id
- billing_cycle (monthly, yearly)
- status (trial, active, past_due, cancelled, suspended)
- trial_ends_at
- starts_at
- ends_at
- cancelled_at
- price
- payment_method
- stripe_subscription_id
- metadata (JSON)
```

### `tenant_modules`
```sql
- id
- tenant_id
- module_id
- status (active, trial, expired, disabled)
- trial_ends_at
- starts_at
- ends_at
- price
- billing_cycle
- is_demo
- metadata (JSON)
```

### `platform_invoices`
```sql
- id
- invoice_number
- tenant_id
- subtotal
- tax_amount
- total_amount
- currency
- status (draft, pending, paid, overdue, cancelled)
- issue_date
- due_date
- paid_date
- payment_method
- payment_reference
- notes
- line_items (JSON)
```

### `demo_history`
```sql
- id
- tenant_id
- module_id
- plan_id
- demo_type (module, plan, full_app)
- started_at
- ends_at
- converted_at
- status (active, expired, converted, cancelled)
- created_by
- notes
```

### Modifications `tenants`
```sql
+ subscription_status (trial, active, past_due, cancelled, suspended)
+ current_plan_id
```

---

## Modules Disponibles (19 modules)

### Modules Core (Gratuits, toujours inclus)
1. **core_boxes** - Gestion des Boxes
2. **core_customers** - Gestion Clients
3. **core_invoicing** - Facturation

### Modules Marketing & CRM
4. **crm** - CRM Avancé (29€/mois)
5. **booking** - Système de Réservation (49€/mois)
6. **loyalty** - Programme de Fidélité (19€/mois)
7. **reviews** - Gestion des Avis (15€/mois)

### Modules Operations
8. **maintenance** - Gestion Maintenance (25€/mois)
9. **inspections** - Inspections & Rondes (20€/mois)
10. **overdue** - Gestion Impayés (30€/mois)
11. **staff** - Gestion du Personnel (35€/mois)
12. **valet** - Valet Storage (40€/mois)

### Modules Integrations
13. **iot** - IoT & Smart Locks (45€/mois)
14. **accounting** - Intégration Comptable (35€/mois)
15. **webhooks** - API & Webhooks (25€/mois)
16. **video_calls** - Visites Virtuelles (20€/mois)

### Modules Analytics
17. **analytics** - Analytics Avancés (30€/mois)
18. **ai_advisor** - Conseiller IA (50€/mois)

### Modules Premium
19. **dynamic_pricing** - Tarification Dynamique (40€/mois)
20. **sustainability** - Durabilité (25€/mois)
21. **gdpr** - Conformité RGPD (30€/mois)

---

## Installation & Configuration

### 1. Exécuter les migrations
```bash
php artisan migrate
```

### 2. Seed modules et plans
```bash
php artisan db:seed --class=SuperAdminSeeder
```

### 3. Ajouter les routes dans `routes/web.php`

Ajouter ces imports en haut du fichier:
```php
use App\Http\Controllers\SuperAdmin\SubscriptionPlanController as SuperAdminPlanController;
use App\Http\Controllers\SuperAdmin\TenantManagementController as SuperAdminTenantManagementController;
use App\Http\Controllers\SuperAdmin\ImprovedDashboardController;
```

Dans le groupe SuperAdmin, remplacer la route dashboard:
```php
Route::get('/dashboard', [ImprovedDashboardController::class, 'index'])->name('dashboard');
```

Ajouter les routes depuis le fichier `routes/superadmin_additional.php`:
```php
// Plans d'abonnement
Route::prefix('plans')->name('plans.')->group(function () {
    Route::get('/', [SuperAdminPlanController::class, 'index'])->name('index');
    // ... (voir superadmin_additional.php)
});

// Gestion complète des tenants
Route::prefix('tenant-management')->name('tenant-management.')->group(function () {
    // ... (voir superadmin_additional.php)
});
```

### 4. Créer un utilisateur SuperAdmin

```php
$user = User::create([
    'name' => 'Super Admin',
    'email' => 'admin@boxibox.com',
    'password' => bcrypt('password'),
]);

$user->assignRole('super_admin');
```

---

## Utilisation

### Workflow typique pour un nouveau tenant:

1. **Créer le tenant** via `superadmin.tenants.create`
   - Définir les informations de base
   - Créer l'utilisateur admin

2. **Assigner un plan** via `superadmin.tenant-management.subscription`
   - Choisir un plan (Starter, Professional, Business, Enterprise)
   - Définir le cycle de facturation (mensuel/annuel)
   - Optionnel: période d'essai

3. **Activer des modules additionnels** via `superadmin.modules.tenant`
   - Ajouter des modules hors plan si nécessaire
   - Possibilité de démarrer en mode démo

4. **Créer les données de base** (si nécessaire)
   - Créer des sites via `superadmin.tenant-management.boxes`
   - Créer des boxes
   - Créer des clients via `superadmin.tenant-management.customers`
   - Créer des contrats via `superadmin.tenant-management.contracts`

5. **Facturation**
   - Les factures mensuelles peuvent être générées automatiquement
   - Ou créées manuellement via `superadmin.billing.create`

### Suspension d'un tenant:

```php
// Via TenantManagementController
POST /superadmin/tenant-management/{tenant}/subscription/suspend

// Ou via TenantController
POST /superadmin/tenants/{tenant}/suspend
```

Cela:
- Désactive le tenant (`is_active = false`)
- Change le statut de l'abonnement en "suspended"
- Le tenant ne peut plus se connecter

### Réactivation:

```php
POST /superadmin/tenant-management/{tenant}/subscription/reactivate
```

---

## API de Service

### ModuleService

Localisation: `App\Services\ModuleService`

Méthodes disponibles:
```php
// Obtenir les modules avec détails pour un tenant
getModulesDetailsForTenant(int $tenantId): Collection

// Activer un module pour un tenant
enableModule(int $tenantId, int $moduleId, array $options = []): TenantModule

// Désactiver un module
disableModule(int $tenantId, int $moduleId): void

// Changer le plan d'un tenant
changePlan(int $tenantId, int $planId, string $billingCycle): TenantSubscription

// Démarrer une démo complète
startFullAppDemo(int $tenantId, int $days): void

// Vérifier si un tenant a accès à un module
hasModuleAccess(int $tenantId, string $moduleCode): bool
```

---

## Permissions

Le SuperAdmin a accès à TOUT sans restriction via le middleware:
```php
Route::middleware('role:super_admin')
```

Aucune vérification de tenant_id ou de limites n'est appliquée pour le SuperAdmin.

---

## Sécurité

### Impersonate (Se connecter comme un tenant)

Le SuperAdmin peut se connecter en tant qu'admin d'un tenant:

```php
POST /superadmin/tenants/{tenant}/impersonate
```

Pour revenir au compte SuperAdmin:
```php
GET /superadmin/stop-impersonating
```

La session stocke l'ID du SuperAdmin dans `impersonating_from`.

---

## Tests

### Tester la création d'un module:
```php
POST /superadmin/modules
{
    "code": "test_module",
    "name": "Module de Test",
    "description": "Description du module",
    "category": "operations",
    "monthly_price": 25,
    "yearly_price": 250,
    "is_core": false
}
```

### Tester l'activation pour un tenant:
```php
POST /superadmin/modules/tenant/{tenant}/enable
{
    "module_id": 1,
    "is_trial": true,
    "trial_days": 14,
    "billing_cycle": "monthly"
}
```

### Tester le changement de plan:
```php
POST /superadmin/tenant-management/{tenant}/subscription/change
{
    "plan_id": 2,
    "billing_cycle": "yearly",
    "trial_days": 30
}
```

---

## Prochaines Étapes Recommandées

1. ✅ Ajouter l'envoi d'emails pour les factures
2. ✅ Intégration Stripe pour paiements automatiques
3. ✅ Webhooks pour événements de paiement
4. ✅ Exports PDF pour factures
5. ✅ Rapports analytics SuperAdmin
6. ✅ Système de tickets support SuperAdmin <-> Tenants
7. ✅ Gestion des feature flags par tenant

---

## Support

Pour toute question sur le système SuperAdmin:
- Documentation technique: Ce fichier
- Code source: `app/Http/Controllers/SuperAdmin/`
- Modèles: `app/Models/`
- Seeder: `database/seeders/SuperAdminSeeder.php`

---

**Version**: 1.0
**Date**: Décembre 2025
**Auteur**: BoxiBox Development Team
