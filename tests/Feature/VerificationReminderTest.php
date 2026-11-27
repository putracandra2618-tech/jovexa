<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Models\UserNotification;
use App\Services\UserNotificationService;
use App\Support\WhatsappContact;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerificationReminderTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_reminds_only_orders_waiting_over_thirty_minutes(): void
    {
        $customer = User::factory()->customer()->create();

        $stale = Order::factory()->for($customer)->waitingVerification()->create();
        $this->recordVerificationEntry($stale, 31);

        $fresh = Order::factory()->for($customer)->waitingVerification()->create();
        $this->recordVerificationEntry($fresh, 10);

        $this->artisan('orders:notify-stale-verification')->assertSuccessful();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $customer->getKey(),
            'order_id' => $stale->getKey(),
            'type' => UserNotification::VerificationReminder,
        ]);

        $this->assertSame(0, $customer->inAppNotifications()->where('order_id', $fresh->getKey())->count());
    }

    public function test_command_uses_the_latest_verification_entry(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->waitingVerification()->create();

        $this->recordVerificationEntry($order, 40);
        $this->recordVerificationEntry($order, 5);

        $this->artisan('orders:notify-stale-verification')->assertSuccessful();

        $this->assertSame(0, $customer->inAppNotifications()->where('order_id', $order->getKey())->count());
    }

    public function test_command_does_not_remind_after_verification(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->queued()->create();

        $this->recordVerificationEntry($order, 40);

        $this->artisan('orders:notify-stale-verification')->assertSuccessful();

        $this->assertSame(0, $customer->inAppNotifications()->where('order_id', $order->getKey())->count());
    }

    public function test_command_does_not_duplicate_a_reminder(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->waitingVerification()->create();

        $this->recordVerificationEntry($order, 31);

        $this->artisan('orders:notify-stale-verification')->assertSuccessful();
        $this->artisan('orders:notify-stale-verification')->assertSuccessful();

        $this->assertSame(1, $customer->inAppNotifications()
            ->where('order_id', $order->getKey())
            ->where('type', UserNotification::VerificationReminder)
            ->count());
    }

    public function test_dashboard_shows_the_reminder_with_a_prefilled_whatsapp_link(): void
    {
        config()->set('jovexa.contact.whatsapp', '6281234567890');

        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->waitingVerification()->create();

        app(UserNotificationService::class)->verificationReminder($order);

        $link = WhatsappContact::link(
            config('jovexa.contact.whatsapp'),
            'hallo min, pesanan saya belum di verifikasi '.$order->order_number,
        );

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('sudah lebih dari 30 menit menunggu verifikasi')
            ->assertSee($order->order_number)
            ->assertSee('Hubungi CS via WhatsApp')
            ->assertSee($link);
    }

    public function test_dashboard_hides_the_reminder_after_verification(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->waitingVerification()->create();

        app(UserNotificationService::class)->verificationReminder($order);

        $order->update([
            'order_status_id' => OrderStatusLookup::ensureIdFor(
                OrderStatus::Queued->value,
                OrderStatus::Queued->label(),
            ),
        ]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertDontSee('sudah lebih dari 30 menit menunggu verifikasi');
    }

    private function recordVerificationEntry(Order $order, int $minutesAgo): void
    {
        OrderStatusHistory::factory()->for($order)->create([
            'created_at' => now()->subMinutes($minutesAgo),
            'updated_at' => now()->subMinutes($minutesAgo),
        ]);
    }
}
