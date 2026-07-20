<?php

namespace Database\Factories;

use App\Enums\Position;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Gán Position mặc định theo role cuối cùng (sau khi test override ->create(['role' => ...])),
     * ở tier cao nhất để giữ nguyên hành vi phê duyệt hiện có của các test không set position.
     */
    public function configure(): static
    {
        return $this->afterMaking(function (User $user): void {
            if ($user->position !== null) {
                return;
            }

            $user->position = match ($user->role) {
                User::ROLE_DEPARTMENT_STAFF => Position::DEPARTMENT_HEAD,
                User::ROLE_TRAINING_OFFICE => Position::TRAINING_HEAD,
                User::ROLE_LEADERSHIP => Position::PRINCIPAL,
                default => null,
            };
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role' => fake()->randomElement(User::getAvailableRoles()),
            'status' => fake()->randomElement(User::getAvailableStatuses()),
            'employee_code' => fake()->unique()->bothify('EMP-####'),
            'phone' => fake()->phoneNumber(),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
