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
        Schema::table('monthly_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_schedules', 'class_id')) {
                $table->dropForeign(['class_id']);
                $table->dropColumn('class_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreignId('class_id')
                ->nullable()
                ->after('class_name')
                ->constrained('classes')
                ->nullOnDelete();
        });
    }
};
