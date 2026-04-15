<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Schedule\Database\Seeders\ScheduleDatabaseSeeder;
use Modules\Training\Database\Seeders\TrainingDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->createSeedUsers();

        $this->call([
            TrainingDatabaseSeeder::class,  // Run first to create subjects, classes, etc.
            ScheduleDatabaseSeeder::class,  // Then create plans and templates
        ]);
    }

    private function createSeedUsers(): void
    {
        // Password for all seeds: password123
        $users = [
            [
                'name' => 'System Admin',
                'email' => 'admin@example.com',
                'role' => 'admin',
                'status' => 'approved'
            ],
            [
                'name' => 'Leader One',
                'email' => 'leader@example.com',
                'role' => 'leadership',
                'status' => 'approved'
            ],
            [
                'name' => 'Training Office',
                'email' => 'training@example.com',
                'role' => 'training_office',
                'status' => 'approved'
            ],
            [
                'name' => 'Department Staff',
                'email' => 'department@example.com',
                'role' => 'department_staff',
                'status' => 'approved'
            ],
            [
                'name' => 'Teacher',
                'email' => 'teacher@example.com',
                'role' => 'teacher',
                'status' => 'approved'
            ],
            [
                'name' => 'Student',
                'email' => 'student@example.com',
                'role' => 'student',
                'status' => 'approved'
            ],
            [
                'name' => 'Pending User',
                'email' => 'pending@example.com',
                'role' => null,
                'status' => 'pending'
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate([
                'email' => $user['email'],
            ], [
                'name' => $user['name'],
                'role' => $user['role'],
                'status' => $user['status'],
                'password' => bcrypt('password123'),
            ]);
        }
    }
}
