<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_action_required_statuses_share_the_completed_tab(): void
    {
        $this->assertSame(['all', 'pending', 'success'], array_keys(OrderStatus::categories()));

        foreach ([OrderStatus::WaitingCustomer, OrderStatus::Revision, OrderStatus::Expired, OrderStatus::Cancelled] as $status) {
            $this->assertSame('success', $status->category());
            $this->assertContains($status, OrderStatus::statusesForCategory('success'));
        }
    }

    public function test_admin_completed_tab_includes_orders_that_need_action(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Budi Santoso']);
        $cancelled = Order::factory()->for($customer)->cancelled()->create();
        $active = Order::factory()->for($customer)->inProgress()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.index', ['category' => 'success']))
            ->assertOk()
            ->assertSee('Selesai')
            ->assertSee('jx-status-tab--all', escape: false)
            ->assertSee('jx-status-tab--pending', escape: false)
            ->assertSee('jx-status-tab--success', escape: false)
            ->assertSee($cancelled->order_number)
            ->assertDontSee('Perlu tindakan')
            ->assertDontSee($active->order_number);
    }

    public function test_worker_completed_tab_includes_orders_that_need_action(): void
    {
        $worker = User::factory()->worker()->create();
        $cancelled = Order::factory()->cancelled()->assignedTo($worker)->create();
        $active = Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->get(route('worker.orders.index', ['category' => 'success']))
            ->assertOk()
            ->assertSee('jx-status-tab--all', escape: false)
            ->assertSee('jx-status-tab--pending', escape: false)
            ->assertSee('jx-status-tab--success', escape: false)
            ->assertSee($cancelled->order_number)
            ->assertDontSee('Perlu tindakan')
            ->assertDontSee($active->order_number);
    }

    public function test_customer_order_table_has_no_action_required_tab(): void
    {
        $customer = User::factory()->customer()->create();
        $cancelled = Order::factory()->for($customer)->cancelled()->create();

        $this->actingAs($customer)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertDontSee('Perlu tindakan')
            ->assertSee('jx-status-tab--all', escape: false)
            ->assertSee('jx-status-tab--pending', escape: false)
            ->assertSee('jx-status-tab--success', escape: false)
            ->assertSee('data-order-status="success" data-order-stage="done"', escape: false)
            ->assertSee($cancelled->order_number);
    }
}
