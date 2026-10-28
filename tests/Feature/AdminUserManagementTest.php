<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_open_user_management_list(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Diana Putri']);

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('Kelola Pengguna')
            ->assertSee('Diana Putri')
            ->assertSee('Customer');
    }

    public function test_regular_admin_cannot_manage_users(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_user_list_can_be_searched_by_name_or_email(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $target = User::factory()->customer()->create([
            'name' => 'Diana Putri',
            'email' => 'diana@mail.test',
        ]);
        $other = User::factory()->customer()->create(['name' => 'Rina Wijaya']);

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index', ['q' => 'diana@mail.test']))
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name)
            ->assertViewHas('search', 'diana@mail.test');
    }

    public function test_user_list_search_reports_when_nothing_matches(): void
    {
        User::factory()->customer()->create(['name' => 'Diana Putri']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.users.index', ['q' => 'tidak-ada-hasilnya']))
            ->assertOk()
            ->assertSee('Pengguna tidak ditemukan')
            ->assertDontSee('Belum ada pengguna');
    }

    public function test_user_list_search_narrows_within_the_active_role_filter(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Diana Putri']);
        $otherAdmin = User::factory()->admin()->create(['name' => 'Dian Others']);

        $this->actingAs($superAdmin)
            ->get(route('admin.users.index', ['q' => 'Dian', 'role' => UserRole::Customer->value]))
            ->assertOk()
            ->assertSee($customer->name)
            ->assertDontSee($otherAdmin->name);
    }

    public function test_user_list_topbar_search_targets_the_user_list(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('action="'.route('admin.users.index').'" data-admin-search', escape: false)
            ->assertSee('placeholder="Cari nama atau email pengguna"', escape: false);
    }

    public function test_super_admin_can_promote_customer_to_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.role', $customer), ['role' => UserRole::Admin->value])
            ->assertSessionHas('success')
            ->assertRedirect();

        $this->assertTrue($customer->fresh()->isAdmin());
    }

    public function test_super_admin_can_promote_customer_to_worker(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.role', $customer), ['role' => UserRole::Worker->value])
            ->assertSessionHas('success');

        $this->assertTrue($customer->fresh()->isWorker());
    }

    public function test_super_admin_role_cannot_be_assigned_from_the_user_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.role', $customer), ['role' => UserRole::SuperAdmin->value])
            ->assertSessionHasErrors('role');

        $this->assertTrue($customer->fresh()->isCustomer());
    }

    public function test_super_admin_cannot_change_own_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.role', $superAdmin), ['role' => UserRole::Customer->value])
            ->assertSessionHas('error')
            ->assertRedirect();

        $this->assertTrue($superAdmin->fresh()->isSuperAdmin());
    }

    public function test_super_admin_cannot_demote_another_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.role', $otherSuperAdmin), ['role' => UserRole::Customer->value])
            ->assertSessionHas('error');

        $this->assertTrue($otherSuperAdmin->fresh()->isSuperAdmin());
    }

    public function test_worker_with_active_orders_cannot_be_demoted(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $worker = User::factory()->worker()->online()->create();
        Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($superAdmin)
            ->patch(route('admin.users.role', $worker), ['role' => UserRole::Customer->value])
            ->assertSessionHas('error');

        $this->assertTrue($worker->fresh()->isWorker());
    }

    public function test_super_admin_can_delete_customer(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $customer))
            ->assertSessionHas('success')
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
    }

    public function test_super_admin_can_delete_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $admin->id]);
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $superAdmin))
            ->assertSessionHas('error')
            ->assertRedirect();

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_super_admin_cannot_delete_another_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $otherSuperAdmin))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $otherSuperAdmin->id]);
    }

    public function test_worker_with_active_orders_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $worker = User::factory()->worker()->online()->create();
        Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $worker))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', ['id' => $worker->id]);
    }

    public function test_super_admin_can_delete_user_with_orders_and_related_data(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->create();
        $order = $customer->orders()->create([
            'service_id' => $service->id,
            'order_number' => 'JX-'.now()->format('ymd').'-0001',
            'title' => 'Test',
            'description' => 'Desc',
            'requirements' => 'Req',
            'price' => 100000,
            'total_price' => 100000,
            'order_status_id' => OrderStatusLookup::idFor(OrderStatus::PendingPayment->value),
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $customer))
            ->assertSessionHas('success')
            ->assertRedirect();

        $this->assertDatabaseMissing('users', ['id' => $customer->id]);
        $this->assertDatabaseMissing('orders', ['id' => $order->id]);
    }

    public function test_deleting_a_worker_keeps_the_files_they_uploaded(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->for(User::factory()->customer())->create();
        $file = OrderFile::factory()->for($order)->result()->create([
            'uploaded_by' => $worker->getKey(),
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('admin.users.destroy', $worker))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('order_files', [
            'id' => $file->getKey(),
            'uploaded_by' => null,
        ]);
    }

    public function test_customers_cannot_manage_users(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_guests_cannot_manage_users(): void
    {
        $this->get(route('admin.users.index'))
            ->assertRedirect(route('login'));
    }
}
