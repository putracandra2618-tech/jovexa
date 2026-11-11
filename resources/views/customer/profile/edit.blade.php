@extends('layouts.customer')

@section('title', 'Profil Saya')
@section('page-title', 'Profil Saya')
@section('page-subtitle', 'Atur pengaturan akun Anda')

@section('content')
    <x-card>
        <form method="POST" action="{{ route('customer.profile.update') }}" class="needs-validation">
            @csrf
            @method('PATCH')

            <div class="mb-3">
                <x-form.input
                    label="Username"
                    name="name"
                    type="text"
                    :value="old('name', $user->name)"
                    required
                    autocomplete="username"
                />
            </div>

            <div class="mb-3">
                <x-form.input
                    label="Email"
                    name="email"
                    type="email"
                    :value="old('email', $user->email)"
                    required
                    autocomplete="email"
                />
            </div>

            <hr class="my-4">

            <h5 class="mb-3">Ubah Password (Opsional)</h5>

            <div class="mb-3">
                <x-form.input
                    label="Password Lama"
                    name="old_password"
                    type="password"
                    :hint="'Wajib diisi jika ingin mengubah password.'"
                    autocomplete="current-password"
                />
            </div>

            <div class="mb-3">
                <x-form.input
                    label="Password Baru"
                    name="password"
                    type="password"
                    :hint="'Kosongkan jika tidak ingin mengganti password. Minimal 8 karakter.'"
                    autocomplete="new-password"
                />
            </div>

            <div class="mb-3">
                <x-form.input
                    label="Konfirmasi Password Baru"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                />
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('customer.dashboard') }}" class="btn-custom btn-custom-light">Batal</a>
                <button type="submit" class="btn-custom btn-custom-save">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </x-card>
@endsection