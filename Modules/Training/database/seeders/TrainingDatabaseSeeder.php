<?php

namespace Modules\Training\Database\Seeders;

use Illuminate\Database\Seeder;

class TrainingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            DepartmentSeeder::class,
            TeacherSeeder::class,
            TrainingClassSeeder::class,
            SubjectSeeder::class,
            SubjectLessonSeeder::class,
            RoomSeeder::class,
            StudentSeeder::class,
            ApprovalRequestSeeder::class,
            ApprovalActionSeeder::class,
            ChangeRequestSeeder::class,
            DailyTrainingLogSeeder::class,
            SlotEvaluationSeeder::class,
            MonthlyReportSeeder::class,
        ]);
    }
}