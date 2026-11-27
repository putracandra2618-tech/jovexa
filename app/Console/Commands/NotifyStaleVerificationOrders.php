<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\UserNotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

#[Signature('orders:notify-stale-verification')]
#[Description('Notify customers whose orders have been waiting for verification for over 30 minutes.')]
class NotifyStaleVerificationOrders extends Command
{
    private const ReminderDelayMinutes = 30;

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
        $threshold = now()->subMinutes(self::ReminderDelayMinutes);

        $notified = 0;

        Order::query()
            ->with('user')
            ->whereStatus(OrderStatus::WaitingVerification)
            ->whereHas('statusHistory', function ($query) use ($threshold): void {
                $query->where('created_at', '<=', $threshold)
                    ->whereHas('toStatusRecord', fn ($query) => $query->where('code', OrderStatus::WaitingVerification->value));
            })
            ->whereDoesntHave('statusHistory', function ($query) use ($threshold): void {
                $query->where('created_at', '>', $threshold)
                    ->whereHas('toStatusRecord', fn ($query) => $query->where('code', OrderStatus::WaitingVerification->value));
            })
            ->chunkById(100, function (Collection $orders) use (&$notified): void {
                foreach ($orders as $order) {
                    $this->notifications->verificationReminder($order);
                    $notified++;
                }
            });

        $this->info("Verification reminders sent: {$notified}.");

        return self::SUCCESS;
    }
}
