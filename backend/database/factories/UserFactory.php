<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

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
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'display_name' => fake()->name(),
            'birth_date' => fake()->dateTimeBetween('-60 years', '-18 years')->format('Y-m-d'),
            'email_verified_at' => now(),
            'age_verified_at' => now(),
            'age_verify_method' => 'self_declared',
            'role' => 'user',
            'status' => 'active',
            'rating' => ['sum' => 0, 'count' => 0],
            'stats' => [
                'completed_count' => 0,
                'accepted_count' => 0,
                'cancel_after_accept_count' => 0,
                'sos_count' => 0,
            ],
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
