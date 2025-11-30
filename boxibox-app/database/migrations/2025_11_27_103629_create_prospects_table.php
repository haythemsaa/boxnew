<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prospects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['individual', 'company'])->default('individual');

            // Individual info
            $table->string('first_name');
            $table->string('last_name');

            // Company info
            $table->string('company_name')->nullable();
            $table->string('siret')->nullable();

            // Contact info
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('city')->nullable();
            $table->string('country')->default('France');

            // Prospect details
            $table->enum('status', ['new', 'contacted', 'qualified', 'quoted', 'converted', 'lost'])->default('new');
            $table->enum('source', ['website', 'phone', 'email', 'referral', 'walk_in', 'social_media', 'other'])->default('website');
            $table->string('box_size_interested')->nullable(); // ex: "10m²", "20m²"
            $table->date('move_in_date')->nullable();
            $table->decimal('budget', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->integer('follow_up_count')->default(0);
            $table->timestamp('last_contact_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained()->onDelete('set null'); // Link when converted

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospects');
    }
};
