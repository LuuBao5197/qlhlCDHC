<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\Department;
use Modules\Training\Models\TrainingClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrainingClass>
 */
class TrainingClassFactory extends Factory
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
            'code' => fake()->unique()->bothify('CLS-##??'),
            'name' => 'Lop ' . fake()->unique()->bothify('##??'),
            'course_year' => fake()->numberBetween(2023, 2030),
            'status' => fake()->randomElement(['active', 'inactive', 'archived']),
        ];
    }
}
