@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Ringkasan pesanan yang sedang berjalan di Jovexa.')

@section('page-actions')
    <a href="{{ route('admin.orders.index') }}" class="btn-custom btn-custom-primary">
        <i class="bi bi-list-task me-1"></i> Kelola Pesanan
    </a>
@endsection

@section('content')
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
        <div class="col">
            <x-stat-card label="Menunggu Verifikasi" :value="$waitingVerificationCount" icon="bi-inbox"
                         :href="route('admin.orders.index', ['status' => 'waiting_verification'])" />
        </div>

        <div class="col">
            <x-stat-card label="Antrean Worker" :value="$queuedCount" icon="bi-inboxes"
                         :href="route('admin.orders.index', ['status' => 'queued'])" />
        </div>

        <div class="col">
            <x-stat-card label="Sedang Dikerjakan" :value="$inProgressCount" icon="bi-hourglass-split"
                         :href="route('admin.orders.index', ['status' => 'in_progress'])" />
        </div>

        <div class="col">
            <x-stat-card label="Menunggu Info Customer" :value="$waitingCustomerCount" icon="bi-chat-left-dots"
                         :href="route('admin.orders.index', ['status' => 'waiting_customer'])" />
        </div>
    </div>

    <div class="row row-cols-1 row-cols-md-3 g-3 mt-1">
        <div class="col">
            <x-stat-card label="Perlu Revisi" :value="$revisionCount" icon="bi-arrow-repeat"
                         :href="route('admin.orders.revision')" />
        </div>

        <div class="col">
            <x-stat-card label="Pendapatan Selesai" value="Rp {{ number_format($revenue, 0, ',', '.') }}"
                         icon="bi-cash-stack" />
        </div>

        <div class="col">
            <x-stat-card label="Jasa Tayang" :value="$serviceCount" icon="bi-box-seam"
                         :href="route('admin.services.index')" />
        </div>
    </div>

    <x-card title="Pesanan Perlu Perhatian" class="mt-4">
        @if ($newOrders->isEmpty())
            <x-empty-state
                icon="bi-check2-all"
                title="Tidak ada pesanan menunggu"
                description="Semua pesanan sudah ditangani. Cek daftar pesanan untuk melihat arsip."
            >
                <x-slot:actions>
                    <a href="{{ route('admin.orders.index') }}" class="btn-custom btn-custom-light">
                        Lihat Semua Pesanan
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
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($newOrders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>{{ $order->user->name }}</td>
                                <td>{{ $order->service->name }}</td>
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
        @endif
    </x-card>
@endsection
