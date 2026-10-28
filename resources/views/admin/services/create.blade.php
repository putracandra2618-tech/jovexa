@extends('layouts.admin')

@section('title', 'Tambah Jasa')
@section('page-title', 'Tambah Jasa')
@section('page-subtitle', 'Jasa berstatus Tayang langsung tampil di katalog publik.')

@section('content')
    <form method="POST" action="{{ route('admin.services.store') }}" class="needs-validation" novalidate>
        @csrf

        <x-card>
            @include('admin.services._form', ['service' => $service, 'statuses' => $statuses])

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.services.index') }}" class="btn-custom btn-custom-light">Batal</a>
                <button type="submit" class="btn-custom btn-custom-save">
                    <i class="bi bi-check-lg me-1"></i> Simpan Jasa
                </button>
            </div>
        </x-card>
    </form>
@endsection
