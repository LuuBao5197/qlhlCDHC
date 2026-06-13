<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('monthly_schedules', 'department_id')) {
                try {
                    $table->dropForeign(['department_id']);
                } catch (Throwable $e) {
                }

                try {
                    $table->dropUnique('monthly_schedules_plan_department_month_year_unique');
                } catch (Throwable $e) {
                }

                $table->dropColumn('department_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table) {
            $table->foreignId('department_id')
                ->nullable()
                ->after('plan_id')
                ->constrained('departments')
                ->onDelete('set null');
        });
    }
};
