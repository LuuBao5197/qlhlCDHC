<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\ApprovalRequest;
use App\Models\User;
use Modules\Training\Database\Factories\Concerns\ResolvesScheduleReferences;
use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Schedule\Models\Plans;

/**
 * @extends Factory<ApprovalRequest>
 */
class ApprovalRequestFactory extends Factory
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
            'entity_type' => Plans::class,
            'entity_id' => fn () => $this->firstOrCreatePlan()->id,
            'submitted_by' => User::factory(),
            'current_step' => fake()->randomElement(['submitted', 'training_review', 'leadership_review']),
            'status' => fake()->randomElement(['pending', 'processing', 'approved', 'rejected', 'returned']),
            'submitted_at' => now(),
            'completed_at' => fake()->boolean(35) ? now()->addDay() : null,
        ];
    }
}
