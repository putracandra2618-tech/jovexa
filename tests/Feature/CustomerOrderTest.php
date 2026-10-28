<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CustomerOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_the_order_form(): void
    {
        $service = Service::factory()->published()->create();

        $this->get(route('orders.create', $service))->assertRedirect(route('login'));
    }

    public function test_customer_can_open_the_order_form_for_a_published_service(): void
    {
        $service = Service::factory()->published()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('orders.create', $service))
            ->assertOk()
            ->assertSee($service->name);
    }

    public function test_order_form_is_unavailable_for_an_unpublished_service(): void
    {
        $service = Service::factory()->draft()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('orders.create', $service))
            ->assertNotFound();
    }

    public function test_customer_can_create_an_order_with_requirement_files(): void
    {
        Storage::fake('local');

        $customer = User::factory()->customer()->create();
        $service = Service::factory()->published()->create(['price' => 450000]);

        $response = $this->actingAs($customer)->post(route('orders.store', $service), [
            'description' => 'Saya butuh landasan teori untuk bab dua beserta visualisasinya.',
            'requirements' => [
                UploadedFile::fake()->create('modul.pdf', 120, 'application/pdf'),
                UploadedFile::fake()->create('catatan.docx', 80),
            ],
        ]);

        $order = Order::query()->sole();

        $response->assertRedirect(route('customer.orders.show', $order));
        $this->assertSame($customer->getKey(), $order->user_id);
        $this->assertSame($service->getKey(), $order->service_id);
        $this->assertSame(450000, $order->total_price);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertStringStartsWith('JVX-', $order->order_number);

        $this->assertCount(2, $order->requirements);
        $this->assertCount(0, $order->results);

        foreach ($order->files as $file) {
            Storage::disk('local')->assertExists($file->file_path);
        }
    }

    public function test_order_creation_rejects_unsupported_attachment_formats(): void
    {
        Storage::fake('local');

        $service = Service::factory()->published()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->from(route('orders.create', $service))
            ->post(route('orders.store', $service), [
                'description' => 'Saya butuh bantuan mengerjakan laporan akhir semester ini.',
                'requirements' => [
                    UploadedFile::fake()->create('payload.php', 5, 'application/x-php'),
                ],
            ])
            ->assertSessionHasErrors('requirements.0');

        $this->assertSame(0, Order::query()->count());
    }

    public function test_order_creation_records_the_initial_status_history(): void
    {
        $customer = User::factory()->customer()->create();
        $service = Service::factory()->published()->create();

        $this->actingAs($customer)->post(route('orders.store', $service), [
            'description' => 'Tolong bantu membuat ringkasan bab tiga beserta kesimpulan akhirnya.',
        ]);

        $order = Order::query()->sole();
        $history = $order->statusHistory()->sole();

        $this->assertNull($history->from_status);
        $this->assertSame(OrderStatus::PendingPayment, $history->to_status);
        $this->assertSame($customer->getKey(), $history->changed_by);
    }

    public function test_order_description_must_be_long_enough(): void
    {
        $service = Service::factory()->published()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->post(route('orders.store', $service), ['description' => 'terlalu pendek'])
            ->assertSessionHasErrors('description');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_unpublished_service_cannot_be_ordered(): void
    {
        $service = Service::factory()->draft()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->from(route('orders.create', $service))
            ->post(route('orders.store', $service), [
                'description' => 'Saya ingin memesan jasa yang sudah diarsipkan ini sekarang.',
            ])
            ->assertNotFound();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_cannot_use_the_customer_order_form(): void
    {
        $service = Service::factory()->published()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('orders.create', $service))
            ->assertForbidden();
    }

    public function test_customer_sees_only_their_own_orders(): void
    {
        $customer = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();

        $own = Order::factory()->for($customer)->create();
        $foreign = Order::factory()->for($other)->create();

        $this->actingAs($customer)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertSee($own->order_number)
            ->assertDontSee($foreign->order_number);
    }

    public function test_customer_cannot_open_another_customers_order(): void
    {
        $foreign = Order::factory()->for(User::factory()->customer())->create();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.orders.show', $foreign))
            ->assertForbidden();
    }

    public function test_orders_index_exposes_the_status_filter_hooks(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->inProgress()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.index'))
            ->assertOk()
            ->assertSee('data-status-filter-group', escape: false)
            ->assertSee('data-order-status="pending"', escape: false);
    }

    public function test_order_show_renders_the_animated_stepper_and_cancel_hooks(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->inProgress()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('data-stepper', escape: false)
            ->assertSee('jx-confirm-modal', escape: false)
            ->assertSee('data-confirm=', escape: false);
    }

    public function test_order_form_renders_the_dropzone_and_character_counter_hooks(): void
    {
        $service = Service::factory()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('orders.create', $service))
            ->assertOk()
            ->assertSee('data-dropzone', escape: false)
            ->assertSee('data-char-counter="5000"', escape: false);
    }

    public function test_customer_can_cancel_a_running_order(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->inProgress()->create();

        $this->actingAs($order->user)
            ->delete(route('customer.orders.destroy', $order))
            ->assertRedirect(route('customer.orders.index'));

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_delivered_order_cannot_be_cancelled(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();

        $this->actingAs($order->user)
            ->delete(route('customer.orders.destroy', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_customer_can_upload_extra_files_only_while_waiting_for_response(): void
    {
        Storage::fake('local');

        $order = Order::factory()->for(User::factory()->customer())->waitingCustomer()->create();

        $this->actingAs($order->user)
            ->post(route('customer.orders.requirements', $order), [
                'requirements' => [UploadedFile::fake()->create('bab1.pdf', 90, 'application/pdf')],
            ])
            ->assertRedirect();

        $this->assertCount(1, $order->fresh()->requirements);
        $this->assertSame(OrderStatus::WaitingVerification, $order->fresh()->status);
    }

    public function test_customer_reply_returns_an_assigned_order_to_work(): void
    {
        Storage::fake('local');

        $worker = User::factory()->worker()->create();
        $order = Order::factory()
            ->for(User::factory()->customer())
            ->waitingCustomer()
            ->assignedTo($worker)
            ->create();

        $this->actingAs($order->user)
            ->post(route('customer.orders.requirements', $order), [
                'requirements' => [UploadedFile::fake()->create('bab1.pdf', 90, 'application/pdf')],
            ])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame(OrderStatus::InProgress, $order->status);
        $this->assertSame($worker->getKey(), $order->worker_id);
    }

    public function test_customer_cannot_upload_extra_files_while_the_order_is_in_progress(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->inProgress()->create();

        $this->actingAs($order->user)
            ->post(route('customer.orders.requirements', $order), [
                'requirements' => [UploadedFile::fake()->create('tambahan.pdf', 90, 'application/pdf')],
            ])
            ->assertForbidden();

        $this->assertCount(0, $order->fresh()->files);
    }

    public function test_customer_can_download_their_requirement_file(): void
    {
        Storage::fake('local');

        $order = Order::factory()->for(User::factory()->customer())->create();
        $path = UploadedFile::fake()->create('modul.pdf', 50, 'application/pdf')->store('orders/test', 'local');

        $file = OrderFile::factory()->for($order)->create(['file_path' => $path, 'file_name' => 'modul.pdf']);

        $this->actingAs($order->user)
            ->get(route('customer.orders.file', [$order, $file]))
            ->assertOk();
    }

    public function test_result_file_is_locked_until_the_work_is_delivered(): void
    {
        Storage::fake('local');

        $order = Order::factory()->for(User::factory()->customer())->inProgress()->create();
        $path = UploadedFile::fake()->create('hasil.pdf', 50, 'application/pdf')->store('orders/test', 'local');

        $file = OrderFile::factory()->for($order)->result()->create(['file_path' => $path]);

        $this->actingAs($order->user)
            ->get(route('customer.orders.file', [$order, $file]))
            ->assertForbidden();
    }

    public function test_result_file_becomes_readable_once_the_work_is_delivered(): void
    {
        Storage::fake('local');

        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();
        $path = UploadedFile::fake()->create('hasil.pdf', 50, 'application/pdf')->store('orders/test', 'local');

        $file = OrderFile::factory()->for($order)->result()->create(['file_path' => $path]);

        $this->actingAs($order->user)
            ->get(route('customer.orders.file', [$order, $file]))
            ->assertOk();
    }

    public function test_customer_cannot_download_a_file_from_another_order(): void
    {
        Storage::fake('local');

        $order = Order::factory()->for(User::factory()->customer())->create();
        $otherOrder = Order::factory()->for(User::factory()->customer())->create();

        $path = UploadedFile::fake()->create('rahasia.pdf', 50, 'application/pdf')->store('orders/test', 'local');
        $file = OrderFile::factory()->for($otherOrder)->create(['file_path' => $path]);

        $this->actingAs($order->user)
            ->get(route('customer.orders.file', [$order, $file]))
            ->assertNotFound();
    }

    public function test_files_are_not_stored_in_the_public_disk(): void
    {
        Storage::fake('local');

        $service = Service::factory()->published()->create();

        $this->actingAs(User::factory()->customer()->create())->post(route('orders.store', $service), [
            'description' => 'Bantu saya menyusun bab satu beserta daftar isi lengkapnya.',
            'requirements' => [UploadedFile::fake()->create('rahasia.pdf', 50, 'application/pdf')],
        ]);

        $file = OrderFile::query()->sole();

        $this->assertStringStartsWith('orders/', $file->file_path);
        $this->assertStringNotContainsString('public/', $file->file_path);
    }

    public function test_dashboard_shows_customer_order_counts(): void
    {
        $customer = User::factory()->customer()->create();

        Order::factory()->count(2)->for($customer)->inProgress()->create();
        Order::factory()->for($customer)->pendingPayment()->create();
        Order::factory()->for($customer)->delivered()->create();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('Pesanan Aktif');
    }
}
