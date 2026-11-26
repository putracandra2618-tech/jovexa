@php
    $actions = app(\App\Support\OrderActions::class)->for($order, auth()->user());
@endphp

@extends('layouts.customer')

@section('title', 'Pesanan '.$order->order_number)
@section('page-title', 'Pesanan '.$order->order_number)
@section('page-subtitle', $order->service->name)

@section('page-actions')
    <button type="button" class="btn-custom btn-custom-light"
            data-copy="{{ $order->order_number }}"
            data-copy-message="Nomor pesanan tersalin.">
        <i class="bi bi-clipboard"></i> Salin Nomor
    </button>
@endsection

@section('content')
    @php
        $steps = \App\Enums\OrderStatus::customerSteps();
        $currentStep = $order->status->customerStepIndex();
    @endphp

    <div class="jx-stepper-card">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
            <span class="d-flex align-items-center gap-2">
                <i class="bi bi-activity"></i>
                Progres Pengerjaan
            </span>
            <x-badge :variant="$order->status->badgeClass()">{{ $order->status->label() }}</x-badge>
        </div>

        <ol class="jx-stepper" data-stepper>
            @foreach ($steps as $index => $step)
                @php
                    $state = $currentStep === -1
                        ? 'muted'
                        : match (true) {
                            $index < $currentStep => 'done',
                            $index === $currentStep => 'current',
                            default => 'upcoming',
                        };
                @endphp

                <li class="jx-stepper-item {{ $state }}">
                    <span class="jx-stepper-dot">
                        @if ($state === 'done')
                            <i class="bi bi-check-lg"></i>
                        @else
                            <i class="bi {{ $step['icon'] }}"></i>
                        @endif
                    </span>
                    <span class="jx-stepper-label">{{ $step['label'] }}</span>
                </li>
            @endforeach
        </ol>
    </div>

    <div class="row g-4">
        <div class="col-lg-8">
            <x-card title="Status Pengerjaan" data-turbo-cache="false">
                @php
                    $informationRequest = $order->statusHistory
                        ->filter(fn ($entry) => $entry->to_status === \App\Enums\OrderStatus::WaitingCustomer && filled($entry->note))
                        ->sortByDesc('id')
                        ->first();
                @endphp

                @if ($order->status === \App\Enums\OrderStatus::WaitingVerification)
                    <x-alert variant="warning">
                        Pesanan anda sedang di tinjau admin. Setelah disetujui, kamu bisa melanjutkan pembayaran.
                    </x-alert>
                @elseif ($order->isPayable())
                    <p class="page-subtitle mb-0">
                        Pesanan ini belum dibayar, jadi belum masuk ke antrean pengerjaan.
                    </p>
                @elseif ($order->status === \App\Enums\OrderStatus::WaitingCustomer)
                    <p class="page-subtitle mb-0">
                        Tim Jovexa butuh informasi tambahan darimu. Unggah file pendukung agar
                        pengerjaan bisa dilanjutkan.
                    </p>

                    @if ($informationRequest)
                        <x-alert variant="warning" class="mt-3">
                            <strong>Pesan dari tim Jovexa:</strong><br>
                            <span style="white-space: pre-line">{{ $informationRequest->note }}</span>
                        </x-alert>
                    @endif
                @elseif ($order->canDownloadResults())
                    <div class="jx-result-banner">
                        <span class="jx-result-icon">
                            <i class="bi bi-check2-circle"></i>
                        </span>
                        <div>
                            <strong>Hasil pengerjaan sudah siap</strong>
                            <p class="mb-0">Unduh file hasil pengerjaan di daftar lampiran di bawah.</p>
                        </div>
                    </div>
                @elseif ($actions === [])
                    <p class="page-subtitle mb-0">
                        Kamu akan mendapat pembaruan status di halaman ini setiap ada progres.
                    </p>
                @endif

                <x-order-actions :actions="$actions" />
            </x-card>

            <x-revision-panel :order="$order" />

            <x-card title="Kebutuhan Kamu" class="mt-4">
                <p class="jx-prose mb-0">{{ $order->description }}</p>
            </x-card>

            <x-card title="File" class="mt-4">
                @if ($order->files->isEmpty())
                    <p class="page-subtitle mb-0">Belum ada file yang dilampirkan.</p>
                @else
                    <ul class="jx-file-list">
                        @foreach ($order->files as $file)
                            <li class="jx-file-item">
                                <span class="jx-file-icon">
                                    <i class="bi {{ $file->icon() }}"></i>
                                </span>

                                <div class="jx-file-info">
                                    <div class="jx-file-name">{{ $file->file_name }}</div>
                                    <small class="text-muted">
                                        {{ $file->isResult() ? 'Hasil' : 'Pendukung' }} &middot; {{ $file->readableSize() }}
                                    </small>
                                </div>

                                @if (! $file->isResult() || $order->canDownloadResults())
                                    <a href="{{ route('customer.orders.file', [$order, $file]) }}"
                                       class="btn-custom btn-custom-light btn-custom-sm">
                                        <i class="bi bi-download"></i> Unduh
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>

        <div class="col-lg-4">
            <div class="jx-order-card">
                <h2 class="card-title mb-3">Ringkasan</h2>

                <dl class="jx-summary">
                    <dt>Jasa</dt>
                    <dd>{{ $order->service->name }}</dd>

                    <dt>Total</dt>
                    <dd class="fw-bold">{{ 'Rp'.number_format($order->total_price, 0, ',', '.') }}</dd>

                    <dt>Dibuat</dt>
                    <dd>{{ $order->created_at->format('d M Y H:i') }}</dd>

                    @if ($order->latestPayment)
                        <dt>Pembayaran</dt>
                        <dd>
                            <x-badge :variant="$order->latestPayment->status->badgeClass()">
                                {{ $order->latestPayment->status->label() }}
                            </x-badge>
                        </dd>
                    @endif
                </dl>

                <a href="{{ route('services.show', $order->service) }}" class="btn-custom btn-custom-light w-100">
                    <i class="bi bi-box-seam"></i> Lihat Detail Jasa
                </a>
            </div>

            <x-card title="Riwayat Status" class="mt-4">
                @if ($order->statusHistory->isEmpty())
                    <p class="page-subtitle mb-0">Belum ada riwayat.</p>
                @else
                    <ul class="jx-timeline">
                        @foreach ($order->statusHistory->sortByDesc('created_at')->sortByDesc('id') as $entry)
                            <li class="jx-timeline-item">
                                <span class="jx-timeline-dot"></span>
                                <div>
                                    <div class="fw-semibold">
                                        {{ $entry->from_status?->label() ?? 'Pesanan dibuat' }}
                                        &rarr; {{ $entry->to_status->label() }}
                                    </div>
                                    <small class="text-muted">
                                        {{ $entry->created_at->format('d M Y H:i') }}
                                        @if ($entry->author) &middot; {{ $entry->author->name }} @endif
                                    </small>
                                    @if ($entry->note)
                                        <div class="small mt-1">{{ $entry->note }}</div>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-card>
        </div>
    </div>
@endsection
