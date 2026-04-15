<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\ApprovalAction;
use Modules\Training\Models\ApprovalRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ApprovalAction>
 */
class ApprovalActionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'approval_request_id' => ApprovalRequest::factory(),
            'step_code' => fake()->randomElement(['submitted', 'training_review', 'leadership_review']),
            'action' => fake()->randomElement(['submit', 'approve', 'reject', 'return', 'comment']),
            'acted_by' => User::factory(),
            'acted_at' => now(),
            'comment' => fake()->sentence(),
        ];
    }
}
