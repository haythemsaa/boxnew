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
        Schema::create('sepa_mandates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('contract_id')->nullable()->constrained()->onDelete('set null');

            // SEPA mandate details
            $table->string('rum')->unique(); // Référence Unique du Mandat
            $table->string('ics'); // Identifiant Créancier SEPA
            $table->enum('type', ['recurrent', 'one_time'])->default('recurrent');
            $table->enum('status', ['pending', 'active', 'suspended', 'cancelled', 'expired'])->default('pending');

            // Bank account info
            $table->string('iban');
            $table->string('bic')->nullable();
            $table->string('account_holder');

            // Signature
            $table->date('signature_date');
            $table->string('signature_place')->nullable();
            $table->string('signed_document_path')->nullable();

            // Tracking
            $table->timestamp('activated_at')->nullable();
            $table->date('first_collection_date')->nullable();
            $table->date('last_collection_date')->nullable();
            $table->timestamp('last_collection_at')->nullable();
            $table->integer('collection_count')->default(0);
            $table->decimal('total_collected', 12, 2)->default(0);

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'status']);
            $table->index(['customer_id']);
            $table->index(['rum']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sepa_mandates');
    }
};
