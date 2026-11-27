@extends('layouts.admin')

@section('title', 'Soft Delete')
@section('page-title', 'Soft Delete')
@section('page-subtitle', 'Data yang dihapus admin disimpan di sini dan dapat dipulihkan.')

@section('content')
    <x-card>
        <div class="jx-status-tabs mb-4">
            @foreach ($tabs as $tabKey => $tabLabel)
                @if ($tabKey !== 'users' || Route::has('admin.trash.users'))
                    <a href="{{ route('admin.trash.'.$tabKey) }}"
                       class="jx-status-tab {{ request()->routeIs('admin.trash.'.$tabKey) ? 'is-active' : '' }}"
                       aria-pressed="{{ request()->routeIs('admin.trash.'.$tabKey) ? 'true' : 'false' }}">
                        {{ $tabLabel }}
                    </a>
                @endif
            @endforeach
        </div>

        @if ($trashed->isEmpty())
            <x-empty-state
                icon="bi-trash"
                title="Tidak ada data di soft delete"
                description="Data yang dihapus dari menu CRUD akan tampil di halaman ini."
            />
        @else
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        @if ($type === 'users')
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Peran</th>
                                <th>Dihapus</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        @elseif ($type === 'services')
                            <tr>
                                <th>Nama Jasa</th>
                                <th>Slug</th>
                                <th>Harga</th>
                                <th>Dihapus</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        @else
                            <tr>
                                <th>Nomor Pesanan</th>
                                <th>Customer</th>
                                <th>Jasa</th>
                                <th>Status</th>
                                <th>Dihapus</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        @endif
                    </thead>
                    <tbody>
                        @foreach ($trashed as $item)
                            <tr>
                                @if ($type === 'users')
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td>{{ $item->email }}</td>
                                    <td>{{ $item->role?->label() ?? '-' }}</td>
                                    <td class="text-nowrap">{{ $item->deleted_at?->format('d M Y H:i') }}</td>
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('admin.trash.users.restore', $item) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-custom btn-custom-primary btn-custom-sm">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.trash.users.force-delete', $item) }}"
                                              class="d-inline"
                                              data-confirm="Hapus permanen akun {{ $item->name }} beserta seluruh datanya? Tindakan ini tidak bisa dibatalkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm">
                                                <i class="bi bi-trash me-1"></i> Hapus permanen
                                            </button>
                                        </form>
                                    </td>
                                @elseif ($type === 'services')
                                    <td class="fw-semibold">{{ $item->name }}</td>
                                    <td><small class="text-muted">/{{ $item->slug }}</small></td>
                                    <td>{{ 'Rp'.number_format($item->price, 0, ',', '.') }}</td>
                                    <td class="text-nowrap">{{ $item->deleted_at?->format('d M Y H:i') }}</td>
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('admin.trash.services.restore', $item) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-custom btn-custom-primary btn-custom-sm">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.trash.services.force-delete', $item) }}"
                                              class="d-inline"
                                              data-confirm="Hapus permanen jasa &quot;{{ $item->name }}&quot; beserta pesanannya? Tindakan ini tidak bisa dibatalkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm">
                                                <i class="bi bi-trash me-1"></i> Hapus permanen
                                            </button>
                                        </form>
                                    </td>
                                @else
                                    <td class="fw-semibold">{{ $item->order_number }}</td>
                                    <td>{{ $item->user?->name ?? '-' }}</td>
                                    <td>{{ $item->service?->name ?? '-' }}</td>
                                    <td>{{ $item->status?->label() ?? '-' }}</td>
                                    <td class="text-nowrap">{{ $item->deleted_at?->format('d M Y H:i') }}</td>
                                    <td class="text-end text-nowrap">
                                        <form method="POST" action="{{ route('admin.trash.orders.restore', $item) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn-custom btn-custom-primary btn-custom-sm">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Pulihkan
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.trash.orders.force-delete', $item) }}"
                                              class="d-inline"
                                              data-confirm="Hapus permanen pesanan {{ $item->order_number }} beserta file dan riwayatnya? Tindakan ini tidak bisa dibatalkan.">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm">
                                                <i class="bi bi-trash me-1"></i> Hapus permanen
                                            </button>
                                        </form>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($trashed->hasPages())
                <div class="mt-3 d-flex justify-content-center">
                    {{ $trashed->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
