<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('trigger'); // new_lead, abandoned_booking, onboarding, retention, win_back
            $table->boolean('is_active')->default(true);
            $table->json('steps'); // Array of email steps with delays
            $table->integer('total_enrolled')->default(0);
            $table->integer('total_completed')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active']);
            $table->index('trigger');
        });

        Schema::create('email_sequence_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_sequence_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('lead_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('status')->default('active'); // active, completed, unsubscribed, bounced
            $table->integer('current_step')->default(0);
            $table->timestamp('next_send_at')->nullable();
            $table->timestamp('enrolled_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['email_sequence_id', 'status']);
            $table->index('next_send_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_sequence_enrollments');
        Schema::dropIfExists('email_sequences');
    }
};
