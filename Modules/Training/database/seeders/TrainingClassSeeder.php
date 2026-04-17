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

        $classCodes = [
            'QY01',
            'QY02',
            'QY03'
        ];

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
    }
}
