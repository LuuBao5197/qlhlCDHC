<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\Student;
use Modules\Training\Models\TrainingClass;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Student>
 */
class StudentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_id' => TrainingClass::factory(),
            'student_code' => fake()->unique()->bothify('STU#####'),
            'name' => fake()->name(),
            'date_of_birth' => fake()->dateTimeBetween('-25 years', '-18 years')->format('Y-m-d'),
            'status' => fake()->randomElement(['active', 'suspended', 'graduated']),
        ];
    }
}
