<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuration Factur-X par tenant
        Schema::create('facturx_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            // Profil Factur-X (Minimum, Basic, EN16931, Extended)
            $table->enum('profile', ['minimum', 'basic', 'en16931', 'extended'])->default('basic');

            // Informations légales obligatoires
            $table->string('company_name');
            $table->string('siret', 14);
            $table->string('siren', 9)->nullable();
            $table->string('vat_number', 20)->nullable(); // FR + 11 chiffres
            $table->string('rcs')->nullable(); // RCS + ville
            $table->string('legal_form')->nullable(); // SARL, SAS, etc.
            $table->decimal('share_capital', 12, 2)->nullable();

            // Adresse de facturation
            $table->string('billing_address');
            $table->string('billing_postal_code');
            $table->string('billing_city');
            $table->string('billing_country')->default('FR');

            // Coordonnées bancaires
            $table->string('iban', 34)->nullable();
            $table->string('bic', 11)->nullable();
            $table->string('bank_name')->nullable();

            // Plateforme de dématérialisation partenaire (PDP)
            $table->string('pdp_identifier')->nullable(); // Identifiant sur la PDP
            $table->string('pdp_name')->nullable(); // Chorus Pro, etc.
            $table->string('pdp_api_key')->nullable();
            $table->string('pdp_api_secret')->nullable();

            // Options
            $table->boolean('auto_generate')->default(true);
            $table->boolean('auto_submit_to_pdp')->default(false);
            $table->boolean('include_xml_in_pdf')->default(true);
            $table->string('default_payment_terms')->default('30 jours');
            $table->text('legal_mentions')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Factures électroniques générées
        Schema::create('electronic_invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignId('configuration_id')->constrained('facturx_configurations');

            // Identifiants
            $table->string('facturx_id')->unique(); // ID unique Factur-X
            $table->string('external_id')->nullable(); // ID sur la PDP

            // Fichiers
            $table->string('pdf_path');
            $table->string('xml_path');
            $table->string('pdf_with_xml_path')->nullable();
            $table->string('checksum')->nullable();

            // Statuts
            $table->enum('status', [
                'draft',
                'generated',
                'submitted',
                'accepted',
                'rejected',
                'paid',
                'cancelled'
            ])->default('draft');
            $table->enum('pdp_status', [
                'pending',
                'submitted',
                'processing',
                'delivered',
                'accepted',
                'rejected',
                'error'
            ])->nullable();

            // Informations de transmission
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            // Destinataire
            $table->string('recipient_siret')->nullable();
            $table->string('recipient_vat_number')->nullable();
            $table->string('recipient_identifier')->nullable(); // CHORUS: service code

            // Validation
            $table->boolean('is_valid')->default(false);
            $table->json('validation_errors')->nullable();
            $table->string('schema_version')->default('1.0');

            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['invoice_id']);
        });

        // Historique des transmissions
        Schema::create('facturx_transmissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electronic_invoice_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['submission', 'status_check', 'download', 'cancellation']);
            $table->enum('direction', ['outgoing', 'incoming']);

            $table->enum('status', ['success', 'failed', 'pending']);
            $table->integer('http_code')->nullable();
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['electronic_invoice_id', 'created_at']);
        });

        // Archivage légal (10 ans minimum)
        Schema::create('invoice_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('electronic_invoice_id')->constrained()->cascadeOnDelete();

            $table->string('archive_path');
            $table->string('archive_checksum');
            $table->string('archive_format')->default('PDF/A-3');

            $table->timestamp('archived_at');
            $table->date('retention_until'); // Date de fin de conservation
            $table->boolean('is_sealed')->default(true);

            // Signature électronique d'archivage
            $table->text('signature')->nullable();
            $table->string('signature_algorithm')->nullable();
            $table->timestamp('signed_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'archived_at']);
        });

        // Codes TVA pour Factur-X
        Schema::create('vat_codes', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique();
            $table->string('description');
            $table->decimal('rate', 5, 2);
            $table->string('category'); // S (Standard), Z (Zero), E (Exempt), etc.
            $table->string('country')->default('FR');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_codes');
        Schema::dropIfExists('invoice_archives');
        Schema::dropIfExists('facturx_transmissions');
        Schema::dropIfExists('electronic_invoices');
        Schema::dropIfExists('facturx_configurations');
    }
};
