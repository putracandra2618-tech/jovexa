@extends('layouts.admin')

@section('title', 'Ubah Jasa')
@section('page-title', 'Ubah Jasa')
@section('page-subtitle', 'Perbarui informasi jasa yang sudah ada di katalog.')

@section('content')
    <form method="POST" action="{{ route('admin.services.update', $service) }}" class="needs-validation" novalidate>
        @csrf
        @method('PUT')

        <x-card>
            @include('admin.services._form', ['service' => $service, 'statuses' => $statuses])

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('admin.services.index') }}" class="btn-custom btn-custom-light">Batal</a>
                <button type="submit" class="btn-custom btn-custom-save">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
            </div>
        </x-card>
    </form>
@endsection
