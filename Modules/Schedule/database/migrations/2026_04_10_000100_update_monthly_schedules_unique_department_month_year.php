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
            try {
                $table->dropForeign(['plan_id']);
            } catch (\Exception $e) {
            }

            try {
                $table->dropForeign(['class_id']);
            } catch (\Exception $e) {
            }

            try {
                $table->dropUnique('monthly_schedules_plan_class_month_year_unique');
            } catch (\Exception $e) {
            }

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->unique(
                ['plan_id', 'department_id', 'month', 'year'],
                'monthly_schedules_plan_department_month_year_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            try {
                $table->dropForeign(['plan_id']);
            } catch (\Exception $e) {
            }

            try {
                $table->dropForeign(['class_id']);
            } catch (\Exception $e) {
            }

            try {
                $table->dropUnique('monthly_schedules_plan_department_month_year_unique');
            } catch (\Exception $e) {
            }

            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
            $table->unique(
                ['plan_id', 'class_id', 'month', 'year'],
                'monthly_schedules_plan_class_month_year_unique'
            );
        });
    }
};
