<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderStatusHistory>
 */
class OrderStatusHistoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'from_order_status_id' => $this->statusId(OrderStatus::PendingPayment),
            'to_order_status_id' => $this->statusId(OrderStatus::WaitingVerification),
            'changed_by' => User::factory()->admin(),
            'note' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * The entry recorded when the order was first created.
     */
    public function initial(): static
    {
        return $this->state(fn (): array => [
            'from_order_status_id' => null,
            'to_order_status_id' => $this->statusId(OrderStatus::PendingPayment),
            'changed_by' => null,
            'note' => 'Pesanan dibuat.',
        ]);
    }

    /**
     * Resolve the lookup id for an order status.
     */
    private function statusId(OrderStatus $status): int
    {
        return OrderStatusLookup::ensureIdFor($status->value, $status->label());
    }
}
