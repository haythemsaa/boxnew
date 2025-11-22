<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('site_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->onDelete('set null');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('status')->default('new'); // new, contacted, qualified, converted, lost
            $table->string('source')->nullable(); // website, phone, referral, walk-in, google_ads, facebook
            $table->integer('score')->default(0); // Lead scoring 0-100
            $table->string('box_type_interest')->nullable();
            $table->decimal('budget_min', 10, 2)->nullable();
            $table->decimal('budget_max', 10, 2)->nullable();
            $table->date('move_in_date')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional data (utm_params, page_views, etc.)
            $table->timestamp('first_contacted_at')->nullable();
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_to_customer_id')->nullable()->constrained('customers')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['assigned_to', 'status']);
            $table->index('score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
