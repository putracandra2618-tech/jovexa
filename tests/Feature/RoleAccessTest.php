<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admins_can_access_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('sidebar-wrapper', escape: false);
    }

    public function test_super_admins_can_access_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('sidebar-wrapper', escape: false);
    }

    public function test_workers_cannot_access_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->worker()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_customers_cannot_access_the_admin_dashboard(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }

    public function test_customers_can_access_their_dashboard(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Saya');
    }

    public function test_admins_cannot_access_the_customer_dashboard(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('customer.dashboard'))
            ->assertForbidden();
    }

    public function test_workers_can_access_their_dashboard(): void
    {
        $this->actingAs(User::factory()->worker()->create())
            ->get(route('worker.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Worker');
    }

    public function test_authenticated_users_are_kept_away_from_guest_only_screens(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('login'))
            ->assertRedirect(route('customer.dashboard'));
    }

    public function test_role_helpers_reflect_the_stored_role(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $admin = User::factory()->admin()->create();
        $worker = User::factory()->worker()->create();
        $customer = User::factory()->customer()->create();

        $this->assertTrue($superAdmin->isSuperAdmin());
        $this->assertTrue($superAdmin->isStaff());
        $this->assertTrue($superAdmin->hasRole(UserRole::SuperAdmin));

        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($admin->isStaff());
        $this->assertFalse($admin->isSuperAdmin());
        $this->assertTrue($admin->hasRole(UserRole::Admin));

        $this->assertTrue($worker->isWorker());
        $this->assertFalse($worker->isStaff());
        $this->assertTrue($worker->hasRole(UserRole::Worker));

        $this->assertTrue($customer->isCustomer());
        $this->assertFalse($customer->hasRole(UserRole::Admin));
    }
}
