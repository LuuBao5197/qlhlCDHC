<?php

namespace Modules\Training\Database\Seeders;

use Modules\Training\Models\Student;
use Modules\Training\Models\TrainingClass;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        TrainingClass::query()->each(function (TrainingClass $trainingClass) {
            for ($number = 1; $number <= 5; $number++) {
                Student::updateOrCreate(
                    ['student_code' => $trainingClass->code . '-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT)],
                    [
                        'class_id' => $trainingClass->id,
                        'name' => 'Hoc vien ' . $trainingClass->code . ' ' . $number,
                        'date_of_birth' => now()->subYears(20)->addDays($number)->toDateString(),
                        'status' => 'active',
                    ]
                );
            }
        });
    }
}
