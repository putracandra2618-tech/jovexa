<?php

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Lookup\PaymentStatus as PaymentStatusLookup;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $order = Order::factory();
        $reference = 'PUB-'.$this->faker->unique()->numerify('############');

        return [
            'order_id' => $order,
            'transaction_id' => $reference,
            'tripay_reference' => $reference,
            'payment_method' => 'QRIS',
            'payment_channel' => 'QRIS',
            'amount' => fake()->numberBetween(100_000, 2_000_000),
            'payment_status_id' => $this->statusId(PaymentStatus::Pending),
            'checkout_url' => 'https://checkout.tripay.id/'.$reference,
            'expires_at' => now()->addDay(),
            'paid_at' => null,
        ];
    }

    /**
     * The customer has an open checkout the gateway keeps alive.
     */
    public function open(): static
    {
        return $this->state(fn (): array => [
            'payment_status_id' => $this->statusId(PaymentStatus::Pending),
            'expires_at' => now()->addDay(),
        ]);
    }

    /**
     * The checkout has already run out of time.
     */
    public function expired(): static
    {
        return $this->state(fn (): array => [
            'payment_status_id' => $this->statusId(PaymentStatus::Expired),
            'expires_at' => now()->subSecond(),
        ]);
    }

    /**
     * The gateway confirmed the payment.
     */
    public function paid(): static
    {
        return $this->state(fn (): array => [
            'payment_status_id' => $this->statusId(PaymentStatus::Paid),
            'amount_received' => fn (array $attributes): int => $attributes['amount'],
            'paid_at' => now(),
        ]);
    }

    /**
     * The payment attempt failed.
     */
    public function failed(): static
    {
        return $this->state(fn (): array => [
            'payment_status_id' => $this->statusId(PaymentStatus::Failed),
        ]);
    }

    /**
     * Resolve the lookup id for a payment status.
     */
    private function statusId(PaymentStatus $status): int
    {
        return PaymentStatusLookup::ensureIdFor($status->value, $status->label());
    }
}
