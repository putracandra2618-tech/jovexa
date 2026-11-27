@extends('layouts.admin')

@section('title', 'Daftar Pesanan')
@section('page-title', 'Daftar Pesanan')
@section('page-subtitle', 'Semua pesanan yang masuk, dari verifikasi hingga pengiriman.')

@section('page-actions')
    <a href="{{ route('admin.orders.revision') }}" class="btn-custom btn-custom-light">
        <i class="bi bi-arrow-repeat me-1"></i> Revisi
    </a>
    <a href="{{ route('admin.orders.history') }}" class="btn-custom btn-custom-light">
        <i class="bi bi-clock-history me-1"></i> Riwayat
    </a>
@endsection

@section('content')
    <x-card data-admin-search-results>
        <div class="row g-2 align-items-center mb-3">
            <div class="col-md-4">
                <form method="GET" action="{{ route('admin.orders.index') }}" class="d-flex gap-2">
                    @if ($search !== '')
                        <input type="hidden" name="q" value="{{ $search }}">
                    @endif

                    @if ($activeCategory !== 'all')
                        <input type="hidden" name="category" value="{{ $activeCategory }}">
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
                    route="admin.orders.index"
                    :filtered="$search !== '' || request()->filled('status') || $activeCategory !== 'all'"
                />
            </div>
        </div>

        @if ($orders->isEmpty())
            @if ($search !== '')
                <x-empty-state
                    icon="bi-search"
                    title="Pesanan tidak ditemukan"
                    description="Tidak ada pesanan yang cocok dengan kata kunci tersebut."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.orders.index') }}" class="btn-custom btn-custom-primary">
                            Lihat semua pesanan
                        </a>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="bi-briefcase"
                    title="Belum ada pesanan"
                    description="Pesanan yang dibuat customer akan muncul di daftar ini."
                />
            @endif
        @else
            <div class="jx-status-tabs mb-4">
                @foreach ($categories as $categoryKey => $categoryLabel)
                    @php
                        $tabQuery = array_filter([
                            'category' => $categoryKey,
                            'q' => $search,
                            'status' => request('status'),
                        ], fn ($value): bool => $value !== null && $value !== '');
                    @endphp

                    <a href="{{ route('admin.orders.index', $tabQuery) }}"
                       class="jx-status-tab {{ $activeCategory === $categoryKey ? 'is-active' : '' }}"
                       aria-pressed="{{ $activeCategory === $categoryKey ? 'true' : 'false' }}">
                        {{ $categoryLabel }}
                    </a>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nomor Pesanan</th>
                            <th>Customer</th>
                            <th>Jasa</th>
                            <th>Worker</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $currentStage = null; @endphp
                        @foreach ($orders as $order)
                            @php $stage = $order->status->stage(); @endphp
                            @if ($stage !== $currentStage)
                                <tr class="table-light" data-status-group-row="{{ $stage }}">
                                    <th colspan="7">{{ $order->status->stageLabel() }}</th>
                                </tr>
                                @php $currentStage = $stage; @endphp
                            @endif
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>
                                    <div>{{ $order->user->name }}</div>
                                    <small class="text-muted">{{ $order->user->email }}</small>
                                </td>
                                <td>{{ $order->service->name }}</td>
                                <td>
                                    @if ($order->worker)
                                        {{ $order->worker->name }}
                                    @else
                                        <span class="text-muted">Belum diambil</span>
                                    @endif
                                </td>
                                <td class="text-nowrap">Rp {{ number_format($order->total_price, 0, ',', '.') }}</td>
                                <td><x-badge :variant="$order->status->badgeClass()">{{ $order->status->label() }}</x-badge></td>
                                <td class="text-end">
                                    <a href="{{ route('admin.orders.show', $order) }}"
                                       class="btn-custom btn-custom-edit btn-custom-sm">
                                        Kelola
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
