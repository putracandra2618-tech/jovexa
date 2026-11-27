<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Payment;
use App\Models\RevisionRequest;
use App\Models\Service;
use App\Models\User;
use App\Models\UserNotification;
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
        $this->assertSame(OrderStatus::WaitingVerification, $order->status);
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
        $this->assertSame(OrderStatus::WaitingVerification, $history->to_status);
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
            ->assertDontSee($foreign->order_number)
            ->assertDontSee('<th>Revisi</th>', escape: false);
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
        $order = Order::factory()->for(User::factory()->customer())->pendingPayment()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('data-stepper', escape: false)
            ->assertSee('jx-confirm-modal', escape: false)
            ->assertSee('data-confirm=', escape: false)
            ->assertSee('WIB');
    }

    public function test_an_unreviewed_order_shows_a_review_message_without_a_pay_button(): void
    {
        config(['jovexa.contact.whatsapp' => '62895633852241']);

        $order = Order::factory()->for(User::factory()->customer())->waitingVerification()->create();
        $message = 'Halo Admin Jovexa, saya ingin menanyakan status pesanan '.$order->order_number.'.';

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Pesanan anda sedang di tinjau admin. Setelah disetujui, kamu bisa melanjutkan pembayaran. Kamu masih bisa membatalkan sebelum pembayaran; setelah dibayar, ajukan refund lewat WhatsApp sampai hasil dikirim.')
            ->assertSee('Hubungi CS Admin')
            ->assertSee('https://wa.me/62895633852241?text='.rawurlencode($message), escape: false)
            ->assertSee('target="_blank"', escape: false)
            ->assertSee('rel="noopener"', escape: false)
            ->assertSee('Batalkan Pesanan')
            ->assertDontSee('Bayar Sekarang')
            ->assertDontSee('Request Refund');
    }

    public function test_an_approved_order_offers_the_pay_button(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->pendingPayment()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Bayar Sekarang');
    }

    public function test_customer_cannot_cancel_after_a_worker_claims_the_order(): void
    {
        config(['jovexa.contact.whatsapp' => '62895633852241']);

        $order = Order::factory()
            ->for(User::factory()->customer())
            ->inProgress()
            ->assignedTo(User::factory()->worker()->create())
            ->create();
        Payment::factory()->for($order)->paid()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Request Refund')
            ->assertDontSee('Batalkan Pesanan');

        $this->actingAs($order->user)
            ->delete(route('customer.orders.destroy', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::InProgress, $order->fresh()->status);
        $this->assertSame(0, $order->statusHistory()->count());
    }

    public function test_customer_cannot_cancel_after_worker_requests_information(): void
    {
        $order = Order::factory()
            ->for(User::factory()->customer())
            ->waitingCustomer()
            ->assignedTo(User::factory()->worker()->create())
            ->create();

        $this->actingAs($order->user)
            ->delete(route('customer.orders.destroy', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::WaitingCustomer, $order->fresh()->status);
    }

    public function test_unpaid_order_waiting_for_customer_information_shows_cancel_instead_of_refund(): void
    {
        config(['jovexa.contact.whatsapp' => '62895633852241']);

        $order = Order::factory()->for(User::factory()->customer())->waitingCustomer()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Batalkan Pesanan')
            ->assertDontSee('Request Refund')
            ->assertDontSee('Hubungi CS Admin');
    }

    public function test_customer_service_button_is_hidden_when_whatsapp_is_not_configured(): void
    {
        config(['jovexa.contact.whatsapp' => null]);

        $order = Order::factory()->for(User::factory()->customer())->waitingVerification()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Hubungi CS Admin');
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

    public function test_customer_can_cancel_before_payment(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->pendingPayment()->create();

        $this->actingAs($order->user)
            ->delete(route('customer.orders.destroy', $order))
            ->assertRedirect(route('customer.orders.index'));

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_paid_order_offers_refund_instead_of_cancel(): void
    {
        config(['jovexa.contact.whatsapp' => '+62 895-6338-52241']);

        $order = Order::factory()->for(User::factory()->customer())->queued()->create();
        Payment::factory()->for($order)->paid()->create();
        $message = sprintf(
            'Halo Admin Jovexa, saya ingin mengajukan refund untuk pesanan %s (%s). Mohon ditindaklanjuti.',
            $order->order_number,
            $order->service->name,
        );

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Request Refund')
            ->assertSee('https://wa.me/62895633852241?text='.rawurlencode($message), escape: false)
            ->assertSee('target="_blank"', escape: false)
            ->assertSee('rel="noopener"', escape: false)
            ->assertDontSee('Batalkan Pesanan');

        $this->actingAs($order->user)
            ->delete(route('customer.orders.destroy', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Queued, $order->fresh()->status);
    }

    public function test_refund_is_hidden_after_the_result_is_delivered(): void
    {
        config(['jovexa.contact.whatsapp' => '62895633852241']);

        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();
        Payment::factory()->for($order)->paid()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Request Refund');
    }

    public function test_refund_is_hidden_when_whatsapp_is_not_configured(): void
    {
        config(['jovexa.contact.whatsapp' => null]);

        $order = Order::factory()->for(User::factory()->customer())->queued()->create();
        Payment::factory()->for($order)->paid()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Request Refund')
            ->assertDontSee('Batalkan Pesanan');
    }

    public function test_refund_remains_visible_when_a_later_payment_attempt_fails(): void
    {
        config(['jovexa.contact.whatsapp' => '62895633852241']);

        $order = Order::factory()->for(User::factory()->customer())->queued()->create();
        Payment::factory()->for($order)->paid()->create();
        Payment::factory()->for($order)->failed()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Request Refund');
    }

    public function test_delivered_order_cannot_be_cancelled(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();

        $this->actingAs($order->user)
            ->delete(route('customer.orders.destroy', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_customer_can_accept_the_delivered_result_and_notify_staff(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->for($customer)->delivered()->assignedTo($worker)->create();
        $this->createDeliverable($order);

        $this->actingAs($customer)
            ->from(route('customer.orders.show', $order))
            ->post(route('customer.orders.accept', $order))
            ->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);

        $entry = $order->statusHistory()->latest('id')->first();
        $this->assertSame(OrderStatus::Delivered, $entry->from_status);
        $this->assertSame(OrderStatus::Completed, $entry->to_status);
        $this->assertSame('Customer menerima hasil pekerjaan.', $entry->note);

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->getKey(),
            'order_id' => $order->getKey(),
            'type' => UserNotification::OrderAccepted,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $worker->getKey(),
            'order_id' => $order->getKey(),
            'type' => UserNotification::OrderAccepted,
        ]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $customer->getKey()]);
    }

    public function test_customer_cannot_accept_the_result_before_it_is_delivered(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->readyForDelivery()->create();
        $this->createDeliverable($order);

        $this->actingAs($order->user)
            ->post(route('customer.orders.accept', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::ReadyForDelivery, $order->fresh()->status);
    }

    public function test_customer_cannot_accept_the_result_without_a_deliverable(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();

        $this->actingAs($order->user)
            ->post(route('customer.orders.accept', $order))
            ->assertForbidden();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_accepted_order_hides_revision_refund_and_accept_actions(): void
    {
        config(['jovexa.contact.whatsapp' => '62895633852241']);

        $order = Order::factory()->for(User::factory()->customer())->completed()->create();
        Payment::factory()->for($order)->paid()->create();
        $this->createDeliverable($order);

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Hasil pesanan sudah kamu terima')
            ->assertDontSee('Terima & Selesai')
            ->assertDontSee('Minta Revisi')
            ->assertDontSee('Request Refund');

        $this->actingAs($order->user)
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => 'Masih perlu diperbaiki.',
            ])
            ->assertForbidden();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
        $this->assertDatabaseCount('order_revision_requests', 0);
    }

    public function test_customer_can_request_revision_on_a_ready_for_delivery_order(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->readyForDelivery()->create();
        $this->createDeliverable($order);

        $this->actingAs($order->user)
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => 'Tolong perbaiki bagian kesimpulan dan tambahkan referensi.',
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Revision, $order->fresh()->status);

        $history = $order->statusHistory()->latest()->first();
        $this->assertStringContainsString('Tolong perbaiki bagian kesimpulan', $history->note);
    }

    public function test_customer_can_request_revision_on_a_delivered_order(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();
        $this->createDeliverable($order);

        $this->actingAs($order->user)
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => 'Tolong sesuaikan dengan format terbaru.',
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Revision, $order->fresh()->status);
        $this->assertDatabaseHas('order_revision_requests', [
            'order_id' => $order->getKey(),
            'requested_by' => $order->user_id,
            'revision_number' => 1,
            'note' => 'Tolong sesuaikan dengan format terbaru.',
        ]);
    }

    public function test_customer_can_request_revision_while_order_is_in_progress_with_a_result(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->inProgress()->create();
        $this->createDeliverable($order);

        $this->actingAs($order->user)
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => 'Tolong ubah warna diagram pada bagian kedua.',
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::Revision, $order->fresh()->status);
    }

    public function test_another_customer_cannot_request_revision_on_someone_elses_order(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();
        $other = User::factory()->customer()->create();

        $this->actingAs($other)
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => 'Catatan dari customer lain.',
            ])
            ->assertForbidden();

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_revision_note_must_not_exceed_1000_characters(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();

        $this->actingAs($order->user)
            ->from(route('customer.orders.show', $order))
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors('revision_note');

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
    }

    public function test_revision_note_is_required(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->delivered()->create();

        $this->actingAs($order->user)
            ->from(route('customer.orders.show', $order))
            ->post(route('customer.orders.revision', $order), ['revision_note' => '   '])
            ->assertSessionHasErrors('revision_note');

        $this->assertSame(OrderStatus::Delivered, $order->fresh()->status);
        $this->assertDatabaseCount('order_revision_requests', 0);
    }

    public function test_revision_request_notifies_staff_without_notifying_the_requester(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->for($customer)->readyForDelivery()->assignedTo($worker)->create();
        $this->createDeliverable($order);

        $this->actingAs($customer)
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => 'Tolong perbaiki bagian daftar pustaka.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->getKey(),
            'order_id' => $order->getKey(),
            'type' => UserNotification::RevisionRequested,
        ]);
        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $worker->getKey(),
            'order_id' => $order->getKey(),
            'type' => UserNotification::RevisionRequested,
        ]);
        $this->assertDatabaseMissing('user_notifications', ['user_id' => $customer->getKey()]);
    }

    public function test_revision_notes_are_visible_on_the_order_detail_page(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->delivered()->create();
        $note = 'Tolong perbaiki bagian kesimpulan dan tambahkan referensi.';

        RevisionRequest::factory()->for($order)->create([
            'requested_by' => $customer->getKey(),
            'revision_number' => 1,
            'note' => $note,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Revisi 1/3')
            ->assertSee($note);
    }

    public function test_customer_cannot_request_a_fourth_revision(): void
    {
        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->readyForDelivery()->create();
        $this->createDeliverable($order);

        foreach (['Perbaikan pertama.', 'Perbaikan kedua.', 'Perbaikan ketiga.'] as $number => $note) {
            RevisionRequest::factory()->for($order)->create([
                'requested_by' => $customer->getKey(),
                'revision_number' => $number + 1,
                'note' => $note,
            ]);
        }

        $this->actingAs($customer)
            ->from(route('customer.orders.show', $order))
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => 'Permintaan keempat.',
            ])
            ->assertSessionHasErrors('revision_note');

        $this->assertSame(OrderStatus::ReadyForDelivery, $order->fresh()->status);
        $this->assertSame(3, $order->revisionRequests()->count());

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Minta Revisi')
            ->assertSee('Kuota revisi sudah habis')
            ->assertSee('Revisi 3/3');
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

    public function test_customer_can_reply_with_text_without_uploading_a_file(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->waitingCustomer()->create();

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Kirim Informasi')
            ->assertSee('Balasan Teks');

        $this->actingAs($order->user)
            ->post(route('customer.orders.requirements', $order), [
                'note' => 'Judul bab satu sudah saya perbaiki sesuai catatan.',
            ])
            ->assertRedirect();

        $this->assertCount(0, $order->fresh()->requirements);
        $this->assertSame(OrderStatus::WaitingVerification, $order->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $order->getKey(),
            'note' => 'Customer: Judul bab satu sudah saya perbaiki sesuai catatan.',
        ]);
    }

    public function test_customer_text_reply_is_notified_and_visible_to_the_admin_who_requested_it(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->for($customer)->inProgress()->assignedTo($worker)->create();
        $requestNote = 'Mohon kirim daftar bab yang sudah direvisi.';
        $reply = 'Daftar bab sudah saya kirim lewat teks, mohon dicek.';

        $this->actingAs($admin)
            ->post(route('admin.orders.information', $order), ['note' => $requestNote])
            ->assertRedirect();

        $this->actingAs($customer)
            ->post(route('customer.orders.requirements', $order), ['note' => $reply])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $admin->getKey(),
            'order_id' => $order->getKey(),
            'type' => UserNotification::InformationReplied,
            'body' => 'Pesanan '.$order->order_number.': '.$reply,
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $worker->getKey(),
            'type' => UserNotification::InformationReplied,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Balasan dari Customer')
            ->assertSee($reply);

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Balasan dari Customer');

        $this->assertNotNull(
            $admin->inAppNotifications()->where('type', UserNotification::InformationReplied)->first()?->read_at
        );

        $this->actingAs($worker)
            ->get(route('worker.orders.show', $order))
            ->assertOk()
            ->assertSee('Balasan dari Customer')
            ->assertSee($reply);
    }

    public function test_customer_text_reply_is_notified_and_visible_to_the_worker_who_requested_it(): void
    {
        $customer = User::factory()->customer()->create();
        $admin = User::factory()->admin()->create();
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->for($customer)->inProgress()->assignedTo($worker)->create();
        $requestNote = 'Mohon kirim logo resolusi tinggi untuk cover.';
        $reply = 'Logo resolusi tinggi sudah saya siapkan.';

        $this->actingAs($worker)
            ->post(route('worker.orders.information', $order), ['note' => $requestNote])
            ->assertRedirect();

        $this->actingAs($customer)
            ->post(route('customer.orders.requirements', $order), ['note' => $reply])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $worker->getKey(),
            'order_id' => $order->getKey(),
            'type' => UserNotification::InformationReplied,
        ]);
        $this->assertDatabaseMissing('user_notifications', [
            'user_id' => $admin->getKey(),
            'type' => UserNotification::InformationReplied,
        ]);

        $this->actingAs($worker)
            ->get(route('worker.orders.show', $order))
            ->assertOk()
            ->assertSee('Balasan dari Customer')
            ->assertSee($reply);

        $this->actingAs($worker)
            ->get(route('worker.orders.show', $order))
            ->assertOk()
            ->assertDontSee('Balasan dari Customer');

        $this->actingAs($admin)
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Balasan dari Customer')
            ->assertSee($reply);
    }

    public function test_customer_cannot_send_an_empty_information_reply(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->waitingCustomer()->create();

        $this->actingAs($order->user)
            ->post(route('customer.orders.requirements', $order), ['note' => '   '])
            ->assertSessionHasErrors('note');

        $this->assertSame(OrderStatus::WaitingCustomer, $order->fresh()->status);
    }

    public function test_staff_information_request_is_visible_and_notified_to_the_customer(): void
    {
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();
        $order = Order::factory()->for($customer)->inProgress()->assignedTo($worker)->create();
        $note = 'Mohon kirim logo resolusi tinggi untuk bagian sampan.';

        $this->actingAs($worker)
            ->post(route('worker.orders.information', $order), ['note' => $note])
            ->assertRedirect();

        $this->assertDatabaseHas('user_notifications', [
            'user_id' => $customer->getKey(),
            'order_id' => $order->getKey(),
            'type' => UserNotification::InformationRequested,
        ]);

        $this->actingAs($customer)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('Pesan dari tim Jovexa')
            ->assertSee($note);
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

    public function test_customer_cannot_exceed_the_requirement_attachment_quota(): void
    {
        Storage::fake('local');

        $customer = User::factory()->customer()->create();
        $order = Order::factory()->for($customer)->waitingCustomer()->create();

        OrderFile::factory()
            ->requirement()
            ->count(OrderFile::MAX_REQUIREMENT_FILES)
            ->for($order)
            ->create(['uploaded_by' => $customer->getKey()]);

        $this->actingAs($customer)
            ->post(route('customer.orders.requirements', $order), [
                'requirements' => [UploadedFile::fake()->create('tambahan.pdf', 50, 'application/pdf')],
            ])
            ->assertSessionHasErrors('requirements');

        $this->assertSame(
            OrderFile::MAX_REQUIREMENT_FILES,
            $order->requirements()->count(),
        );
    }

    public function test_order_detail_groups_requirement_result_and_revision_files(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->completed()->create();

        OrderFile::factory()->for($order)->requirement()->create(['file_name' => 'contoh.pdf']);
        OrderFile::factory()->for($order)->result()->create(['file_name' => 'hasil.pdf']);
        OrderFile::factory()->for($order)->revision()->create(['file_name' => 'revisi.pdf']);

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSeeInOrder([
                'File dari Kamu',
                'contoh.pdf',
                'File Hasil Kerja',
                'hasil.pdf',
                'File Hasil Revisi',
                'revisi.pdf',
            ]);
    }

    public function test_replying_to_an_information_request_does_not_offer_revision(): void
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
                'requirements' => [UploadedFile::fake()->create('foto.png', 90, 'image/png')],
            ])
            ->assertRedirect();

        $this->assertSame(OrderStatus::InProgress, $order->fresh()->status);

        $this->actingAs($order->user)
            ->get(route('customer.orders.show', $order))
            ->assertOk()
            ->assertSee('File dari Kamu')
            ->assertSee('foto.png')
            ->assertDontSee('Minta Revisi');
    }

    public function test_customer_cannot_request_revision_before_a_result_exists(): void
    {
        $order = Order::factory()->for(User::factory()->customer())->inProgress()->create();

        $this->actingAs($order->user)
            ->from(route('customer.orders.show', $order))
            ->post(route('customer.orders.revision', $order), [
                'revision_note' => 'Tolong perbaiki hasilnya.',
            ])
            ->assertSessionHasErrors([
                'revision_note' => 'Belum ada hasil pekerjaan yang bisa direvisi.',
            ]);

        $this->assertSame(OrderStatus::InProgress, $order->fresh()->status);
        $this->assertDatabaseCount('order_revision_requests', 0);
    }

    public function test_revision_file_is_locked_until_the_work_is_delivered(): void
    {
        Storage::fake('local');

        $order = Order::factory()->for(User::factory()->customer())->inProgress()->create();
        $path = UploadedFile::fake()->create('revisi.pdf', 50, 'application/pdf')->store('orders/test', 'local');
        $file = OrderFile::factory()->for($order)->revision()->create(['file_path' => $path]);

        $this->actingAs($order->user)
            ->get(route('customer.orders.file', [$order, $file]))
            ->assertForbidden();
    }

    private function createDeliverable(Order $order): OrderFile
    {
        return OrderFile::factory()->for($order)->result()->create();
    }
}
