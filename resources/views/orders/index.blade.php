@extends('layouts.customer')

@section('title', 'Pesanan Saya')
@section('page-title', 'Pesanan Saya')
@section('page-subtitle', 'Semua pesanan yang pernah kamu buat beserta status pengerjaannya.')

@section('page-actions')
    <a href="{{ route('services.index') }}" class="btn-custom btn-custom-primary">
        <i class="bi bi-plus-lg"></i> Buat Pesanan
    </a>
@endsection

@section('content')
    <x-card>
        @if ($orders->isEmpty())
            <x-empty-state
                icon="bi-bag"
                title="Belum ada pesanan"
                description="Pesanan yang kamu buat akan tampil di sini beserta status pengerjaannya."
            >
                <x-slot:actions>
                    <a href="{{ route('services.index') }}" class="btn-custom btn-custom-primary">
                        Lihat Katalog
                    </a>
                </x-slot:actions>
            </x-empty-state>
        @else
            <div class="jx-status-tabs mb-4" data-status-filter-group data-status-filter-target="[data-orders-table]">
                <button type="button" class="jx-status-tab is-active" data-status-filter="all" aria-pressed="true">
                    Semua
                </button>
                <button type="button" class="jx-status-tab" data-status-filter="pending" aria-pressed="false">
                    Berjalan
                </button>
                <button type="button" class="jx-status-tab" data-status-filter="success" aria-pressed="false">
                    Selesai
                </button>
                <button type="button" class="jx-status-tab" data-status-filter="failed" aria-pressed="false">
                    Perlu tindakan
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0" data-orders-table>
                    <thead>
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Jasa</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Dibuat</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr data-order-status="{{ $order->status->badgeClass() }}">
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>
                                    <a href="{{ route('services.show', $order->service) }}">{{ $order->service->name }}</a>
                                </td>
                                <td class="fw-semibold">{{ 'Rp'.number_format($order->total_price, 0, ',', '.') }}</td>
                                <td><x-badge :variant="$order->status->badgeClass()">{{ $order->status->label() }}</x-badge></td>
                                <td class="text-muted">{{ $order->created_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('customer.orders.show', $order) }}"
                                       class="btn-custom btn-custom-light btn-custom-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="d-none" data-status-filter-empty>
                <x-empty-state
                    icon="bi-funnel"
                    title="Tidak ada pesanan di kategori ini"
                    description="Pilih tab lain untuk melihat pesanan dengan status berbeda."
                />
            </div>

            @if ($orders->hasPages())
                <div class="mt-4 d-flex justify-content-center">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
