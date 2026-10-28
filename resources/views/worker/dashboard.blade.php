@extends('layouts.admin')

@section('title', 'Dashboard Worker')
@section('page-title', 'Dashboard Worker')
@section('page-subtitle', 'Pantau kuota, status online, dan pesanan yang sedang kamu kerjakan.')

@section('page-actions')
    <a href="{{ route('worker.queue.index') }}" class="btn-custom btn-custom-primary">
        <i class="bi bi-inbox me-1"></i> Lihat Antrean
    </a>
@endsection

@section('content')
    <div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3">
        <div class="col">
            <x-stat-card label="Pesanan Aktif" :value="$activeCount.'/'.$maxActiveOrders" icon="bi-briefcase"
                         :href="route('worker.orders.index')" />
        </div>

        <div class="col">
            <x-stat-card label="Antrean Tersedia" :value="$availableCount" icon="bi-inbox"
                         :href="route('worker.queue.index')" />
        </div>

        <div class="col">
            <x-stat-card label="Sudah Dikirim" :value="$completedCount" icon="bi-send-check"
                         :href="route('worker.orders.history')" />
        </div>

        <div class="col">
            <x-card title="Status Kerja" class="status-card h-100">
                <x-slot:actions>
                    <x-badge :variant="auth()->user()->is_online ? 'success' : 'pending'">
                        {{ auth()->user()->is_online ? 'Online' : 'Offline' }}
                    </x-badge>
                </x-slot:actions>

                @if (auth()->user()->is_online)
                    <p class="status-card-copy">Kamu bisa mengambil pesanan baru dari antrean.</p>

                    <form method="POST" action="{{ route('worker.availability') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="is_online" value="0">
                        <button type="submit" class="btn-custom btn-custom-light w-100">
                            <i class="bi bi-pause-circle me-1"></i> Ubah ke Offline
                        </button>
                    </form>
                @else
                    <p class="status-card-copy">Aktifkan online untuk mengambil pesanan baru.</p>

                    <form method="POST" action="{{ route('worker.availability') }}">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="is_online" value="1">
                        <button type="submit" class="btn-custom btn-custom-primary w-100">
                            <i class="bi bi-play-circle me-1"></i> Ubah ke Online
                        </button>
                    </form>
                @endif
            </x-card>
        </div>
    </div>

    @if (! auth()->user()->is_online)
        <x-alert variant="info" class="mt-4">
            Status kamu sedang offline. Kamu tetap bisa menyelesaikan pesanan aktif, tetapi belum bisa
            mengambil pesanan baru dari antrean.
        </x-alert>
    @elseif ($activeCount >= $maxActiveOrders)
        <x-alert variant="warning" class="mt-4">
            Kuota pesanan aktif sudah penuh ({{ $maxActiveOrders }}). Selesaikan atau kirim salah satu
            pesanan sebelum mengambil yang baru.
        </x-alert>
    @endif

    <x-card title="Pesanan Aktif" class="mt-4">
        @if ($activeOrders->isEmpty())
            <x-empty-state
                icon="bi-check2-all"
                title="Belum ada pesanan aktif"
                description="Ambil pesanan dari antrean saat kamu siap bekerja."
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
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($activeOrders as $order)
                            <tr>
                                <td class="fw-semibold">{{ $order->order_number }}</td>
                                <td>{{ $order->user->name }}</td>
                                <td>{{ $order->service->name }}</td>
                                <td>
                                    <x-badge :variant="$order->status->badgeClass()">
                                        {{ $order->status->label() }}
                                    </x-badge>
                                </td>
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
        @endif
    </x-card>
@endsection
