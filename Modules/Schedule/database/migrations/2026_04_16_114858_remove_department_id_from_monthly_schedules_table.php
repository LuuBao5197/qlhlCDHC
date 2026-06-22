<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('monthly_schedules', 'department_id')) {
            return;
        }

        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropForeign('monthly_schedules_department_id_foreign');
            });
        } catch (Throwable $e) {
        }

        try {
            Schema::table('monthly_schedules', function (Blueprint $table) {
                $table->dropUnique('monthly_schedules_plan_department_month_year_unique');
            });
        } catch (Throwable $e) {
        }

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->dropColumn('department_id');
        });
    }

    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('plan_id');
        });

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreign('department_id')
                ->references('id')
                ->on('departments')
                ->nullOnDelete();
        });

        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->unique(
                ['plan_id', 'department_id', 'month', 'year'],
                'monthly_schedules_plan_department_month_year_unique'
            );
        });
    }
};
