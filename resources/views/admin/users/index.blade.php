@extends('layouts.admin')

@section('title', 'Kelola Pengguna')
@section('page-title', 'Kelola Pengguna')
@section('page-subtitle', 'Kelola peran, status worker, dan akses akun pengguna.')

@section('content')
    @php
        $assignableRoles = collect($roles)->reject(fn ($role) => $role === \App\Enums\UserRole::SuperAdmin);
    @endphp

    <x-card data-admin-search-results>
        <div class="row g-2 align-items-center mb-3">
            <div class="col-md-4">
                <form method="GET" action="{{ route('admin.users.index') }}" class="d-flex gap-2">
                    @if ($search !== '')
                        <input type="hidden" name="q" value="{{ $search }}">
                    @endif

                    <select name="role" class="form-select-custom" data-admin-search-select
                            aria-label="Saring berdasarkan peran">
                        <option value="">Semua Peran</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected(request('role') === $role->value)>
                                {{ $role->label() }}
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
                    :total="$users->total()"
                    :search="$search"
                    route="admin.users.index"
                    :filtered="$search !== '' || request()->filled('role')"
                />
            </div>
        </div>

        @if ($users->isEmpty())
            @if ($search !== '')
                <x-empty-state
                    icon="bi-search"
                    title="Pengguna tidak ditemukan"
                    description="Tidak ada pengguna yang cocok dengan kata kunci tersebut."
                >
                    <x-slot:actions>
                        <a href="{{ route('admin.users.index') }}" class="btn-custom btn-custom-primary">
                            Lihat semua pengguna
                        </a>
                    </x-slot:actions>
                </x-empty-state>
            @else
                <x-empty-state
                    icon="bi-people"
                    title="Belum ada pengguna"
                    description="Pengguna yang terdaftar akan muncul di daftar ini."
                />
            @endif
        @else
            <div class="table-responsive">
                <table class="table table-custom align-middle mb-0">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Peran</th>
                            <th>Pesanan</th>
                            <th>Terdaftar</th>
                            <th class="text-end">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            @php
                                $isSelf = $user->is(auth()->user());
                                $isSuperAdmin = $user->isSuperAdmin();
                            @endphp
                            <tr>
                                <td class="fw-medium">
                                    {{ $user->name }}
                                    @if ($user->isWorker())
                                        <div class="small text-nowrap">
                                            <x-badge :variant="$user->is_online ? 'success' : 'pending'">
                                                {{ $user->is_online ? 'Online' : 'Offline' }}
                                            </x-badge>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $user->email }}</td>
                                <td class="text-nowrap">
                                    <x-badge :variant="$user->role->badgeClass()">{{ $user->role->label() }}</x-badge>
                                </td>
                                <td>
                                    <div>{{ $user->orders_count }} sebagai customer</div>
                                    @if ($user->isWorker())
                                        <small class="text-muted">{{ $user->assigned_orders_count }} sebagai worker</small>
                                    @endif
                                </td>
                                <td class="text-nowrap">{{ $user->created_at?->format('d M Y') }}</td>
                                <td class="text-end">
                                    @if ($isSelf)
                                        <span class="text-muted small">Akun Anda</span>
                                    @elseif ($isSuperAdmin)
                                        <span class="text-muted small">Super Admin</span>
                                    @else
                                        <div class="table-actions-stack">
                                            <form method="POST" action="{{ route('admin.users.role', $user) }}"
                                                  class="table-role-form">
                                                @csrf
                                                @method('PATCH')

                                                <select name="role" class="form-select-custom form-select-sm"
                                                        aria-label="Peran {{ $user->name }}">
                                                    @foreach ($assignableRoles as $role)
                                                        <option value="{{ $role->value }}" @selected($user->role === $role)>
                                                            {{ $role->label() }}
                                                        </option>
                                                    @endforeach
                                                </select>

                                                <button type="submit" class="btn-custom btn-custom-save btn-custom-sm">
                                                    <i class="bi bi-check-lg me-1"></i> Simpan
                                                </button>
                                            </form>

                                            <div class="table-actions">
                                                <form method="POST" action="{{ route('admin.users.impersonate', $user) }}"
                                                      data-confirm="Masuk sebagai {{ $user->name }}? Anda dapat kembali ke akun Super Admin kapan saja.">
                                                    @csrf
                                                    <button type="submit" class="btn-custom btn-custom-light btn-custom-sm">
                                                        <i class="bi bi-box-arrow-in-right me-1"></i> Login sebagai
                                                    </button>
                                                </form>

                                                <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                                                      data-confirm="Hapus akun {{ $user->name }} secara permanen? Tindakan ini tidak bisa dibatalkan.">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm">
                                                        <i class="bi bi-trash me-1"></i> Hapus
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-3 d-flex justify-content-center">
                {{ $users->links() }}
            </div>
        @endif
    </x-card>
@endsection
