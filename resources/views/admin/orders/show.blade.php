@php
    use App\Enums\OrderStatus;

    $nextStatus = $order->status->next();
    $canAdvance = $nextStatus !== null
        && $nextStatus->isSettableByAdmin()
        && ! in_array($order->status, [OrderStatus::Queued, OrderStatus::WaitingCustomer], strict: true);
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

                                <a href="{{ route('admin.orders.file', [$order, $file]) }}"
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

                @if ($canAdvance)
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-4">
                        @csrf
                        @method('PATCH')

                        <input type="hidden" name="status" value="{{ $nextStatus->value }}">

                        <x-form.textarea
                            name="note"
                            label="Catatan (opsional)"
                            rows="2"
                            placeholder="Contoh: Pembayaran sudah dicek, lanjut dikerjakan."
                        />

                        <button type="submit" class="btn-custom btn-custom-primary w-100"
                                data-confirm="Ubah status menjadi {{ $nextStatus->label() }}?">
                            @if ($order->status === OrderStatus::WaitingVerification)
                                <i class="bi bi-inboxes me-1"></i> Lepas ke Antrean Worker
                            @else
                                Lanjut ke {{ $nextStatus->label() }}
                            @endif
                        </button>
                    </form>
                @elseif ($nextStatus !== null && ! in_array($order->status, [OrderStatus::Queued, OrderStatus::WaitingCustomer], strict: true))
                    <x-alert variant="info" class="mt-4">
                        {{ $nextStatus->label() }} hanya bisa diperbarui otomatis oleh sistem
                        @if ($nextStatus === OrderStatus::Paid)
                            setelah webhook pembayaran Tripay diterima
                        @else
                            lewat aksi khusus
                        @endif
                        , jadi tombol manual tidak ditampilkan.
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
                        Pesanan akan kembali otomatis ke status dikerjakan setelah customer mengunggah file.
                    </x-alert>
                @endif

                @if ($order->status === OrderStatus::WaitingVerification)
                    <form method="POST" action="{{ route('admin.orders.information', $order) }}" class="mt-4">
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

                @if ($order->status === OrderStatus::WaitingCustomer && ! $order->worker)
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ OrderStatus::WaitingVerification->value }}">

                        <button type="submit" class="btn-custom btn-custom-light w-100">
                            Informasi Sudah Diterima
                        </button>
                    </form>
                @endif

                @if ($order->status === OrderStatus::Revision)
                    <x-alert variant="info">
                        Customer sudah kirim revisi. Setelah diterima, tandai pekerjaan sebagai selesai
                        lalu lanjut ke proses kirim hasil.
                    </x-alert>

                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-4"
                          data-confirm="Tandai pekerjaan selesai?">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ OrderStatus::Completed->value }}">

                        <x-form.textarea
                            name="note"
                            label="Catatan (opsional)"
                            rows="2"
                            placeholder="Contoh: Revisi sudah incorporated."
                        />

                        <button type="submit" class="btn-custom btn-custom-primary w-100">
                            <i class="bi bi-check2-circle me-1"></i> Revisi Diterima, Selesai
                        </button>
                    </form>
                @endif

                @if (in_array($order->status, [OrderStatus::InProgress, OrderStatus::Completed], strict: true))
                    <form method="POST" action="{{ route('admin.orders.status', $order) }}" class="mt-2"
                          data-confirm="Kembalikan pesanan ke customer untuk revisi?">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ OrderStatus::Revision->value }}">
                        <button type="submit" class="btn-custom btn-custom-light w-100">
                            <i class="bi bi-arrow-repeat me-1"></i> Minta Revisi
                        </button>
                    </form>
                @endif

                @if ($order->status === OrderStatus::Completed)
                    <a href="{{ route('admin.orders.deliver', $order) }}" class="btn-custom btn-custom-primary w-100 mt-2">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Unggah Hasil
                    </a>
                @endif

                @if ($order->status === OrderStatus::Completed)
                    <form method="POST" action="{{ route('admin.orders.send', $order) }}" class="mt-2"
                          data-confirm="Kirim hasil pekerjaan ke customer?">
                        @csrf
                        <button type="submit" class="btn-custom btn-custom-primary w-100">
                            <i class="bi bi-send me-1"></i> Kirim ke Customer
                        </button>
                    </form>
                @endif

                @unless ($order->status->isFinal())
                    <form method="POST" action="{{ route('admin.orders.cancel', $order) }}" class="mt-3"
                          data-confirm="Batalkan pesanan {{ $order->order_number }}?">
                        @csrf
                        <button type="submit" class="btn-custom btn-custom-danger w-100">Batalkan Pesanan</button>
                    </form>
                @endunless
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
