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
        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropForeign('monthly_schedules_plan_id_foreign');
            });
        } catch (\Exception $e) {
        }

        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropForeign('monthly_schedules_class_id_foreign');
            });
        } catch (\Exception $e) {
        }

        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropUnique('monthly_schedules_plan_class_month_year_unique');
            });
        } catch (\Exception $e) {
        }

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
        });

        Schema::table('monthly_schedules', function (Blueprint $table) {
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
        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropForeign('monthly_schedules_plan_id_foreign');
            });
        } catch (\Exception $e) {
        }

        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropForeign('monthly_schedules_class_id_foreign');
            });
        } catch (\Exception $e) {
        }

        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropUnique('monthly_schedules_plan_department_month_year_unique');
            });
        } catch (\Exception $e) {
        }

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreign('plan_id')->references('id')->on('plans')->cascadeOnDelete();
        });

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreign('class_id')->references('id')->on('classes')->nullOnDelete();
        });

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->unique(
                ['plan_id', 'class_id', 'month', 'year'],
                'monthly_schedules_plan_class_month_year_unique'
            );
        });
    }
};
