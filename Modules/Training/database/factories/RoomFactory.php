<?php

namespace Modules\Training\Database\Factories;

use Modules\Training\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Room>
 */
class RoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('RM-###'),
            'name' => 'Phong ' . fake()->unique()->bothify('###'),
            'capacity' => fake()->numberBetween(20, 80),
            'room_type' => fake()->randomElement(['classroom', 'lab', 'hall']),
            'status' => fake()->randomElement(['active', 'inactive', 'maintenance']),
        ];
    }
}
