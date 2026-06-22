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
        if (! Schema::hasColumn('monthly_schedules', 'class_id')) {
            return;
        }

        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropForeign('monthly_schedules_class_id_foreign');
            });
        } catch (\Exception $e) {
        }

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->dropColumn('class_id');
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
                ->after('class_name');
        });

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreign('class_id')
                ->references('id')
                ->on('classes')
                ->nullOnDelete();
        });
    }
};
