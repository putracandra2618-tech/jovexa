@extends('layouts.admin')

@section('title', 'Pesanan Revisi')
@section('page-title', 'Pesanan Revisi')
@section('page-subtitle', 'Pesanan yang dikembalikan customer untuk diperbaiki.')

@section('content')
    <x-card data-admin-search-results>
        <div class="mb-3">
            <x-admin.list-summary
                :total="$orders->total()"
                :search="$search"
                route="admin.orders.revision"
                :filtered="$search !== ''"
            />
        </div>

        @if ($orders->isEmpty())
            @if ($search !== '')
                <x-empty-state
                    icon="bi-search"
                    title="Pesanan revisi tidak ditemukan"
                    description="Tidak ada pesanan revisi yang cocok dengan kata kunci tersebut."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.orders.revision') }}" class="btn-custom btn-custom-primary">
                            Lihat semua pesanan revisi
                        </a>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="bi-check2-all"
                    title="Tidak ada pesanan revisi"
                    description="Semua pesanan berjalan progressed sesuai waktu."
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
                            <th>Catatan Terakhir</th>
                            <th>Status</th>
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
                                    @if ($order->latestRevisionRequest)
                                        <span class="badge bg-warning text-dark">
                                            Revisi {{ $order->revision_requests_count }}/{{ \App\Services\RevisionService::MAX_PER_ORDER }}
                                        </span>
                                        <div class="small mt-1" style="max-width: 24rem; white-space: pre-line;">
                                            {{ $order->latestRevisionRequest->clean_note }}
                                        </div>
                                        <small class="text-muted">
                                            {{ $order->latestRevisionRequest->requester?->name ?? 'Sistem' }}
                                            &middot; {{ $order->latestRevisionRequest->created_at->format('d M Y H:i') }}
                                        </small>
                                    @else
                                        <span class="text-muted">Tidak ada catatan</span>
                                    @endif
                                </td>
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
