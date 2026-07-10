<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\SlotEvaluation;
use App\Models\User;
use Modules\Training\Database\Factories\Concerns\ResolvesScheduleReferences;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SlotEvaluation>
 */
class SlotEvaluationFactory extends Factory
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
            'evaluator_id' => User::factory(),
            'rating_level' => fake()->randomElement(['tot', 'kha', 'trung_binh', 'yeu']),
            'comment' => fake()->sentence(),
        ];
    }
}
