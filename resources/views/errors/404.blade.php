@extends('layouts.error')

@section('title', 'Halaman Tidak Ditemukan')

@section('content')
    <div class="jx-error">
        <span class="jx-blob jx-blob-mint" aria-hidden="true"></span>
        <span class="jx-blob jx-blob-sky" aria-hidden="true"></span>

        <div class="jx-error-code">
            <span>4</span>
            <i class="bi bi-asterisk"></i>
            <span>4</span>
        </div>

        <h1 class="jx-error-title">Halaman Tidak Ditemukan</h1>
        <p class="jx-error-text">
            Halaman yang kamu cari mungkin sudah dihapus, berganti nama, atau sedang tidak tersedia.
        </p>

        <div class="d-flex flex-wrap justify-content-center gap-2">
            <a href="{{ route('home') }}" class="btn-custom btn-custom-primary">
                <i class="bi bi-house"></i> Kembali ke Beranda
            </a>

            <a href="{{ route('services.index') }}" class="btn-custom btn-custom-light">
                <i class="bi bi-box-seam"></i> Lihat Katalog
            </a>
        </div>
    </div>
@endsection
