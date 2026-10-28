<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use App\Support\OrderNumberGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()->customer(),
            'service_id' => Service::factory()->published(),
            'order_number' => app(OrderNumberGenerator::class)->generate(),
            'description' => fake()->paragraph(4),
            'order_status_id' => $this->statusId(OrderStatus::PendingPayment),
            'total_price' => fake()->numberBetween(100_000, 2_000_000),
        ];
    }

    /**
     * The customer has not paid yet.
     */
    public function pendingPayment(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::PendingPayment),
        ]);
    }

    /**
     * The order is waiting for the admin/worker to verify the payment.
     */
    public function waitingVerification(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::WaitingVerification),
        ]);
    }

    /**
     * The order was verified and is waiting for a worker to claim it.
     */
    public function queued(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::Queued),
            'worker_id' => null,
            'worker_claimed_at' => null,
        ]);
    }

    /**
     * The order is assigned to a specific worker.
     */
    public function assignedTo(User $worker): static
    {
        return $this->state(fn (): array => [
            'worker_id' => $worker->getKey(),
            'worker_claimed_at' => now(),
        ]);
    }

    /**
     * The admin/worker asked the customer a question.
     */
    public function waitingCustomer(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::WaitingCustomer),
        ]);
    }

    /**
     * The work is currently being done.
     */
    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::InProgress),
        ]);
    }

    /**
     * The customer asked for a revision.
     */
    public function revision(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::Revision),
        ]);
    }

    /**
     * The work is finished but not yet delivered.
     */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::Completed),
        ]);
    }

    /**
     * The work has been handed over to the customer.
     */
    public function delivered(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::Delivered),
        ]);
    }

    /**
     * The order was cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (): array => [
            'order_status_id' => $this->statusId(OrderStatus::Cancelled),
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
