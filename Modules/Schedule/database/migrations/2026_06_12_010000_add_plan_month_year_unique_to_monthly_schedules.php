<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDEX_NAME = 'monthly_schedules_plan_month_year_unique';

    public function up(): void
    {
        $duplicate = DB::table('monthly_schedules')
            ->select(['plan_id', 'month', 'year'])
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('plan_id', 'month', 'year')
            ->havingRaw('COUNT(*) > 1')
            ->first();

        if ($duplicate !== null) {
            throw new RuntimeException(sprintf(
                'Khong the tao unique monthly_schedules: plan_id=%s, month=%s, year=%s dang co %s ban ghi.',
                $duplicate->plan_id,
                $duplicate->month,
                $duplicate->year,
                $duplicate->aggregate
            ));
        }

        Schema::table('monthly_schedules', function (Blueprint $table): void {
            $table->unique(['plan_id', 'month', 'year'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        Schema::table('monthly_schedules', function (Blueprint $table): void {
            $table->dropUnique(self::INDEX_NAME);
        });
    }
};
