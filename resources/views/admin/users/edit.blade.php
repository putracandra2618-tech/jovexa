@extends('layouts.admin')

@section('title', 'Ubah Pengguna')
@section('page-title', 'Ubah Pengguna')
@section('page-subtitle', 'Perbarui profil, peran, atau password akun pengguna.')

@section('content')
    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <x-card>
            @include('admin.users._form', ['user' => $user, 'roles' => $roles])

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.users.index') }}" class="btn-custom btn-custom-light">Batal</a>
                <button type="submit" class="btn-custom btn-custom-save">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
            </div>
        </x-card>
    </form>
@endsection
