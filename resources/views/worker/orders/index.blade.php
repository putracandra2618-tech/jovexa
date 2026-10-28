@extends('layouts.admin')

@section('title', 'Pesanan Saya')
@section('page-title', 'Pesanan Saya')
@section('page-subtitle', 'Semua pesanan yang sedang kamu kerjakan.')

@section('page-actions')
    <a href="{{ route('worker.queue.index') }}" class="btn-custom btn-custom-primary">
        <i class="bi bi-inbox me-1"></i> Antrean
    </a>
    <a href="{{ route('worker.orders.history') }}" class="btn-custom btn-custom-light">
        <i class="bi bi-clock-history me-1"></i> Riwayat
    </a>
@endsection

@section('content')
    <x-card data-admin-search-results>
        <div class="row g-2 align-items-center mb-3">
            <div class="col-md-4">
                <form method="GET" action="{{ route('worker.orders.index') }}" class="d-flex gap-2">
                    @if ($search !== '')
                        <input type="hidden" name="q" value="{{ $search }}">
                    @endif

                    <select name="status" class="form-select-custom" data-admin-search-select
                            aria-label="Saring berdasarkan status">
                        <option value="">Semua Status</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>
                                {{ $status->label() }}
                            </option>
                        @endforeach
                    </select>

                    <noscript>
                        <button type="submit" class="btn-custom btn-custom-primary btn-custom-sm">Filter</button>
                    </noscript>
                </form>
            </div>

            <div class="col-md-8">
                <x-admin.list-summary
                    :total="$orders->total()"
                    :search="$search"
                    route="worker.orders.index"
                    :filtered="$search !== '' || request()->filled('status')"
                />
            </div>
        </div>

        @if ($orders->isEmpty())
            <x-empty-state
                icon="bi-briefcase"
                title="Belum ada pesanan"
                description="Pesanan yang kamu ambil dari antrean akan muncul di sini."
            >
                <x-slot:actions>
                    <a href="{{ route('worker.queue.index') }}" class="btn-custom btn-custom-primary">
                        Buka Antrean
                    </a>
                </x-slot:actions>
            </x-empty-state>
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
                                <td>
                                    <div>{{ $order->user->name }}</div>
                                    <small class="text-muted">{{ $order->user->email }}</small>
                                </td>
                                <td>{{ $order->service->name }}</td>
                                <td>
                                    <x-badge :variant="$order->status->badgeClass()">
                                        {{ $order->status->label() }}
                                    </x-badge>
                                </td>
                                <td>{{ $order->worker_claimed_at?->format('d M Y H:i') ?? '—' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('worker.orders.show', $order) }}"
                                       class="btn-custom btn-custom-edit btn-custom-sm">
                                        Kerjakan
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
