# Boxibox - Multi-Tenant Storage Management Application

## 🚀 Quick Start Guide

### Prerequisites
- PHP 8.4+
- Composer
- Node.js 18+ & npm
- PostgreSQL/MySQL/SQLite
- Redis (optional, for caching)

### Installation Steps

1. **Install PHP Dependencies**
   ```bash
   composer install
   ```

2. **Install JavaScript Dependencies**
   ```bash
   npm install
   ```

3. **Configure Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Configure Database**
   Edit `.env` file with your database credentials:
   ```env
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=boxibox
   DB_USERNAME=root
   DB_PASSWORD=
   ```

5. **Run Migrations & Seeders**
   ```bash
   php artisan migrate:fresh --seed
   ```

6. **Compile Assets**
   ```bash
   # Development
   npm run dev

   # Production
   npm run build
   ```

7. **Start Development Server**
   ```bash
   php artisan serve
   ```

8. **Access Application**
   - URL: http://localhost:8000
   - Demo Admin: admin@demo-storage.com
   - Password: password

## 📦 What's Included

### Database Schema (19 Migrations)
- **Tenants**: Multi-tenant companies with subscription plans
- **Sites**: Storage locations with GPS coordinates
- **Buildings**: Buildings per site (indoor/outdoor/mixed)
- **Floors**: Floors with floor plan support
- **Boxes**: Storage units with dynamic pricing
- **Customers**: Complete CRM with KYC tracking
- **Contracts**: Rental contracts with e-signatures
- **Invoices**: Recurring billing system
- **Payments**: Multi-gateway payment processing
- **Messages**: Tenant-client messaging
- **Notifications**: Multi-channel notifications
- **Pricing Rules**: Dynamic pricing engine
- **Subscriptions**: Stripe integration
- **Floor Plans**: Visual floor plan editor

### Eloquent Models (14 Models)
All models include:
- Complete relationships (belongsTo, hasMany, etc.)
- Query scopes for common queries
- Accessors for computed properties
- Helper methods for business logic
- Soft deletes where applicable

### Roles & Permissions
- **super_admin**: Full platform access
- **tenant_admin**: Full tenant access
- **tenant_staff**: Limited tenant access
- **client**: Customer portal access

50+ granular permissions for CRUD operations

### Frontend Stack
- **Vue 3** (Composition API)
- **Inertia.js 2.0** (SPA without API)
- **Tailwind CSS 4** (Latest version)
- **Heroicons** (Beautiful icons)
- Responsive layouts with mobile menu
- Animated dashboard with stat cards

## 🎯 Next Steps

### Immediate Development
1. Implement full authentication (Laravel Breeze/Fortify)
2. Create remaining CRUD controllers
3. Build floor plan drag & drop editor
4. Integrate Stripe for payments
5. Add email/SMS notifications

### Future Features
- Advanced analytics & reporting
- Mobile app (React Native)
- API for third-party integrations
- Automated contract renewals
- Multi-language support
- Document management system

## 🛠️ Development Commands

```bash
# Run migrations
php artisan migrate

# Fresh migrations with seed data
php artisan migrate:fresh --seed

# Create new migration
php artisan make:migration create_example_table

# Create new controller
php artisan make:controller ExampleController

# Create new model
php artisan make:model Example -m

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run tests
php artisan test

# Code formatting
./vendor/bin/pint
```

## 📊 Database Schema Overview

```
tenants (companies)
  ├── subscriptions (billing)
  ├── sites (locations)
  │   ├── buildings
  │   │   └── floors
  │   │       ├── boxes (storage units)
  │   │       └── floor_plans (visual editor)
  │   └── pricing_rules (dynamic pricing)
  ├── customers (CRM)
  │   ├── contracts (rentals)
  │   │   └── invoices
  │   │       └── payments
  │   └── messages
  └── notifications
```

## 🔐 Security Features

- Role-based access control (Spatie Permission)
- Multi-tenant data isolation
- Soft deletes for data retention
- Password hashing (bcrypt)
- CSRF protection
- SQL injection prevention
- XSS protection

## 🌐 API Endpoints (Future)

Once API is implemented:
- `/api/v1/sites` - Sites CRUD
- `/api/v1/boxes` - Boxes CRUD
- `/api/v1/customers` - Customers CRUD
- `/api/v1/contracts` - Contracts CRUD
- `/api/v1/invoices` - Invoices CRUD
- `/api/v1/payments` - Payment processing

## 📝 License

Proprietary - All rights reserved

## 👥 Support

For issues or questions:
- Email: support@boxibox.com
- Documentation: https://docs.boxibox.com
