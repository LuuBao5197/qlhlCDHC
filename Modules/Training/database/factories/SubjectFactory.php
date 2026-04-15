<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\Department;
use Modules\Training\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'department_id' => Department::factory(),
            'code' => fake()->unique()->bothify('SUB-###'),
            'name' => ucfirst(fake()->unique()->word()) . ' Training',
            'total_periods' => fake()->numberBetween(12, 60),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
