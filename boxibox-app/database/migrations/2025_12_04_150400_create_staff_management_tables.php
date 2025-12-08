<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Profils employés
        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('employee_id')->nullable();
            $table->string('position');
            $table->string('department')->nullable();
            $table->date('hire_date')->nullable();
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('monthly_salary', 10, 2)->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('skills')->nullable();
            $table->text('certifications')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id']);
        });

        // Planning des shifts
        Schema::create('staff_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('shift_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('type', ['regular', 'overtime', 'on_call', 'training', 'meeting']);
            $table->enum('status', ['scheduled', 'confirmed', 'in_progress', 'completed', 'cancelled', 'no_show'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'shift_date']);
            $table->index(['user_id', 'shift_date']);
        });

        // Pointage
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('staff_shifts')->nullOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->date('entry_date');
            $table->timestamp('clock_in')->nullable();
            $table->timestamp('clock_out')->nullable();
            $table->timestamp('break_start')->nullable();
            $table->timestamp('break_end')->nullable();
            $table->integer('total_minutes')->nullable();
            $table->integer('break_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->string('clock_in_location')->nullable();
            $table->string('clock_out_location')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'entry_date']);
            $table->index(['user_id', 'entry_date']);
        });

        // Tâches assignées
        Schema::create('staff_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete();
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['pending', 'in_progress', 'completed', 'cancelled'])->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->text('completion_notes')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_pattern')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['assigned_to', 'status']);
        });

        // Congés et absences
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['vacation', 'sick', 'personal', 'unpaid', 'training', 'other']);
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('days_requested', 4, 1);
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
        });

        // Solde congés
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('year');
            $table->decimal('vacation_entitled', 4, 1)->default(25);
            $table->decimal('vacation_used', 4, 1)->default(0);
            $table->decimal('sick_entitled', 4, 1)->default(10);
            $table->decimal('sick_used', 4, 1)->default(0);
            $table->decimal('personal_entitled', 4, 1)->default(3);
            $table->decimal('personal_used', 4, 1)->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'year']);
        });

        // KPIs et performance
        Schema::create('staff_performance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('contracts_created')->default(0);
            $table->integer('contracts_value')->default(0);
            $table->integer('leads_converted')->default(0);
            $table->integer('tickets_resolved')->default(0);
            $table->integer('average_resolution_time')->nullable();
            $table->integer('patrols_completed')->default(0);
            $table->decimal('attendance_rate', 5, 2)->nullable();
            $table->decimal('customer_satisfaction', 3, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'user_id', 'period_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_performance');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('staff_tasks');
        Schema::dropIfExists('time_entries');
        Schema::dropIfExists('staff_shifts');
        Schema::dropIfExists('staff_profiles');
    }
};
