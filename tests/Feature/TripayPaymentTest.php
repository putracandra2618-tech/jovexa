<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Lookup\PaymentStatus as PaymentStatusLookup;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\TripayClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TripayPaymentTest extends TestCase
{
    use RefreshDatabase;

    private const CHANNEL_CACHE_KEY = 'tripay.payment_channels';

    /**
     * Configure the gateway credentials and a known channel list for the test.
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

        Cache::put(self::CHANNEL_CACHE_KEY, [
            'QRIS' => 'QRIS',
            'BRIVA' => 'BRI Virtual Account',
        ]);
    }

    /**
     * The payment row the gateway would have created for the order.
     */
    private function makePayment(Order $order, array $overrides = []): Payment
    {
        $reference = 'PUB-'.$order->order_number;

        return $order->payments()->create(array_merge([
            'transaction_id' => $reference,
            'tripay_reference' => $reference,
            'payment_method' => 'QRIS',
            'payment_channel' => 'QRIS',
            'amount' => $order->total_price,
            'payment_status_id' => PaymentStatusLookup::idFor(PaymentStatus::Pending->value),
            'checkout_url' => 'https://tripay.co.id/open/'.$reference,
            'expires_at' => now()->addDay(),
        ], $overrides));
    }

    /**
     * A callback payload shaped like the ones Tripay really sends.
     *
     * @return array<string, mixed>
     */
    private function payload(Payment $payment, array $overrides = []): array
    {
        $order = $payment->order;

        return array_merge([
            'reference' => $payment->tripay_reference,
            'merchant_ref' => $order->order_number,
            'merchant_code' => 'T00000',
            'status' => 'PAID',
            'total_amount' => $payment->amount,
            'payment_method_code' => 'QRIS',
            'paid_at' => now()->format('Y-m-d H:i:s'),
        ], $overrides);
    }

    /**
     * Post a callback the same way the gateway would.
     *
     * @param  array<string, mixed>  $payload
     */
    private function postCallback(array $payload, ?string $signature = null, string $event = 'payment_status'): TestResponse
    {
        return $this->postJson(route('tripay.notification'), $payload, [
            'X-Callback-Signature' => $signature ?? $this->signature($payload),
            'X-Callback-Event' => $event,
        ]);
    }

    /**
     * Sign a payload the same way the gateway does.
     *
     * @param  array<string, mixed>  $payload
     */
    private function signature(array $payload): string
    {
        // Signs the exact bytes that postJson() puts on the wire, which is what
        // the gateway signs. Re-encoding with different flags is not equivalent.
        return hash_hmac('sha256', json_encode($payload), 'dev-private-key');
    }

    public function test_pay_page_requires_a_signed_in_customer(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->create();

        $this->get(route('customer.orders.pay', $order))->assertRedirect(route('login'));
    }

    public function test_customer_cannot_open_the_pay_page_of_another_customer(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->create();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.orders.pay', $order))
            ->assertForbidden();
    }

    public function test_pay_page_is_closed_once_the_order_is_paid(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->waitingVerification()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.pay', $order))
            ->assertNotFound();
    }

    public function test_pay_page_lists_the_channels_activated_at_the_gateway(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.pay', $order))
            ->assertOk()
            ->assertSee('BRI Virtual Account');
    }

    public function test_pay_page_offers_each_channel_as_a_payment_method_choice(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.pay', $order))
            ->assertOk()
            ->assertSee('name="payment_method"', escape: false)
            ->assertSee('jx-pay-option', escape: false);
    }

    public function test_channel_list_falls_back_to_config_when_the_gateway_is_unreachable(): void
    {
        $this->withTripayConfigured();

        Cache::forget(self::CHANNEL_CACHE_KEY);

        Http::fake([
            'tripay.co.id/api-sandbox/merchant/payment-channel' => Http::response([], 500),
        ]);

        $order = Order::factory()->for(User::factory()->customer())->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.pay', $order))
            ->assertOk()
            ->assertSee('BCA Virtual Account');
    }

    public function test_no_payment_is_recorded_when_the_gateway_is_not_configured(): void
    {
        config([
            'tripay.api_key' => null,
            'tripay.private_key' => null,
            'tripay.merchant_code' => null,
        ]);

        $order = Order::factory()->for(User::factory()->customer())->create();

        $this->actingAs($order->user)
            ->from(route('customer.orders.pay', $order))
            ->post(route('customer.orders.pay.store', $order), ['payment_method' => 'QRIS'])
            ->assertRedirect(route('customer.orders.pay', $order))
            ->assertSessionHas('warning');

        $this->assertCount(0, $order->payments()->get());
    }

    public function test_customer_is_redirected_to_the_gateway_checkout(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();

        Http::fake([
            'tripay.co.id/api-sandbox/transaction/create' => Http::response([
                'success' => true,
                'data' => [
                    'reference' => 'REF-'.$order->order_number,
                    'checkout_url' => 'https://tripay.co.id/checkout/qris',
                    'payment_method' => 'QRIS',
                    'expired_time' => now()->addDay()->timestamp,
                ],
            ]),
        ]);

        $this->actingAs($order->user)
            ->post(route('customer.orders.pay.store', $order), ['payment_method' => 'QRIS'])
            ->assertRedirect('https://tripay.co.id/checkout/qris');

        $payment = $order->payments()->sole();

        $this->assertSame(PaymentStatus::Pending, $payment->status);
        $this->assertSame($order->total_price, $payment->amount);
        $this->assertSame('QRIS', $payment->payment_method);
        $this->assertSame('REF-'.$order->order_number, $payment->tripay_reference);
        $this->assertSame('https://tripay.co.id/checkout/qris', $payment->checkout_url);
    }

    public function test_create_transaction_payload_carries_the_tripay_fields(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer()->create([
            'phone' => '081234567890',
        ]))->create();

        Http::fake([
            'tripay.co.id/api-sandbox/transaction/create' => Http::response([
                'success' => true,
                'data' => [
                    'reference' => 'REF-'.$order->order_number,
                    'checkout_url' => 'https://tripay.co.id/checkout/qris',
                    'payment_method' => 'QRIS',
                    'expired_time' => now()->addDay()->timestamp,
                ],
            ]),
        ]);

        $this->actingAs($order->user)
            ->post(route('customer.orders.pay.store', $order), ['payment_method' => 'QRIS'])
            ->assertRedirect();

        Http::assertSent(function ($request) use ($order): bool {
            $payload = $request->data();

            return $payload['method'] === 'QRIS'
                && $payload['merchant_ref'] === $order->order_number
                && (int) $payload['amount'] === $order->total_price
                && $payload['customer_email'] === $order->user->email
                && $payload['customer_phone'] === '081234567890'
                && $payload['callback_url'] === route('tripay.notification')
                && $payload['return_url'] === route('customer.orders.show', $order)
                && is_string($payload['signature'])
                && $order->order_number !== '';
        });
    }

    public function test_the_open_transaction_is_reused_when_the_customer_returns(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();
        $payment = $this->makePayment($order);

        $this->actingAs($order->user)
            ->post(route('customer.orders.pay.store', $order), ['payment_method' => 'BRIVA'])
            ->assertRedirect($payment->checkout_url);

        $this->assertCount(1, $order->payments()->get());
    }

    public function test_unsupported_channel_is_rejected(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();

        $this->actingAs($order->user)
            ->post(route('customer.orders.pay.store', $order), ['payment_method' => 'EWALLET'])
            ->assertSessionHasErrors('payment_method');

        $this->assertCount(0, $order->payments()->get());
    }

    public function test_gateway_failure_is_reported_without_leaving_a_payment_row(): void
    {
        $this->withTripayConfigured();

        Http::fake([
            'tripay.co.id/api-sandbox/transaction/create' => Http::response([
                'success' => false,
                'message' => 'Payment channel is not enabled (QRIS please enable in Merchant > Channel Pembayaran',
            ], 400),
        ]);

        $order = Order::factory()->for(User::factory()->customer())->create();

        $this->actingAs($order->user)
            ->post(route('customer.orders.pay.store', $order), ['payment_method' => 'QRIS'])
            ->assertSessionHasErrors('payment');

        $this->assertCount(0, $order->payments()->get());
        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_callback_rejects_a_request_with_an_invalid_signature(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        $this->postCallback($this->payload($payment), 'signature-palsu')->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_callback_rejects_a_request_without_a_signature(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        // The signature header is absent entirely; only the payload is sent.
        $this->postJson(route('tripay.notification'), $this->payload($payment), [
            'X-Callback-Event' => 'payment_status',
        ])->assertForbidden();

        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_callback_rejects_an_unexpected_event(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        $this->postCallback($this->payload($payment), event: 'disbursement')->assertForbidden();

        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_valid_paid_callback_moves_the_order_to_the_verification_queue(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        $this->postCallback($this->payload($payment))->assertOk();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame(OrderStatus::WaitingVerification, $payment->order->fresh()->status);

        $this->assertSame(
            [OrderStatus::Paid, OrderStatus::WaitingVerification],
            $payment->order->statusHistory()->orderBy('id')->get()->map(fn ($entry) => $entry->to_status)->all(),
        );
    }

    public function test_paid_callback_records_the_received_amount_and_fee(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        $this->postCallback($this->payload($payment, [
            'amount_received' => $payment->amount,
            'total_fee' => 2500,
        ]))->assertOk();

        $payment->refresh();

        $this->assertSame($payment->amount, $payment->amount_received);
        $this->assertSame(2500, $payment->total_fee);
    }

    public function test_amount_mismatch_rejects_the_callback(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        $this->postCallback($this->payload($payment, [
            'total_amount' => $payment->amount + 1,
        ]))->assertStatus(422);

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_failed_callback_keeps_the_order_unpaid(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        $this->postCallback($this->payload($payment, ['status' => 'FAILED']))->assertOk();

        $this->assertSame(PaymentStatus::Failed, $payment->fresh()->status);
        $this->assertSame(OrderStatus::PendingPayment, $payment->order->fresh()->status);
    }

    public function test_expired_callback_marks_the_order_expired_and_still_payable(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(
            Order::factory()->for(User::factory()->customer())->create(),
            ['expires_at' => now()->subHour()],
        );

        $this->postCallback($this->payload($payment, ['status' => 'EXPIRED']))->assertOk();

        $this->assertSame(PaymentStatus::Expired, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Expired, $payment->order->fresh()->status);
        $this->assertTrue($payment->order->fresh()->isPayable());
        $this->assertTrue($payment->order->fresh()->status->canTransitionTo(OrderStatus::PendingPayment));
    }

    public function test_replayed_expired_callback_is_idempotent(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(
            Order::factory()->for(User::factory()->customer())->create(),
            ['expires_at' => now()->subHour()],
        );

        $payload = $this->payload($payment, ['status' => 'EXPIRED']);

        $this->postCallback($payload)->assertOk();
        $this->postCallback($payload)->assertOk();

        $this->assertSame(PaymentStatus::Expired, $payment->fresh()->status);
        $this->assertSame(OrderStatus::Expired, $payment->order->fresh()->status);
        $this->assertSame(1, $payment->order->statusHistory()->count());
    }

    public function test_callback_for_an_unknown_order_is_rejected(): void
    {
        $this->withTripayConfigured();

        $payload = [
            'reference' => 'PUB-JVX-00000000-0000',
            'merchant_ref' => 'JVX-00000000-0000',
            'merchant_code' => 'T00000',
            'status' => 'PAID',
            'total_amount' => 100_000,
        ];

        $this->postCallback($payload)->assertNotFound();
    }

    public function test_callback_for_an_unknown_transaction_is_rejected(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();
        $payment = $this->makePayment($order);

        $this->postCallback($this->payload($payment, ['reference' => 'PUB-LAIN']))->assertNotFound();

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_repeated_callback_does_not_duplicate_the_status_history(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());
        $payload = $this->payload($payment);

        foreach (range(1, 3) as $attempt) {
            $this->postCallback($payload)->assertOk();
        }

        $this->assertSame(OrderStatus::WaitingVerification, $payment->order->fresh()->status);
        $this->assertSame(2, $payment->order->statusHistory()->count());
    }

    public function test_client_reports_whether_it_is_configured(): void
    {
        $this->withTripayConfigured();
        $this->assertTrue(app(TripayClient::class)->isConfigured());

        config(['tripay.api_key' => null]);
        $this->assertFalse(app(TripayClient::class)->isConfigured());
    }

    public function test_callback_signature_is_verified_against_the_raw_request_body(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        // A body whose slashes and non-ASCII characters are escaped by the
        // default encoder. Re-encoding the decoded payload would produce a
        // different string, so this only passes when the raw body is signed.
        $body = json_encode([
            'reference' => $payment->tripay_reference,
            'merchant_ref' => $payment->order->order_number,
            'merchant_code' => 'T00000',
            'status' => 'PAID',
            'total_amount' => $payment->amount,
            'payment_method_code' => 'QRIS',
            'checkout_url' => 'https://checkout.tripay.id/public/ref/ä',
        ]);

        $this->call(
            'POST',
            route('tripay.notification'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_CALLBACK_SIGNATURE' => hash_hmac('sha256', $body, 'dev-private-key'),
                'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            ],
            $body,
        )->assertOk();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(OrderStatus::WaitingVerification, $payment->order->fresh()->status);
    }

    public function test_callback_does_not_require_a_session(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());
        $body = json_encode($this->payload($payment));

        // The gateway is a server-to-server caller: no session, no cookies, no
        // CSRF token. The endpoint is exempt from CSRF and authenticated by its
        // signature instead.
        $this->call(
            'POST',
            route('tripay.notification'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
                'HTTP_X_CALLBACK_SIGNATURE' => hash_hmac('sha256', $body, 'dev-private-key'),
                'HTTP_X_CALLBACK_EVENT' => 'payment_status',
            ],
            $body,
        )->assertOk();

        $this->assertSame(OrderStatus::WaitingVerification, $payment->order->fresh()->status);
    }

    public function test_replayed_paid_callback_does_not_overwrite_the_recorded_amount_and_fee(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();
        $payment = $this->makePayment($order);

        $this->postCallback($this->payload($payment, [
            'amount_received' => 98_000,
            'total_fee' => 2_500,
        ]))->assertOk();

        $this->assertSame(2_500, $payment->fresh()->total_fee);

        // The gateway resends the notification on a retry. A captured payment is
        // final, so the recorded figures must survive the replay untouched.
        $this->postCallback($this->payload($payment, [
            'amount_received' => 1,
            'total_fee' => 999_999,
        ]))->assertOk();

        $payment->refresh();

        $this->assertSame(98_000, $payment->amount_received);
        $this->assertSame(2_500, $payment->total_fee);
    }

    public function test_an_expired_transaction_can_still_settle_late(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();
        $payment = $this->makePayment($order);

        $this->postCallback($this->payload($payment, ['status' => 'EXPIRED']))->assertOk();

        $this->assertSame(OrderStatus::Expired, $order->fresh()->status);

        // The customer finished paying just as the transaction lapsed. That money
        // has to be accepted, so the replay guard must not block a settled
        // payment that was only ever expired.
        $this->postCallback($this->payload($payment))->assertOk();

        $this->assertSame(PaymentStatus::Paid, $payment->fresh()->status);
        $this->assertSame(OrderStatus::WaitingVerification, $order->fresh()->status);
    }

    public function test_callback_issued_for_another_merchant_is_rejected(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        $this->postCallback($this->payload($payment, ['merchant_code' => 'T99999']))
            ->assertForbidden();

        $this->assertSame(PaymentStatus::Pending, $payment->fresh()->status);
    }

    public function test_callback_is_rate_limited(): void
    {
        $this->withTripayConfigured();

        $payment = $this->makePayment(Order::factory()->for(User::factory()->customer())->create());

        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $this->postCallback($this->payload($payment))->assertOk();
        }

        $this->postCallback($this->payload($payment))->assertStatus(429);
    }

    public function test_payment_initiation_is_refused_while_another_request_holds_the_lock(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();

        // Stand in for a concurrent request that is already talking to the
        // gateway. Holding the lock must make this one back off rather than open
        // a second transaction for the same order.
        $lock = Cache::lock('tripay:initiate:order:'.$order->getKey(), 60);
        $this->assertTrue($lock->get());

        $this->actingAs($order->user)
            ->post(route('customer.orders.pay.store', $order), ['payment_method' => 'QRIS'])
            ->assertSessionHasErrors('payment_method');

        $this->assertCount(0, $order->payments()->get());

        $lock->release();

        // Once the other request is done the next attempt goes through.
        $this->actingAs($order->user)
            ->post(route('customer.orders.pay.store', $order), ['payment_method' => 'QRIS'])
            ->assertSessionHasErrors('payment');
    }

    public function test_the_pay_page_warns_when_the_channel_list_is_a_guess(): void
    {
        $this->withTripayConfigured();

        $order = Order::factory()->for(User::factory()->customer())->create();

        // An empty cache plus valid credentials means the gateway could not be
        // reached, so the list on offer is the configured guess rather than the
        // channels the merchant actually activated.
        Cache::forget(self::CHANNEL_CACHE_KEY);

        Http::fake([
            'tripay.co.id/api-sandbox/merchant/payment-channel' => Http::response([], 500),
        ]);

        $this->actingAs($order->user)
            ->get(route('customer.orders.pay', $order))
            ->assertOk()
            ->assertViewHas('channelsAreGuess', true);

        // Once the channels are cached from a real fetch, the list is genuine and
        // the warning goes away.
        Cache::put(self::CHANNEL_CACHE_KEY, ['QRIS' => 'QRIS']);

        $this->actingAs($order->user)
            ->get(route('customer.orders.pay', $order))
            ->assertOk()
            ->assertViewHas('channelsAreGuess', false);
    }
}
