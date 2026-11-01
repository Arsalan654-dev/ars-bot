<?php

namespace Database\Factories;

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

    /**
     * A platform super admin (no payment required).
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_SUPER_ADMIN,
            'is_paid' => true,
            'bot_active' => true,
            'api_key' => User::generateApiKey(),
        ]);
    }

    /**
     * A customer with an active paid subscription.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_USER,
            'is_paid' => true,
            'paid_at' => now(),
            'bot_active' => true,
            'api_key' => User::generateApiKey(),
            'subscription_plan' => 'monthly',
            'subscription_days' => 30,
            'subscription_ends_at' => now()->addDays(30),
            'plan_label' => 'Monthly',
            'plan_amount' => 79,
            'plan_currency' => 'USD',
            'plan_days' => 30,
        ]);
    }

    /**
     * A customer who has not paid yet.
     */
    public function unpaid(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => User::ROLE_USER,
            'is_paid' => false,
            'bot_active' => false,
            'api_key' => User::generateApiKey(),
            'plan_label' => 'Monthly',
            'plan_amount' => 79,
            'plan_currency' => 'USD',
            'plan_days' => 30,
        ]);
    }
}
