<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\Role;
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
            'role_id' => Role::ensureIdFor(UserRole::Customer->value, UserRole::Customer->label()),
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
     * Indicate that the user places orders as a customer.
     */
    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::ensureIdFor(UserRole::Customer->value, UserRole::Customer->label()),
        ]);
    }

    /**
     * Indicate that the user manages services and orders as an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::ensureIdFor(UserRole::Admin->value, UserRole::Admin->label()),
        ]);
    }

    /**
     * Indicate that the user manages accounts as a super admin.
     */
    public function superAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::ensureIdFor(UserRole::SuperAdmin->value, UserRole::SuperAdmin->label()),
        ]);
    }

    /**
     * Indicate that the user handles claimed orders as a worker.
     */
    public function worker(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::ensureIdFor(UserRole::Worker->value, UserRole::Worker->label()),
            'is_online' => false,
        ]);
    }

    /**
     * Indicate that the worker is available to claim new orders.
     */
    public function online(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_online' => true,
        ]);
    }
}
