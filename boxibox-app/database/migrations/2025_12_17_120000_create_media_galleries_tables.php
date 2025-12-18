<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main media table (photos, videos, 360 images)
        Schema::create('site_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');
            $table->foreignId('box_id')->nullable()->constrained()->onDelete('cascade');

            // Media details
            $table->enum('type', ['photo', 'video', 'photo_360', 'virtual_tour'])->default('photo');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('alt_text')->nullable();

            // File info
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type');
            $table->integer('size'); // in bytes
            $table->string('path');
            $table->string('thumbnail_path')->nullable();

            // Image dimensions
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();

            // 360 specific
            $table->boolean('is_360')->default(false);
            $table->json('hotspots')->nullable(); // For interactive 360 tours

            // Video specific
            $table->integer('duration_seconds')->nullable();
            $table->string('video_url')->nullable(); // YouTube/Vimeo embed

            // Virtual tour specific
            $table->string('tour_provider')->nullable(); // matterport, kuula, etc.
            $table->string('tour_embed_code')->nullable();
            $table->string('tour_external_url')->nullable();

            // Display settings
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_public')->default(true);
            $table->boolean('show_on_widget')->default(true);
            $table->boolean('show_on_portal')->default(true);

            // Categories/tags
            $table->enum('category', [
                'exterior',     // Vue exterieure
                'interior',     // Vue interieure
                'entrance',     // Entree
                'corridor',     // Couloir
                'box',          // Box de stockage
                'security',     // Securite
                'amenities',    // Equipements
                'surroundings', // Environnement
                'team',         // Equipe
                'other',        // Autre
            ])->default('other');

            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['tenant_id', 'site_id', 'type']);
            $table->index(['tenant_id', 'site_id', 'category']);
            $table->index(['site_id', 'is_featured']);
            $table->index(['site_id', 'is_public', 'sort_order']);
        });

        // Virtual tours (360 tours with multiple panoramas)
        Schema::create('virtual_tours', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->constrained()->onDelete('cascade');

            $table->string('name');
            $table->text('description')->nullable();

            // Tour settings
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->boolean('autoplay')->default(false);
            $table->integer('start_panorama_id')->nullable();

            // External tour integration
            $table->string('provider')->nullable(); // matterport, kuula, cloudpano, etc.
            $table->string('external_id')->nullable();
            $table->text('embed_code')->nullable();
            $table->string('external_url')->nullable();

            // Stats
            $table->integer('view_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'site_id']);
        });

        // Virtual tour panoramas (scenes in a 360 tour)
        Schema::create('virtual_tour_panoramas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('virtual_tour_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_media_id')->nullable()->constrained()->onDelete('set null');

            $table->string('name');
            $table->text('description')->nullable();

            // Panorama image
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable();

            // Position in tour
            $table->integer('sort_order')->default(0);

            // Initial view settings
            $table->float('initial_pitch')->default(0);
            $table->float('initial_yaw')->default(0);
            $table->float('initial_hfov')->default(100);

            // Hotspots (links to other panoramas, info points)
            $table->json('hotspots')->nullable();

            $table->timestamps();
        });

        // Add photo columns to sites table
        Schema::table('sites', function (Blueprint $table) {
            $table->string('cover_image_path')->nullable()->after('images');
            $table->string('logo_path')->nullable()->after('cover_image_path');
            $table->unsignedBigInteger('featured_tour_id')->nullable()->after('logo_path');
            $table->boolean('show_gallery_widget')->default(true)->after('featured_tour_id');
        });

        // Add photo column to boxes table
        Schema::table('boxes', function (Blueprint $table) {
            $table->string('photo_path')->nullable()->after('images');
            $table->string('photo_360_path')->nullable()->after('photo_path');
        });
    }

    public function down(): void
    {
        Schema::table('boxes', function (Blueprint $table) {
            $table->dropColumn(['photo_path', 'photo_360_path']);
        });

        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['cover_image_path', 'logo_path', 'featured_tour_id', 'show_gallery_widget']);
        });

        Schema::dropIfExists('virtual_tour_panoramas');
        Schema::dropIfExists('virtual_tours');
        Schema::dropIfExists('site_media');
    }
};
