@extends('layouts.error')

@section('title', 'Akses Ditolak')

@section('content')
    <div class="jx-error">
        <span class="jx-blob jx-blob-coral" aria-hidden="true"></span>
        <span class="jx-blob jx-blob-lilac" aria-hidden="true"></span>

        <div class="jx-error-code">
            <span>4</span>
            <i class="bi bi-asterisk"></i>
            <span>3</span>
        </div>

        <h1 class="jx-error-title">Akses Ditolak</h1>
        <p class="jx-error-text">
            Akunmu tidak punya izin untuk membuka halaman ini. Hubungi admin Jovexa bila kamu merasa ini keliru.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ route(auth()->user()?->role->homeRoute() ?? 'home') }}" class="btn-custom btn-custom-primary">
                <i class="bi bi-house"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
@endsection
