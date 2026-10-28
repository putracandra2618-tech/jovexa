<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderWorkflow
{
    /**
     * Move an order to the given status and record the change in the audit trail.
     *
     * Reserved statuses are enforced here rather than in a controller, so the rule
     * holds no matter who calls the workflow. Payment statuses may only be reached
     * through the gateway, which passes $confirmedByGateway; delivery additionally
     * requires the order to already carry a result file.
     *
     * @param  bool  $confirmedByGateway  set only by the payment gateway callback
     *
     * @throws InvalidArgumentException when the transition is not allowed
     */
    public function transitionTo(
        Order $order,
        OrderStatus $target,
        ?User $actor = null,
        ?string $note = null,
        bool $confirmedByGateway = false,
    ): Order {
        if (! $order->status->canTransitionTo($target)) {
            throw new InvalidArgumentException(
                "Status pesanan tidak dapat berubah dari {$order->status->label()} ke {$target->label()}."
            );
        }

        $this->guardReservedStatus($order, $target, $confirmedByGateway);

        return DB::transaction(function () use ($order, $target, $actor, $note): Order {
            $previous = $order->status;

            $order->update([
                'order_status_id' => OrderStatusLookup::idFor($target->value),
            ]);

            $order->unsetRelation('statusRecord');

            $order->statusHistory()->create([
                'from_order_status_id' => $previous === null
                    ? null
                    : OrderStatusLookup::idFor($previous->value),
                'to_order_status_id' => OrderStatusLookup::idFor($target->value),
                'changed_by' => $actor?->getKey(),
                'note' => $note,
            ]);

            return $order;
        });
    }

    /**
     * Record the very first status of a freshly created order.
     */
    public function recordInitialStatus(Order $order, ?User $actor = null): OrderStatusHistory
    {
        return $order->statusHistory()->create([
            'from_order_status_id' => null,
            'to_order_status_id' => OrderStatusLookup::idFor($order->status->value),
            'changed_by' => $actor?->getKey(),
            'note' => 'Pesanan dibuat.',
        ]);
    }

    /**
     * Reject statuses that only the gateway or a dedicated action may set.
     *
     * @throws InvalidArgumentException when the target is reserved
     */
    private function guardReservedStatus(Order $order, OrderStatus $target, bool $confirmedByGateway): void
    {
        if (! $confirmedByGateway && in_array($target, OrderStatus::GATEWAY_ONLY, strict: true)) {
            throw new InvalidArgumentException(
                "Status {$target->label()} hanya bisa diubah oleh webhook gateway pembayaran."
            );
        }

        if ($target === OrderStatus::Delivered && ! $order->results()->exists()) {
            throw new InvalidArgumentException('Pesanan harus punya file hasil sebelum dikirim ke customer.');
        }
    }
}
