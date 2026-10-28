@extends('layouts.admin')

@section('title', 'Riwayat Pesanan')
@section('page-title', 'Riwayat Pesanan')
@section('page-subtitle', 'Pesanan yang sudah selesai dikirim atau dibatalkan.')

@section('content')
    <x-card data-admin-search-results>
        <div class="mb-3">
            <x-admin.list-summary
                :total="$orders->total()"
                :search="$search"
                route="admin.orders.history"
                :filtered="$search !== ''"
            />
        </div>

        @if ($orders->isEmpty())
            @if ($search !== '')
                <x-empty-state
                    icon="bi-search"
                    title="Riwayat tidak ditemukan"
                    description="Tidak ada pesanan yang cocok dengan kata kunci tersebut."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.orders.history') }}" class="btn-custom btn-custom-primary">
                            Lihat semua riwayat
                        </a>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="bi-clock-history"
                    title="Belum ada riwayat"
                    description="Pesanan yang sudah dikirim atau dibatalkan akan tercatat di sini."
                />
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Customer</th>
                            <th>Jasa</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Selesai</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>{{ $order->user->name }}</td>
                                <td>{{ $order->service->name }}</td>
                                <td class="text-nowrap">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                                <td><x-badge :variant="$order->status->badgeClass()">{{ $order->status->label() }}</x-badge></td>
                                <td>{{ $order->updated_at->format('d M Y') }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="btn-custom btn-custom-light btn-custom-sm">
                                        Detail
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($orders->hasPages())
                <div class="card-footer bg-transparent border-0 pt-3">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
