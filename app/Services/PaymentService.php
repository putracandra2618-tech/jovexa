<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Lookup\PaymentStatus as PaymentStatusLookup;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    /**
     * How long payment initiation is held exclusively, in seconds.
     *
     * Only a crash safety net: the gateway call times out well inside this, and
     * the lock is released in a finally block. It is comfortably longer than the
     * client timeout so a slow gateway cannot have its lock expire mid-request.
     */
    private const INITIATION_LOCK_SECONDS = 60;

    public function __construct(
        private readonly TripayClient $tripay,
        private readonly OrderWorkflow $workflow,
    ) {}

    /**
     * Send the customer to the payment gateway.
     *
     * When the customer still has an open transaction the existing checkout URL
     * is reused, so a double click or a refreshed page never produces a second
     * transaction for the same order.
     *
     * The gateway is called before the payment row is written, so a rejected
     * transaction never leaves a dangling pending payment behind.
     *
     * @throws ValidationException when the requested channel is unavailable, or
     *                             when another request is already starting one
     * @throws TripayException when the gateway rejects the request
     */
    public function initiate(Order $order, string $method = 'QRIS'): string
    {
        $open = $this->openPaymentFor($order);

        if ($open !== null) {
            return $open->checkout_url;
        }

        $lock = Cache::lock($this->initiationLockKey($order), self::INITIATION_LOCK_SECONDS);

        if (! $lock->get()) {
            // Another request is already talking to the gateway for this order.
            // It may finish before we look, so check once more before giving up.
            $open = $this->openPaymentFor($order);

            if ($open !== null) {
                return $open->checkout_url;
            }

            throw ValidationException::withMessages([
                'payment_method' => 'Pembayaran sedang diproses. Coba lagi sebentar.',
            ]);
        }

        try {
            // Re-checked inside the lock: the request that held it may have been
            // the one that created an open transaction.
            $open = $this->openPaymentFor($order);

            if ($open !== null) {
                return $open->checkout_url;
            }

            if (! $this->tripay->supportsChannel($method)) {
                throw ValidationException::withMessages([
                    'payment_method' => "Metode pembayaran \"{$method}\" tidak tersedia.",
                ]);
            }

            $payment = new Payment;
            $payment->order()->associate($order);
            $payment->fill([
                'transaction_id' => $this->tripay->merchantReferenceFor($order).'-'.Str::uuid()->toString(),
                'payment_method' => $method,
                'amount' => $order->total_price,
                'payment_status_id' => PaymentStatusLookup::idFor(PaymentStatus::Pending->value),
            ]);

            $transaction = $this->tripay->createTransaction($payment);

            $payment->fill([
                'tripay_reference' => $transaction['reference'],
                'checkout_url' => $transaction['checkout_url'] ?? null,
                'payment_channel' => $transaction['payment_method'] ?? $method,
                'expires_at' => isset($transaction['expired_time'])
                    ? now()->setTimestamp((int) $transaction['expired_time'])
                    : now()->addMinutes((int) config('tripay.expiry_minutes', 1440)),
            ])->save();

            return (string) $payment->checkout_url;
        } finally {
            $lock->release();
        }
    }

    /**
     * Apply a gateway callback to the order, moving it to verification once the
     * payment is confirmed as successful.
     *
     * The gateway retries failed notifications, so this is written to be safe to
     * run more than once for the same transaction.
     *
     * @param  array<string, mixed>  $payload
     *
     * @throws TripayException when the callback does not match the transaction
     */
    public function applyCallback(Payment $payment, array $payload): Order
    {
        $status = $this->resolveStatus((string) ($payload['status'] ?? ''));

        if ($status === PaymentStatus::Pending) {
            return $payment->order;
        }

        // A captured payment is final. The gateway retries notifications and a
        // replayed one would otherwise overwrite the recorded amount and fee.
        // Only a paid payment is short-circuited here: an expired transaction can
        // still legitimately settle late, and that money has to be accepted.
        if ($payment->isPaid()) {
            return $payment->order;
        }

        $amount = $payload['total_amount'] ?? null;

        if ($amount !== null && (int) $amount !== $payment->amount) {
            throw new TripayException('Nominal pembayaran tidak cocok dengan tagihan pesanan.');
        }

        return DB::transaction(function () use ($payment, $status, $payload): Order {
            $payment->update([
                'payment_status_id' => PaymentStatusLookup::idFor($status->value),
                'payment_channel' => $payload['payment_method_code'] ?? $payment->payment_channel,
                'amount_received' => isset($payload['amount_received']) ? (int) $payload['amount_received'] : $payment->amount_received,
                'total_fee' => isset($payload['total_fee']) ? (int) $payload['total_fee'] : $payment->total_fee,
                'paid_at' => $status === PaymentStatus::Paid
                    ? now()->setTimestamp((int) ($payload['paid_at'] ?? now()->timestamp))
                    : $payment->paid_at,
            ]);

            $payment->unsetRelation('statusRecord');

            $order = $payment->order;

            $this->advanceOrder($order, $status);

            return $order->fresh();
        });
    }

    /**
     * Move the order to the stage implied by the payment status.
     */
    private function advanceOrder(Order $order, PaymentStatus $status): void
    {
        if ($status === PaymentStatus::Paid) {
            if ($order->status->isPayable()) {
                $order = $this->workflow->transitionTo(
                    $order,
                    OrderStatus::Paid,
                    note: 'Pembayaran dikonfirmasi oleh gateway Tripay.',
                    confirmedByGateway: true,
                );

                $this->workflow->transitionTo(
                    $order,
                    OrderStatus::WaitingVerification,
                    note: 'Pesanan masuk antrean verifikasi Admin.',
                );
            }

            return;
        }

        // Only a still-pending order transitions here. Expired orders stay
        // payable for a late settlement, so replaying an EXPIRED callback must
        // not attempt Expired -> Expired and blow up.
        if ($status === PaymentStatus::Expired && $order->status === OrderStatus::PendingPayment) {
            $this->workflow->transitionTo(
                $order,
                OrderStatus::Expired,
                note: 'Transaksi pembayaran kedaluwarsa sebelum customer menyelesaikan pembayaran.',
                confirmedByGateway: true,
            );
        }
    }

    /**
     * The most recent transaction the customer can still pay.
     */
    private function openPaymentFor(Order $order): ?Payment
    {
        return $order->payments()
            ->whereStatus(PaymentStatus::Pending)
            ->whereNotNull('checkout_url')
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->latest('id')
            ->first();
    }

    /**
     * The lock key that serialises payment initiation for a single order.
     */
    private function initiationLockKey(Order $order): string
    {
        return 'tripay:initiate:order:'.$order->getKey();
    }

    /**
     * Map the gateway status string onto the local payment status.
     */
    private function resolveStatus(string $gatewayStatus): PaymentStatus
    {
        return match (strtoupper($gatewayStatus)) {
            'PAID', 'SETTLED', 'SUCCESS' => PaymentStatus::Paid,
            'EXPIRED' => PaymentStatus::Expired,
            'FAILED', 'DENIED', 'CANCELLED' => PaymentStatus::Failed,
            default => PaymentStatus::Pending,
        };
    }
}
