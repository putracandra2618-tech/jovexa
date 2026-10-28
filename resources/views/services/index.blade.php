@extends('layouts.public')

@section('title', 'Daftar Jasa')
@section('description', 'Katalog jasa digital Jovexa: tugas sekolah, coding, desain, dan lainnya.')

@section('content')
    <section class="jx-section jx-band-sky">
        <div class="jx-container">
            <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                <div class="jx-section-head mb-0">
                    <span class="jx-section-eyebrow">Katalog</span>
                    <h1 class="jx-section-title mb-2">Jasa digital siap dikerjakan</h1>
                    <p class="jx-section-text">
                        Pilih jasa yang paling sesuai, cek estimasi hari pengerjaannya, lalu pesan.
                    </p>
                </div>
            </div>

            <div class="card mb-4">
                <form method="GET" action="{{ route('services.index') }}" class="d-flex flex-wrap align-items-center gap-3"
                      data-live-search>
                    <div class="jx-search">
                        <i class="bi bi-search"></i>
                        <label for="q" class="jx-sr-only">Cari jasa</label>
                        <input type="search" id="q" name="q" value="{{ $search }}"
                               class="form-control-custom" placeholder="Cari jasa, misal: skripsi atau coding"
                               data-live-search-input autocomplete="off">
                    </div>

                    <button type="submit" class="btn-custom btn-custom-primary">
                        <i class="bi bi-search"></i> Cari
                    </button>

                    <a href="{{ route('services.index') }}" class="btn-custom btn-custom-light">
                        <i class="bi bi-x-circle"></i> Reset
                    </a>

                    <span class="ms-auto text-muted small" data-live-search-count>
                        {{ $services->total() }} jasa tersedia
                    </span>
                </form>
            </div>

            @if ($search !== '')
                <div class="alert alert-custom-info mb-4">
                    <i class="bi bi-search alert-custom-icon"></i>
                    <div class="alert-custom-content">
                        Menampilkan hasil untuk <strong>{{ $search }}</strong>.
                    </div>
                </div>
            @endif

            <div class="row g-4" data-live-search-target>
                @forelse ($services as $service)
                    <div class="col-md-6 col-lg-4 jx-reveal" data-reveal-delay="{{ $loop->index * 90 }}"
                         data-service-item
                         data-service-name="{{ $service->name }}"
                         data-service-description="{{ Str::limit($service->description, 160) }}">
                        <x-service-card :service="$service" />
                    </div>
                @empty
                    <div class="col-12">
                        <div class="card">
                            <x-empty-state
                                icon="bi-search"
                                :title="$search === '' ? 'Belum ada jasa yang tayang' : 'Jasa tidak ditemukan'"
                                :description="$search === ''
                                    ? 'Admin belum menambahkan jasa ke katalog. Silakan kembali beberapa saat lagi.'
                                    : 'Coba kata kunci lain, atau lihat seluruh daftar jasa yang tersedia.'"
                            >
                                <x-slot:actions>
                                    <a href="{{ route('services.index') }}" class="btn-custom btn-custom-primary">
                                        Lihat semua jasa
                                    </a>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    </div>
                @endforelse
            </div>

            <div class="card d-none" data-live-search-empty>
                <x-empty-state
                    icon="bi-search"
                    title="Tidak ada jasa yang cocok"
                    description="Ubah kata kuncinya, atau tekan Reset untuk melihat semua jasa."
                />
            </div>

            @if ($services->hasPages())
                <div class="mt-5 d-flex justify-content-center">
                    {{ $services->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
