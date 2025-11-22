# 🚀 Guide de Développement Complet - Boxibox

## Vue d'ensemble

Ce guide fournit tous les templates et instructions pour compléter l'application Boxibox multi-tenant.

## 📝 Migrations Restantes à Compléter

### 1. Buildings Migration

```php
// database/migrations/xxxx_create_buildings_table.php
Schema::create('buildings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('site_id')->constrained()->onDelete('cascade');
    $table->string('name');
    $table->string('code')->nullable();
    $table->integer('total_floors')->default(1);
    $table->enum('type', ['indoor', 'outdoor', 'mixed'])->default('indoor');
    $table->text('description')->nullable();
    $table->boolean('has_elevator')->default(false);
    $table->boolean('has_security')->default(false);
    $table->json('amenities')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'site_id']);
    $table->unique(['site_id', 'code']);
});
```

### 2. Floors Migration

```php
// database/migrations/xxxx_create_floors_table.php
Schema::create('floors', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('site_id')->constrained()->onDelete('cascade');
    $table->foreignId('building_id')->constrained()->onDelete('cascade');
    $table->integer('floor_number'); // 0 = Ground, 1 = First, -1 = Basement
    $table->string('name')->nullable(); // e.g., "Ground Floor", "Basement"
    $table->foreignId('floor_plan_id')->nullable()->constrained()->onDelete('set null');
    $table->integer('total_boxes')->default(0);
    $table->decimal('total_area', 10, 2)->nullable(); // in m²
    $table->json('settings')->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'site_id', 'building_id']);
    $table->unique(['building_id', 'floor_number']);
});
```

### 3. Customers Migration

```php
// database/migrations/xxxx_create_customers_table.php
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // Portal account

    // Personal Information
    $table->enum('type', ['individual', 'company'])->default('individual');
    $table->enum('civility', ['mr', 'mrs', 'ms', 'other'])->nullable();
    $table->string('first_name');
    $table->string('last_name');
    $table->string('company_name')->nullable();
    $table->date('birth_date')->nullable();
    $table->string('birth_place')->nullable();

    // Contact
    $table->string('email')->index();
    $table->string('phone');
    $table->string('mobile')->nullable();

    // Address
    $table->text('address');
    $table->string('address_2')->nullable();
    $table->string('city');
    $table->string('state')->nullable();
    $table->string('postal_code');
    $table->string('country')->default('FR');

    // Company Info (if type = company)
    $table->string('company_number')->nullable(); // SIRET
    $table->string('vat_number')->nullable();
    $table->string('legal_form')->nullable(); // SARL, SAS, etc.

    // Identity Documents
    $table->enum('id_type', ['id_card', 'passport', 'driving_license', 'residence_permit'])->nullable();
    $table->string('id_number')->nullable();
    $table->date('id_expiry')->nullable();
    $table->string('id_document_path')->nullable();

    // Billing
    $table->boolean('same_billing_address')->default(true);
    $table->text('billing_address')->nullable();
    $table->string('billing_city')->nullable();
    $table->string('billing_postal_code')->nullable();
    $table->string('billing_country')->nullable();

    // Preferences
    $table->enum('preferred_language', ['fr', 'en', 'es', 'de'])->default('fr');
    $table->enum('preferred_contact', ['email', 'phone', 'sms'])->default('email');
    $table->boolean('marketing_consent')->default(false);

    // Status & Scoring
    $table->enum('status', ['active', 'inactive', 'blocked', 'archived'])->default('active');
    $table->integer('credit_score')->default(0); // 0-100
    $table->boolean('payment_issues')->default(false);

    // Statistics
    $table->integer('total_contracts')->default(0);
    $table->integer('active_contracts')->default(0);
    $table->decimal('total_revenue', 10, 2)->default(0);
    $table->decimal('outstanding_balance', 10, 2)->default(0);

    // Notes
    $table->text('notes')->nullable();
    $table->text('internal_notes')->nullable();

    // Emergency Contact
    $table->string('emergency_contact_name')->nullable();
    $table->string('emergency_contact_phone')->nullable();

    // RGPD
    $table->timestamp('gdpr_consent_at')->nullable();
    $table->boolean('gdpr_export_requested')->default(false);

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'email']);
    $table->index(['tenant_id', 'status']);
    $table->index('phone');
});
```

### 4. Contracts Migration

```php
// database/migrations/xxxx_create_contracts_table.php
Schema::create('contracts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('site_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->foreignId('box_id')->constrained()->onDelete('cascade');

    // Contract Details
    $table->string('contract_number')->unique();
    $table->enum('status', ['draft', 'pending_signature', 'active', 'expired', 'terminated', 'cancelled'])->default('draft');
    $table->enum('type', ['standard', 'short_term', 'long_term', 'temporary'])->default('standard');

    // Dates
    $table->date('start_date');
    $table->date('end_date')->nullable();
    $table->date('actual_end_date')->nullable();
    $table->date('notice_date')->nullable(); // When customer gave notice
    $table->integer('notice_period_days')->default(30);

    // Renewal
    $table->boolean('auto_renew')->default(true);
    $table->enum('renewal_period', ['monthly', 'quarterly', 'yearly'])->default('monthly');
    $table->date('next_renewal_date')->nullable();

    // Pricing
    $table->decimal('monthly_price', 10, 2);
    $table->decimal('deposit_amount', 10, 2)->default(0);
    $table->boolean('deposit_paid')->default(false);
    $table->date('deposit_paid_at')->nullable();

    // Discounts
    $table->decimal('discount_percentage', 5, 2)->default(0);
    $table->decimal('discount_amount', 10, 2)->default(0);
    $table->string('discount_reason')->nullable();
    $table->date('discount_valid_until')->nullable();

    // Payment
    $table->enum('billing_frequency', ['monthly', 'quarterly', 'yearly'])->default('monthly');
    $table->integer('billing_day')->default(1); // Day of month for billing
    $table->enum('payment_method', ['card', 'bank_transfer', 'cash', 'cheque', 'sepa'])->default('card');
    $table->boolean('auto_pay')->default(false);
    $table->string('payment_method_id')->nullable(); // Stripe payment method ID

    // Access
    $table->string('access_code', 10)->nullable();
    $table->date('access_code_valid_until')->nullable();
    $table->string('key_number')->nullable();
    $table->boolean('key_given')->default(false);
    $table->boolean('key_returned')->default(false);

    // Insurance
    $table->boolean('insurance_required')->default(true);
    $table->boolean('insurance_provided')->default(false);
    $table->string('insurance_provider')->nullable();
    $table->string('insurance_policy_number')->nullable();
    $table->date('insurance_valid_until')->nullable();

    // Signature
    $table->boolean('signed_by_customer')->default(false);
    $table->timestamp('customer_signed_at')->nullable();
    $table->string('customer_signature_ip')->nullable();
    $table->text('customer_signature_data')->nullable(); // Base64 signature

    $table->boolean('signed_by_staff')->default(false);
    $table->foreignId('staff_user_id')->nullable()->constrained('users')->onDelete('set null');
    $table->timestamp('staff_signed_at')->nullable();

    // Documents
    $table->string('pdf_path')->nullable();
    $table->json('attached_documents')->nullable();

    // Termination
    $table->enum('termination_reason', [
        'customer_request',
        'non_payment',
        'breach_of_contract',
        'end_of_term',
        'other'
    ])->nullable();
    $table->text('termination_notes')->nullable();

    // Conditions
    $table->text('special_conditions')->nullable();
    $table->json('terms_accepted')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'customer_id']);
    $table->index(['tenant_id', 'box_id']);
    $table->index('contract_number');
    $table->index('status');
    $table->index(['start_date', 'end_date']);
});
```

### 5. Invoices Migration

```php
// database/migrations/xxxx_create_invoices_table.php
Schema::create('invoices', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');

    // Invoice Details
    $table->string('invoice_number')->unique();
    $table->enum('type', ['invoice', 'credit_note', 'proforma', 'reminder', 'deposit'])->default('invoice');
    $table->enum('status', ['draft', 'sent', 'paid', 'partial', 'overdue', 'cancelled'])->default('draft');

    // Dates
    $table->date('invoice_date');
    $table->date('due_date');
    $table->date('paid_at')->nullable();
    $table->date('sent_at')->nullable();

    // Period
    $table->date('period_start')->nullable();
    $table->date('period_end')->nullable();

    // Amounts
    $table->decimal('subtotal', 10, 2);
    $table->decimal('tax_rate', 5, 2)->default(20); // VAT percentage
    $table->decimal('tax_amount', 10, 2);
    $table->decimal('discount_amount', 10, 2)->default(0);
    $table->decimal('total', 10, 2);
    $table->decimal('paid_amount', 10, 2)->default(0);
    $table->decimal('balance', 10, 2)->virtualAs('total - paid_amount');

    // Currency
    $table->string('currency', 3)->default('EUR');
    $table->decimal('exchange_rate', 10, 6)->default(1);

    // Line Items (JSON)
    $table->json('items'); // Array of {description, quantity, unit_price, tax_rate, total}

    // Payment Info
    $table->string('payment_reference')->nullable();
    $table->enum('payment_method', ['card', 'bank_transfer', 'cash', 'cheque', 'sepa', 'stripe', 'paypal'])->nullable();

    // Documents
    $table->string('pdf_path')->nullable();
    $table->integer('pdf_download_count')->default(0);

    // Reminders
    $table->integer('reminder_count')->default(0);
    $table->date('last_reminder_sent')->nullable();
    $table->date('next_reminder_date')->nullable();

    // Late Fees
    $table->decimal('late_fee_amount', 10, 2)->default(0);
    $table->boolean('late_fee_applied')->default(false);

    // Notes
    $table->text('notes')->nullable();
    $table->text('internal_notes')->nullable();
    $table->text('footer_text')->nullable();

    // Recurring
    $table->boolean('is_recurring')->default(false);
    $table->foreignId('recurring_template_id')->nullable()->constrained('invoices')->onDelete('set null');

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'customer_id']);
    $table->index('invoice_number');
    $table->index('status');
    $table->index('due_date');
    $table->index(['invoice_date', 'status']);
});
```

### 6. Payments Migration

```php
// database/migrations/xxxx_create_payments_table.php
Schema::create('payments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->constrained()->onDelete('cascade');
    $table->foreignId('invoice_id')->nullable()->constrained()->onDelete('set null');
    $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');

    // Payment Details
    $table->string('payment_number')->unique();
    $table->enum('type', ['payment', 'refund', 'deposit', 'credit'])->default('payment');
    $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'])->default('pending');

    // Amounts
    $table->decimal('amount', 10, 2);
    $table->decimal('fee', 10, 2)->default(0); // Gateway fee
    $table->decimal('net_amount', 10, 2)->virtualAs('amount - fee');
    $table->string('currency', 3)->default('EUR');

    // Payment Method
    $table->enum('method', ['card', 'bank_transfer', 'cash', 'cheque', 'sepa', 'stripe', 'paypal', 'apple_pay', 'google_pay'])->default('card');
    $table->enum('gateway', ['stripe', 'paypal', 'sepa', 'manual'])->default('stripe');

    // Gateway Info
    $table->string('gateway_payment_id')->nullable()->index(); // Stripe payment_intent, PayPal transaction ID
    $table->string('gateway_customer_id')->nullable();
    $table->string('gateway_payment_method_id')->nullable();
    $table->json('gateway_response')->nullable();

    // Card Info (last 4 digits only)
    $table->string('card_brand')->nullable(); // Visa, Mastercard, etc.
    $table->string('card_last_four', 4)->nullable();
    $table->string('card_exp_month', 2)->nullable();
    $table->string('card_exp_year', 4)->nullable();

    // Bank Transfer Info
    $table->string('bank_reference')->nullable();
    $table->string('bank_name')->nullable();

    // Cheque Info
    $table->string('cheque_number')->nullable();
    $table->string('cheque_bank')->nullable();

    // Dates
    $table->timestamp('paid_at')->nullable();
    $table->timestamp('processed_at')->nullable();
    $table->timestamp('failed_at')->nullable();
    $table->date('reconciled_at')->nullable();

    // Refund
    $table->foreignId('refund_for_payment_id')->nullable()->constrained('payments')->onDelete('set null');
    $table->decimal('refunded_amount', 10, 2)->default(0);
    $table->string('refund_reason')->nullable();

    // Failure Info
    $table->string('failure_code')->nullable();
    $table->text('failure_message')->nullable();
    $table->integer('retry_count')->default(0);

    // Notes
    $table->text('notes')->nullable();
    $table->text('internal_notes')->nullable();

    // IP & Security
    $table->string('ip_address')->nullable();
    $table->string('user_agent')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'customer_id']);
    $table->index(['tenant_id', 'status']);
    $table->index('gateway_payment_id');
    $table->index('payment_number');
    $table->index(['paid_at', 'status']);
});
```

### 7. Messages Migration

```php
// database/migrations/xxxx_create_messages_table.php
Schema::create('messages', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('sender_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
    $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');

    // Message
    $table->string('subject')->nullable();
    $table->text('body');
    $table->enum('type', ['message', 'notification', 'reminder', 'system'])->default('message');
    $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

    // Thread
    $table->foreignId('parent_id')->nullable()->constrained('messages')->onDelete('cascade');
    $table->string('thread_id')->index(); // Group related messages

    // Status
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->boolean('is_starred')->default(false);
    $table->boolean('is_archived')->default(false);

    // Attachments
    $table->json('attachments')->nullable();
    $table->boolean('has_attachments')->default(false);

    // Metadata
    $table->string('ip_address')->nullable();
    $table->json('metadata')->nullable();

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'sender_id']);
    $table->index(['tenant_id', 'recipient_id']);
    $table->index('thread_id');
    $table->index(['is_read', 'created_at']);
});
```

### 8. Notifications Migration

```php
// database/migrations/xxxx_create_notifications_table.php
Schema::create('notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');

    // Notification Details
    $table->enum('type', [
        'payment_reminder',
        'payment_received',
        'payment_failed',
        'contract_expiring',
        'contract_expired',
        'contract_renewed',
        'invoice_generated',
        'message_received',
        'system_alert',
        'maintenance_scheduled',
        'access_code_changed'
    ]);
    $table->string('title');
    $table->text('message');
    $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');

    // Channels
    $table->json('channels'); // ['email', 'sms', 'push', 'in_app']
    $table->enum('status', ['pending', 'sent', 'failed', 'read'])->default('pending');

    // Delivery Status
    $table->boolean('email_sent')->default(false);
    $table->timestamp('email_sent_at')->nullable();
    $table->boolean('sms_sent')->default(false);
    $table->timestamp('sms_sent_at')->nullable();
    $table->boolean('push_sent')->default(false);
    $table->timestamp('push_sent_at')->nullable();

    // Read Status
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();

    // Related Records
    $table->string('related_type')->nullable(); // Polymorphic
    $table->unsignedBigInteger('related_id')->nullable();
    $table->json('action_url')->nullable(); // URL to navigate to

    // Scheduling
    $table->timestamp('scheduled_for')->nullable();
    $table->timestamp('sent_at')->nullable();

    // Retry Logic
    $table->integer('retry_count')->default(0);
    $table->text('last_error')->nullable();

    // Data
    $table->json('data')->nullable(); // Additional context data

    $table->timestamps();

    $table->index(['tenant_id', 'user_id']);
    $table->index(['tenant_id', 'customer_id']);
    $table->index(['type', 'status']);
    $table->index('scheduled_for');
    $table->index(['related_type', 'related_id']);
});
```

### 9. Pricing Rules Migration

```php
// database/migrations/xxxx_create_pricing_rules_table.php
Schema::create('pricing_rules', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');

    // Rule Details
    $table->string('name');
    $table->text('description')->nullable();
    $table->enum('type', [
        'occupation_based',
        'seasonal',
        'duration_discount',
        'size_based',
        'promotional',
        'early_bird',
        'loyalty'
    ]);
    $table->integer('priority')->default(0); // Higher = applied first
    $table->boolean('is_active')->default(true);

    // Conditions (JSON)
    $table->json('conditions'); // Complex conditions

    // Adjustment
    $table->enum('adjustment_type', ['percentage', 'fixed_amount', 'new_price'])->default('percentage');
    $table->decimal('adjustment_value', 10, 2);
    $table->decimal('min_price', 10, 2)->nullable(); // Price floor
    $table->decimal('max_price', 10, 2)->nullable(); // Price ceiling

    // Date Range
    $table->date('valid_from')->nullable();
    $table->date('valid_until')->nullable();

    // Applicability
    $table->json('applicable_box_types')->nullable(); // Filter by box characteristics
    $table->json('applicable_customer_types')->nullable();
    $table->boolean('stackable')->default(false); // Can combine with other rules

    // Usage Tracking
    $table->integer('times_applied')->default(0);
    $table->decimal('total_revenue_impact', 12, 2)->default(0);

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'is_active', 'priority']);
    $table->index(['valid_from', 'valid_until']);
});
```

### 10. Subscriptions Migration (Tenant Plans)

```php
// database/migrations/xxxx_create_subscriptions_table.php
Schema::create('subscriptions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');

    // Plan
    $table->enum('plan', ['free', 'starter', 'professional', 'enterprise'])->default('free');
    $table->enum('billing_period', ['monthly', 'yearly'])->default('monthly');

    // Status
    $table->enum('status', [
        'active',
        'trialing',
        'past_due',
        'cancelled',
        'expired',
        'suspended'
    ])->default('trialing');

    // Dates
    $table->date('trial_started_at')->nullable();
    $table->date('trial_ends_at')->nullable();
    $table->date('started_at')->nullable();
    $table->date('current_period_start')->nullable();
    $table->date('current_period_end')->nullable();
    $table->date('cancelled_at')->nullable();
    $table->date('expires_at')->nullable();

    // Pricing
    $table->decimal('amount', 10, 2);
    $table->decimal('discount', 10, 2)->default(0);
    $table->string('currency', 3)->default('EUR');

    // Payment
    $table->string('stripe_subscription_id')->nullable()->unique();
    $table->string('stripe_customer_id')->nullable();
    $table->string('stripe_payment_method_id')->nullable();

    // Quantities (for metered billing)
    $table->integer('quantity_sites')->default(1);
    $table->integer('quantity_boxes')->default(50);
    $table->integer('quantity_users')->default(3);

    // Usage Tracking
    $table->integer('current_sites')->default(0);
    $table->integer('current_boxes')->default(0);
    $table->integer('current_users')->default(0);

    // Features
    $table->json('features')->nullable();

    // Cancellation
    $table->string('cancellation_reason')->nullable();
    $table->text('cancellation_feedback')->nullable();

    $table->timestamps();

    $table->index(['tenant_id', 'status']);
    $table->index('stripe_subscription_id');
    $table->index(['current_period_start', 'current_period_end']);
});
```

### 11. Floor Plans Migration

```php
// database/migrations/xxxx_create_floor_plans_table.php
Schema::create('floor_plans', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->foreignId('site_id')->constrained()->onDelete('cascade');
    $table->foreignId('building_id')->nullable()->constrained()->onDelete('cascade');
    $table->foreignId('floor_id')->nullable()->constrained()->onDelete('set null');

    // Plan Details
    $table->string('name');
    $table->text('description')->nullable();
    $table->integer('version')->default(1);
    $table->boolean('is_active')->default(true);

    // Dimensions
    $table->decimal('width', 10, 2); // Canvas width in pixels or meters
    $table->decimal('height', 10, 2); // Canvas height
    $table->decimal('scale', 10, 2)->default(1); // Pixels per meter
    $table->string('unit', 10)->default('meters');

    // Canvas Data (JSON)
    $table->json('elements'); // Walls, boxes, corridors, doors, etc.
    /*
    Example structure:
    {
        "walls": [
            {"id": "w1", "x1": 0, "y1": 0, "x2": 100, "y2": 0, "thickness": 10}
        ],
        "boxes": [
            {"id": "b1", "box_id": 123, "x": 10, "y": 10, "width": 20, "height": 15, "rotation": 0}
        ],
        "corridors": [
            {"id": "c1", "points": [[0,0], [100,0], [100,50]], "width": 10}
        ],
        "doors": [
            {"id": "d1", "x": 50, "y": 0, "width": 10, "direction": "horizontal"}
        ],
        "labels": [
            {"id": "l1", "text": "Entrance", "x": 50, "y": 5, "fontSize": 12}
        ]
    }
    */

    // Background Image
    $table->string('background_image')->nullable();
    $table->decimal('background_opacity', 3, 2)->default(0.5);

    // Grid Settings
    $table->boolean('show_grid')->default(true);
    $table->integer('grid_size')->default(10);
    $table->string('grid_color', 7)->default('#cccccc');

    // View Settings
    $table->decimal('zoom_level', 5, 2)->default(1);
    $table->json('camera_position')->nullable(); // {x, y}

    // Statistics
    $table->integer('total_boxes_on_plan')->default(0);
    $table->decimal('total_area_planned', 10, 2)->default(0);

    // History
    $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
    $table->foreignId('updated_by')->nullable()->constrained('users')->onDelete('set null');
    $table->json('change_log')->nullable(); // Track major changes

    $table->timestamps();
    $table->softDeletes();

    $table->index(['tenant_id', 'site_id']);
    $table->index(['tenant_id', 'is_active']);
});
```

## 🎯 Modèles Eloquent

Après avoir complété les migrations, créer les modèles avec les relations appropriées.

### Exemple: Tenant Model

```php
<?php
// app/Models/Tenant.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'domain', 'email', 'phone', 'address',
        'city', 'state', 'postal_code', 'country', 'plan',
        'trial_ends_at', 'subscription_ends_at', 'is_active',
        'max_sites', 'max_boxes', 'max_users', 'company_number',
        'vat_number', 'logo_url', 'website', 'settings',
        'features', 'monthly_revenue', 'total_customers',
        'occupation_rate', 'stripe_customer_id', 'payment_gateway'
    ];

    protected $casts = [
        'trial_ends_at' => 'date',
        'subscription_ends_at' => 'date',
        'is_active' => 'boolean',
        'settings' => 'array',
        'features' => 'array',
        'monthly_revenue' => 'decimal:2',
        'occupation_rate' => 'decimal:2',
    ];

    // Relationships
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class);
    }

    public function boxes(): HasMany
    {
        return $this->hasMany(Box::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByPlan($query, $plan)
    {
        return $query->where('plan', $plan);
    }

    // Accessors & Mutators
    public function getIsTrialAttribute(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    public function getIsSubscriptionActiveAttribute(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }

    // Methods
    public function makeCurrent(): void
    {
        app()->instance('current_tenant', $this);
    }

    public function canAddSite(): bool
    {
        return $this->sites()->count() < $this->max_sites;
    }

    public function canAddBox(): bool
    {
        return $this->boxes()->count() < $this->max_boxes;
    }

    public function canAddUser(): bool
    {
        return $this->users()->count() < $this->max_users;
    }

    public function calculateOccupationRate(): float
    {
        $totalBoxes = $this->boxes()->count();
        if ($totalBoxes === 0) return 0;

        $occupiedBoxes = $this->boxes()->where('status', 'occupied')->count();
        return ($occupiedBoxes / $totalBoxes) * 100;
    }

    public function updateStatistics(): void
    {
        $this->update([
            'total_customers' => $this->customers()->count(),
            'occupation_rate' => $this->calculateOccupationRate(),
            'monthly_revenue' => $this->calculateMonthlyRevenue(),
        ]);
    }

    protected function calculateMonthlyRevenue(): float
    {
        return $this->contracts()
            ->where('status', 'active')
            ->sum('monthly_price');
    }
}
```

## 📦 Prochaines Étapes

1. **Compléter toutes les migrations** avec les structures ci-dessus
2. **Créer tous les modèles** avec relations
3. **Exécuter les migrations**: `php artisan migrate`
4. **Créer les seeders** pour données de test
5. **Créer les controllers** pour chaque interface
6. **Créer les composants Vue** pour le frontend
7. **Configurer les routes** séparées
8. **Tester l'application**

Référez-vous à `IMPLEMENTATION_STATUS.md` pour l'architecture complète.

---

**Note**: Ce guide sera complété avec les Controllers, Services, et Composants Vue dans les prochaines sections.
