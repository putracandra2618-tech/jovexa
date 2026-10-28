<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dashboard_renders_the_spark_admin_shell(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('sidebar-wrapper', escape: false)
            ->assertSee('navbar-custom', escape: false)
            ->assertSee('Dashboard', escape: false);
    }

    public function test_admin_sidebar_only_renders_registered_routes(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Kelola Jasa', escape: false)
            ->assertSee('Daftar Pesanan', escape: false)
            ->assertSee('Pesanan Revisi', escape: false)
            ->assertSee('Riwayat Pesanan', escape: false)
            ->assertDontSee('Menu Rahasia', escape: false);
    }

    public function test_admin_topbar_search_is_hidden_on_screens_without_a_list(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('data-admin-search', escape: false);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('data-admin-search', escape: false);
    }

    public function test_admin_topbar_search_hides_on_the_service_forms(): void
    {
        $admin = User::factory()->admin()->create();
        $service = Service::factory()->create();

        $this->actingAs($admin)
            ->get(route('admin.services.create'))
            ->assertOk()
            ->assertDontSee('data-admin-search', escape: false);

        $this->actingAs($admin)
            ->get(route('admin.services.edit', $service))
            ->assertOk()
            ->assertDontSee('data-admin-search', escape: false);
    }

    public function test_customer_top_nav_shows_customer_menu_only(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('jx-header-customer', escape: false)
            ->assertSee('Pesanan Saya', escape: false)
            ->assertSee('Katalog Jasa', escape: false)
            ->assertDontSee('Kelola Jasa', escape: false);
    }

    public function test_customer_dashboard_renders_the_customer_shell(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Saya')
            ->assertDontSee('Pesan Jasa')
            ->assertDontSee('sidebar-wrapper', escape: false);
    }

    public function test_customer_navigation_uses_a_side_drawer(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('data-bs-target="#customerNavDrawer"', escape: false)
            ->assertSee('offcanvas-start jx-drawer', escape: false)
            ->assertSee('jx-drawer-nav', escape: false);
    }
}
