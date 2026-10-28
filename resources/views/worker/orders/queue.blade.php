@extends('layouts.admin')

@section('title', 'Antrean Pesanan')
@section('page-title', 'Antrean Pesanan')
@section('page-subtitle', 'Pesanan yang sudah diverifikasi Admin dan siap diambil worker.')

@section('page-actions')
    <a href="{{ route('worker.orders.index') }}" class="btn-custom btn-custom-light">
        <i class="bi bi-briefcase me-1"></i> Pesanan Saya
    </a>
@endsection

@section('content')
    @if (! $isOnline)
        <x-alert variant="info">
            Aktifkan status online di dashboard sebelum mengambil pesanan dari antrean.
        </x-alert>
    @elseif ($activeCount >= $maxActiveOrders)
        <x-alert variant="warning">
            Kuota pesanan aktif sudah penuh ({{ $activeCount }}/{{ $maxActiveOrders }}).
            Selesaikan salah satu pesanan terlebih dahulu.
        </x-alert>
    @else
        <x-alert variant="success">
            Kamu online dengan kuota {{ $activeCount }}/{{ $maxActiveOrders }}. Pilih pesanan yang siap dikerjakan.
        </x-alert>
    @endif

    <x-card data-admin-search-results class="mt-4">
        @if ($orders->isEmpty())
            @if ($search !== '')
                <x-empty-state
                    icon="bi-search"
                    title="Pesanan tidak ditemukan"
                    description="Tidak ada pesanan di antrean yang cocok dengan kata kunci tersebut."
                >
                    <x-slot:actions>
                        <a href="{{ route('worker.queue.index') }}" class="btn-custom btn-custom-primary">
                            Lihat semua antrean
                        </a>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="bi-inbox"
                    title="Antrean sedang kosong"
                    description="Pesanan baru akan muncul di sini setelah Admin memverifikasi pembayaran."
                />
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Jasa</th>
                            <th>Kebutuhan</th>
                            <th>Total</th>
                            <th>Masuk</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>
                                    <div>{{ $order->service->name }}</div>
                                    <small class="text-muted">{{ $order->user->name }}</small>
                                </td>
                                <td style="max-width: 22rem">
                                    <span class="d-inline-block text-truncate" style="max-width: 22rem">
                                        {{ $order->description }}
                                    </span>
                                </td>
                                <td class="text-nowrap">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                                <td>{{ $order->created_at->format('d M Y H:i') }}</td>
                                <td class="text-end">
                                    <form method="POST" action="{{ route('worker.orders.claim', $order) }}"
                                          data-confirm="Ambil pesanan {{ $order->order_number }}? Kuota aktif akan bertambah.">
                                        @csrf
                                        <button type="submit" class="btn-custom btn-custom-primary btn-custom-sm"
                                                @disabled(! $isOnline || $activeCount >= $maxActiveOrders)>
                                            <i class="bi bi-hand-index-thumb me-1"></i> Ambil
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
