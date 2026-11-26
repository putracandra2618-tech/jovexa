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

            <x-card title="File" class="mt-4">
                @if ($order->files->isEmpty())
                    <p class="page-subtitle mb-0">Customer belum melampirkan file.</p>
                @else
                    <ul class="list-unstyled mb-0">
                        @foreach ($order->files as $file)
                            <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi {{ $file->icon() }} fs-5 text-muted"></i>
                                    <div>
                                        <div class="fw-semibold">{{ $file->file_name }}</div>
                                        <small class="text-muted">
                                            {{ $file->isResult() ? 'Hasil' : 'Pendukung' }} &middot; {{ $file->readableSize() }}
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="{{ route('worker.orders.file', [$order, $file]) }}"
                                       class="btn-custom btn-custom-light btn-custom-sm">
                                        <i class="bi bi-download me-1"></i> Unduh
                                    </a>
                                    @if ($file->isResult() && $order->status === OrderStatus::Completed)
                                        <form method="POST" action="{{ route('worker.orders.file.destroy', [$order, $file]) }}"
                                              data-confirm="Yakin ingin menghapus file ini? File yang dihapus tidak dapat dikembalikan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm">
                                                <i class="bi bi-trash me-1"></i> Hapus
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (in_array($order->status, [OrderStatus::Completed, OrderStatus::Revision], true))
                    <hr class="my-4" id="unggah-hasil">
                    <h5 class="mb-3">
                        {{ $order->status === OrderStatus::Revision ? 'Unggah Hasil Revisi' : 'Unggah File Hasil' }}
                    </h5>
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
                @endif
            </x-card>

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
