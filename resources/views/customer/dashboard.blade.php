@extends('layouts.customer')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard Saya')
@section('page-subtitle', 'Pantau status pesanan yang sedang berjalan.')

@section('page-actions')
    <a href="{{ route('services.index') }}" class="btn-custom btn-custom-primary">
        <i class="bi bi-plus-circle me-1"></i> Buat Pesanan
    </a>
@endsection

@section('content')
    <div class="row g-4">
        <div class="col-md-4">
            <x-stat-card label="Pesanan Aktif" :value="$activeCount" icon="bi-hourglass-split"
                         :href="route('customer.orders.index')" />
        </div>

        <div class="col-md-4">
            <x-stat-card label="Menunggu Pembayaran" :value="$pendingPaymentCount" icon="bi-wallet2"
                         :href="route('customer.orders.index')" />
        </div>

        <div class="col-md-4">
            <x-stat-card label="Pesanan Selesai" :value="$deliveredCount" icon="bi-check2-circle"
                         :href="route('customer.orders.index')" />
        </div>
    </div>

    <x-card title="Pesanan Terbaru" class="mt-4">
        @if ($recentOrders->isEmpty())
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
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Jasa</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($recentOrders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>{{ $order->service->name }}</td>
                                <td><x-badge :variant="$order->status->badgeClass()">{{ $order->status->label() }}</x-badge></td>
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
        @endif
    </x-card>
@endsection
