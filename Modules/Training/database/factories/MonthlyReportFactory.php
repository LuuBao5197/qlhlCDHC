<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\MonthlyReport;
use App\Models\User;
use Modules\Training\Database\Factories\Concerns\ResolvesScheduleReferences;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonthlyReport>
 */
class MonthlyReportFactory extends Factory
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
            'created_by' => User::factory(),
            'summary' => fake()->paragraph(),
            'result_overview' => fake()->paragraph(),
            'recommendation' => fake()->paragraph(),
            'status' => fake()->randomElement(['draft', 'submitted', 'approved', 'rejected']),
            'submitted_at' => now(),
            'approved_by' => fake()->boolean(50) ? User::factory() : null,
            'approved_at' => fake()->boolean(50) ? now()->addDay() : null,
        ];
    }
}
