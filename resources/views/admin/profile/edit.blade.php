@extends('layouts.admin')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Atur pengaturan akun Anda')

@section('content')
    <x-card>
        <form method="POST" action="{{ route('admin.profile.update') }}" class="needs-validation">
            @csrf
            @method('PATCH')

            <div class="mb-3">
                <x-form.input
                    label="Password Baru (opsional)"
                    name="password"
                    type="password"
                    :hint="'Kosongkan jika tidak ingin mengganti password. Minimal 8 karakter.'"
                    autocomplete="new-password"
                />
            </div>

            <div class="mb-3">
                <x-form.input
                    label="Konfirmasi Password"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </div>

            <div class="mb-3">
                <x-form.input
                    label="Password Lama"
                    name="old_password"
                    type="password"
                    :hint="'Wajib diisi jika ingin mengganti password.'"
                    autocomplete="current-password"
                />
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.dashboard') }}" class="btn-custom btn-custom-light">Batal</a>
                <button type="submit" class="btn-custom btn-custom-save">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </x-card>
@endsection