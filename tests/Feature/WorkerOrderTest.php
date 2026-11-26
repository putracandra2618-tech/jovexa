<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use App\Services\WorkerOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WorkerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_worker_pages(): void
    {
        $this->get(route('worker.dashboard'))->assertRedirect(route('login'));
    }

    public function test_admins_and_customers_cannot_access_worker_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('worker.dashboard'))
            ->assertForbidden();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('worker.queue.index'))
            ->assertForbidden();
    }

    public function test_worker_dashboard_shows_the_active_quota(): void
    {
        $worker = User::factory()->worker()->online()->create();
        Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->get(route('worker.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard Worker')
            ->assertSee('1/'.WorkerOrderService::MAX_ACTIVE_ORDERS);
    }

    public function test_queue_only_lists_unclaimed_queued_orders(): void
    {
        $worker = User::factory()->worker()->online()->create();
        $queued = Order::factory()->queued()->create();
        $assigned = Order::factory()->inProgress()->assignedTo($worker)->create();
        $verification = Order::factory()->waitingVerification()->create();

        $this->actingAs($worker)
            ->get(route('worker.queue.index'))
            ->assertOk()
            ->assertSee($queued->order_number)
            ->assertDontSee($assigned->order_number)
            ->assertDontSee($verification->order_number);
    }

    public function test_worker_can_claim_a_queued_order(): void
    {
        $worker = User::factory()->worker()->online()->create();
        $order = Order::factory()->queued()->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.claim', $order))
            ->assertRedirect(route('worker.orders.show', $order));

        $order->refresh();

        $this->assertSame($worker->getKey(), $order->worker_id);
        $this->assertSame(OrderStatus::InProgress, $order->status);
        $this->assertNotNull($order->worker_claimed_at);
        $this->assertSame(OrderStatus::InProgress, $order->statusHistory()->latest('id')->first()->to_status);
        $this->assertSame($worker->getKey(), $order->statusHistory()->latest('id')->first()->changed_by);
    }

    public function test_offline_worker_cannot_claim_an_order(): void
    {
        $worker = User::factory()->worker()->create(['is_online' => false]);
        $order = Order::factory()->queued()->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.claim', $order))
            ->assertSessionHasErrors('order');

        $this->assertNull($order->fresh()->worker_id);
        $this->assertSame(OrderStatus::Queued, $order->fresh()->status);
    }

    public function test_worker_cannot_claim_more_than_five_active_orders(): void
    {
        $worker = User::factory()->worker()->online()->create();

        Order::factory()
            ->count(WorkerOrderService::MAX_ACTIVE_ORDERS)
            ->inProgress()
            ->assignedTo($worker)
            ->create();

        $order = Order::factory()->queued()->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.claim', $order))
            ->assertSessionHasErrors('order');

        $this->assertNull($order->fresh()->worker_id);
    }

    public function test_worker_cannot_claim_an_order_that_is_no_longer_queued(): void
    {
        $worker = User::factory()->worker()->online()->create();
        $order = Order::factory()->inProgress()->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.claim', $order))
            ->assertSessionHasErrors('order');

        $this->assertNull($order->fresh()->worker_id);
    }

    public function test_worker_only_sees_orders_assigned_to_them(): void
    {
        $worker = User::factory()->worker()->create();
        $otherWorker = User::factory()->worker()->create();
        $own = Order::factory()->inProgress()->assignedTo($worker)->create();
        $other = Order::factory()->inProgress()->assignedTo($otherWorker)->create();

        $this->actingAs($worker)
            ->get(route('worker.orders.index'))
            ->assertOk()
            ->assertSee($own->order_number)
            ->assertDontSee($other->order_number);
    }

    public function test_worker_cannot_view_another_workers_order(): void
    {
        $worker = User::factory()->worker()->create();
        $other = Order::factory()->inProgress()->assignedTo(User::factory()->worker()->create())->create();

        $this->actingAs($worker)
            ->get(route('worker.orders.show', $other))
            ->assertForbidden();
    }

    public function test_worker_can_mark_their_order_as_ready_for_delivery(): void
    {
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->patch(route('worker.orders.status', $order), [
                'status' => OrderStatus::ReadyForDelivery->value,
            ])
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::ReadyForDelivery, $order->fresh()->status);
    }

    public function test_worker_cannot_mark_an_order_as_completed(): void
    {
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->readyForDelivery()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->patch(route('worker.orders.status', $order), [
                'status' => OrderStatus::Completed->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::ReadyForDelivery, $order->fresh()->status);
    }

    public function test_worker_cannot_set_a_status_reserved_for_the_queue_or_customer(): void
    {
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->patch(route('worker.orders.status', $order), [
                'status' => OrderStatus::InProgress->value,
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::InProgress, $order->fresh()->status);
    }

    public function test_worker_can_ask_the_customer_for_information(): void
    {
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.information', $order), [
                'note' => 'Mohon kirimkan file logo resolusi tinggi.',
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::WaitingCustomer, $order->fresh()->status);
    }

    public function test_worker_cannot_request_a_revision(): void
    {
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->patch(route('worker.orders.status', $order), [
                'status' => OrderStatus::Revision->value,
                'note' => 'Mohon perbaiki bagian implementasi.',
            ])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::InProgress, $order->fresh()->status);
        $this->assertDatabaseCount('order_revision_requests', 0);
    }

    public function test_worker_can_upload_a_result_only_when_the_order_is_ready_for_delivery(): void
    {
        Storage::fake('local');

        $worker = User::factory()->worker()->create();
        $inProgress = Order::factory()->inProgress()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.result.store', $inProgress), [
                'result' => UploadedFile::fake()->create('hasil.pdf', 100, 'application/pdf'),
            ])
            ->assertNotFound();

        $readyForDelivery = Order::factory()->readyForDelivery()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.result.store', $readyForDelivery), [
                'result' => UploadedFile::fake()->create('hasil.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $file = $readyForDelivery->results()->sole();

        $this->assertTrue($file->isResult());
        Storage::disk('local')->assertExists($file->file_path);
    }

    public function test_worker_can_upload_a_revised_result_without_revision_or_information_actions(): void
    {
        Storage::fake('local');

        $worker = User::factory()->worker()->create();
        $order = Order::factory()->revision()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->get(route('worker.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Minta Revisi')
            ->assertDontSee('Minta Informasi')
            ->assertSee('Unggah Hasil Revisi');

        $this->actingAs($worker)
            ->post(route('worker.orders.result.store', $order), [
                'result' => UploadedFile::fake()->create('hasil-revisi.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect();

        $this->assertTrue($order->revisions()->exists());
        $this->assertFalse($order->results()->exists());
        $this->assertTrue($order->revisions()->sole()->isRevision());
        Storage::disk('local')->assertExists($order->revisions()->sole()->file_path);
    }

    public function test_worker_can_deliver_after_uploading_a_result(): void
    {
        Storage::fake('local');

        $worker = User::factory()->worker()->create();
        $order = Order::factory()->readyForDelivery()->assignedTo($worker)->create();

        $this->actingAs($worker)
            ->post(route('worker.orders.result.store', $order), [
                'result' => UploadedFile::fake()->create('hasil.pdf', 100, 'application/pdf'),
            ])->assertRedirect();

        $this->actingAs($worker)
            ->post(route('worker.orders.send', $order), ['note' => 'Silakan dicek.'])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_worker_order_detail_has_only_one_send_action(): void
    {
        Storage::fake('local');

        $worker = User::factory()->worker()->create();
        $order = Order::factory()->readyForDelivery()->assignedTo($worker)->create();
        OrderFile::factory()->result()->stored()->for($order)->create();

        $response = $this->actingAs($worker)
            ->get(route('worker.orders.show', $order))
            ->assertOk();

        $this->assertSame(1, substr_count((string) $response->getContent(), 'Kirim ke Customer'));
    }

    public function test_worker_history_only_lists_their_finished_orders(): void
    {
        $worker = User::factory()->worker()->create();
        $delivered = Order::factory()->delivered()->assignedTo($worker)->create();
        $active = Order::factory()->inProgress()->assignedTo($worker)->create();
        $other = Order::factory()->delivered()->assignedTo(User::factory()->worker()->create())->create();

        $this->actingAs($worker)
            ->get(route('worker.orders.history'))
            ->assertOk()
            ->assertSee($delivered->order_number)
            ->assertDontSee($active->order_number)
            ->assertDontSee($other->order_number);
    }

    public function test_worker_can_delete_a_result_file_and_remove_it_from_storage(): void
    {
        Storage::fake('local');

        $worker = User::factory()->worker()->create();
        $order = Order::factory()->readyForDelivery()->assignedTo($worker)->create();
        $file = OrderFile::factory()->result()->stored()->for($order)->create();

        Storage::disk('local')->assertExists($file->file_path);

        $this->actingAs($worker)
            ->delete(route('worker.orders.file.destroy', [$order, $file]))
            ->assertRedirect(route('worker.orders.show', $order));

        $this->assertDatabaseMissing('order_files', ['id' => $file->getKey()]);
        Storage::disk('local')->assertMissing($file->file_path);
    }

    public function test_worker_cannot_delete_a_result_file_after_delivery(): void
    {
        Storage::fake('local');

        $worker = User::factory()->worker()->create();
        $order = Order::factory()->delivered()->assignedTo($worker)->create();
        $file = OrderFile::factory()->result()->stored()->for($order)->create();

        $this->actingAs($worker)
            ->delete(route('worker.orders.file.destroy', [$order, $file]))
            ->assertForbidden();

        $this->assertDatabaseHas('order_files', ['id' => $file->getKey()]);
        Storage::disk('local')->assertExists($file->file_path);
    }
}
