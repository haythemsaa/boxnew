# 🗺️ Feuille de Route - Boxibox

## ✅ Phase 1: Foundation (TERMINÉE)

### Backend
- [x] Installation Laravel 12.39.0 + PHP 8.4
- [x] Configuration Vite + Vue 3 + Inertia.js 2.0
- [x] Configuration Tailwind CSS 4
- [x] 19 migrations database complètes
- [x] 14 modèles Eloquent avec relations
- [x] Spatie Permission (4 rôles, 50+ permissions)
- [x] Seeders (roles, permissions, demo tenant)
- [x] Middleware Inertia configuré
- [x] User model avec HasRoles trait

### Frontend
- [x] Layout AuthenticatedLayout (sidebar + mobile menu)
- [x] Layout GuestLayout
- [x] Dashboard page avec 8 stat cards animées
- [x] Composant NavLink avec Heroicons
- [x] Composant StatsCard réutilisable
- [x] Pages Sites/Index et Boxes/Index
- [x] Root template Blade pour Inertia

### Documentation
- [x] README_SETUP.md (guide installation)
- [x] COMMANDS.md (commandes utiles)
- [x] ROADMAP.md (ce fichier)
- [x] 9 commits git avec messages clairs

---

## 🚧 Phase 2: Authentification (PRIORITÉ)

### Tasks
- [ ] Installer Laravel Breeze (Inertia stack)
  ```bash
  composer require laravel/breeze --dev
  php artisan breeze:install vue --inertia
  ```
- [ ] Pages Login, Register, Forgot Password
- [ ] Email verification
- [ ] Two-factor authentication (optionnel)
- [ ] Page de profil utilisateur
- [ ] Changement de mot de passe
- [ ] Guards pour tenant/client/super_admin

### Controllers à créer
- Auth/LoginController
- Auth/RegisterController
- Auth/ForgotPasswordController
- Auth/ProfileController

### Estimation: 2-3 jours

---

## 📦 Phase 3: CRUD Complets (EN COURS)

### 3.1 Sites Management
- [ ] SiteController.index() - Liste avec pagination
- [ ] SiteController.create() - Formulaire création
- [ ] SiteController.store() - Validation + sauvegarde
- [ ] SiteController.edit() - Formulaire édition
- [ ] SiteController.update() - Validation + mise à jour
- [ ] SiteController.destroy() - Soft delete
- [ ] Pages Vue: Sites/Create, Sites/Edit
- [ ] Form validation (FormRequest)
- [ ] Tests Feature pour CRUD

### 3.2 Buildings Management
- [ ] BuildingController (resource)
- [ ] Pages Vue: Buildings/Index, Create, Edit
- [ ] Relation avec Sites

### 3.3 Floors Management
- [ ] FloorController (resource)
- [ ] Pages Vue: Floors/Index, Create, Edit
- [ ] Relation avec Buildings
- [ ] Sélecteur de floor plan

### 3.4 Boxes Management
- [ ] BoxController.index() - avec filtres avancés
- [ ] BoxController.create/store/edit/update
- [ ] Calculateur de volume automatique
- [ ] Gestion des statuts (available, occupied, etc.)
- [ ] Gestion des features (climate, electricity, etc.)
- [ ] Pricing dynamique avec règles
- [ ] Code d'accès unique auto-généré
- [ ] Pages Vue complètes avec images

### 3.5 Customers Management
- [ ] CustomerController (resource)
- [ ] Formulaire avec type (individual/company)
- [ ] Upload document KYC
- [ ] Gestion billing address séparée
- [ ] Historique contrats/paiements
- [ ] Score crédit automatique

### 3.6 Contracts Management
- [ ] ContractController (resource)
- [ ] Génération numéro contrat automatique
- [ ] Signature électronique (canvas)
- [ ] PDF generation (DomPDF/Snappy)
- [ ] Email notification
- [ ] Workflow: draft → pending → active → terminated
- [ ] Auto-renewal logic

### 3.7 Invoices Management
- [ ] InvoiceController (resource)
- [ ] Génération automatique mensuelle
- [ ] Invoice items (JSON)
- [ ] Calcul taxes automatique
- [ ] PDF generation
- [ ] Email sending
- [ ] Reminders automatiques (scheduled job)
- [ ] Multi-currency support

### 3.8 Payments Management
- [ ] PaymentController (process, refund)
- [ ] Stripe integration
- [ ] PayPal integration (optionnel)
- [ ] SEPA direct debit (optionnel)
- [ ] Webhook handlers
- [ ] Receipt generation

### Estimation: 4-5 semaines

---

## 🎨 Phase 4: Floor Plan Editor (COMPLEXE)

### Technologies
- Konva.js ou Fabric.js (canvas manipulation)
- Vue 3 + Composition API
- Drag & Drop API
- JSON structure pour elements

### Features
- [ ] Canvas de dessin responsive
- [ ] Grille avec snap-to-grid
- [ ] Outils: walls, boxes, doors, corridors
- [ ] Sélection et déplacement objets
- [ ] Rotation et resize
- [ ] Propriétés panel (dimensions, couleur)
- [ ] Background image upload
- [ ] Zoom et pan
- [ ] Undo/Redo (history stack)
- [ ] Save/Load (JSON → DB)
- [ ] Export PNG/PDF
- [ ] Versioning des plans
- [ ] Template library (plans pré-faits)

### Components Vue
- FloorPlanEditor.vue (main)
- FloorPlanToolbar.vue
- FloorPlanCanvas.vue
- FloorPlanProperties.vue
- FloorPlanLayersList.vue

### JSON Structure Example
```json
{
  "version": 1,
  "elements": [
    {
      "type": "wall",
      "id": "wall-1",
      "points": [[0, 0], [100, 0]],
      "thickness": 10,
      "color": "#333"
    },
    {
      "type": "box",
      "id": "box-1",
      "box_id": 123,
      "x": 50,
      "y": 50,
      "width": 30,
      "height": 20,
      "rotation": 0,
      "color": "#4CAF50"
    }
  ]
}
```

### Estimation: 3-4 semaines

---

## 💰 Phase 5: Payment Gateway Integration

### Stripe
- [ ] Configuration Stripe API keys
- [ ] Stripe Checkout Session
- [ ] Webhook handlers (payment_intent.succeeded, etc.)
- [ ] Subscription management
- [ ] Customer Portal
- [ ] Invoice auto-payment
- [ ] Failed payment retry logic

### Components
- PaymentGatewayService.php
- StripeService.php
- Webhook Controller

### Estimation: 1-2 semaines

---

## 🧮 Phase 6: Dynamic Pricing Engine

### Features
- [ ] PricingRuleController (CRUD)
- [ ] Rule types:
  - Occupation-based (discount si faible taux)
  - Seasonal (prix été/hiver)
  - Duration discount (6 mois, 12 mois)
  - Size-based (prix au m³)
  - Promotional (codes promo)
- [ ] Priority system
- [ ] Stackable rules
- [ ] Min/max price constraints
- [ ] Date validity
- [ ] Preview pricing avant apply
- [ ] Historique changements prix
- [ ] Dashboard analytics

### Estimation: 2 semaines

---

## 👥 Phase 7: Client Portal

### Features
- [ ] Client dashboard
- [ ] View active contracts
- [ ] View invoices & download PDF
- [ ] Make payments
- [ ] View payment history
- [ ] Update profile
- [ ] Document upload (insurance, etc.)
- [ ] Message tenant
- [ ] Notifications (email + in-app)
- [ ] Mobile responsive

### Routes
- /client/dashboard
- /client/contracts
- /client/invoices
- /client/payments
- /client/messages
- /client/profile

### Estimation: 2 semaines

---

## 🔔 Phase 8: Notifications System

### Multi-channel
- [ ] Email (Mailgun/SendGrid/SES)
- [ ] SMS (Twilio/Vonage)
- [ ] In-app notifications
- [ ] Push notifications (optionnel)

### Types
- Payment reminders
- Contract expiring
- Invoice generated
- Payment received
- Message received
- Maintenance scheduled

### Implementation
- Laravel Notifications
- Queue jobs
- Scheduled commands (cron)
- NotificationService.php

### Estimation: 1 semaine

---

## 👑 Phase 9: SuperAdmin Dashboard

### Features
- [ ] View all tenants
- [ ] Create/Edit/Delete tenants
- [ ] Manage subscriptions
- [ ] View platform statistics
- [ ] Revenue analytics
- [ ] User activity logs
- [ ] System health monitoring
- [ ] Email templates management
- [ ] Feature flags per tenant

### Controllers
- SuperAdmin/DashboardController
- SuperAdmin/TenantController
- SuperAdmin/SubscriptionController
- SuperAdmin/AnalyticsController

### Estimation: 2 semaines

---

## 📊 Phase 10: Analytics & Reports

### Features
- [ ] Revenue reports (daily, monthly, yearly)
- [ ] Occupation rate trends
- [ ] Customer analytics
- [ ] Box utilization
- [ ] Contract renewal rates
- [ ] Payment success rates
- [ ] Export to Excel/PDF
- [ ] Charts (Chart.js ou ApexCharts)
- [ ] Customizable dashboards

### Estimation: 2-3 semaines

---

## 🔍 Phase 11: Search & Filters

### Features
- [ ] Global search (tenants, customers, boxes)
- [ ] Advanced filters per entity
- [ ] Saved searches
- [ ] Quick filters
- [ ] Sort options
- [ ] Elasticsearch integration (optionnel)

### Estimation: 1 semaine

---

## 📱 Phase 12: Mobile App (Optionnel)

### Stack
- React Native ou Flutter
- API Laravel
- JWT authentication
- Push notifications
- Offline mode

### Features
- Tenant mobile app
- Client mobile app
- QR code scanner (access codes)
- Camera for document upload
- Maps integration

### Estimation: 2-3 mois

---

## 🚀 Phase 13: Optimizations & DevOps

### Performance
- [ ] Database indexes optimization
- [ ] Query optimization (N+1 problems)
- [ ] Redis caching
- [ ] Lazy loading images
- [ ] Code splitting (Vite)
- [ ] CDN for assets

### Security
- [ ] Penetration testing
- [ ] OWASP Top 10 checks
- [ ] Rate limiting
- [ ] CORS configuration
- [ ] Security headers
- [ ] Audit logs

### DevOps
- [ ] Docker setup
- [ ] CI/CD pipeline (GitHub Actions)
- [ ] Automated tests
- [ ] Staging environment
- [ ] Production monitoring (Sentry)
- [ ] Backups automation
- [ ] SSL certificates

### Estimation: 2-3 semaines

---

## 🧪 Phase 14: Testing

### Tests
- [ ] Unit tests (models, services)
- [ ] Feature tests (controllers, routes)
- [ ] Browser tests (Laravel Dusk)
- [ ] API tests
- [ ] Integration tests
- [ ] Performance tests
- [ ] Security tests

### Tools
- PHPUnit
- Pest (alternative)
- Laravel Dusk
- Faker

### Target Coverage: 80%+

### Estimation: 2-3 semaines

---

## 📚 Phase 15: Documentation

### Developer Docs
- [ ] API documentation (Swagger/OpenAPI)
- [ ] Code comments (PHPDoc)
- [ ] Architecture diagram
- [ ] Database schema diagram
- [ ] Setup guide pour développeurs
- [ ] Contributing guidelines

### User Docs
- [ ] User manual (tenants)
- [ ] User manual (clients)
- [ ] Video tutorials
- [ ] FAQ
- [ ] Troubleshooting guide

### Estimation: 1-2 semaines

---

## 🌍 Phase 16: Internationalization (i18n)

### Languages
- Français (primary)
- English
- Espagnol (optionnel)
- Arabe (optionnel)

### Implementation
- [ ] Laravel translations
- [ ] Vue i18n
- [ ] Date/time localization
- [ ] Currency localization
- [ ] RTL support

### Estimation: 1-2 semaines

---

## 📦 Technologies & Packages à Ajouter

### Backend
- `laravel/breeze` - Authentication scaffolding
- `barryvdh/laravel-dompdf` - PDF generation
- `maatwebsite/excel` - Excel export
- `spatie/laravel-backup` - Automated backups
- `spatie/laravel-activitylog` - Activity logging
- `spatie/laravel-query-builder` - Advanced filtering
- `intervention/image` - Image manipulation

### Frontend
- `@vueuse/core` - Vue composition utilities
- `chart.js` + `vue-chartjs` - Charts
- `vee-validate` - Form validation
- `vue-toastification` - Toast notifications
- `dayjs` - Date manipulation
- `fabric.js` ou `konva` - Canvas manipulation

---

## 🎯 Timeline Estimé

- **Phase 1**: ✅ Terminée (2 semaines)
- **Phase 2-3**: 6-8 semaines
- **Phase 4**: 3-4 semaines
- **Phase 5-8**: 6-7 semaines
- **Phase 9-11**: 5-6 semaines
- **Phase 12**: 2-3 mois (optionnel)
- **Phase 13-15**: 5-8 semaines

**Total estimé**: 6-9 mois pour application complète
**MVP**: 3-4 mois (sans mobile app et features avancées)

---

## 💡 Priorités Recommandées

### MVP (3-4 mois)
1. Authentification ✅
2. CRUD Sites, Boxes, Customers
3. CRUD Contracts, Invoices
4. Paiements Stripe basiques
5. Client Portal basique
6. Email notifications

### V1.0 (6 mois)
1. MVP +
2. Floor Plan Editor
3. Dynamic Pricing
4. Analytics basiques
5. SuperAdmin dashboard

### V2.0 (9 mois)
1. V1.0 +
2. Mobile app
3. Advanced analytics
4. Optimizations
5. Full testing
6. Documentation complète
