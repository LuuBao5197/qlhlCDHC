<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('DEP-###'),
            'name' => fake()->unique()->company() . ' Department',
            'description' => fake()->sentence(),
            'status' => fake()->randomElement(['active', 'inactive']),
        ];
    }
}
