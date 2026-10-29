@extends('layouts.admin')

@section('title', 'Tambah Pengguna')
@section('page-title', 'Tambah Pengguna')
@section('page-subtitle', 'Buat akun baru untuk customer, worker, atau admin.')

@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}" class="needs-validation" novalidate>
        @csrf

        <x-card>
            @include('admin.users._form', ['user' => $user, 'roles' => $roles])

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.users.index') }}" class="btn-custom btn-custom-light">Batal</a>
                <button type="submit" class="btn-custom btn-custom-save">
                    <i class="bi bi-check-lg me-1"></i> Simpan Pengguna
                </button>
            </div>
        </x-card>
    </form>
@endsection
