<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\SubjectLesson;
use Modules\Training\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SubjectLesson>
 */
class SubjectLessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'lesson_no' => fake()->numberBetween(1, 10),
            'title' => ucfirst(fake()->words(3, true)),
            'expected_periods' => fake()->numberBetween(1, 4),
            'note' => fake()->sentence(),
        ];
    }
}
