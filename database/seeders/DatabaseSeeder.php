<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Training\Database\Seeders\TrainingDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            TrainingDatabaseSeeder::class,
        ]);
    }
}
