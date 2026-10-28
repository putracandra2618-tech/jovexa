<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class TripayClient
{
    /**
     * The cache key holding the payment channels the merchant has activated.
     */
    public const CHANNEL_CACHE_KEY = 'tripay.payment_channels';

    /**
     * Determine whether the gateway credentials are present.
     */
    public function isConfigured(): bool
    {
        return $this->apiKey() !== null
            && $this->privateKey() !== null
            && $this->merchantCode() !== null;
    }

    /**
     * Build the merchant reference sent to the gateway.
     *
     * The gateway echoes this value back on every callback, so it has to be the
     * order number rather than a locally generated identifier.
     */
    public function merchantReferenceFor(Order $order): string
    {
        return $order->order_number;
    }

    /**
     * Determine whether the channel list on offer came from the gateway.
     *
     * When it did not, the codes shown are only a guess. The page has to say so,
     * otherwise a channel that looks available is only rejected after the
     * customer has already committed to it.
     *
     * The cache key is only populated by a successful fetch, so an empty cache
     * together with valid credentials means the gateway could not be reached.
     */
    public function channelsAreFallback(): bool
    {
        return ! $this->isConfigured() || Cache::get(self::CHANNEL_CACHE_KEY) === null;
    }

    /**
     * The payment channels the merchant has activated.
     *
     * The list is cached so the checkout page does not call the gateway on every
     * request. When the gateway is unreachable the configured fallback is used
     * so the page still renders.
     *
     * @return array<string, string> channel code => human readable label
     */
    public function availableChannels(): array
    {
        if (! $this->isConfigured()) {
            return $this->fallbackChannels();
        }

        $cached = Cache::get(self::CHANNEL_CACHE_KEY);

        if (is_array($cached) && $cached !== []) {
            return $cached;
        }

        $channels = $this->fetchChannels();

        if ($channels === []) {
            return $this->fallbackChannels();
        }

        Cache::put(self::CHANNEL_CACHE_KEY, $channels, (int) config('tripay.channel_cache_seconds', 21600));

        return $channels;
    }

    /**
     * Determine whether the given channel code may be used for checkout.
     */
    public function supportsChannel(?string $code): bool
    {
        return $code !== null && $code !== '' && array_key_exists($code, $this->availableChannels());
    }

    /**
     * Create a transaction and return the gateway response data.
     *
     * The caller persists the returned reference, because that value is what
     * every later callback and status lookup is keyed by.
     *
     * @return array<string, mixed>
     *
     * @throws TripayException when the gateway rejects the request
     */
    public function createTransaction(Payment $payment): array
    {
        $order = $payment->order;

        try {
            $response = $this->request()->post('/transaction/create', $this->payload($payment));
        } catch (\Throwable $exception) {
            throw new TripayException(
                'Gateway pembayaran sedang tidak terjangkau. Coba beberapa saat lagi.',
                previous: $exception,
            );
        }

        $body = $response->json();

        if ($response->failed() || ! ($body['success'] ?? false) || ! isset($body['data']['reference'])) {
            throw new TripayException(
                $body['message'] ?? 'Permintaan pembayaran ditolak oleh gateway.',
                previous: $response->toException(),
            );
        }

        return $body['data'];
    }

    /**
     * Query a single transaction to confirm its status.
     *
     * The detail endpoint is used instead of check-status because only the
     * former returns the transaction data (status, amounts, paid_at) needed to
     * rebuild a callback payload. check-status only reports a message.
     *
     * @return array<string, mixed>
     *
     * @throws TripayException when the gateway is unreachable
     */
    public function transactionStatus(string $reference): array
    {
        try {
            $response = $this->request()->get('/transaction/detail', ['reference' => $reference]);
        } catch (\Throwable $exception) {
            throw new TripayException(
                'Gateway pembayaran sedang tidak terjangkau.',
                previous: $exception,
            );
        }

        $body = $response->json();

        if ($response->failed() || ! ($body['success'] ?? false)) {
            throw new TripayException(
                $body['message'] ?? 'Gagal mengambil status transaksi dari gateway.',
                previous: $response->toException(),
            );
        }

        return $body;
    }

    /**
     * The transaction reference the gateway assigned to a payment.
     *
     * A payment that never reached the gateway has no reference, so the fallback
     * is the merchant reference we would have sent.
     */
    public function referenceOf(Payment $payment): string
    {
        return $payment->tripay_reference ?? $this->merchantReferenceFor($payment->order);
    }

    /**
     * Verify the callback signature the gateway sends with every notification.
     *
     * The signature is an HMAC-SHA256 of the raw notification body keyed by the
     * merchant private key. It is deliberately computed from the untouched raw
     * body rather than a re-encoded array, because re-encoding may reorder keys
     * or change escaping and would then never match the gateway signature.
     */
    public function verifyCallbackSignature(string $rawBody, ?string $signature): bool
    {
        if ($signature === null || $signature === '') {
            return false;
        }

        $privateKey = $this->privateKey();

        if ($privateKey === null) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawBody, $privateKey);

        return hash_equals($expected, $signature);
    }

    /**
     * The callback event carried by the notification.
     */
    public function callbackEvent(?string $event): ?string
    {
        return $event === null || $event === '' ? null : $event;
    }

    /**
     * Determine whether the notification was issued for this merchant.
     *
     * The callback signature already covers this, but checking it explicitly
     * keeps a valid notification meant for a different merchant from being
     * applied even if the credentials were ever mixed up.
     */
    public function matchesMerchantCode(?string $code): bool
    {
        $expected = $this->merchantCode();

        return $expected !== null && is_string($code) && hash_equals($expected, $code);
    }

    /**
     * Build the request signature the gateway validates on closed payments.
     *
     * The signature covers the merchant code, the merchant reference and the
     * amount, concatenated without separators.
     */
    public function signFor(string $merchantReference, int $amount): string
    {
        $privateKey = (string) $this->privateKey();

        return hash_hmac('sha256', $this->merchantCode().$merchantReference.$amount, $privateKey);
    }

    /**
     * Pull the activated payment channels from the gateway.
     *
     * @return array<string, string>
     */
    private function fetchChannels(): array
    {
        try {
            $response = $this->request()->get('/merchant/payment-channel');
        } catch (\Throwable) {
            return [];
        }

        if ($response->failed()) {
            return [];
        }

        $channels = [];

        foreach ((array) ($response->json('data') ?? []) as $channel) {
            if (! is_array($channel) || ($channel['active'] ?? false) !== true) {
                continue;
            }

            $code = $channel['code'] ?? null;

            if (is_string($code) && $code !== '') {
                $channels[$code] = (string) ($channel['name'] ?? $code);
            }
        }

        return $channels;
    }

    /**
     * The channel list used when the gateway cannot be reached.
     *
     * @return array<string, string>
     */
    private function fallbackChannels(): array
    {
        return (array) config('tripay.fallback_channels', []);
    }

    /**
     * Build the transaction payload expected by the gateway.
     *
     * @return array<string, mixed>
     */
    private function payload(Payment $payment): array
    {
        $order = $payment->order;
        $merchantReference = $this->merchantReferenceFor($order);

        $payload = [
            'method' => $payment->payment_method,
            'merchant_ref' => $merchantReference,
            'amount' => $payment->amount,
            'customer_name' => $order->user->name,
            'customer_email' => $order->user->email,
            'order_items' => [[
                'name' => $order->service->name,
                'price' => $payment->amount,
                'quantity' => 1,
            ]],
            'return_url' => route('customer.orders.show', $order),
            'callback_url' => route('tripay.notification'),
            'expired_time' => now()->addMinutes($this->expiryMinutes())->timestamp,
            'signature' => $this->signFor($merchantReference, $payment->amount),
        ];

        if (filled($order->user->phone)) {
            $payload['customer_phone'] = $order->user->phone;
        }

        return $payload;
    }

    /**
     * The preconfigured HTTP client for the gateway.
     */
    private function request(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->withToken((string) $this->apiKey())
            ->acceptJson()
            ->asForm()
            ->timeout(30);
    }

    private function expiryMinutes(): int
    {
        return (int) config('tripay.expiry_minutes', 1440);
    }

    private function baseUrl(): string
    {
        return (string) config('tripay.base_url');
    }

    private function apiKey(): ?string
    {
        return $this->config('api_key');
    }

    private function privateKey(): ?string
    {
        return $this->config('private_key');
    }

    private function merchantCode(): ?string
    {
        return $this->config('merchant_code');
    }

    private function config(string $key): ?string
    {
        $value = config("tripay.{$key}");

        return $value === null || $value === '' ? null : (string) $value;
    }
}
