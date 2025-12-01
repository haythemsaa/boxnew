<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Marques/Brands pour white-label
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Domaine personnalisé
            $table->string('custom_domain')->nullable()->unique();
            $table->boolean('domain_verified')->default(false);
            $table->string('domain_verification_token')->nullable();
            $table->timestamp('domain_verified_at')->nullable();

            // Logo et branding
            $table->string('logo_url')->nullable();
            $table->string('logo_dark_url')->nullable();
            $table->string('favicon_url')->nullable();

            // Couleurs
            $table->string('primary_color')->default('#4f46e5');
            $table->string('secondary_color')->default('#7c3aed');
            $table->string('accent_color')->default('#06b6d4');
            $table->string('background_color')->default('#ffffff');
            $table->string('text_color')->default('#1f2937');

            // Typographie
            $table->string('font_family')->default('Inter');
            $table->string('heading_font_family')->nullable();

            // Informations légales
            $table->string('company_name')->nullable();
            $table->string('legal_name')->nullable();
            $table->string('siret')->nullable();
            $table->string('vat_number')->nullable();
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();

            // Réseaux sociaux
            $table->string('facebook_url')->nullable();
            $table->string('instagram_url')->nullable();
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('youtube_url')->nullable();

            // Pages personnalisées
            $table->text('terms_of_service')->nullable();
            $table->text('privacy_policy')->nullable();
            $table->text('cookie_policy')->nullable();

            // Options
            $table->boolean('show_powered_by')->default(true);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
        });

        // Templates d'email par marque
        Schema::create('brand_email_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('brand_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');
            $table->string('subject');
            $table->text('body_html');
            $table->text('body_text')->nullable();

            $table->string('from_name')->nullable();
            $table->string('from_email')->nullable();
            $table->string('reply_to')->nullable();

            $table->json('variables')->nullable(); // Variables disponibles

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['brand_id', 'slug']);
        });

        // Sites associés à une marque
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->after('tenant_id')->constrained()->nullOnDelete();
        });

        // Configuration multi-marques du tenant
        Schema::create('tenant_branding_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();

            $table->boolean('multi_brand_enabled')->default(false);
            $table->foreignId('default_brand_id')->nullable()->constrained('brands');

            // Options de personnalisation autorisées
            $table->boolean('allow_custom_domain')->default(true);
            $table->boolean('allow_custom_colors')->default(true);
            $table->boolean('allow_custom_emails')->default(true);
            $table->boolean('allow_hide_powered_by')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_branding_settings');

        Schema::table('sites', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropColumn('brand_id');
        });

        Schema::dropIfExists('brand_email_templates');
        Schema::dropIfExists('brands');
    }
};
