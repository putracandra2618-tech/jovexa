<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOrderWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_list_requires_admin_role(): void
    {
        $this->get(route('admin.orders.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('admin.orders.index'))
            ->assertForbidden();
    }

    public function test_admin_sees_the_order_queue_with_filters(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->inProgress()->create();

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['status' => OrderStatus::InProgress->value]))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_order_queue_can_be_searched_by_customer_name(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Budi Santoso']);
        $order = Order::factory()->for($customer)->create();

        $this->actingAs($admin)
            ->get(route('admin.orders.index', ['q' => 'Budi']))
            ->assertOk()
            ->assertSee($order->order_number);
    }

    public function test_order_queue_search_reports_when_nothing_matches(): void
    {
        Order::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.index', ['q' => 'tidak-ada-hasilnya']))
            ->assertOk()
            ->assertSee('Pesanan tidak ditemukan')
            ->assertViewHas('search', 'tidak-ada-hasilnya');
    }

    public function test_order_queue_search_narrows_within_the_active_status_filter(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Budi Santoso']);
        $inProgress = Order::factory()->for($customer)->inProgress()->create();
        $cancelled = Order::factory()->for($customer)->cancelled()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.index', ['q' => 'Budi', 'status' => OrderStatus::Cancelled->value]))
            ->assertOk()
            ->assertSee($cancelled->order_number)
            ->assertDontSee($inProgress->order_number);
    }

    public function test_order_queue_topbar_search_targets_the_order_list(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('action="'.route('admin.orders.index').'" data-admin-search', escape: false)
            ->assertSee('placeholder="Cari nomor pesanan, nama, atau email customer"', escape: false);
    }

    public function test_order_queue_topbar_search_carries_the_active_status_filter(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.index', ['status' => OrderStatus::InProgress->value]))
            ->assertOk()
            ->assertSee('<input type="hidden" name="status" value="in_progress">', escape: false);
    }

    public function test_revision_queue_can_be_searched_and_targets_its_own_list(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['name' => 'Budi Santoso']);
        $revision = Order::factory()->for($customer)->revision()->create();
        $unrelated = Order::factory()->revision()->create();

        $this->actingAs($admin)
            ->get(route('admin.orders.revision', ['q' => 'Budi']))
            ->assertOk()
            ->assertSee($revision->order_number)
            ->assertDontSee($unrelated->order_number)
            ->assertSee('action="'.route('admin.orders.revision').'" data-admin-search', escape: false)
            ->assertSee('placeholder="Cari pesanan yang perlu direvisi"', escape: false);
    }

    public function test_history_queue_can_be_searched_and_targets_its_own_list(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create(['email' => 'siti@mail.test']);
        $delivered = Order::factory()->for($customer)->delivered()->create();
        $other = Order::factory()->delivered()->create();

        $this->actingAs($admin)
            ->get(route('admin.orders.history', ['q' => 'siti@mail.test']))
            ->assertOk()
            ->assertSee($delivered->order_number)
            ->assertDontSee($other->order_number)
            ->assertSee('action="'.route('admin.orders.history').'" data-admin-search', escape: false)
            ->assertSee('placeholder="Cari riwayat pesanan"', escape: false);
    }

    public function test_paginating_a_searched_queue_keeps_the_keyword(): void
    {
        $customer = User::factory()->customer()->create(['name' => 'Budi Santoso']);
        Order::factory()->count(11)->for($customer)->revision()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.revision', ['q' => 'Budi']))
            ->assertOk()
            ->assertViewHas('orders', fn ($orders) => $orders->total() === 11)
            ->assertSee('q=Budi', escape: false);
    }

    public function test_admin_can_release_a_verified_order_to_the_worker_queue(): void
    {
        $admin = User::factory()->admin()->create();
        $order = Order::factory()->waitingVerification()->create();

        $this->actingAs($admin)
            ->from(route('admin.orders.show', $order))
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Queued->value,
                'note' => 'Pembayaran sudah dicek.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(OrderStatus::Queued, $order->fresh()->status);

        $entry = $order->statusHistory()->latest('id')->first();

        $this->assertSame(OrderStatus::WaitingVerification, $entry->from_status);
        $this->assertSame(OrderStatus::Queued, $entry->to_status);
        $this->assertSame($admin->getKey(), $entry->changed_by);
        $this->assertSame('Pembayaran sudah dicek.', $entry->note);
    }

    public function test_admin_cannot_bypass_the_worker_queue(): void
    {
        $order = Order::factory()->queued()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::InProgress->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Queued, $order->fresh()->status);
        $this->assertNull($order->fresh()->worker_id);
    }

    public function test_skipping_a_status_is_rejected(): void
    {
        $order = Order::factory()->pendingPayment()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Delivered->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_delivered_order_cannot_be_changed_again(): void
    {
        $order = Order::factory()->delivered()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Cancelled->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_admin_can_ask_the_customer_for_information(): void
    {
        $order = Order::factory()->waitingVerification()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->from(route('admin.orders.show', $order))
            ->post(route('admin.orders.information', $order), [
                'note' => 'Mohon kirim data lengkap bab satu.',
            ])
            ->assertRedirect(route('admin.orders.show', $order));

        $this->assertSame(OrderStatus::WaitingCustomer, $order->fresh()->status);
    }

    public function test_information_request_requires_a_meaningful_note(): void
    {
        $order = Order::factory()->waitingVerification()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.orders.information', $order), ['note' => 'kurang'])
            ->assertSessionHasErrors('note');

        $this->assertSame(OrderStatus::WaitingVerification, $order->fresh()->status);
    }

    public function test_admin_can_send_the_order_back_for_revision(): void
    {
        $order = Order::factory()->inProgress()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Revision->value,
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Revision, $order->fresh()->status);
    }

    public function test_admin_cannot_mark_an_order_as_paid_manually(): void
    {
        $order = Order::factory()->pendingPayment()->create();

        // Only the gateway webhook is allowed to confirm a payment, because the
        // admin/worker must never be able to record a payment that did not reach
        // the bank.
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Paid->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::PendingPayment, $order->fresh()->status);
    }

    public function test_admin_cannot_set_delivered_status_manually(): void
    {
        $order = Order::factory()->completed()->create();

        // Delivery must go through the dedicated send action, which refuses to
        // run unless a result file has been uploaded.
        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Delivered->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_revision_order_is_marked_completed_once_redone(): void
    {
        $order = Order::factory()->revision()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.status', $order), [
                'status' => OrderStatus::Completed->value,
                'note' => 'Revisi sudah incorporated.',
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_cancelling_a_delivered_order_returns_forbidden(): void
    {
        $order = Order::factory()->delivered()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.orders.cancel', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_revision_queue_lists_only_orders_awaiting_revision(): void
    {
        $admin = User::factory()->admin()->create();
        $revision = Order::factory()->revision()->create();

        Order::factory()->inProgress()->create();

        $this->actingAs($admin)
            ->get(route('admin.orders.revision'))
            ->assertOk()
            ->assertSee($revision->order_number);
    }

    public function test_history_page_lists_only_final_orders(): void
    {
        $admin = User::factory()->admin()->create();
        $delivered = Order::factory()->delivered()->create();

        Order::factory()->inProgress()->create();

        $this->actingAs($admin)
            ->get(route('admin.orders.history'))
            ->assertOk()
            ->assertSee($delivered->order_number);
    }

    public function test_deliver_screen_is_closed_until_the_work_is_finished(): void
    {
        $order = Order::factory()->inProgress()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.deliver', $order))
            ->assertNotFound();
    }

    public function test_admin_can_upload_the_finished_work(): void
    {
        Storage::fake('local');

        $order = Order::factory()->completed()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.orders.deliver', $order))
            ->assertOk();

        $this->actingAs($admin)
            ->post(route('admin.orders.result.store', $order), [
                'result' => UploadedFile::fake()->create('hasil-akhir.pdf', 200, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = $order->results()->sole();

        $this->assertTrue($file->isResult());
        Storage::disk('local')->assertExists($file->file_path);
    }

    public function test_result_upload_is_blocked_while_the_order_is_not_finished(): void
    {
        Storage::fake('local');

        $order = Order::factory()->waitingVerification()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.orders.result.store', $order), [
                'result' => UploadedFile::fake()->create('hasil.pdf', 200, 'application/pdf'),
            ])
            ->assertNotFound();

        $this->assertCount(0, $order->files);
    }

    public function test_cannot_deliver_without_an_uploaded_result(): void
    {
        $order = Order::factory()->completed()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.orders.send', $order))
            ->assertSessionHasErrors();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_admin_can_deliver_the_finished_work(): void
    {
        Storage::fake('local');

        $order = Order::factory()->completed()->create();
        OrderFile::factory()->for($order)->result()->stored()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.orders.send', $order))
            ->assertRedirect();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_admin_can_cancel_an_undelivered_order(): void
    {
        $order = Order::factory()->inProgress()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.orders.cancel', $order))
            ->assertRedirect();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_admin_can_download_customer_requirement_files(): void
    {
        Storage::fake('local');

        $order = Order::factory()->inProgress()->create();
        $path = UploadedFile::fake()->create('modul.pdf', 50, 'application/pdf')->store('orders/test', 'local');
        $file = OrderFile::factory()->for($order)->create(['file_path' => $path]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.file', [$order, $file]))
            ->assertOk();
    }

    public function test_admin_cannot_download_a_file_belonging_to_another_order(): void
    {
        Storage::fake('local');

        $order = Order::factory()->create();
        $otherOrder = Order::factory()->create();

        $path = UploadedFile::fake()->create('modul.pdf', 50, 'application/pdf')->store('orders/test', 'local');
        $file = OrderFile::factory()->for($otherOrder)->create(['file_path' => $path]);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.file', [$order, $file]))
            ->assertNotFound();
    }

    public function test_admin_dashboard_shows_pipeline_counts(): void
    {
        Order::factory()->count(2)->waitingVerification()->create();
        Order::factory()->count(3)->queued()->create();
        Order::factory()->count(4)->inProgress()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Menunggu Verifikasi')
            ->assertSee('Antrean Worker')
            ->assertSee('Sedang Dikerjakan');
    }

    public function test_customer_cannot_reach_admin_order_pages(): void
    {
        $order = Order::factory()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('admin.orders.show', $order))
            ->assertForbidden();
    }
}
