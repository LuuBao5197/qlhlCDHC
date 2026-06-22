<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\TrainingProgram;

class TrainingProgramSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        $programs = [
            ['code' => 'DUOC', 'name' => 'Chương trình Dược'],
            ['code' => 'DIEU_DUONG', 'name' => 'Chương trình Điều dưỡng'],
            ['code' => 'Y_SI_DA_KHOA', 'name' => 'Chương trình Y sĩ đa khoa'],
        ];

        foreach ($programs as $program) {
            TrainingProgram::firstOrCreate(
                ['code' => $program['code']],
                [
                    'name' => $program['name'],
                    'status' => 'active',
                ]
            );
        }
    }
}
