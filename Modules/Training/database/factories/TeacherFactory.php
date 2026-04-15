<?php

namespace Modules\Training\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Training\Models\Department;
use Modules\Training\Models\Teacher;

/**
 * @extends Factory<Teacher>
 */
class TeacherFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_code' => fake()->unique()->bothify('GV-####'),
            'name' => fake()->name(),
            'status' => fake()->randomElement(['active', 'inactive']),
            'department_id' => Department::factory(),
        ];
    }
}