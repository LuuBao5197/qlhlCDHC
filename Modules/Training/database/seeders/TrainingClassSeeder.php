<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\Department;
use Modules\Training\Models\TrainingClass;
use Illuminate\Database\Seeder;
use Modules\Schedule\Models\MonthlySchedule;

class TrainingClassSeeder extends Seeder
{
    public function run(): void
    {
        $defaultDepartment = Department::where('code', 'KCNTT')->first() ?? Department::query()->first();

        $classCodes = MonthlySchedule::query()
            ->whereNotNull('class_name')
            ->pluck('class_name')
            ->unique()
            ->filter()
            ->values()
            ->all();

        // If no classes found in MonthlySchedules, use default classes
        if (empty($classCodes)) {
            $classCodes = [
                'KCNTT-K68-A',
                'KCNTT-K68-B',
                'KCNTT-K68-C'
            ];
        }

        foreach ($classCodes as $index => $classCode) {
            TrainingClass::updateOrCreate(
                ['code' => $classCode],
                [
                    'name' => 'Lop ' . $classCode,
                    'course_year' => 2024 + ($index % 3),
                    'status' => 'active',
                ]
            );
        }

        MonthlySchedule::query()->get()->each(function (MonthlySchedule $monthlySchedule) use ($defaultDepartment) {
            if (! $monthlySchedule->department_id) {
                $monthlySchedule->update([
                    'department_id' => $defaultDepartment?->id,
                ]);
            }
        });
    }
}
