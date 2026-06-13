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
        Schema::table('plans', function (Blueprint $table) {
            $table->dropForeign(['training_batch_id']);
            $table->dropUnique('plans_training_batch_id_unique');
            $table->unique(['training_batch_id', 'semester', 'year'], 'plans_batch_semester_year_unique');
            $table->foreign('training_batch_id')
                ->references('id')
                ->on('training_batches')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropForeign(['training_batch_id']);
            $table->dropUnique('plans_batch_semester_year_unique');
            $table->unique('training_batch_id', 'plans_training_batch_id_unique');
            $table->foreign('training_batch_id')
                ->references('id')
                ->on('training_batches')
                ->nullOnDelete();
        });
    }
};
