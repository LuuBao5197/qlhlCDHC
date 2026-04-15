<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\DailyTrainingLog;
use App\Models\User;
use Modules\Training\Database\Factories\Concerns\ResolvesScheduleReferences;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyTrainingLog>
 */
class DailyTrainingLogFactory extends Factory
{
    use ResolvesScheduleReferences;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'schedule_slot_id' => fn () => $this->firstOrCreateScheduleSlot()->id,
            'teacher_id' => User::factory(),
            'actual_date' => now(),
            'actual_period_number' => fake()->numberBetween(1, 9),
            'result_status' => fake()->randomElement(['recorded', 'completed', 'missed', 'delayed']),
            'actual_content' => fake()->sentence(),
            'issue_note' => fake()->optional()->sentence(),
            'checked_by' => User::factory(),
            'checked_at' => now(),
        ];
    }
}
