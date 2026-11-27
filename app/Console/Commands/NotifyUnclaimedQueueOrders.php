<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\UserNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('orders:notify-unclaimed-queue')]
#[Description('Alert admins when an order has been waiting in the worker queue for over two days.')]
class NotifyUnclaimedQueueOrders extends Command
{
    private const UnclaimedDelayDays = 2;

    public function __construct(
        private readonly UserNotificationService $notifications,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = now()->subDays(self::UnclaimedDelayDays);

        $notified = 0;

        Order::query()
            ->availableForWorkers()
            ->whereHas('statusHistory', function ($query) use ($threshold): void {
                $query->where('created_at', '<=', $threshold)
                    ->whereHas('toStatusRecord', fn ($query) => $query->where('code', OrderStatus::Queued->value));
            })
            ->whereDoesntHave('statusHistory', function ($query) use ($threshold): void {
                $query->where('created_at', '>', $threshold)
                    ->whereHas('toStatusRecord', fn ($query) => $query->where('code', OrderStatus::Queued->value));
            })
            ->chunkById(100, function (Collection $orders) use (&$notified): void {
                foreach ($orders as $order) {
                    $this->notifications->unclaimedQueueReminder($order);
                    $notified++;
                }
            });

        $this->info("Unclaimed queue alerts sent: {$notified}.");

        return self::SUCCESS;
    }
}
