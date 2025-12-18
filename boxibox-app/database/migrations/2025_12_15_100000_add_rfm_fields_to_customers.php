<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // RFM Scores (1-5 each)
            $table->unsignedTinyInteger('rfm_recency')->nullable()->after('notes');
            $table->unsignedTinyInteger('rfm_frequency')->nullable()->after('rfm_recency');
            $table->unsignedTinyInteger('rfm_monetary')->nullable()->after('rfm_frequency');
            $table->decimal('rfm_total', 3, 1)->nullable()->after('rfm_monetary'); // Weighted total
            $table->string('rfm_segment', 30)->nullable()->after('rfm_total');
            $table->timestamp('rfm_calculated_at')->nullable()->after('rfm_segment');

            // Index for segment queries
            $table->index(['tenant_id', 'rfm_segment']);
            $table->index(['tenant_id', 'rfm_total']);
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'rfm_segment']);
            $table->dropIndex(['tenant_id', 'rfm_total']);

            $table->dropColumn([
                'rfm_recency',
                'rfm_frequency',
                'rfm_monetary',
                'rfm_total',
                'rfm_segment',
                'rfm_calculated_at',
            ]);
        });
    }
};
