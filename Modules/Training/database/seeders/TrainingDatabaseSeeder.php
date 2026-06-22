<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\Concerns\DemoSeedingGuard;

class TrainingDatabaseSeeder extends Seeder
{
    use DemoSeedingGuard;

    public function run(): void
    {
        if (! $this->shouldRunDemoSeeding()) {
            return;
        }

        $this->call([
            DepartmentSeeder::class,
            DemoUserSeeder::class,
            TrainingProgramSeeder::class,
            TrainingBatchSeeder::class,
            TeacherSeeder::class,
            RoomSeeder::class,
            TrainingClassSeeder::class,
            SubjectSeeder::class,
            SubjectLessonSeeder::class,
            StudentSeeder::class,
        ]);
    }
}
