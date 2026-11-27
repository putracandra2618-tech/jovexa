<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderFile;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Service;
use App\Models\User;
use App\Models\UserNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SoftDeleteTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_a_customer_soft_deletes_every_related_record(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->published()->create();
        $order = Order::factory()->for($customer)->for($service)->create();
        $payment = Payment::factory()->for($order)->create();
        $history = OrderStatusHistory::factory()->for($order)->create();
        $file = OrderFile::factory()->for($order)->create();
        $notification = UserNotification::factory()->for($customer)->for($order)->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $customer))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('users', ['id' => $customer->getKey()]);
        $this->assertSoftDeleted('orders', ['id' => $order->getKey()]);
        $this->assertSoftDeleted('payments', ['id' => $payment->getKey()]);
        $this->assertSoftDeleted('order_status_histories', ['id' => $history->getKey()]);
        $this->assertSoftDeleted('order_files', ['id' => $file->getKey()]);
        $this->assertSoftDeleted('user_notifications', ['id' => $notification->getKey()]);
    }

    public function test_a_deleted_user_and_their_data_can_be_restored(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Sinta Utami']);
        $order = Order::factory()->for($customer)->create();
        $payment = Payment::factory()->for($order)->create();
        $file = OrderFile::factory()->for($order)->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $customer))
            ->assertSessionHas('success');

        $this->actingAs($superAdmin)
            ->get(route('admin.trash.users'))
            ->assertOk()
            ->assertSee('Sinta Utami');

        $this->actingAs($superAdmin)
            ->post(route('admin.trash.users.restore', $customer))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', ['id' => $customer->getKey(), 'deleted_at' => null]);
        $this->assertDatabaseHas('orders', ['id' => $order->getKey(), 'deleted_at' => null]);
        $this->assertDatabaseHas('payments', ['id' => $payment->getKey(), 'deleted_at' => null]);
        $this->assertDatabaseHas('order_files', ['id' => $file->getKey(), 'deleted_at' => null]);
    }

    public function test_force_deleting_a_user_removes_their_files_from_storage(): void
    {
        Storage::fake('local');

        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->create();
        $file = OrderFile::factory()->for($order)->result()->stored()->create();

        Storage::disk('local')->assertExists($file->file_path);

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $customer))
            ->assertSessionHas('success');

        $this->actingAs($superAdmin)
            ->delete(route('admin.trash.users.force-delete', $customer))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $customer->getKey()]);
        $this->assertDatabaseMissing('orders', ['id' => $order->getKey()]);
        $this->assertDatabaseMissing('order_files', ['id' => $file->getKey()]);
        Storage::disk('local')->assertMissing($file->file_path);
    }

    public function test_admin_can_soft_delete_a_service_and_restore_it_with_its_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->published()->create();
        $order = Order::factory()->for($service)->create();

        $this->actingAs($admin)
            ->delete(route('admin.services.destroy', $service))
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.services.index'));

        $this->assertSoftDeleted('services', ['id' => $service->getKey()]);
        $this->assertSoftDeleted('orders', ['id' => $order->getKey()]);

        $this->actingAs($admin)
            ->get(route('admin.trash.services'))
            ->assertOk()
            ->assertSee($service->name);

        $this->actingAs($admin)
            ->post(route('admin.trash.services.restore', $service))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('services', ['id' => $service->getKey(), 'deleted_at' => null]);
        $this->assertDatabaseHas('orders', ['id' => $order->getKey(), 'deleted_at' => null]);
    }

    public function test_order_trash_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('admin.trash.orders'));
        $this->assertFalse(Route::has('admin.trash.orders.restore'));
        $this->assertFalse(Route::has('admin.trash.orders.force-delete'));
    }

    public function test_regular_admin_cannot_open_the_user_trash(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.trash.users'))
            ->assertForbidden();
    }
}
