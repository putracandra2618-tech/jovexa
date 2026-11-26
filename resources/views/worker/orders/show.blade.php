@php
    use App\Enums\OrderStatus;

    $actions = app(\App\Support\OrderActions::class)->for($order, auth()->user());
@endphp

@extends('layouts.admin')

@section('title', 'Pesanan '.$order->order_number)
@section('page-title', 'Pesanan '.$order->order_number)
@section('page-subtitle', $order->service->name.' · '.$order->user->name)

@section('page-actions')
    <a href="{{ route('worker.orders.index') }}" class="btn-custom btn-custom-light">
        <i class="bi bi-arrow-left me-1"></i> Kembali
    </a>
@endsection

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
                               route="worker.orders.file"
                               variant="staff"
                               empty-message="Customer belum melampirkan file." />

            <x-order-file-list title="File Hasil Kerja"
                               :files="$order->results"
                               :order="$order"
                               label="Hasil"
                               route="worker.orders.file"
                               variant="staff"
                               :deletable="true"
                               empty-message="Belum ada file hasil kerja." />

            <x-order-file-list title="File Hasil Revisi"
                               :files="$order->revisions"
                               :order="$order"
                               label="Revisi"
                               route="worker.orders.file"
                               variant="staff"
                               :deletable="true"
                               empty-message="Belum ada file hasil revisi." />

            @if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Revision], true))
                <x-card title="{{ $order->status === OrderStatus::Revision ? 'Unggah Hasil Revisi' : 'Unggah File Hasil' }}" class="mt-4">
                    <div id="unggah-hasil">
                        <form method="POST" action="{{ route('worker.orders.result.store', $order) }}"
                              enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <input type="file" name="result" class="form-control" required>
                            </div>
                            <button type="submit" class="btn-custom btn-custom-primary">
                                <i class="bi bi-cloud-arrow-up me-1"></i> Unggah
                            </button>
                        </form>
                    </div>
                </x-card>
            @endif

            <x-revision-panel :order="$order" />

            <x-card title="Riwayat Status" class="mt-4">
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

                @if ($order->status === OrderStatus::WaitingCustomer)
                    <x-alert variant="info" class="mt-4">
                        Menunggu customer mengirim informasi tambahan. Pesanan akan kembali otomatis
                        ke daftar kerjamu setelah customer mengunggah file.
                    </x-alert>
                @endif

                @if ($actions === [])
                    <x-alert variant="info" class="mt-4">
                        @if ($order->status === OrderStatus::Delivered)
                            Pesanan sudah dikirim ke customer. Terima kasih.
                        @elseif ($order->status === OrderStatus::Cancelled)
                            Pesanan ini sudah dibatalkan.
                        @else
                            Tidak ada aksi yang bisa dilakukan pada status ini.
                        @endif
                    </x-alert>
                @endif

                <x-order-actions :actions="$actions" />
            </x-card>

            <x-card title="Data Pesanan" class="mt-4">
                <dl class="mb-0">
                    <dt class="text-muted fw-normal small">Customer</dt>
                    <dd>{{ $order->user->name }}<br><small class="text-muted">{{ $order->user->email }}</small></dd>

                    <dt class="text-muted fw-normal small">Total</dt>
                    <dd>Rp {{ number_format($order->total_price, 0, ',', '.') }}</dd>

                    <dt class="text-muted fw-normal small">Diambil</dt>
                    <dd class="mb-0">{{ $order->worker_claimed_at?->format('d M Y H:i') ?? '—' }}</dd>
                </dl>
            </x-card>
        </div>
    </div>
@endsection
