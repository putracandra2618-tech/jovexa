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
use Illuminate\Support\Facades\Hash;
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

    public function test_super_admin_can_open_create_user_page(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.users.create'))
            ->assertOk()
            ->assertSee('Tambah Pengguna');
    }

    public function test_regular_admin_cannot_create_users(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.create'))
            ->assertForbidden();
    }

    public function test_super_admin_can_create_user(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.store'), [
                'name' => 'Diana Putri',
                'email' => 'diana@mail.test',
                'phone' => '081234567890',
                'role' => UserRole::Worker->value,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.users.index'));

        $user = User::query()->where('email', 'diana@mail.test')->firstOrFail();

        $this->assertSame('Diana Putri', $user->name);
        $this->assertSame('081234567890', $user->phone);
        $this->assertTrue($user->isWorker());
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_create_user_rejects_duplicate_email(): void
    {
        $existing = User::factory()->customer()->create(['email' => 'diana@mail.test']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.users.store'), [
                'name' => 'Diana Putri',
                'email' => $existing->email,
                'role' => UserRole::Customer->value,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertSessionHasErrors('email');
    }

    public function test_create_user_rejects_super_admin_role(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.users.store'), [
                'name' => 'Diana Putri',
                'email' => 'diana@mail.test',
                'role' => UserRole::SuperAdmin->value,
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_create_user_requires_matching_password_confirmation(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.users.store'), [
                'name' => 'Diana Putri',
                'email' => 'diana@mail.test',
                'role' => UserRole::Customer->value,
                'password' => 'rahasia123',
                'password_confirmation' => 'beda12345',
            ])
            ->assertSessionHasErrors('password');
    }

    public function test_super_admin_can_open_edit_user_page(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.users.edit', $user))
            ->assertOk()
            ->assertSee($user->email);
    }

    public function test_regular_admin_cannot_edit_users(): void
    {
        $user = User::factory()->customer()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.users.edit', $user))
            ->assertForbidden();
    }

    public function test_super_admin_accounts_cannot_be_edited(): void
    {
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.users.edit', $otherSuperAdmin))
            ->assertSessionHas('error')
            ->assertRedirect(route('admin.users.index'));
    }

    public function test_super_admin_can_update_user_profile_and_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Diana Putri']);

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $customer), $this->updatePayload($customer, [
                'name' => 'Diana Baru',
                'email' => 'diana.baru@mail.test',
                'phone' => '089876543210',
                'role' => UserRole::Admin->value,
            ]))
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.users.index'));

        $customer->refresh();

        $this->assertSame('Diana Baru', $customer->name);
        $this->assertSame('diana.baru@mail.test', $customer->email);
        $this->assertSame('089876543210', $customer->phone);
        $this->assertTrue($customer->isAdmin());
    }

    public function test_update_user_rejects_duplicate_email(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create(['email' => 'diana@mail.test']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.users.update', $customer), $this->updatePayload($customer, [
                'email' => $other->email,
            ]))
            ->assertSessionHasErrors('email');
    }

    public function test_blank_password_keeps_the_existing_password(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.users.update', $customer), $this->updatePayload($customer, [
                'password' => '',
                'password_confirmation' => '',
            ]))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('password', $customer->fresh()->password));
    }

    public function test_filled_password_is_rehashed_on_update(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.users.update', $customer), $this->updatePayload($customer, [
                'password' => 'passwordbaru',
                'password_confirmation' => 'passwordbaru',
            ]))
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('passwordbaru', $customer->fresh()->password));
    }

    public function test_super_admin_can_promote_customer_to_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $customer), $this->updatePayload($customer, [
                'role' => UserRole::Admin->value,
            ]))
            ->assertSessionHas('success');

        $this->assertTrue($customer->fresh()->isAdmin());
    }

    public function test_super_admin_can_promote_customer_to_worker(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $customer), $this->updatePayload($customer, [
                'role' => UserRole::Worker->value,
            ]))
            ->assertSessionHas('success');

        $this->assertTrue($customer->fresh()->isWorker());
    }

    public function test_super_admin_role_cannot_be_assigned_from_the_user_page(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $customer), $this->updatePayload($customer, [
                'role' => UserRole::SuperAdmin->value,
            ]))
            ->assertSessionHasErrors('role');

        $this->assertTrue($customer->fresh()->isCustomer());
    }

    public function test_super_admin_cannot_change_own_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $superAdmin), $this->updatePayload($superAdmin, [
                'role' => UserRole::Customer->value,
            ]))
            ->assertSessionHas('error')
            ->assertRedirect();

        $this->assertTrue($superAdmin->fresh()->isSuperAdmin());
    }

    public function test_super_admin_cannot_demote_another_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $otherSuperAdmin), $this->updatePayload($otherSuperAdmin, [
                'role' => UserRole::Customer->value,
            ]))
            ->assertSessionHas('error');

        $this->assertTrue($otherSuperAdmin->fresh()->isSuperAdmin());
    }

    public function test_worker_with_active_orders_cannot_be_demoted(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $worker = User::factory()->worker()->online()->create();
        Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($superAdmin)
            ->put(route('admin.users.update', $worker), $this->updatePayload($worker, [
                'role' => UserRole::Customer->value,
            ]))
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

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function updatePayload(User $user, array $overrides = []): array
    {
        return array_merge([
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role?->value,
        ], $overrides);
    }
}
