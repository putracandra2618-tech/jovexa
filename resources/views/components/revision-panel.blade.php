@props(['order'])

@php
    $requests = $order->revisionRequests->sortByDesc('created_at')->sortByDesc('id');
    $used = $requests->count();
    $isCustomerView = auth()->user()?->isCustomer() === true;
@endphp

@if ($used > 0 || $order->status === \App\Enums\OrderStatus::Revision)
    <x-card title="Permintaan Revisi" class="mt-4">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <x-badge :variant="$used >= \App\Services\RevisionService::MAX_PER_ORDER ? 'failed' : 'pending'">
                Revisi {{ $used }}/{{ \App\Services\RevisionService::MAX_PER_ORDER }}
            </x-badge>
            <small class="text-muted">
                {{ $isCustomerView ? 'Catatan yang kamu kirim' : 'Catatan permintaan revisi' }}
            </small>
        </div>

        @if ($requests->isEmpty())
            <p class="page-subtitle mb-0">Belum ada catatan revisi yang tercatat untuk pesanan ini.</p>
        @else
            <ul class="list-unstyled mb-0">
                @foreach ($requests as $request)
                    <li class="py-2 border-bottom">
                        <div class="fw-semibold small">
                            Revisi ke-{{ $request->revision_number }}
                            &middot; {{ $request->requester?->name ?? 'Sistem' }}
                        </div>
                        <div class="small mt-1" style="white-space: pre-line">{{ $request->clean_note }}</div>
                        <small class="text-muted">{{ \App\Support\DisplayTime::format($request->created_at) }}</small>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($order->status === \App\Enums\OrderStatus::Completed)
            <x-alert variant="success" class="mt-3">
                Pesanan sudah diterima customer dan dinyatakan selesai. Revisi tidak bisa diajukan lagi.
            </x-alert>
        @elseif ($used >= \App\Services\RevisionService::MAX_PER_ORDER)
            <x-alert variant="warning" class="mt-3">
                Kuota revisi sudah habis ({{ $used }}/{{ \App\Services\RevisionService::MAX_PER_ORDER }}).
                {{ $isCustomerView ? 'Silakan hubungi tim Jovexa jika masih ada kendala.' : 'Kirim pesanan ini atau hubungi customer untuk kesepakatan berikutnya.' }}
            </x-alert>
        @elseif ($order->status === \App\Enums\OrderStatus::Revision)
            <x-alert variant="info" class="mt-3">
                {{ $isCustomerView
                    ? 'Menunggu tim kami memperbaiki hasil sesuai catatanmu.'
                    : 'Perbaiki sesuai catatan, unggah hasil baru, lalu setujui revisi.' }}
            </x-alert>
        @endif
    </x-card>
@endif
