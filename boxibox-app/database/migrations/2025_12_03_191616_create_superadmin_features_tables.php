<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // System Announcements
        if (!Schema::hasTable('announcements')) {
            Schema::create('announcements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->text('content');
                $table->enum('type', ['info', 'warning', 'maintenance', 'feature', 'promotion'])->default('info');
                $table->enum('target', ['all', 'tenants', 'specific'])->default('all');
                $table->json('target_tenant_ids')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_dismissible')->default(true);
                $table->timestamp('starts_at')->nullable();
                $table->timestamp('ends_at')->nullable();
                $table->timestamps();

                $table->index(['is_active', 'starts_at', 'ends_at']);
            });
        }

        // Announcement Read Status
        if (!Schema::hasTable('announcement_reads')) {
            Schema::create('announcement_reads', function (Blueprint $table) {
                $table->id();
                $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->boolean('is_dismissed')->default(false);
                $table->timestamp('read_at');

                $table->unique(['announcement_id', 'user_id']);
            });
        }

        // Email Templates
        if (!Schema::hasTable('email_templates')) {
            Schema::create('email_templates', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->string('subject');
                $table->text('body_html');
                $table->text('body_text')->nullable();
                $table->json('variables')->nullable();
                $table->enum('category', ['system', 'tenant', 'billing', 'support', 'marketing'])->default('system');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        // Tenant Billing / Platform Invoices
        if (!Schema::hasTable('platform_invoices')) {
            Schema::create('platform_invoices', function (Blueprint $table) {
                $table->id();
                $table->string('invoice_number')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->decimal('subtotal', 12, 2);
                $table->decimal('tax_amount', 12, 2)->default(0);
                $table->decimal('total_amount', 12, 2);
                $table->string('currency', 3)->default('EUR');
                $table->enum('status', ['draft', 'pending', 'paid', 'overdue', 'cancelled'])->default('draft');
                $table->date('issue_date');
                $table->date('due_date');
                $table->date('paid_date')->nullable();
                $table->string('payment_method')->nullable();
                $table->string('payment_reference')->nullable();
                $table->text('notes')->nullable();
                $table->json('line_items');
                $table->timestamps();

                $table->index(['tenant_id', 'status']);
                $table->index('due_date');
            });
        }

        // System Backups
        if (!Schema::hasTable('system_backups')) {
            Schema::create('system_backups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('disk')->default('local');
                $table->string('path');
                $table->unsignedBigInteger('size');
                $table->enum('type', ['full', 'database', 'files'])->default('full');
                $table->enum('status', ['pending', 'in_progress', 'completed', 'failed'])->default('pending');
                $table->text('error_message')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // Feature Flags
        if (!Schema::hasTable('feature_flags')) {
            Schema::create('feature_flags', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('key')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_enabled')->default(false);
                $table->json('enabled_for_tenants')->nullable();
                $table->json('enabled_for_plans')->nullable();
                $table->timestamps();
            });
        }

        // System Settings (global)
        if (!Schema::hasTable('system_settings')) {
            Schema::create('system_settings', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->text('value')->nullable();
                $table->string('type')->default('string');
                $table->string('group')->default('general');
                $table->text('description')->nullable();
                $table->boolean('is_public')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('platform_invoices');
        Schema::dropIfExists('email_templates');
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
