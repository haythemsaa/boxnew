# 🚀 GUIDE DE DÉMARRAGE RAPIDE - BOXIBOX

**Application SaaS multi-tenant pour la gestion de box de stockage**

---

## 📋 PRÉREQUIS

Avant de commencer, assurez-vous d'avoir installé:

- **PHP 8.2 ou supérieur** (avec extensions: pdo, sqlite, mbstring, xml, curl, zip)
- **Composer 2.x**
- **Node.js 18+** et **npm**
- **Git**

Vérifiez vos versions:
```bash
php -v        # Doit afficher 8.2 ou plus
composer -V   # Doit afficher 2.x
node -v       # Doit afficher v18 ou plus
npm -v
```

---

## ⚡ INSTALLATION EN 5 MINUTES

### Étape 1: Cloner et accéder au projet

```bash
# Si vous n'avez pas encore cloné le repo
git clone https://github.com/haythemsaa/boxnew.git
cd boxnew/boxibox-app

# Ou si vous êtes déjà dans boxnew/
cd boxibox-app
```

### Étape 2: Installer les dépendances

```bash
# Installer les dépendances PHP
composer install

# Installer les dépendances Node.js
npm install
```

### Étape 3: Configuration de l'environnement

```bash
# Le fichier .env existe déjà, générez juste la clé d'application
php artisan key:generate
```

### Étape 4: Base de données

```bash
# Créer la base de données SQLite et exécuter les migrations
php artisan migrate:fresh

# Remplir avec des données de démonstration
php artisan db:seed
```

**Ce que cela crée:**
- ✅ 1 Super Admin (email: admin@boxibox.com, password: password)
- ✅ 2 Tenants de démo (Demo Storage, Central Box)
- ✅ Sites, buildings, floors, boxes
- ✅ Clients de test
- ✅ Contrats actifs
- ✅ Factures et paiements

### Étape 5: Compiler le frontend

```bash
# Build des assets (développement)
npm run build

# OU en mode watch pour le développement
npm run dev
```

### Étape 6: Lancer le serveur

Dans un terminal:
```bash
php artisan serve
```

Dans un autre terminal (optionnel, pour les jobs en arrière-plan):
```bash
php artisan queue:work
```

---

## 🎉 ACCÉDER À L'APPLICATION

Une fois le serveur lancé, ouvrez votre navigateur:

**URL:** http://localhost:8000

### Comptes de connexion par défaut:

#### Super Admin
- **Email:** admin@boxibox.com
- **Mot de passe:** password
- **Accès:** Gestion de tous les tenants

#### Tenant Admin (Demo Storage)
- **Email:** demo@storage.com
- **Mot de passe:** password
- **Accès:** Dashboard admin du tenant "Demo Storage"

#### Client (Particulier)
- **Email:** john@example.com
- **Mot de passe:** password
- **Accès:** Portail client (voir ses boxes, factures, paiements)

---

## 📁 STRUCTURE DU PROJET

```
boxibox-app/
├── app/
│   ├── Models/              # Modèles Eloquent (19 modèles)
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── API/V1/      # API REST v1
│   │   │   ├── Tenant/      # Admin des tenants
│   │   │   ├── Portal/      # Portail client
│   │   │   └── Booking/     # Réservation en ligne
│   │   └── Middleware/      # Middlewares personnalisés
│   └── Services/            # Services métier
│       ├── StripeService.php
│       ├── BillingService.php
│       ├── PricingService.php
│       ├── AnalyticsService.php
│       └── ReportService.php
│
├── database/
│   ├── migrations/          # 26 migrations créées
│   └── seeders/             # Seeders pour données de démo
│
├── resources/
│   ├── js/
│   │   ├── Pages/           # Pages Vue.js (34 pages)
│   │   │   ├── Auth/        # Connexion, inscription
│   │   │   ├── Tenant/      # Dashboard admin
│   │   │   ├── Portal/      # Dashboard client
│   │   │   └── Booking/     # Réservation publique
│   │   └── Components/      # Composants réutilisables
│   └── css/
│       └── app.css          # Styles Tailwind CSS
│
└── routes/
    ├── web.php              # Routes web (Inertia)
    ├── api.php              # API REST
    └── console.php          # Commandes Artisan
```

---

## 🎯 FONCTIONNALITÉS DISPONIBLES

### 1️⃣ Dashboard Admin Tenant
- **KPIs en temps réel:** Occupation, revenus MRR, nombre de clients
- **Graphiques:** Occupation par mois, revenus mensuels
- **Quick actions:** Nouveau contrat, nouveau client, nouveau box
- **Notifications:** Alertes factures impayées, contrats expirant

### 2️⃣ Gestion des Boxes
- **Liste complète** avec filtres (site, statut, taille, prix)
- **CRUD complet:** Créer, voir, éditer, supprimer
- **Statuts:** Available, Occupied, Maintenance, Reserved
- **Plan de sol:** Vue visuelle des boxes par étage

### 3️⃣ CRM Clients
- **Fiche client complète:** Coordonnées, documents, historique
- **Gestion contrats:** Voir tous les contrats du client
- **Facturation:** Historique factures et paiements
- **Notes internes:** Ajouter des notes privées

### 4️⃣ Gestion Contrats
- **Créer contrat:** Lier client + box, définir prix et durée
- **Suivi:** Actifs, en préavis, terminés
- **Renouvellement auto:** Option auto-renewal
- **Codes d'accès:** Générés automatiquement
- **Signature électronique:** PDF signable en ligne

### 5️⃣ Facturation Automatique
- **Génération auto:** Factures récurrentes mensuelles
- **Envoi email:** PDF envoyé automatiquement
- **Statuts:** Draft, Sent, Paid, Overdue, Cancelled
- **Relances:** Emails de rappel automatiques
- **Multi-devises:** Support EUR, USD, GBP

### 6️⃣ Paiements
- **Méthodes:** CB (Stripe), Virement, Cash, Chèque
- **Prélèvement auto:** Via Stripe pour clients récurrents
- **Historique:** Tous les paiements tracés
- **Rapports:** Export Excel/PDF

### 7️⃣ Portail Client Self-Service
- **Dashboard personnel:** Vue d'ensemble locations et factures
- **Mes boxes:** Voir détails, codes d'accès 24/7
- **Mes factures:** Télécharger PDF, payer en ligne
- **Mon profil:** Modifier coordonnées, documents

### 8️⃣ Réservation en Ligne (Booking)
- **Catalogue boxes:** Voir boxes disponibles avec photos
- **Réservation directe:** Sélectionner box, dates, produits additionnels
- **Paiement en ligne:** Stripe intégré
- **Confirmation:** Email + SMS avec code d'accès

### 9️⃣ Analytics & Rapports
- **Tableaux de bord:** Métriques clés avec Chart.js
- **Rapports personnalisés:** Export Excel, PDF
- **Prédictions:** Occupation future, revenus prévisionnels

### 🔟 Multi-Tenancy
- **Isolation complète:** Chaque tenant a ses propres données
- **Plans tarifaires:** Free, Starter, Professional, Enterprise
- **Limites configurables:** Sites, boxes, users selon plan
- **Sous-domaines:** tenant1.boxibox.com

---

## 🛠️ COMMANDES UTILES

### Développement

```bash
# Lancer serveur de développement
php artisan serve

# Watch des changements frontend
npm run dev

# Travailler sur les jobs de queue
php artisan queue:work

# Voir les logs en temps réel
php artisan pail
```

### Base de données

```bash
# Créer une nouvelle migration
php artisan make:migration create_something_table

# Exécuter les migrations
php artisan migrate

# Rollback dernière migration
php artisan migrate:rollback

# Reset complet + seed
php artisan migrate:fresh --seed
```

### Création de code

```bash
# Créer un modèle
php artisan make:model NomModele

# Créer un controller
php artisan make:controller NomController

# Créer un seeder
php artisan make:seeder NomSeeder

# Créer un service
php artisan make:service NomService
```

### Cache & Optimisation

```bash
# Nettoyer tous les caches
php artisan optimize:clear

# Mettre en cache la config (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 🔧 CONFIGURATION AVANCÉE

### Utiliser MySQL au lieu de SQLite

1. Créer une base de données MySQL:
```sql
CREATE DATABASE boxibox CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Modifier `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=boxibox
DB_USERNAME=root
DB_PASSWORD=votre_mot_de_passe
```

3. Relancer les migrations:
```bash
php artisan migrate:fresh --seed
```

### Activer Redis (Performance)

1. Installer Redis localement

2. Modifier `.env`:
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

3. Installer predis:
```bash
composer require predis/predis
```

### Configurer Stripe (Paiements)

1. Créer un compte sur https://stripe.com

2. Récupérer vos clés API (mode test): https://dashboard.stripe.com/apikeys

3. Modifier `.env`:
```env
STRIPE_KEY=pk_test_votre_cle_publique
STRIPE_SECRET=sk_test_votre_cle_secrete
```

4. Redémarrer le serveur

### Configurer les emails

**Avec Mailtrap (développement):**

1. Créer compte sur https://mailtrap.io

2. Modifier `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=votre_username
MAIL_PASSWORD=votre_password
```

**Avec SendGrid (production):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.sendgrid.net
MAIL_PORT=587
MAIL_USERNAME=apikey
MAIL_PASSWORD=votre_api_key_sendgrid
```

---

## 📊 JOBS AUTOMATIQUES (CRON)

Pour activer les tâches planifiées (facturation récurrente, relances, etc.):

1. Ajouter dans le crontab:
```bash
* * * * * cd /chemin/vers/boxibox-app && php artisan schedule:run >> /dev/null 2>&1
```

**Jobs automatiques configurés:**
- ✅ Génération factures mensuelles récurrentes
- ✅ Vérification contrats expirant (notification J-30, J-15, J-7)
- ✅ Vérification factures impayées (relance J+5, J+10, J+15)
- ✅ Cleanup sessions expirées
- ✅ Backup base de données (quotidien)

---

## 🐛 RÉSOLUTION DE PROBLÈMES

### Erreur: "No application encryption key"
```bash
php artisan key:generate
```

### Erreur: "Class not found"
```bash
composer dump-autoload
```

### Frontend ne compile pas
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

### Erreur de permission (storage/logs)
```bash
chmod -R 775 storage bootstrap/cache
```

### Base de données locked (SQLite)
```bash
# Supprimer le fichier database.sqlite et recréer
rm database/database.sqlite
touch database/database.sqlite
php artisan migrate:fresh --seed
```

---

## 🧪 TESTS

Lancer les tests automatisés:

```bash
# Tous les tests
php artisan test

# Tests spécifiques
php artisan test --filter=TenantTest

# Avec coverage
php artisan test --coverage
```

---

## 📚 DOCUMENTATION ADDITIONNELLE

- **Architecture technique:** `STATUS.md`
- **API REST:** `API_MOBILE.md`
- **Plan de sol éditeur:** `FLOOR_PLAN_GUIDE.md`
- **Guide de déploiement:** `DEPLOYMENT_GUIDE.md`
- **Roadmap fonctionnalités:** `ROADMAP.md`

---

## 🚀 MISE EN PRODUCTION

### Checklist avant déploiement

- [ ] `.env` configuré en mode production (`APP_ENV=production`, `APP_DEBUG=false`)
- [ ] `APP_KEY` généré et sécurisé
- [ ] Base de données PostgreSQL ou MySQL configurée
- [ ] Redis configuré pour cache et sessions
- [ ] Stripe en mode live (clés production)
- [ ] Emails configurés (SendGrid/Mailgun)
- [ ] Certificat SSL/HTTPS installé
- [ ] Caches optimisés (`php artisan optimize`)
- [ ] Queue worker en service systemd
- [ ] Cron job configuré pour `schedule:run`
- [ ] Backup automatique configuré
- [ ] Monitoring (Sentry, New Relic, etc.)

### Services recommandés

**Hébergement:**
- DigitalOcean (App Platform): À partir de 25€/mois
- AWS Lightsail: À partir de 10€/mois
- Laravel Forge + DigitalOcean: 15€ + 6€/mois

**Base de données:**
- PostgreSQL 15+ (recommandé)
- MySQL 8.0+

**Cache & Queue:**
- Redis 7+

**Stockage fichiers:**
- AWS S3
- DigitalOcean Spaces

**Email:**
- SendGrid: 15€/mois (40k emails)
- Mailgun: 35€/mois (50k emails)

**Monitoring:**
- Sentry (erreurs)
- New Relic (performance)
- UptimeRobot (disponibilité)

---

## 💡 CONSEILS

### Performance
- Utilisez **Redis** pour cache/sessions en production
- Activez **OPcache** PHP
- Utilisez **CDN** pour assets statiques (CloudFlare)
- Activez **gzip** compression
- Optimisez images (WebP, lazy loading)

### Sécurité
- **HTTPS** obligatoire en production
- **2FA** pour admins (déjà implémenté)
- **CSRF** tokens (Laravel par défaut)
- **XSS** protection (escape automatique Blade/Vue)
- **SQL Injection** protection (Eloquent par défaut)
- Backups quotidiens automatiques
- Logs d'audit (déjà implémenté)

### SEO & Marketing
- Sitemap XML généré automatiquement
- Meta tags optimisés
- Schema.org markup pour Google
- Google Analytics intégré
- Facebook Pixel support

---

## 📞 SUPPORT & CONTRIBUTION

### Besoin d'aide ?
- 📧 Email: support@boxibox.com
- 📖 Documentation: https://docs.boxibox.com
- 💬 Discord: https://discord.gg/boxibox

### Rapporter un bug
Ouvrez une issue sur GitHub avec:
- Description du problème
- Steps pour reproduire
- Version PHP/Laravel
- Logs d'erreur

### Contribuer
Les Pull Requests sont bienvenues! Consultez `CONTRIBUTING.md`

---

## 🎓 RESSOURCES D'APPRENTISSAGE

**Laravel:**
- Documentation officielle: https://laravel.com/docs
- Laracasts vidéos: https://laracasts.com

**Vue.js:**
- Documentation officielle: https://vuejs.org
- Vue Mastery: https://www.vuemastery.com

**Inertia.js:**
- Documentation: https://inertiajs.com

**Stripe:**
- Documentation API: https://stripe.com/docs/api
- Testing: https://stripe.com/docs/testing

---

## ✅ PROCHAINES ÉTAPES

Maintenant que votre application est lancée, vous pouvez:

1. **Explorer l'interface admin** - Connectez-vous avec demo@storage.com
2. **Créer vos premiers boxes** - Dans Sites > Boxes
3. **Ajouter des clients** - Dans Customers
4. **Créer des contrats** - Lier clients et boxes
5. **Tester la facturation** - Générer une facture
6. **Essayer le portail client** - Connectez-vous avec john@example.com
7. **Personnaliser le design** - Modifier les couleurs dans `tailwind.config.js`
8. **Configurer Stripe** - Pour activer les paiements en ligne
9. **Créer votre premier tenant** - Si vous êtes super admin

---

## 🎉 FÉLICITATIONS !

Vous avez maintenant une application SaaS complète de gestion de box de stockage!

**Fonctionnalités opérationnelles:**
- ✅ Multi-tenancy
- ✅ Dashboard analytics
- ✅ Gestion complète (sites, boxes, clients, contrats)
- ✅ Facturation automatique
- ✅ Paiements en ligne (Stripe)
- ✅ Portail client self-service
- ✅ Réservation en ligne
- ✅ API REST
- ✅ 34 pages Vue.js
- ✅ 19 modèles Eloquent
- ✅ 22 controllers
- ✅ 5 services métier
- ✅ 26 migrations
- ✅ Seeders de démo

**Prête pour la production!** 🚀

---

**Version:** 1.0.0
**Date:** 22 Novembre 2025
**Auteur:** Claude AI + Haythem SAA
**License:** MIT
