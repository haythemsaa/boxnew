# Commandes Utiles - Boxibox

## 🚀 Lancement Rapide

```bash
# 1. Installation complète (première fois)
composer install
npm install
cp .env.example .env
php artisan key:generate

# 2. Configuration de la base de données
# Éditer .env avec vos credentials DB

# 3. Migrations et seeders
php artisan migrate:fresh --seed

# 4. Compilation des assets
npm run dev

# 5. Lancement du serveur
php artisan serve
```

## 📊 Base de Données

```bash
# Migrations
php artisan migrate                    # Exécuter les migrations
php artisan migrate:fresh              # Réinitialiser et migrer
php artisan migrate:fresh --seed       # Réinitialiser, migrer et peupler
php artisan migrate:rollback           # Annuler la dernière migration
php artisan migrate:status             # Voir le statut des migrations

# Seeders
php artisan db:seed                    # Exécuter tous les seeders
php artisan db:seed --class=RolesPermissionsSeeder
php artisan db:seed --class=DemoTenantSeeder

# Factory
php artisan tinker
>>> \App\Models\Tenant::factory(10)->create()
```

## 🎨 Frontend

```bash
# Développement (hot reload)
npm run dev

# Build production
npm run build

# Watch pour changements
npm run watch
```

## 🔧 Artisan Commands

```bash
# Générer des composants
php artisan make:model Product -mcr        # Model + Migration + Controller + Resource
php artisan make:controller ProductController --resource
php artisan make:migration create_products_table
php artisan make:seeder ProductSeeder
php artisan make:factory ProductFactory

# Cache
php artisan cache:clear               # Vider le cache
php artisan config:clear              # Vider config cache
php artisan route:clear               # Vider route cache
php artisan view:clear                # Vider view cache
php artisan optimize                  # Optimiser l'application

# Permissions Spatie
php artisan permission:cache-reset    # Reset permission cache
php artisan permission:create-role admin
php artisan permission:create-permission "edit articles"
```

## 🧪 Tests

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter=UserTest
php artisan test tests/Feature/AuthTest.php

# Coverage
php artisan test --coverage
```

## 🔍 Debug & Maintenance

```bash
# Mode maintenance
php artisan down                      # Activer mode maintenance
php artisan up                        # Désactiver mode maintenance

# Logs
tail -f storage/logs/laravel.log      # Suivre les logs en temps réel

# Queue (si activé)
php artisan queue:work                # Traiter les jobs
php artisan queue:listen              # Écouter les jobs
php artisan queue:restart             # Redémarrer les workers

# Scheduler (cron jobs)
php artisan schedule:run              # Exécuter les tâches planifiées
php artisan schedule:list             # Lister les tâches

# Tinker (REPL)
php artisan tinker
>>> $user = \App\Models\User::first()
>>> $user->name
>>> \App\Models\Tenant::count()
```

## 🗂️ Fichiers Importants

```bash
# Config
.env                                  # Configuration environnement
config/app.php                        # Config application
config/database.php                   # Config base de données
config/permission.php                 # Config permissions

# Routes
routes/web.php                        # Routes web
routes/api.php                        # Routes API (à créer)

# Modèles
app/Models/                           # Tous les modèles Eloquent

# Controllers
app/Http/Controllers/Tenant/          # Controllers tenant
app/Http/Controllers/SuperAdmin/      # Controllers super admin (à créer)
app/Http/Controllers/Client/          # Controllers client (à créer)

# Middleware
app/Http/Middleware/                  # Middleware custom

# Vue
resources/js/Pages/                   # Pages Inertia/Vue
resources/js/Layouts/                 # Layouts Vue
resources/js/Components/              # Composants réutilisables

# CSS
resources/css/app.css                 # Styles Tailwind
```

## 📦 Production Deployment

```bash
# 1. Préparer l'environnement
composer install --optimize-autoloader --no-dev
npm run build

# 2. Optimiser
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 3. Permissions
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# 4. Migrations (avec backup DB!)
php artisan migrate --force

# 5. Redémarrer services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

## 🔐 Utilisateurs Demo

Après `php artisan migrate:fresh --seed`:

```
Super Admin (à créer):
- Email: superadmin@boxibox.com
- Password: password

Tenant Admin:
- Email: admin@demo-storage.com
- Password: password

Tenant Staff:
- Email: staff@demo-storage.com
- Password: password
```

## 🚨 Troubleshooting

```bash
# Erreur de permissions
sudo chown -R $USER:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Erreur composer
composer dump-autoload
composer update

# Erreur npm
rm -rf node_modules package-lock.json
npm install

# Régénérer clé application
php artisan key:generate

# Reset complet
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
composer dump-autoload
php artisan optimize
```

## 📊 Vérification Santé

```bash
# Vérifier l'application
php artisan about                     # Info système
php artisan route:list                # Lister toutes les routes
php artisan db:show                   # Info base de données
php artisan model:show Tenant         # Info sur un modèle

# Analyser le code
./vendor/bin/phpstan analyse          # Analyse statique
./vendor/bin/pint                     # Format code
php artisan test                      # Tests
```

## 🌐 URLs Importantes

```
Application: http://localhost:8000
Dashboard: http://localhost:8000/tenant/dashboard
Login: http://localhost:8000/login

API (futur): http://localhost:8000/api/v1/
Docs API (futur): http://localhost:8000/docs
```

## 💡 Tips

- Utilisez `php artisan tinker` pour tester rapidement du code
- Activez le debug bar en dev: `composer require barryvdh/laravel-debugbar --dev`
- Utilisez `php artisan route:list` pour voir toutes les routes
- Les logs sont dans `storage/logs/laravel.log`
- Pour XDebug, configurez votre IDE (VSCode/PHPStorm)
