<?php

namespace Modules\Schedule\Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\Plans;
use Modules\Training\Models\Department;

class ScheduleDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        MonthlySchedule::query()->delete();
        Plans::query()->delete();
        DB::table('plan_templates')->delete();

        DB::statement('ALTER TABLE monthly_schedules AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE plans AUTO_INCREMENT = 1');
        DB::statement('ALTER TABLE plan_templates AUTO_INCREMENT = 1');

        Plans::create([
            'name' => 'Kế hoạch huấn luyện HK1',
            'semester' => 1,
            'year' => 2026,
            'file_path' => 'plans/hk1_2026.pdf',
            'effective_from' => Carbon::create(2026, 1, 1),
            'effective_to' => Carbon::create(2026, 7, 30),
        ]);

        $departments = [
            ['code' => 'KCNTT', 'name' => 'Khoa Công Nghệ Thông Tin'],
            ['code' => 'KQS', 'name' => 'Khoa Quân Sự'],
        ];

        foreach ($departments as $departmentData) {
            Department::firstOrCreate(
                ['code' => $departmentData['code']],
                [
                    'name' => $departmentData['name'],
                    'description' => 'Seeded department for plan templates',
                    'status' => 'active',
                ]
            );
        }

        $this->call(PlanTemplateSeeder::class);
    }
}
