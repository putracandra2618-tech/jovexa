<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Lookup\PaymentStatus as PaymentStatusLookup;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Covers the two scheduled sweeps that recover from callbacks the gateway never
 * delivered. Both are the only thing standing between a lost notification and an
 * order that is stuck forever.
 */
class TripayReconcilePaymentsTest extends TestCase
{
    use RefreshDatabase;

    // Http::fake() matches against the full URL including the query string, so the
    // pattern needs a trailing wildcard or the real gateway gets called instead.
    private const DETAIL_URL = 'tripay.co.id/api-sandbox/transaction/detail*';

    public function test_it_expires_a_stale_payment_that_the_gateway_never_reported(): void
    {
        $payment = $this->makePayment([
            'expires_at' => now()->subMinute(),
        ]);

        $this->artisan('tripay:expire-stale-payments')
            ->expectsOutput('1 pesanan ditandai kedaluwarsa.')
            ->assertSuccessful();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Expired, $payment->status);
        $this->assertSame(OrderStatus::Expired, $payment->order->fresh()->status);
    }

    public function test_it_leaves_a_payment_alone_until_its_expiry_passes(): void
    {
        $payment = $this->makePayment([
            'expires_at' => now()->addMinute(),
        ]);

        $this->artisan('tripay:expire-stale-payments')
            ->expectsOutput('0 pesanan ditandai kedaluwarsa.')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_it_never_expires_a_payment_that_was_already_captured(): void
    {
        $payment = $this->makePayment([
            'expires_at' => now()->subDay(),
            'payment_status_id' => PaymentStatusLookup::idFor(PaymentStatus::Paid->value),
            'paid_at' => now()->subDays(2),
        ]);

        $this->artisan('tripay:expire-stale-payments')->assertSuccessful();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_it_expires_every_pending_payment_on_the_same_order(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->create();

        $first = $this->makePayment([
            'order' => $order,
            'expires_at' => now()->subMinute(),
        ]);
        $second = $this->makePayment([
            'order' => $order,
            'expires_at' => now()->subMinute(),
            'transaction_id' => 'PUB-'.$order->order_number.'-2',
            'tripay_reference' => 'PUB-'.$order->order_number.'-2',
        ]);

        $this->artisan('tripay:expire-stale-payments')
            ->expectsOutput('1 pesanan ditandai kedaluwarsa.')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Expired, $first->fresh()->status);
        $this->assertSame(PaymentStatus::Expired, $second->fresh()->status);
        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);
    }

    public function test_it_does_not_touch_an_order_that_is_no_longer_payable(): void
    {
        $payment = $this->makePayment([
            'expires_at' => now()->subMinute(),
            'order' => ['order_status_id' => OrderStatusLookup::idFor(OrderStatus::Cancelled->value)],
        ]);

        $this->artisan('tripay:expire-stale-payments')
            ->expectsOutput('0 pesanan ditandai kedaluwarsa.')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Cancelled, $payment->order->fresh()->status);
    }

    public function test_it_recovers_a_paid_transaction_whose_callback_was_lost(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment([
            'created_at' => now()->subHours(2),
        ]);

        $this->fakeGatewayStatus('PAID');

        $this->artisan('tripay:reconcile-payments')
            ->expectsOutput('1 transaksi diperiksa, 1 diperbarui, 0 gagal dijangkau.')
            ->assertSuccessful();

        $payment->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame(OrderStatus::WaitingVerification, $payment->order->fresh()->status);

        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/transaction/detail'));
    }

    public function test_it_leaves_a_transaction_that_is_still_pending_at_the_gateway(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment([
            'created_at' => now()->subHours(2),
        ]);

        $this->fakeGatewayStatus('PENDING');

        $this->artisan('tripay:reconcile-payments')
            ->expectsOutput('1 transaksi diperiksa, 0 diperbarui, 0 gagal dijangkau.')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_it_recovers_a_payment_the_local_sweep_already_expired(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment([
            'created_at' => now()->subHours(2),
            'expires_at' => now()->subHour(),
            'payment_status_id' => PaymentStatusLookup::idFor(PaymentStatus::Expired->value),
            'order' => ['order_status_id' => OrderStatusLookup::idFor(OrderStatus::Expired->value)],
        ]);

        $this->fakeGatewayStatus('PAID');

        $this->artisan('tripay:reconcile-payments')
            ->expectsOutput('1 transaksi diperiksa, 1 diperbarui, 0 gagal dijangkau.')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(OrderStatus::WaitingVerification, $payment->order->fresh()->status);
    }

    public function test_it_stops_reconciling_long_expired_payments(): void
    {
        $this->withTripayConfigured();

        $this->makePayment([
            'created_at' => now()->subDays(10),
            'expires_at' => now()->subDays(8),
            'payment_status_id' => PaymentStatusLookup::idFor(PaymentStatus::Expired->value),
            'order' => ['order_status_id' => OrderStatusLookup::idFor(OrderStatus::Expired->value)],
        ]);

        $this->artisan('tripay:reconcile-payments')
            ->expectsOutput('0 transaksi diperiksa, 0 diperbarui, 0 gagal dijangkau.')
            ->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_it_treats_an_unpaid_transaction_as_still_pending(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment([
            'created_at' => now()->subHours(2),
        ]);

        $this->fakeGatewayStatus('UNPAID');

        $this->artisan('tripay:reconcile-payments')
            ->expectsOutput('1 transaksi diperiksa, 0 diperbarui, 0 gagal dijangkau.')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_it_skips_a_transaction_that_is_too_fresh_to_be_lost(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment([
            'created_at' => now()->subMinutes(5),
        ]);

        Http::fake([
            self::DETAIL_URL => Http::response([], 500),
        ]);

        $this->artisan('tripay:reconcile-payments')
            ->expectsOutput('0 transaksi diperiksa, 0 diperbarui, 0 gagal dijangkau.')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_it_keeps_going_when_the_gateway_cannot_be_reached(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment([
            'created_at' => now()->subHours(2),
        ]);

        Http::fake([
            self::DETAIL_URL => Http::response(['message' => 'Gateway down'], 500),
        ]);

        $this->artisan('tripay:reconcile-payments')
            ->expectsOutput('1 transaksi diperiksa, 0 diperbarui, 1 gagal dijangkau.')
            ->assertSuccessful();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_it_does_nothing_without_gateway_credentials(): void
    {
        // The credentials have to be cleared explicitly: config() falls back to the
        // developer's real .env, so leaving them alone would make this test pass
        // only on a machine that has no Tripay keys.
        config([
            'tripay.api_key' => null,
            'tripay.private_key' => null,
            'tripay.merchant_code' => null,
        ]);

        $payment = $this->makePayment([
            'created_at' => now()->subHours(2),
        ]);

        $this->artisan('tripay:reconcile-payments')
            ->expectsOutput('Kredensial Tripay belum lengkap, rekonsiliasi dilewati.')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    /**
     * Configure the gateway credentials so the sweep does not bail out early.
     */
    private function withTripayConfigured(): void
    {
        // Pin every value the client reads, including the base URL, so the suite
        // never inherits a production gateway from the developer's .env.
        config([
            'tripay.api_key' => 'dev-api-key',
            'tripay.private_key' => 'dev-private-key',
            'tripay.merchant_code' => 'T00000',
            'tripay.base_url' => 'https://tripay.co.id/api-sandbox',
        ]);
    }

    /**
     * Pretend the gateway reports the given status for the checked transaction.
     */
    private function fakeGatewayStatus(string $status): void
    {
        Http::fake([
            self::DETAIL_URL => Http::response([
                'success' => true,
                'data' => [
                    'status' => $status,
                    'amount' => 200_000,
                    'amount_received' => $status === 'PAID' ? 200_000 : 0,
                    'paid_at' => now()->timestamp,
                ],
            ]),
        ]);
    }

    /**
     * A pending payment row sitting in the database.
     *
     * $createdAt backdates the row so the sweep lookback window can be crossed.
     * It has to be forced after the insert because created_at is not mass
     * assignable and would otherwise be stamped with the current time.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function makePayment(array $overrides = []): Payment
    {
        $orderOverrides = $overrides['order'] ?? [];
        $createdAt = $overrides['created_at'] ?? null;

        unset($overrides['order'], $overrides['created_at']);

        $order = $orderOverrides instanceof Order
            ? $orderOverrides
            : Order::factory()->for(User::factory()->customer())->create($orderOverrides);
        $reference = 'PUB-'.$order->order_number;

        $payment = $order->payments()->create(array_merge([
            'transaction_id' => $reference,
            'tripay_reference' => $reference,
            'payment_method' => 'QRIS',
            'payment_channel' => 'QRIS',
            'amount' => 200_000,
            'payment_status_id' => PaymentStatusLookup::idFor(PaymentStatus::Pending->value),
            'checkout_url' => 'https://tripay.co.id/open/'.$reference,
            'expires_at' => now()->addDay(),
        ], $overrides));

        if ($createdAt !== null) {
            $payment->forceFill(['created_at' => $createdAt])->save();
        }

        return $payment;
    }
}
