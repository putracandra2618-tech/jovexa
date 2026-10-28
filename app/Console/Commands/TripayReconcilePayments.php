<?php

namespace App\Console\Commands;

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Services\TripayClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

/**
 * Asks the gateway about transactions whose callback never arrived.
 *
 * This is the only recovery path for an order that was paid but never confirmed,
 * so it runs on a schedule rather than on demand.
 */
#[Signature('tripay:reconcile-payments')]
#[Description('Mengecek status transaksi pending dan yang baru kedaluwarsa langsung ke gateway Tripay')]
class TripayReconcilePayments extends Command
{
    /**
     * How long to wait on the gateway before giving up on this run.
     */
    private const LOOKBACK_MINUTES = 60;

    /**
     * How long locally expired transactions stay eligible for reconciliation.
     *
     * The local sweep may expire a payment before the gateway reports that the
     * customer actually paid, so a lost callback on a late payment would never
     * be recovered if only pending rows were checked.
     */
    private const EXPIRED_LOOKBACK_DAYS = 7;

    /**
     * Execute the console command.
     */
    public function handle(TripayClient $tripay, PaymentService $payments): int
    {
        if (! $tripay->isConfigured()) {
            $this->warn('Kredensial Tripay belum lengkap, rekonsiliasi dilewati.');

            return self::SUCCESS;
        }

        $checked = 0;
        $settled = 0;
        $failed = 0;

        Payment::query()
            ->whereNotNull('tripay_reference')
            ->where(function (Builder $query): void {
                $query->where(function (Builder $pending): void {
                    $pending->whereStatus(PaymentStatus::Pending)
                        ->where('created_at', '<=', now()->subMinutes(self::LOOKBACK_MINUTES));
                })->orWhere(function (Builder $expired): void {
                    $expired->whereStatus(PaymentStatus::Expired)
                        ->where('expires_at', '>=', now()->subDays(self::EXPIRED_LOOKBACK_DAYS));
                });
            })
            ->with('order')
            ->chunkById(200, function ($candidates) use ($tripay, $payments, &$checked, &$settled, &$failed): void {
                foreach ($candidates as $payment) {
                    $checked++;

                    try {
                        $response = $tripay->transactionStatus($payment->tripay_reference);
                    } catch (\Throwable $exception) {
                        $failed++;
                        $this->reportFailure($payment, $exception);

                        continue;
                    }

                    $data = $response['data'] ?? [];
                    $status = strtoupper((string) ($data['status'] ?? ''));

                    // Anything still pending at the gateway is simply not settled
                    // yet, so there is nothing to apply. Tripay uses UNPAID for
                    // a transaction that has not been paid; PENDING is kept for
                    // older payloads.
                    if (in_array($status, ['', 'PENDING', 'UNPAID'], true)) {
                        continue;
                    }

                    try {
                        $payments->applyCallback($payment, $this->normalize($data));
                        $settled++;
                    } catch (\Throwable $exception) {
                        // One transaction the sweep cannot apply must not stop the
                        // rest of the batch.
                        $failed++;
                        $this->reportFailure($payment, $exception);
                    }
                }
            });

        $this->info("{$checked} transaksi diperiksa, {$settled} diperbarui, {$failed} gagal dijangkau.");

        return self::SUCCESS;
    }

    /**
     * Note why a single transaction could not be reconciled.
     *
     * A scheduled command swallows these failures to keep the batch going, so
     * without this the count in the summary would be the only trace.
     */
    private function reportFailure(Payment $payment, \Throwable $exception): void
    {
        Log::warning('Rekonsiliasi pembayaran gagal untuk satu transaksi.', [
            'payment_id' => $payment->getKey(),
            'reference' => $payment->tripay_reference,
            'exception' => $exception->getMessage(),
        ]);
    }

    /**
     * Reshape a transaction detail response into the notification payload shape.
     *
     * The detail endpoint reports the invoice as `amount` (and sometimes
     * `total_amount` as a list of amounts charged), while a notification reports
     * it as a single value. applyCallback compares that figure against the
     * invoice, so it has to be normalised first.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalize(array $data): array
    {
        $totalAmount = $data['total_amount'] ?? $data['amount'] ?? null;

        if (is_array($totalAmount)) {
            $totalAmount = $totalAmount === [] ? null : (int) reset($totalAmount);
        }

        $data['total_amount'] = $totalAmount;
        $data['amount_received'] ??= $totalAmount;

        return $data;
    }
}
