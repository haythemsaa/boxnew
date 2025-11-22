# 🏢 Boxibox - Application Multi-Tenant SaaS

Application complète de gestion de box de stockage avec trois interfaces distinctes.

## 🎯 Interfaces

### 1. SuperAdmin Dashboard
- Gestion de la plateforme
- Gestion des tenants (entreprises)
- Gestion des abonnements et facturation
- Analytics globales

### 2. Tenant Dashboard
- Gestion des sites et buildings
- Éditeur de plan de sol visuel (drag & drop)
- Gestion des box (CRUD, pricing, statut)
- CRM Clients complet
- Gestion des contrats et signatures
- Facturation automatique
- Intégration paiements (Stripe, PayPal, SEPA)
- Pricing dynamique
- Analytics et rapports

### 3. Client Portal
- Dashboard personnel
- Mes box louées
- Mes contrats
- Mes factures et paiements
- Messagerie avec la société
- Notifications et rappels

## 🛠️ Stack Technique

**Backend**: Laravel 12, PostgreSQL, Redis, Spatie Packages (Multitenancy, Permissions, Media), Stripe

**Frontend**: Vue 3, Inertia.js, Tailwind CSS 4, Chart.js, HeroIcons

## 🚀 Installation Rapide

```bash
# Backend
composer install

# Frontend
npm install

# Configuration
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Development
php artisan serve
npm run dev
```

## 📚 Documentation

- **[IMPLEMENTATION_STATUS.md](../IMPLEMENTATION_STATUS.md)** - Vue complète de l'architecture
- **[DEVELOPPEMENT_GUIDE.md](../DEVELOPPEMENT_GUIDE.md)** - Guide de développement avec templates

## 📊 Status

✅ **Complété**:
- Laravel 12 + Vue 3 + Inertia.js configuré
- Tailwind CSS 4 avec thème personnalisé
- 15 migrations de base créées (3 complètes: tenants, sites, boxes)
- Packages Spatie installés

⏳ **À Compléter**:
- Finaliser les 12 migrations restantes (templates fournis)
- Créer les modèles Eloquent
- Créer les Controllers et Services
- Créer les composants Vue
- Implémenter l'éditeur de plan de sol

## 💰 Plans

1. **Free** - 1 site, 50 boxes, 3 users
2. **Starter** - 3 sites, 200 boxes, 10 users - 49€/mois
3. **Professional** - 10 sites, 1000 boxes, 50 users - 149€/mois
4. **Enterprise** - Illimité - Sur devis

## 📞 Support

Documentation: Voir les fichiers `.md` à la racine du projet

---

**Version**: 0.1.0-alpha | **Status**: 🟡 En développement
