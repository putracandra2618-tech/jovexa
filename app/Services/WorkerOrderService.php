<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkerOrderService
{
    public const MAX_ACTIVE_ORDERS = 5;

    public function __construct(private readonly OrderWorkflow $workflow) {}

    /**
     * Claim a queued order for a worker.
     *
     * The worker row is locked so two simultaneous claims cannot both pass the
     * quota check, while the order row is locked so the same order cannot be
     * claimed twice.
     *
     * @throws ValidationException when the worker is offline, full, or the order
     *                             is no longer available
     */
    public function claim(Order $order, User $worker): Order
    {
        return DB::transaction(function () use ($order, $worker): Order {
            $lockedWorker = User::query()->lockForUpdate()->findOrFail($worker->getKey());
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->getKey());

            if (! $lockedWorker->isWorker() || ! $lockedWorker->is_online) {
                throw ValidationException::withMessages([
                    'order' => 'Aktifkan status online sebelum mengambil pesanan.',
                ]);
            }

            if ($lockedOrder->status !== OrderStatus::Queued || $lockedOrder->worker_id !== null) {
                throw ValidationException::withMessages([
                    'order' => 'Pesanan ini sudah tidak tersedia di antrean.',
                ]);
            }

            if ($this->activeOrderCount($lockedWorker) >= self::MAX_ACTIVE_ORDERS) {
                throw ValidationException::withMessages([
                    'order' => 'Kuota pesanan aktif sudah penuh (maksimal '.self::MAX_ACTIVE_ORDERS.').',
                ]);
            }

            $lockedOrder->forceFill([
                'worker_id' => $lockedWorker->getKey(),
                'worker_claimed_at' => now(),
            ])->save();

            $this->workflow->transitionTo(
                $lockedOrder,
                OrderStatus::InProgress,
                $lockedWorker,
                'Pesanan diklaim oleh worker.',
            );

            return $lockedOrder->fresh();
        });
    }

    /**
     * Count the worker's unfinished assigned orders.
     */
    public function activeOrderCount(User $worker): int
    {
        return Order::query()->activeForWorker($worker)->count();
    }

    /**
     * Determine whether the worker still has room for another order.
     */
    public function hasAvailableCapacity(User $worker): bool
    {
        return $this->activeOrderCount($worker) < self::MAX_ACTIVE_ORDERS;
    }
}
