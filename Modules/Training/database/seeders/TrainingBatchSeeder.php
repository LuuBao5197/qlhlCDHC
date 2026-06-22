<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\TrainingBatch;
use Modules\Training\Models\TrainingProgram;

class TrainingBatchSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        $programMap = TrainingProgram::query()
            ->whereIn('code', ['DUOC', 'DIEU_DUONG', 'Y_SI_DA_KHOA'])
            ->get()
            ->keyBy('code');

        $batches = [
            ['code' => 'K26A', 'training_program_code' => 'DUOC', 'name' => 'Khóa 2026A ngành Dược'],
            ['code' => 'K26B', 'training_program_code' => 'DIEU_DUONG', 'name' => 'Khóa 2026B ngành Điều dưỡng'],
            ['code' => 'K26C', 'training_program_code' => 'Y_SI_DA_KHOA', 'name' => 'Khóa 2026C ngành Y sĩ đa khoa'],
        ];

        foreach ($batches as $batch) {
            $program = $programMap->get($batch['training_program_code']);

            if (! $program) {
                continue;
            }

            TrainingBatch::firstOrCreate(
                ['code' => $batch['code']],
                [
                    'training_program_id' => $program->id,
                    'name' => $batch['name'],
                    'status' => 'active',
                ]
            );
        }
    }
}
