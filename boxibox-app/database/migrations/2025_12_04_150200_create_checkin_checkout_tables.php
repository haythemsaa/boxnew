<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // États des lieux
        Schema::create('condition_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('box_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('conducted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['check_in', 'check_out', 'inspection']);
            $table->enum('status', ['draft', 'pending_signature', 'signed', 'disputed'])->default('draft');
            $table->timestamp('conducted_at');
            $table->json('checklist')->nullable();
            $table->text('general_condition')->nullable();
            $table->enum('cleanliness', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->enum('walls_condition', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->enum('floor_condition', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->enum('door_condition', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->enum('lock_condition', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->enum('lighting_condition', ['excellent', 'good', 'fair', 'poor'])->nullable();
            $table->json('photos')->nullable();
            $table->json('videos')->nullable();
            $table->text('damages_noted')->nullable();
            $table->decimal('damage_cost', 10, 2)->nullable();
            $table->text('customer_comments')->nullable();
            $table->text('staff_comments')->nullable();
            $table->string('customer_signature')->nullable();
            $table->timestamp('customer_signed_at')->nullable();
            $table->string('staff_signature')->nullable();
            $table->timestamp('staff_signed_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'type']);
            $table->index(['contract_id', 'type']);
        });

        // Éléments de la checklist configurable
        Schema::create('condition_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
        });

        // Remise de clés/codes
        Schema::create('access_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('type', ['handover', 'return']);
            $table->enum('access_type', ['key', 'code', 'card', 'smart_lock', 'other']);
            $table->string('access_identifier')->nullable();
            $table->integer('quantity')->default(1);
            $table->text('notes')->nullable();
            $table->string('customer_signature')->nullable();
            $table->timestamp('conducted_at');
            $table->timestamps();
        });

        // Calcul de retenue sur caution
        Schema::create('deposit_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('condition_report_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('original_deposit', 10, 2);
            $table->decimal('damage_deduction', 10, 2)->default(0);
            $table->decimal('cleaning_deduction', 10, 2)->default(0);
            $table->decimal('unpaid_rent_deduction', 10, 2)->default(0);
            $table->decimal('other_deduction', 10, 2)->default(0);
            $table->text('other_deduction_reason')->nullable();
            $table->decimal('amount_refunded', 10, 2);
            $table->enum('refund_method', ['bank_transfer', 'check', 'cash', 'credit_note'])->nullable();
            $table->string('refund_reference')->nullable();
            $table->enum('status', ['pending', 'approved', 'refunded', 'disputed'])->default('pending');
            $table->timestamp('refunded_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_settlements');
        Schema::dropIfExists('access_handovers');
        Schema::dropIfExists('condition_checklist_items');
        Schema::dropIfExists('condition_reports');
    }
};
