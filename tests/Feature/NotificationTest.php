<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_the_notification_page(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_user_only_sees_their_own_notifications(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        UserNotification::factory()->for($customer)->create(['title' => 'Revisi pesanan saya']);
        UserNotification::factory()->for($other)->create(['title' => 'Notifikasi orang lain']);

        $this->actingAs($customer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Revisi pesanan saya')
            ->assertDontSee('Notifikasi orang lain');
    }

    public function test_user_can_mark_a_notification_as_read(): void
    {
        $customer = User::factory()->customer()->create();
        $notification = UserNotification::factory()->for($customer)->create();

        $this->actingAs($customer)
            ->post(route('notifications.read', $notification))
            ->assertRedirect();

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $customer = User::factory()->customer()->create();
        UserNotification::factory()->count(2)->for($customer)->create();

        $this->actingAs($customer)
            ->postJson(route('notifications.read-all'))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertSame(0, $customer->inAppNotifications()->unread()->count());
    }

    public function test_user_cannot_mark_another_users_notification_as_read(): void
    {
        $notification = UserNotification::factory()->for(User::factory()->customer())->create();

        $this->actingAs(User::factory()->customer()->create())
            ->post(route('notifications.read', $notification))
            ->assertForbidden();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_delivering_results_notifies_the_customer(): void
    {
        Storage::fake('local');

        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->for($customer)->completed()->assignedTo($worker)->create();
        OrderFile::factory()->for($order)->result()->stored()->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.send', $order))
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $customer->getKey(),
            'order_id' => $order->getKey(),
            'type' => UserNotification::ResultDelivered,
        ]);
    }
}
