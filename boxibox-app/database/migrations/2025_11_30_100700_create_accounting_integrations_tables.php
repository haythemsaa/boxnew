<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Fournisseurs de comptabilité supportés
        Schema::create('accounting_providers', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Sage, QuickBooks, Xero, etc.
            $table->string('slug')->unique();
            $table->string('logo_url')->nullable();
            $table->text('description')->nullable();

            $table->string('oauth_url')->nullable();
            $table->string('api_base_url')->nullable();
            $table->json('scopes')->nullable();
            $table->json('supported_features')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Connexions comptables par tenant
        Schema::create('accounting_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('provider_id')->constrained('accounting_providers');

            $table->string('account_id')->nullable(); // ID du compte chez le provider
            $table->string('account_name')->nullable();

            // OAuth tokens
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Configuration de synchronisation
            $table->boolean('sync_invoices')->default(true);
            $table->boolean('sync_payments')->default(true);
            $table->boolean('sync_customers')->default(true);
            $table->boolean('sync_products')->default(false);

            $table->enum('sync_direction', ['export', 'import', 'bidirectional'])->default('export');
            $table->enum('sync_frequency', ['realtime', 'hourly', 'daily', 'manual'])->default('daily');

            // Mapping des comptes
            $table->json('account_mapping')->nullable(); // {"revenue": "4000", "vat": "44571", ...}
            $table->json('tax_mapping')->nullable();
            $table->json('payment_method_mapping')->nullable();

            $table->enum('status', ['active', 'disconnected', 'error', 'pending'])->default('pending');
            $table->text('error_message')->nullable();

            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('connected_at')->nullable();

            $table->timestamps();

            $table->unique(['tenant_id', 'provider_id']);
        });

        // Historique des synchronisations
        Schema::create('accounting_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('accounting_connections')->cascadeOnDelete();

            $table->enum('sync_type', ['invoices', 'payments', 'customers', 'products', 'full']);
            $table->enum('direction', ['export', 'import']);

            $table->enum('status', ['started', 'completed', 'failed', 'partial'])->default('started');
            $table->integer('records_processed')->default(0);
            $table->integer('records_succeeded')->default(0);
            $table->integer('records_failed')->default(0);

            $table->json('errors')->nullable();
            $table->text('summary')->nullable();

            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();

            $table->timestamps();

            $table->index(['connection_id', 'created_at']);
        });

        // Mapping des entités synchronisées
        Schema::create('accounting_entity_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('connection_id')->constrained('accounting_connections')->cascadeOnDelete();

            $table->string('entity_type'); // invoice, payment, customer
            $table->unsignedBigInteger('local_id');
            $table->string('external_id');

            $table->timestamp('last_synced_at');
            $table->string('sync_status')->default('synced');
            $table->text('sync_error')->nullable();

            $table->timestamps();

            $table->unique(['connection_id', 'entity_type', 'local_id'], 'acct_entity_map_unique');
            $table->index(['connection_id', 'entity_type', 'external_id'], 'acct_entity_map_ext_idx');
        });

        // Export FEC (Fichier des Écritures Comptables) - Obligatoire en France
        Schema::create('fec_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->integer('fiscal_year');
            $table->date('period_start');
            $table->date('period_end');

            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('checksum')->nullable();

            $table->integer('entries_count')->default(0);
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_credit', 15, 2)->default(0);

            $table->enum('status', ['pending', 'generating', 'ready', 'error'])->default('pending');
            $table->text('error_message')->nullable();

            $table->foreignId('generated_by')->nullable()->constrained('users');
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'fiscal_year']);
        });

        // Journaux comptables
        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('code', 10);
            $table->string('name');
            $table->enum('type', ['sales', 'purchases', 'bank', 'cash', 'general', 'opening', 'closing']);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'code']);
        });

        // Écritures comptables générées
        Schema::create('accounting_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_id')->constrained('accounting_journals');

            $table->string('entry_number');
            $table->date('entry_date');
            $table->date('accounting_date');

            $table->string('document_type')->nullable(); // invoice, payment, etc.
            $table->unsignedBigInteger('document_id')->nullable();
            $table->string('document_reference')->nullable();

            $table->text('description');

            $table->boolean('is_validated')->default(false);
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('validated_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'entry_date']);
            $table->index(['document_type', 'document_id']);
        });

        // Lignes d'écritures comptables
        Schema::create('accounting_entry_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('accounting_entries')->cascadeOnDelete();

            $table->string('account_code', 20);
            $table->string('account_label')->nullable();

            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);

            $table->string('partner_reference')->nullable();
            $table->string('tax_code')->nullable();
            $table->decimal('tax_amount', 12, 2)->nullable();

            $table->string('analytic_code')->nullable();
            $table->text('label')->nullable();

            $table->timestamps();

            $table->index(['entry_id']);
            $table->index(['account_code']);
        });

        // Plan comptable personnalisé
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('account_code', 20);
            $table->string('account_name');
            $table->enum('account_type', ['asset', 'liability', 'equity', 'revenue', 'expense']);
            $table->string('parent_code')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false);

            $table->timestamps();

            $table->unique(['tenant_id', 'account_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('accounting_entry_lines');
        Schema::dropIfExists('accounting_entries');
        Schema::dropIfExists('accounting_journals');
        Schema::dropIfExists('fec_exports');
        Schema::dropIfExists('accounting_entity_mappings');
        Schema::dropIfExists('accounting_sync_logs');
        Schema::dropIfExists('accounting_connections');
        Schema::dropIfExists('accounting_providers');
    }
};
