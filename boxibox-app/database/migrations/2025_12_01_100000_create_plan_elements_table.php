<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Éléments du plan (boxes, murs, portes, séparateurs, zones, etc.)
        Schema::create('plan_elements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('floor_id')->nullable()->constrained()->nullOnDelete();

            // Type d'élément
            $table->enum('element_type', [
                'box',           // Box de stockage
                'wall',          // Mur
                'door',          // Porte
                'separator',     // Séparateur/Cloison
                'corridor',      // Couloir
                'lift',          // Ascenseur
                'stairs',        // Escaliers
                'office',        // Bureau
                'reception',     // Accueil
                'loading_zone',  // Zone de chargement
                'parking',       // Parking
                'toilet',        // Toilettes
                'zone',          // Zone générique
                'label',         // Étiquette texte
            ])->default('box');

            // Référence au box si c'est un élément box
            $table->foreignId('box_id')->nullable()->constrained()->nullOnDelete();

            // Position et dimensions (en pixels ou unités du canvas)
            $table->decimal('x', 10, 2)->default(0);
            $table->decimal('y', 10, 2)->default(0);
            $table->decimal('width', 10, 2)->default(100);
            $table->decimal('height', 10, 2)->default(100);
            $table->decimal('rotation', 5, 2)->default(0); // Rotation en degrés

            // Ordre d'affichage (z-index)
            $table->integer('z_index')->default(0);

            // Style
            $table->string('fill_color', 20)->nullable(); // Couleur de remplissage
            $table->string('stroke_color', 20)->nullable(); // Couleur de bordure
            $table->integer('stroke_width')->default(1);
            $table->decimal('opacity', 3, 2)->default(1);
            $table->string('font_size', 10)->nullable();
            $table->string('text_color', 20)->nullable();

            // Contenu texte pour les labels
            $table->string('label')->nullable();
            $table->text('description')->nullable();

            // Propriétés additionnelles (JSON)
            $table->json('properties')->nullable();

            // Verrouillage de l'élément
            $table->boolean('is_locked')->default(false);
            $table->boolean('is_visible')->default(true);

            $table->timestamps();

            $table->index(['site_id', 'floor_id']);
            $table->index(['site_id', 'element_type']);
        });

        // Configurations de plan par site
        Schema::create('plan_configurations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();

            // Dimensions du canvas
            $table->integer('canvas_width')->default(1920);
            $table->integer('canvas_height')->default(1080);

            // Grille
            $table->boolean('show_grid')->default(true);
            $table->integer('grid_size')->default(20);
            $table->boolean('snap_to_grid')->default(true);

            // Couleurs par défaut
            $table->string('default_box_available_color', 20)->default('#22c55e'); // Vert
            $table->string('default_box_occupied_color', 20)->default('#3b82f6');  // Bleu
            $table->string('default_box_reserved_color', 20)->default('#f59e0b');  // Orange
            $table->string('default_box_maintenance_color', 20)->default('#ef4444'); // Rouge
            $table->string('default_wall_color', 20)->default('#1e3a5f');
            $table->string('default_door_color', 20)->default('#ffffff');

            // Image de fond
            $table->string('background_image')->nullable();
            $table->decimal('background_opacity', 3, 2)->default(0.3);

            // Zoom et position initiale
            $table->decimal('initial_zoom', 4, 2)->default(1);
            $table->decimal('initial_x', 10, 2)->default(0);
            $table->decimal('initial_y', 10, 2)->default(0);

            // Options d'affichage
            $table->boolean('show_box_labels')->default(true);
            $table->boolean('show_box_sizes')->default(true);
            $table->boolean('show_legend')->default(true);
            $table->boolean('show_statistics')->default(true);

            $table->timestamps();

            $table->unique('site_id');
        });

        // Templates de plans prédéfinis
        Schema::create('plan_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('thumbnail')->nullable();

            // Catégorie
            $table->enum('category', [
                'small',    // Petit centre (< 100 boxes)
                'medium',   // Moyen centre (100-300 boxes)
                'large',    // Grand centre (300+ boxes)
                'outdoor',  // Extérieur/Containers
                'multi_floor', // Multi-étages
            ])->default('medium');

            // Dimensions suggérées
            $table->integer('suggested_width')->default(1920);
            $table->integer('suggested_height')->default(1080);

            // Données du template (JSON avec tous les éléments)
            $table->json('template_data');

            // Template système ou personnalisé
            $table->boolean('is_system')->default(false);
            $table->boolean('is_public')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_templates');
        Schema::dropIfExists('plan_configurations');
        Schema::dropIfExists('plan_elements');
    }
};
