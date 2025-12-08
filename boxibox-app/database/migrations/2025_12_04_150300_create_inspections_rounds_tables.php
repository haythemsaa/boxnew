<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Planning des rondes
        Schema::create('patrol_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('frequency', ['daily', 'weekly', 'biweekly', 'monthly', 'custom']);
            $table->json('days_of_week')->nullable(); // [1,2,3,4,5] for Mon-Fri
            $table->time('scheduled_time');
            $table->integer('duration_minutes')->default(30);
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('instructions')->nullable();
            $table->json('checkpoints')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Rondes effectuées
        Schema::create('patrols', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('patrol_schedules')->nullOnDelete();
            $table->foreignId('conducted_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->enum('status', ['in_progress', 'completed', 'incomplete', 'cancelled'])->default('in_progress');
            $table->text('notes')->nullable();
            $table->integer('issues_found')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'site_id']);
        });

        // Points de contrôle de la ronde
        Schema::create('patrol_checkpoints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patrol_id')->constrained()->cascadeOnDelete();
            $table->string('location');
            $table->enum('status', ['ok', 'issue', 'skipped'])->default('ok');
            $table->text('notes')->nullable();
            $table->json('photos')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });

        // Inspections planifiées
        Schema::create('inspection_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->enum('type', ['fire_safety', 'electrical', 'security', 'general', 'pest_control', 'hvac', 'other']);
            $table->enum('frequency', ['monthly', 'quarterly', 'biannual', 'annual', 'custom']);
            $table->date('next_due_date');
            $table->date('last_completed_date')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('checklist_template')->nullable();
            $table->boolean('is_mandatory')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Inspections effectuées
        Schema::create('inspections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('schedule_id')->nullable()->constrained('inspection_schedules')->nullOnDelete();
            $table->foreignId('inspector_id')->constrained('users')->cascadeOnDelete();
            $table->string('type');
            $table->date('inspection_date');
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'failed', 'cancelled'])->default('scheduled');
            $table->enum('result', ['pass', 'pass_with_issues', 'fail'])->nullable();
            $table->json('checklist_results')->nullable();
            $table->text('findings')->nullable();
            $table->json('photos')->nullable();
            $table->json('documents')->nullable();
            $table->text('recommendations')->nullable();
            $table->date('follow_up_date')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'site_id']);
            $table->index(['tenant_id', 'status']);
        });

        // Issues trouvées lors des inspections
        Schema::create('inspection_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inspection_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patrol_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('box_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location');
            $table->enum('severity', ['low', 'medium', 'high', 'critical']);
            $table->string('category');
            $table->text('description');
            $table->json('photos')->nullable();
            $table->enum('status', ['open', 'in_progress', 'resolved', 'wont_fix'])->default('open');
            $table->foreignId('maintenance_ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inspection_issues');
        Schema::dropIfExists('inspections');
        Schema::dropIfExists('inspection_schedules');
        Schema::dropIfExists('patrol_checkpoints');
        Schema::dropIfExists('patrols');
        Schema::dropIfExists('patrol_schedules');
    }
};
