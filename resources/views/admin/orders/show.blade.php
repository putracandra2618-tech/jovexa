@php
    use App\Enums\OrderStatus;

    $actions = app(\App\Support\OrderActions::class)->for($order, auth()->user());
    $informationReply = $order->latestInformationReply();
    $informationReplyAlreadyViewed ??= false;
@endphp

@extends('layouts.admin')

@section('title', 'Pesanan '.$order->order_number)
@section('page-title', 'Pesanan '.$order->order_number)
@section('page-subtitle', $order->service->name.' · '.$order->user->name)

@section('content')
    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Kebutuhan Customer">
                <p class="mb-0" style="white-space: pre-line">{{ $order->description }}</p>
            </x-card>

            <x-order-file-list title="File dari Customer"
                               :files="$order->requirements"
                               :order="$order"
                               label="Contoh/Info"
                               route="admin.orders.file"
                               variant="staff"
                               empty-message="Customer belum melampirkan file." />

            <x-order-file-list title="File Hasil Kerja"
                               :files="$order->results"
                               :order="$order"
                               label="Hasil"
                               route="admin.orders.file"
                               variant="staff"
                               empty-message="Belum ada file hasil kerja." />

            <x-order-file-list title="File Hasil Revisi"
                               :files="$order->revisions"
                               :order="$order"
                               label="Revisi"
                               route="admin.orders.file"
                               variant="staff"
                               empty-message="Belum ada file hasil revisi." />

            <x-revision-panel :order="$order" />

            <x-card title="Riwayat Status" class="mt-4" data-turbo-cache="false">
                <ul class="list-unstyled mb-0">
                    @foreach ($order->statusHistory->sortByDesc('created_at')->sortByDesc('id') as $entry)
                        <li class="py-2 border-bottom">
                            <div class="fw-semibold">
                                {{ $entry->from_status?->label() ?? 'Pesanan dibuat' }} &rarr; {{ $entry->to_status->label() }}
                            </div>
                            <small class="text-muted">
                                {{ $entry->created_at->format('d M Y H:i') }}
                                @if ($entry->author) &middot; {{ $entry->author->name }} @endif
                            </small>
                            @if ($entry->note)
                                <div class="small mt-1">{{ $entry->note }}</div>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </x-card>
        </div>

        <div class="col-lg-4">
            <x-card title="Status" data-turbo-cache="false">
                <x-badge :variant="$order->status->badgeClass()">{{ $order->status->label() }}</x-badge>

                @if ($informationReply && ! $informationReplyAlreadyViewed)
                    <x-alert variant="success" class="mt-4">
                        <strong>Balasan dari Customer:</strong><br>
                        <span style="white-space: pre-line">{{ $informationReply->informationReplyText() }}</span>
                    </x-alert>
                @endif

                @if ($order->status === OrderStatus::Queued)
                    <x-alert variant="info" class="mt-4">
                        @if ($order->worker)
                            Pesanan sudah diambil worker <strong>{{ $order->worker->name }}</strong>
                            dan sedang dikerjakan.
                        @else
                            Pesanan menunggu diambil worker yang sedang online dan masih punya kuota.
                        @endif
                    </x-alert>
                @endif

                @if ($order->status === OrderStatus::WaitingCustomer && $order->worker)
                    <x-alert variant="info" class="mt-4">
                        Worker <strong>{{ $order->worker->name }}</strong> menunggu informasi dari customer.
                        Pesanan akan kembali otomatis ke status dikerjakan setelah customer mengirim teks atau mengunggah file.
                    </x-alert>
                @endif

                @if ($actions === [] && ! in_array($order->status, [OrderStatus::Queued, OrderStatus::WaitingCustomer], true))
                    <x-alert variant="info" class="mt-4">
                        @if ($order->status === OrderStatus::Delivered)
                            Hasil sudah dikirim ke customer dan menunggu penerimaan.
                        @elseif ($order->status === OrderStatus::Completed)
                            Customer sudah menerima hasil. Pesanan dinyatakan selesai dan revisi dikunci.
                        @elseif ($order->status === OrderStatus::Cancelled)
                            Pesanan ini sudah dibatalkan.
                        @else
                            Tidak ada aksi manual yang tersedia untuk status ini.
                        @endif
                    </x-alert>
                @endif

                <x-order-actions :actions="$actions" />
            </x-card>

            <x-card title="Data Pesanan" class="mt-4">
                <dl class="mb-0">
                    <dt class="text-muted fw-normal small">Customer</dt>
                    <dd>{{ $order->user->name }}<br><small class="text-muted">{{ $order->user->email }}</small></dd>

                    <dt class="text-muted fw-normal small">Worker</dt>
                    <dd>
                        @if ($order->worker)
                            {{ $order->worker->name }}
                            @if ($order->worker_claimed_at)
                                <br><small class="text-muted">Diambil {{ $order->worker_claimed_at->format('d M Y H:i') }}</small>
                            @endif
                        @else
                            <span class="text-muted">Belum diambil</span>
                        @endif
                    </dd>

                    <dt class="text-muted fw-normal small">Total</dt>
                    <dd>Rp {{ number_format($order->total_price, 0, ',', '.') }}</dd>

                    <dt class="text-muted fw-normal small">Dibuat</dt>
                    <dd class="mb-0">{{ $order->created_at->format('d M Y H:i') }}</dd>
                </dl>
            </x-card>
        </div>
    </div>
@endsection
