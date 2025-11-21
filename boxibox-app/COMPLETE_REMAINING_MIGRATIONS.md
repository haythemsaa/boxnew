# Instructions pour Compléter les Migrations Restantes

## Migrations Déjà Complétées ✅

1. ✅ create_tenants_table
2. ✅ create_sites_table
3. ✅ create_buildings_table
4. ✅ create_floors_table
5. ✅ create_boxes_table
6. ✅ create_customers_table
7. ✅ create_contracts_table
8. ✅ create_invoices_table
9. ✅ create_payments_table

## Migrations Restantes à Compléter

Copier-coller les structures suivantes dans les fichiers correspondants :

### 10. Messages Table

```php
// Dans database/migrations/xxxx_create_messages_table.php
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');

    $table->string('subject')->nullable();
    $table->text('body');
    $table->enum('type', ['message', 'notification', 'system'])->default('message');

    $table->foreignId('parent_id')->nullable()->constrained('messages')->onDelete('cascade');
    $table->string('thread_id')->index();

    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();

    $table->json('attachments')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'sender_id']);
    $table->index(['tenant_id', 'recipient_id']);
    $table->index('thread_id');
});
```

### 11. Notifications Table

```php
// Dans database/migrations/xxxx_create_notifications_table.php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');

    $table->enum('type', [
        'payment_reminder',
        'payment_received',
        'contract_expiring',
        'invoice_generated',
        'message_received'
    ]);
    $table->string('title');
    $table->text('message');

    $table->json('channels'); // ['email', 'sms', 'in_app']
    $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');

    $table->boolean('email_sent')->default(false);
    $table->timestamp('email_sent_at')->nullable();

    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();

    $table->string('related_type')->nullable();
    $table->unsignedBigInteger('related_id')->nullable();

    $table->timestamp('scheduled_for')->nullable();

    $table->json('data')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'user_id']);
    $table->index(['type', 'status']);
});
```

### 12. Pricing Rules Table

```php
// Dans database/migrations/xxxx_create_pricing_rules_table.php
Schema::create('pricing_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');

    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('type', [
        'occupation_based',
        'seasonal',
        'duration_discount',
        'size_based',
        'promotional'
    ]);
    $table->integer('priority')->default(0);
    $table->boolean('is_active')->default(true);

    $table->json('conditions');

    $table->enum('adjustment_type', ['percentage', 'fixed_amount'])->default('percentage');
    $table->decimal('adjustment_value', 10, 2);
    $table->decimal('min_price', 10, 2)->nullable();
    $table->decimal('max_price', 10, 2)->nullable();

    $table->date('valid_from')->nullable();
    $table->date('valid_until')->nullable();

    $table->boolean('stackable')->default(false);

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'is_active', 'priority']);
});
```

### 13. Subscriptions Table

```php
// Dans database/migrations/xxxx_create_subscriptions_table.php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    $table->enum('plan', ['free', 'starter', 'professional', 'enterprise'])->default('free');
    $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');

    $table->enum('status', [
        'active',
        'trialing',
        'past_due',
        'cancelled',
        'expired'
    ])->default('trialing');

    $table->date('trial_ends_at')->nullable();
    $table->date('started_at')->nullable();
    $table->date('current_period_start')->nullable();
    $table->date('current_period_end')->nullable();
    $table->date('cancelled_at')->nullable();

    $table->decimal('amount', 10, 2);
    $table->decimal('discount', 10, 2)->default(0);
    $table->string('currency', 3)->default('EUR');

    $table->string('stripe_subscription_id')->nullable()->unique();
    $table->string('stripe_customer_id')->nullable();

    $table->integer('quantity_sites')->default(1);
    $table->integer('quantity_boxes')->default(50);
    $table->integer('quantity_users')->default(3);

    $table->json('features')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'status']);
});
```

### 14. Floor Plans Table

```php
// Dans database/migrations/xxxx_create_floor_plans_table.php
Schema::create('floor_plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('site_id')->constrained()->onDelete('cascade');
    $table->foreignId('building_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('floor_id')->nullable()->constrained()->onDelete('set null');

    $table->string('name');
    $table->text('description')->nullable();
    $table->integer('version')->default(1);
    $table->boolean('is_active')->default(true);

    $table->decimal('width', 10, 2);
    $table->decimal('height', 10, 2);
    $table->decimal('scale', 10, 2)->default(1);
    $table->string('unit', 10)->default('meters');

    $table->json('elements'); // Walls, boxes, corridors, etc.

    $table->string('background_image')->nullable();
    $table->decimal('background_opacity', 3, 2)->default(0.5);

    $table->boolean('show_grid')->default(true);
    $table->integer('grid_size')->default(10);

    $table->integer('total_boxes_on_plan')->default(0);

    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'site_id']);
});
```

## Commandes pour Appliquer

Après avoir complété les migrations:

```bash
# Naviguer vers le projet
cd /home/user/boxnew/boxibox-app

# Exécuter les migrations
php artisan migrate

# En cas d'erreur, vérifier le statut
php artisan migrate:status

# Refresh complet (attention: supprime toutes les données)
php artisan migrate:fresh
```

## Notes Importantes

1. Les migrations sont déjà créées, il faut juste remplacer le contenu de la méthode `up()`
2. Toutes les clés étrangères utilisent `constrained()` pour auto-détecter la table
3. Les indexes sont ajoutés pour optimiser les performances
4. Soft deletes activé sur la plupart des tables critiques
5. Les champs JSON permettent la flexibilité future

## Vérification

Après avoir complété toutes les migrations, vérifier:

```bash
# Compter les migrations
ls -1 database/migrations/*.php | wc -l
# Devrait retourner 18 (15 custom + 3 Laravel default)

# Lister toutes les tables
php artisan db:show --counts

# Voir les relations
php artisan model:show Tenant
php artisan model:show Contract
```

---

**Status**: 9/14 migrations custom complétées (64%)
**Prochaine étape**: Compléter les 5 migrations restantes puis créer les modèles Eloquent
