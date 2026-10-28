@extends('layouts.public')

@section('title', $service->name)
@section('description', Str::limit($service->description, 155))

@section('content')
    <section class="jx-section jx-band-mint">
        <div class="jx-container">
            <a href="{{ route('services.index') }}" class="jx-breadcrumb-back mb-3">
                <i class="bi bi-arrow-left"></i> Kembali ke katalog
            </a>

            <div class="row g-4 mt-1">
                <div class="col-lg-8">
                    <div class="jx-detail-hero">
                        @php
                            $accent = \App\Support\ServiceAccent::for($service);
                        @endphp

                        <span class="jx-service-icon jx-icon-{{ $accent['tone'] }} mb-3">
                            <i class="bi {{ $accent['icon'] }}"></i>
                        </span>

                        <h1>{{ $service->name }}</h1>

                        <div class="jx-detail-chips">
                            <span class="jx-chip jx-chip-mint">
                                <i class="bi bi-tag"></i>
                                {{ 'Rp'.number_format($service->price, 0, ',', '.') }}
                            </span>
                            <span class="jx-chip">
                                <i class="bi bi-clock"></i>
                                {{ $service->estimated_duration }} hari kerja
                            </span>
                            <span class="jx-chip">
                                <i class="bi bi-arrow-repeat"></i>
                                Bisa direvisi
                            </span>
                        </div>
                    </div>

                    <div class="card">
                        <h2 class="card-title">Deskripsi</h2>
                        <p class="jx-prose">{{ $service->description }}</p>
                    </div>

                    @if ($service->requirements)
                        <div class="card">
                            <h2 class="card-title">Apa yang perlu kamu siapkan</h2>
                            <p class="jx-prose">{{ $service->requirements }}</p>
                        </div>
                    @endif

                    <div class="card">
                        <h2 class="card-title mb-4">Alur pengerjaan</h2>

                        <ul class="jx-check-list">
                            <li>
                                <i class="bi bi-check-lg"></i>
                                <span>Kamu memesan dan membayar lewat kanal Tripay yang tersedia.</span>
                            </li>
                            <li>
                                <i class="bi bi-check-lg"></i>
                                <span>Admin memverifikasi pesanan, lalu worker mengerjakan dan menghubungi kamu bila ada yang perlu ditanyakan.</span>
                            </li>
                            <li>
                                <i class="bi bi-check-lg"></i>
                                <span>Pengerjaan berjalan dan statusnya bisa kamu pantau dari dashboard.</span>
                            </li>
                            <li>
                                <i class="bi bi-check-lg"></i>
                                <span>Hasil dikirim sebagai file lampiran yang bisa kamu unduh.</span>
                            </li>
                        </ul>
                    </div>

                    <div class="card">
                        <h2 class="card-title mb-4">Pertanyaan yang sering muncul</h2>

                        <div class="jx-faq">
                            <details open>
                                <summary>Berapa lama pengerjaannya?
                                    <i class="bi bi-chevron-down"></i>
                                </summary>
                                <div class="jx-faq-answer">
                                    Estimasi {{ $service->estimated_duration }} hari kerja sejak pesanan
                                    terverifikasi. Estimasi ini bisa bertambah bila kamu meminta revisi.
                                </div>
                            </details>

                            <details>
                                <summary>Apakah harga sudah termasuk revisi?
                                    <i class="bi bi-chevron-down"></i>
                                </summary>
                                <div class="jx-faq-answer">
                                    Revisi minor atas hasil yang sudah dikirim bisa Remedy ajukan lewat
                                    halaman detail pesanan. Perubahan scope yang besar dihitung terpisah.
                                </div>
                            </details>

                            <details>
                                <summary>Bagaimana cara membayar?
                                    <i class="bi bi-chevron-down"></i>
                                </summary>
                                <div class="jx-faq-answer">
                                    Pembayaran dilakukan lewat payment gateway Tripay setelah pesanan dibuat,
                                    dan pesanan baru diproses setelah pembayaran terkonfirmasi.
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="jx-order-card">
                        <div class="text-muted small fw-semibold mb-1">Total harga</div>
                        <div class="jx-order-price">
                            {{ 'Rp'.number_format($service->price, 0, ',', '.') }}
                        </div>
                        <p class="page-subtitle mb-4">
                            Estimasi selesai dalam {{ $service->estimated_duration }} hari kerja sejak
                            pesanan terverifikasi.
                        </p>

                        @auth
                            @if (auth()->user()->isStaff())
                                <a href="{{ route('admin.services.edit', $service) }}" class="btn-custom btn-custom-light w-100">
                                    <i class="bi bi-pencil"></i> Ubah Jasa
                                </a>
                            @elseif (auth()->user()->isCustomer())
                                <a href="{{ route('orders.create', $service) }}" class="btn-custom btn-custom-primary btn-custom-lg w-100">
                                    <i class="bi bi-bag-plus"></i> Pesan Jasa
                                </a>
                            @else
                                <a href="{{ route('worker.dashboard') }}" class="btn-custom btn-custom-light w-100">
                                    <i class="bi bi-grid-fill"></i> Buka Dashboard Worker
                                </a>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="btn-custom btn-custom-primary btn-custom-lg w-100">
                                <i class="bi bi-box-arrow-in-right"></i> Masuk untuk Memesan
                            </a>

                            <a href="{{ route('register') }}" class="btn-custom btn-custom-light w-100 mt-2">
                                Belum punya akun? Daftar
                            </a>
                        @endauth

                        <hr class="my-4">

                        <ul class="jx-check-list">
                            <li>
                                <i class="bi bi-shield-check"></i>
                                <span>Pembayaran aman lewat Tripay, dikonfirmasi dari sisi penyedia.</span>
                            </li>
                            <li>
                                <i class="bi bi-eye"></i>
                                <span>Status pengerjaan bisa kamu pantau sendiri kapan saja.</span>
                            </li>
                            <li>
                                <i class="bi bi-chat-left-dots"></i>
                                <span>Ada yang kurang jelas? Hubungi kami sebelum memesan.</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            @if ($relatedServices->isNotEmpty())
                <div class="mt-5">
                    <h2 class="jx-section-title mb-4">Jasa lain yang mungkin cocok</h2>

                    <div class="row g-4">
                        @foreach ($relatedServices as $relatedService)
                            <div class="col-md-6 col-lg-4">
                                <x-service-card :service="$relatedService" />
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
