<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\ChangeRequest;
use App\Models\User;
use Modules\Training\Database\Factories\Concerns\ResolvesScheduleReferences;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChangeRequest>
 */
class ChangeRequestFactory extends Factory
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
            'monthly_schedule_id' => fn () => $this->firstOrCreateMonthlySchedule()->id,
            'schedule_slot_id' => fn () => $this->firstOrCreateScheduleSlot()->id,
            'requested_by' => User::factory(),
            'reason' => fake()->sentence(),
            'old_payload' => ['subject' => 'Old subject', 'room_id' => null],
            'new_payload' => ['subject' => 'New subject', 'room_id' => null],
            'status' => fake()->randomElement(['pending', 'approved', 'rejected', 'resolved']),
            'apply_mode' => fake()->randomElement(['all_or_none', 'best_effort']),
            'apply_changes' => fake()->boolean(80),
            'apply_summary' => null,
            'submitted_at' => now(),
            'resolved_at' => fake()->boolean(35) ? now()->addDay() : null,
        ];
    }
}
