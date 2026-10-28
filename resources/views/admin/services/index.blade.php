@extends('layouts.admin')

@section('title', 'Kelola Jasa')
@section('page-title', 'Kelola Jasa')
@section('page-subtitle', 'Tambah, ubah, dan arsipkan jasa yang tampil di katalog publik.')

@section('content')
    <x-card data-admin-search-results>
        <x-slot:actions>
            <a href="{{ route('admin.services.create') }}" class="btn-custom btn-custom-primary">
                <i class="bi bi-plus-lg me-1"></i> Tambah Jasa
            </a>
        </x-slot:actions>

        <div class="row g-2 align-items-center mb-3">
            <div class="col-md-4">
                <form method="GET" action="{{ route('admin.services.index') }}" class="d-flex gap-2">
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
                    :total="$services->total()"
                    :search="$search"
                    route="admin.services.index"
                    :filtered="$search !== '' || request()->filled('status')"
                />
            </div>
        </div>

        @if ($services->isEmpty())
            @if ($search !== '')
                <x-empty-state
                    icon="bi-search"
                    title="Jasa tidak ditemukan"
                    description="Tidak ada jasa yang cocok dengan kata kunci tersebut."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.services.index') }}" class="btn-custom btn-custom-primary">
                            Lihat semua jasa
                        </a>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="bi-tools"
                    title="Belum ada jasa"
                    description="Tambahkan jasa pertama agar customer bisa mulai memesan."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.services.create') }}" class="btn-custom btn-custom-primary">
                            <i class="bi bi-plus-lg me-1"></i> Tambah Jasa
                        </a>
                    </x-slot:actions>
                </x-empty-state>
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Jasa</th>
                            <th>Harga</th>
                            <th>Estimasi</th>
                            <th>Status</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($services as $service)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $service->name }}</div>
                                    <small class="text-muted">/{{ $service->slug }}</small>
                                </td>
                                <td>{{ 'Rp'.number_format($service->price, 0, ',', '.') }}</td>
                                <td>{{ $service->estimated_duration }} hari</td>
                                <td>
                                    <x-badge :variant="$service->status->badgeClass()">
                                        {{ $service->status->label() }}
                                    </x-badge>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="{{ route('admin.services.edit', $service) }}"
                                       class="btn-custom btn-custom-edit btn-custom-sm">
                                        <i class="bi bi-pencil me-1"></i> Ubah
                                    </a>

                                    @if ($service->status === \App\Enums\ServiceStatus::Archived)
                                        <form method="POST"
                                              action="{{ route('admin.services.publish', $service) }}"
                                              class="d-inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-custom btn-custom-primary btn-custom-sm">
                                                <i class="bi bi-arrow-counterclockwise me-1"></i> Tayangkan
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.services.archive', $service) }}"
                                              class="d-inline"
                                              data-confirm="Arsipkan jasa &quot;{{ $service->name }}&quot;?">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm">
                                                <i class="bi bi-archive me-1"></i> Arsipkan
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if ($services->hasPages())
                <div class="card-footer bg-transparent border-0 pt-3">
                    {{ $services->links() }}
                </div>
            @endif
        @endif
    </x-card>
@endsection
