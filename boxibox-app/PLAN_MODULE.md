# Module Plan - Documentation

## Vue d'ensemble

Le module Plan est un système complet de gestion et visualisation des plans d'entrepôt ou de stockage. Il permet aux utilisateurs de créer, éditer, gérer et exporter des plans de disposition des boxes et éléments structurels.

## Nouvelles fonctionnalités ajoutées

### 1. Menu de navigation
- **Location**: `resources/js/Layouts/TenantLayout.vue`
- **Changements**: Ajout du lien "Plan" dans la section "Principal" du menu latéral
- **Icône**: MapIcon de Heroicons
- **Route**: `tenant.plan.index`

### 2. Sélecteur de modèles (Template Selector)
- **Fichier**: `resources/js/Pages/Tenant/Plan/TemplateSelector.vue`
- **Fonctionnalités**:
  - Affiche une galerie de modèles prédéfinis
  - Permet la création d'un plan vierge
  - Affiche les plans récents
  - Modèles disponibles:
    - **Standard**: Entrepôt classique
    - **Premium**: Entrepôt haute densité, multi-étages, climatisé
    - **Custom**: Plans personnalisés

### 3. Gestion des étages (Floors)
- **Modèle**: `App\Models\Floor` (déjà existant)
- **Composant**: `resources/js/Components/Plan/FloorSelector.vue`
- **Fonctionnalités**:
  - Sélecteur d'étage dans l'éditeur
  - Création rapide d'étages
  - Gestion des propriétés (nom, numéro, surface)
  - Support multi-étages

### 4. Export/Import des plans
- **Composant**: `resources/js/Components/Plan/ExportImport.vue`
- **Formats supportés**:
  - **JSON**: Format brut pour sauvegarde/restauration complète
  - **SVG**: Format vectoriel éditable
  - **PNG/PDF**: Images pour impression et partage (implémentation avancée)
- **Fonctionnalités**:
  - Export en plusieurs formats
  - Import par glisser-déposer
  - Historique des sauvegardes automatiques
  - Restauration des sauvegardes

### 5. Modèles de templates (Plan Templates)
- **Modèle**: `App\Models\PlanTemplate`
- **Migration**: Incluse dans `database/migrations/`
- **Seeder**: `database/seeders/PlanTemplateSeeder.php`
- **Propriétés**:
  - Tenant (association)
  - Nom et description
  - Catégorie (standard, premium, custom)
  - Dimensions (largeur, hauteur)
  - Données de template (JSON)
  - Visibilité (publique/privée)

## Routes ajoutées

```php
Route::prefix('plan')->name('plan.')->group(function () {
    Route::get('/', [PlanController::class, 'index'])->name('index');
    Route::get('/templates', [PlanController::class, 'templates'])->name('templates');
    Route::post('/create', [PlanController::class, 'create'])->name('create');
    Route::get('/editor', [PlanController::class, 'editor'])->name('editor');
    Route::post('/sites/{site}/elements', [PlanController::class, 'saveElements'])->name('save-elements');
    Route::post('/sites/{site}/configuration', [PlanController::class, 'saveConfiguration'])->name('save-configuration');
    Route::post('/sites/{site}/auto-generate', [PlanController::class, 'autoGenerate'])->name('auto-generate');
    Route::get('/boxes/{box}/details', [PlanController::class, 'getBoxDetails'])->name('box-details');
    Route::get('/floors/{site}', [PlanController::class, 'getFloors'])->name('get-floors');
});
```

## Méthodes de contrôleur

### PlanController
- `index()` - Affiche la vue interactive du plan
- `editor()` - Affiche l'éditeur visuel du plan
- `templates()` - Liste les modèles disponibles
- `create()` - Crée un nouveau plan à partir d'un modèle
- `saveElements()` - Sauvegarde les éléments du plan
- `saveConfiguration()` - Sauvegarde la configuration du plan
- `autoGenerate()` - Génère automatiquement le plan à partir des boxes
- `getBoxDetails()` - Récupère les détails d'un box (API)
- `getFloors()` - Récupère les étages d'un site (API)

## Vues principales

### Index
- **Fichier**: `resources/js/Pages/Tenant/Plan/Index.vue`
- **Affiche**: Visualisation interactive du plan
- **Fonctionnalités**:
  - Zoom et panoramique
  - Légende des statuts
  - Statistiques d'occupancy
  - Modal détails des boxes
  - Sélecteur de site

### Editor
- **Fichier**: `resources/js/Pages/Tenant/Plan/Editor.vue`
- **Affiche**: Éditeur visuel complet
- **Fonctionnalités**:
  - Outils de dessin (box, mur, porte, etc.)
  - Positionnement et redimensionnement
  - Rotation des éléments
  - Verrouillage des éléments
  - Historique undo/redo
  - Génération automatique

### Template Selector
- **Fichier**: `resources/js/Pages/Tenant/Plan/TemplateSelector.vue`
- **Affiche**: Galerie de modèles
- **Fonctionnalités**:
  - Grille de modèles
  - Plans récents
  - Modèles recommandés
  - Création rapide

## Composants réutilisables

### FloorSelector
```vue
<FloorSelector
    :floors="floors"
    :siteId="siteId"
    @change="selectedFloor = $event"
    @floor-added="onFloorAdded"
/>
```

### ExportImport
```vue
<ExportImport
    :elements="elements"
    :configuration="configuration"
    :siteId="siteId"
    :backups="backups"
    @import="importPlan"
    @export="exportPlan"
/>
```

## Types d'éléments supportés

- `box` - Unité de stockage
- `wall` - Mur
- `door` - Porte
- `separator` - Séparateur
- `corridor` - Corridor
- `lift` - Ascenseur
- `stairs` - Escalier
- `office` - Bureau
- `reception` - Réception
- `loading_zone` - Zone de chargement
- `parking` - Parking
- `toilet` - Toilettes
- `zone` - Zone générique
- `label` - Étiquette texte

## Propriétés des éléments

```php
[
    'site_id' => integer,
    'floor_id' => integer,
    'element_type' => string,
    'box_id' => integer (nullable),
    'x' => float,
    'y' => float,
    'width' => float,
    'height' => float,
    'rotation' => float,
    'z_index' => integer,
    'fill_color' => string (hex color),
    'stroke_color' => string (hex color),
    'stroke_width' => integer,
    'opacity' => float,
    'font_size' => integer (nullable),
    'text_color' => string (nullable),
    'label' => string (nullable),
    'description' => text (nullable),
    'properties' => json (nullable),
    'is_locked' => boolean,
    'is_visible' => boolean,
]
```

## Configuration du plan

```php
[
    'site_id' => integer,
    'canvas_width' => integer,
    'canvas_height' => integer,
    'show_grid' => boolean,
    'grid_size' => integer,
    'snap_to_grid' => boolean,
    'default_box_available_color' => string,
    'default_box_occupied_color' => string,
    'default_box_reserved_color' => string,
    'default_box_maintenance_color' => string,
    'default_wall_color' => string,
    'default_door_color' => string,
    'background_image' => string (nullable),
    'background_opacity' => float,
    'initial_zoom' => float,
    'initial_x' => float,
    'initial_y' => float,
    'show_box_labels' => boolean,
    'show_box_sizes' => boolean,
    'show_legend' => boolean,
    'show_statistics' => boolean,
]
```

## Seeders

### PlanTemplateSeeder
- **Fichier**: `database/seeders/PlanTemplateSeeder.php`
- **Crée**: 5 modèles de plan prédéfinis
  1. Entrepôt standard
  2. Entrepôt haute densité
  3. Entrepôt multi-étages
  4. Stockage de petites unités
  5. Entrepôt climatisé

### Utilisation
```bash
php artisan db:seed --class=PlanTemplateSeeder
```

## Accès aux fonctionnalités

### Pour les utilisateurs
1. Cliquez sur "Plan" dans le menu latéral
2. Consultez la liste interactive des boxes
3. Créez un nouveau plan via "Créer un plan"
4. Éditez le plan avec l'éditeur visuel
5. Exportez en JSON, SVG, PNG ou PDF

### Pour les développeurs
```php
// Accès aux templates
$templates = PlanTemplate::where('tenant_id', $tenantId)->get();

// Accès aux éléments
$elements = PlanElement::where('site_id', $siteId)->get();

// Accès aux étages
$floors = Floor::where('site_id', $siteId)->get();

// Configuration du plan
$config = PlanConfiguration::where('site_id', $siteId)->first();
```

## Commandes utiles

```bash
# Générer les templates
php artisan db:seed --class=PlanTemplateSeeder

# Générer les plans de démo
php artisan db:seed --class=PlanDemoSeeder

# Tout réinitialiser
php artisan migrate:refresh --seed
```

## Architecture

```
Plan Module
├── Routes
│   └── web.php (routes/plan/*)
├── Controllers
│   └── PlanController
├── Models
│   ├── PlanElement
│   ├── PlanConfiguration
│   ├── PlanTemplate
│   └── Floor
├── Views
│   ├── Index.vue (visualization)
│   ├── Editor.vue (editing)
│   └── TemplateSelector.vue (templates)
├── Components
│   ├── FloorSelector.vue
│   └── ExportImport.vue
├── Migrations
│   └── create_plan_templates_table.php
└── Seeders
    ├── PlanDemoSeeder.php
    └── PlanTemplateSeeder.php
```

## Prochaines étapes (optionnel)

1. **Collaboration en temps réel**: WebSocket pour édition collaborative
2. **Drag & drop avancé**: Interface d'ajout de boxes par glisser-déposer
3. **Couches (Layers)**: Gestion des couches d'éléments
4. **Calcul de zones**: Calcul automatique de densité et optimisation
5. **3D Visualization**: Vue 3D du plan
6. **Mobile Support**: Édition sur mobile avec touch controls
7. **Analytics**: Heatmaps et analytiques d'utilisation
8. **Intégration booking**: Affichage des disponibilités lors de la réservation

## Fichiers modifiés/créés

### Créés
- `resources/js/Pages/Tenant/Plan/TemplateSelector.vue`
- `resources/js/Components/Plan/FloorSelector.vue`
- `resources/js/Components/Plan/ExportImport.vue`
- `app/Models/PlanTemplate.php`
- `database/seeders/PlanTemplateSeeder.php`
- `PLAN_MODULE.md` (ce fichier)

### Modifiés
- `resources/js/Layouts/TenantLayout.vue` (ajout du menu Plan)
- `app/Http/Controllers/Tenant/PlanController.php` (nouvelles méthodes)
- `routes/web.php` (nouvelles routes)

## Support et assistance

Pour toute question ou problème:
1. Consultez la documentation du module
2. Vérifiez les seeders et fixtures
3. Testez avec les données de démonstration
4. Vérifiez les migrations et structures de base de données
