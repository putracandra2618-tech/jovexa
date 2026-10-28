@php
    use App\Enums\OrderStatus;
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

                                <a href="{{ route('worker.orders.file', [$order, $file]) }}"
                                   class="btn-custom btn-custom-light btn-custom-sm">
                                    <i class="bi bi-download me-1"></i> Unduh
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>

            <x-card title="Riwayat Status" class="mt-4">
                <ul class="list-unstyled mb-0">
                    @foreach ($order->statusHistory->sortByDesc('created_at') as $entry)
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
            <x-card title="Status">
                <x-badge :variant="$order->status->badgeClass()">{{ $order->status->label() }}</x-badge>

                @if ($order->status === OrderStatus::WaitingCustomer)
                    <x-alert variant="info" class="mt-4">
                        Menunggu customer mengirim informasi tambahan. Pesanan akan kembali otomatis
                        ke daftar kerjamu setelah customer mengunggah file.
                    </x-alert>
                @endif

                @if (in_array($order->status, [OrderStatus::InProgress, OrderStatus::Revision], strict: true))
                    <form method="POST" action="{{ route('worker.orders.status', $order) }}" class="mt-4"
                          data-confirm="Tandai pekerjaan selesai?">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ OrderStatus::Completed->value }}">

                        <x-form.textarea
                            name="note"
                            label="Catatan (opsional)"
                            rows="2"
                            placeholder="Contoh: Pekerjaan sudah selesai dan siap dikirim."
                        />

                        <button type="submit" class="btn-custom btn-custom-primary w-100">
                            <i class="bi bi-check2-circle me-1"></i> Tandai Selesai
                        </button>
                    </form>

                    @if ($order->status->canTransitionTo(OrderStatus::WaitingCustomer))
                        <form method="POST" action="{{ route('worker.orders.information', $order) }}" class="mt-3">
                            @csrf

                            <x-form.textarea
                                name="note"
                                label="Minta Informasi ke Customer"
                                rows="3"
                                required
                                placeholder="Contoh: Mohon kirim data lengkap bab 1."
                            />

                            <button type="submit" class="btn-custom btn-custom-light w-100">
                                <i class="bi bi-chat-left-dots me-1"></i> Minta Informasi
                            </button>
                        </form>
                    @endif
                @endif

                @if ($order->status === OrderStatus::Completed)
                    <a href="{{ route('worker.orders.deliver', $order) }}"
                       class="btn-custom btn-custom-primary w-100 mt-2">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Unggah Hasil
                    </a>

                    <form method="POST" action="{{ route('worker.orders.send', $order) }}" class="mt-2"
                          data-confirm="Kirim hasil pekerjaan ke customer?">
                        @csrf
                        <button type="submit" class="btn-custom btn-custom-primary w-100">
                            <i class="bi bi-send me-1"></i> Kirim ke Customer
                        </button>
                    </form>
                @endif

                @if ($order->status === OrderStatus::Delivered)
                    <x-alert variant="success" class="mt-4">
                        Pesanan sudah dikirim ke customer. Terima kasih.
                    </x-alert>
                @endif

                @if ($order->status === OrderStatus::Cancelled)
                    <x-alert variant="danger" class="mt-4">
                        Pesanan ini sudah dibatalkan.
                    </x-alert>
                @endif
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
