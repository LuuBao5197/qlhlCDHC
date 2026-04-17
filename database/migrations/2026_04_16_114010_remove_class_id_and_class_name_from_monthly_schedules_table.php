<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            // Drop class_id foreign key if exists
            if (Schema::hasColumn('monthly_schedules', 'class_id')) {
                try {
                    $table->dropForeign(['class_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }
                $table->dropColumn('class_id');
            }

            // Drop class_name column if exists
            if (Schema::hasColumn('monthly_schedules', 'class_name')) {
                $table->dropColumn('class_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreignId('class_id')
                ->nullable()
                ->after('plan_id')
                ->constrained('classes')
                ->onDelete('cascade');

            $table->string('class_name')
                ->nullable()
                ->after('class_id');
        });
    }
};
