<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;
use Modules\Training\Models\Room;
use Modules\Training\Models\TrainingBatch;
use Modules\Training\Models\TrainingClass;

class TrainingClassSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        $batches = TrainingBatch::query()
            ->whereIn('code', ['K26A', 'K26B', 'K26C'])
            ->get()
            ->keyBy('code');

        if ($batches->count() < 3) {
            return;
        }

        $roomMap = Room::query()
            ->whereIn('code', ['P-101', 'P-102', 'P-201', 'P-202'])
            ->pluck('id', 'code');

        $classes = [
            [
                'code' => 'DUOC-K26A-01',
                'name' => 'Lớp Dược K26A - 01',
                'training_batch_code' => 'K26A',
                'default_room_code' => 'P-101',
                'course_year' => 2026,
                'total_students' => 50,
            ],
            [
                'code' => 'DUOC-K26A-02',
                'name' => 'Lớp Dược K26A - 02',
                'training_batch_code' => 'K26A',
                'default_room_code' => 'P-102',
                'course_year' => 2026,
                'total_students' => 50,
            ],
            [
                'code' => 'DIEU_DUONG-K26B-01',
                'name' => 'Lớp Điều dưỡng K26B - 01',
                'training_batch_code' => 'K26B',
                'default_room_code' => 'P-201',
                'course_year' => 2026,
                'total_students' => 50,
            ],
            [
                'code' => 'DIEU_DUONG-K26B-02',
                'name' => 'Lớp Điều dưỡng K26B - 02',
                'training_batch_code' => 'K26B',
                'default_room_code' => 'P-202',
                'course_year' => 2026,
                'total_students' => 50,
            ],
            [
                'code' => 'Y_SI_DA_KHOA-K26C-01',
                'name' => 'Lớp Y sĩ đa khoa K26C - 01',
                'training_batch_code' => 'K26C',
                'default_room_code' => 'P-101',
                'course_year' => 2026,
                'total_students' => 50,
            ],
            [
                'code' => 'Y_SI_DA_KHOA-K26C-02',
                'name' => 'Lớp Y sĩ đa khoa K26C - 02',
                'training_batch_code' => 'K26C',
                'default_room_code' => 'P-102',
                'course_year' => 2026,
                'total_students' => 50,
            ],
        ];

        foreach ($classes as $classData) {
            $batch = $batches->get($classData['training_batch_code']);
            $roomId = $roomMap->get($classData['default_room_code']);

            if (! $batch) {
                continue;
            }

            TrainingClass::unguarded(function () use ($classData, $batch, $roomId): void {
                TrainingClass::firstOrCreate(
                    ['code' => $classData['code']],
                    [
                        'name' => $classData['name'],
                        'training_batch_id' => $batch->id,
                        'default_room_id' => $roomId,
                        'course_year' => $classData['course_year'],
                        'total_students' => $classData['total_students'],
                        'status' => 'active',
                    ]
                );
            });
        }
    }
}
