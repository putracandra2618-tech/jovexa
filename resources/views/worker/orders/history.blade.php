@extends('layouts.admin')

@section('title', 'Riwayat Pesanan Saya')
@section('page-title', 'Riwayat Pesanan Saya')
@section('page-subtitle', 'Pesanan yang sudah selesai kamu tangani.')

@section('page-actions')
    <a href="{{ route('worker.orders.index') }}" class="btn-custom btn-custom-light">
        <i class="bi bi-briefcase me-1"></i> Pesanan Saya
    </a>
@endsection

@section('content')
    <x-card data-admin-search-results>
        @if ($orders->isEmpty())
            <x-empty-state
                icon="bi-clock-history"
                title="Belum ada riwayat"
                description="Pesanan yang sudah dikirim atau dibatalkan akan tercatat di sini."
            />
        @else
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Customer</th>
                            <th>Jasa</th>
                            <th>Status</th>
                            <th>Diambil</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($orders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>{{ $order->user->name }}</td>
                                <td>{{ $order->service->name }}</td>
                                <td>
                                    <x-badge :variant="$order->status->badgeClass()">
                                        {{ $order->status->label() }}
                                    </x-badge>
                                </td>
                                <td>{{ $order->worker_claimed_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('worker.orders.show', $order) }}"
                                       class="btn-custom btn-custom-light btn-custom-sm">
                                        Lihat
                                    </a>
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
