<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnclaimedQueueAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_alerts_admins_only_for_queues_older_than_two_days(): void
    {
        $admin = User::factory()->admin()->create();
        $superAdmin = User::factory()->superAdmin()->create();
        $worker = User::factory()->worker()->create();

        $stale = Order::factory()->queued()->create();
        $this->recordQueuedEntry($stale, 3);

        $fresh = Order::factory()->queued()->create();
        $this->recordQueuedEntry($fresh, 1);

        $claimed = Order::factory()->inProgress()->assignedTo($worker)->create();
        $this->recordQueuedEntry($claimed, 5);

        $this->artisan('orders:notify-unclaimed-queue')->assertSuccessful();

        foreach ([$admin, $superAdmin] as $recipient) {
            $this->assertDatabaseHas('user_notifications', [
                'user_id' => $recipient->getKey(),
                'order_id' => $stale->getKey(),
                'type' => UserNotification::UnclaimedQueue,
            ]);

            $this->assertSame(0, $recipient->inAppNotifications()->where('order_id', $fresh->getKey())->count());
            $this->assertSame(0, $recipient->inAppNotifications()->where('order_id', $claimed->getKey())->count());
        }

        $this->assertSame(0, $worker->inAppNotifications()->count());
    }

    public function test_command_does_not_duplicate_the_alert(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->queued()->create();
        $this->recordQueuedEntry($order, 4);

        $this->artisan('orders:notify-unclaimed-queue')->assertSuccessful();
        $this->artisan('orders:notify-unclaimed-queue')->assertSuccessful();

        $this->assertSame(1, $admin->inAppNotifications()
            ->where('order_id', $order->getKey())
            ->where('type', UserNotification::UnclaimedQueue)
            ->count());
    }

    public function test_command_uses_the_latest_queue_entry(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->queued()->create();

        $this->recordQueuedEntry($order, 5);
        $this->recordQueuedEntry($order, 1);

        $this->artisan('orders:notify-unclaimed-queue')->assertSuccessful();

        $this->assertSame(0, $admin->inAppNotifications()->where('order_id', $order->getKey())->count());
    }

    public function test_admin_dashboard_shows_the_alert_and_hides_it_after_claim(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->queued()->create();
        $this->recordQueuedEntry($order, 3);

        $this->artisan('orders:notify-unclaimed-queue')->assertSuccessful();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Ada antrean yang belum di ambil worker')
            ->assertSee('sudah lebih dari 2 hari menunggu worker')
            ->assertSee($order->order_number);

        $order->forceFill([
            'worker_id' => User::factory()->worker()->create()->getKey(),
            'order_status_id' => OrderStatusLookup::idFor(OrderStatus::InProgress->value),
        ])->save();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('sudah lebih dari 2 hari menunggu worker');
    }

    private function recordQueuedEntry(Order $order, int $daysAgo): void
    {
        OrderStatusHistory::factory()->for($order)->create([
            'to_order_status_id' => OrderStatusLookup::idFor(OrderStatus::Queued->value),
            'created_at' => now()->subDays($daysAgo),
            'updated_at' => now()->subDays($daysAgo),
        ]);
    }
}
