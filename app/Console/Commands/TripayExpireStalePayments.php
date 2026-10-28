<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Lookup\PaymentStatus as PaymentStatusLookup;
use App\Models\Payment;
use App\Services\OrderWorkflow;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Expires transactions the customer never settled.
 *
 * The gateway normally pushes an EXPIRED notification, but a callback can be lost
 * outright. Without this sweep an unpaid order would stay payable forever and the
 * customer would keep being offered a checkout that no longer exists.
 */
#[Signature('tripay:expire-stale-payments')]
#[Description('Mengubah pesanan yang pembayarannya kedaluwarsa menjadi status Expired')]
class TripayExpireStalePayments extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OrderWorkflow $workflow): int
    {
        $expired = 0;

        Payment::query()
            ->whereStatus(PaymentStatus::Pending)
            ->whereNotNull('checkout_url')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->with('order')
            ->chunkById(200, function ($payments) use ($workflow, &$expired): void {
                foreach ($payments as $payment) {
                    try {
                        if ($this->expire($workflow, $payment)) {
                            $expired++;
                        }
                    } catch (\Throwable $exception) {
                        // One transaction the sweep cannot expire must not stop the
                        // rest of the batch from being cleaned up.
                        Log::warning('Gagal menandai pembayaran kedaluwarsa.', [
                            'payment_id' => $payment->getKey(),
                            'order_id' => $payment->order_id,
                            'exception' => $exception->getMessage(),
                        ]);
                    }
                }
            });

        $this->info("{$expired} pesanan ditandai kedaluwarsa.");

        return self::SUCCESS;
    }

    /**
     * Expire one payment and, when it is still the reason the order is payable,
     * the order itself.
     *
     * An order can have more than one pending payment, so a later payment must
     * only be expired locally once the order has already moved on. Returning
     * false in that case keeps the summary counting orders, not payment rows.
     */
    private function expire(OrderWorkflow $workflow, Payment $payment): bool
    {
        return DB::transaction(function () use ($workflow, $payment): bool {
            $order = $payment->order()->lockForUpdate()->first();

            if ($order === null || ! $order->status->isPayable()) {
                return false;
            }

            $transitioned = false;

            if ($order->status === OrderStatus::PendingPayment) {
                $workflow->transitionTo(
                    $order,
                    OrderStatus::Expired,
                    note: 'Transaksi pembayaran kedaluwarsa tanpa konfirmasi dari gateway.',
                    confirmedByGateway: true,
                );

                $transitioned = true;
            }

            $payment->update([
                'payment_status_id' => PaymentStatusLookup::idFor(PaymentStatus::Expired->value),
            ]);
            $payment->unsetRelation('statusRecord');

            return $transitioned;
        });
    }
}
