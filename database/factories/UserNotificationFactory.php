<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserNotification>
 */
class UserNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'order_id' => Order::factory(),
            'type' => UserNotification::RevisionRequested,
            'title' => 'Notifikasi pesanan',
            'body' => fake()->sentence(10),
        ];
    }
}
