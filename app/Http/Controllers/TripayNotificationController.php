<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\TripayClient;
use App\Services\TripayException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TripayNotificationController extends Controller
{
    /**
     * The only callback event the gateway currently sends.
     */
    private const PAYMENT_STATUS_EVENT = 'payment_status';

    public function __construct(
        private readonly TripayClient $tripay,
        private readonly PaymentService $payments,
    ) {}

    /**
     * Receive the payment status notification from the gateway.
     *
     * The order status is only ever changed from this endpoint, never from the
     * customer's browser, and only when the callback signature is valid.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $rawBody = $request->getContent();

        if (! $this->tripay->verifyCallbackSignature($rawBody, $request->header('X-Callback-Signature'))) {
            return $this->reject('Signature tidak valid.');
        }

        if ($this->tripay->callbackEvent($request->header('X-Callback-Event')) !== self::PAYMENT_STATUS_EVENT) {
            return $this->reject('Event callback tidak dikenal.');
        }

        $payload = $request->json()->all();

        if (! $this->tripay->matchesMerchantCode($payload['merchant_code'] ?? null)) {
            return $this->reject('Kode merchant tidak cocok.');
        }

        $payment = $this->locatePayment($payload);

        if ($payment === null) {
            return $this->reject('Transaksi tidak ditemukan.', 404);
        }

        try {
            $this->payments->applyCallback($payment, $payload);
        } catch (TripayException|ValidationException $exception) {
            return $this->reject($exception->getMessage(), 422);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Find the payment the callback refers to.
     *
     * The gateway keys the notification by the reference it assigned during
     * transaction creation, and echoes the merchant reference we sent as
     * merchant_ref. Both are checked so a notification can never be applied to
     * an order it was not raised for.
     *
     * @param  array<string, mixed>  $payload
     */
    private function locatePayment(array $payload): ?Payment
    {
        $order = Order::query()
            ->where('order_number', $payload['merchant_ref'] ?? null)
            ->first();

        if ($order === null) {
            return null;
        }

        $reference = $payload['reference'] ?? null;

        if (! is_string($reference) || $reference === '') {
            return null;
        }

        return $order->payments()
            ->where(fn ($query) => $query
                ->where('tripay_reference', $reference)
                ->orWhere('transaction_id', $reference))
            ->first();
    }

    /**
     * Build a negative acknowledgement the gateway understands.
     */
    private function reject(string $message, int $status = 403): JsonResponse
    {
        return response()->json(['success' => false, 'message' => $message], $status);
    }
}
