@extends('layouts.public')

@section('body_class', 'jx-home')
@section('title', config('app.name'))
@section('description', 'Jovexa — platform penyedia jasa digital untuk tugas sekolah, coding, dan desain.')

@section('content')
    <section class="jx-hero">
        <span class="jx-blob jx-blob-mint jx-blob-1"></span>
        <span class="jx-blob jx-blob-mango jx-blob-2"></span>
        <span class="jx-blob jx-blob-sky jx-blob-hero-sky" aria-hidden="true"></span>
        <span class="jx-blob jx-blob-lilac jx-blob-hero-lilac" aria-hidden="true"></span>
        <span class="jx-blob jx-blob-coral jx-blob-hero-coral" aria-hidden="true"></span>
        <span class="jx-confetti" aria-hidden="true"></span>

        <div class="jx-container">
            <div class="jx-hero-grid">
                <div>
                    <span class="jx-eyebrow jx-reveal" data-reveal-delay="0">
                        <i class="bi bi-stars"></i> Tugas, skripsi, coding &amp; desain
                    </span>

                    <h1 class="jx-hero-title jx-reveal" data-reveal-delay="80">
                        Jasa digital dikerjakan ahlinya, <span class="jx-highlight">tanpa drama</span>.
                    </h1>

                    <p class="jx-hero-text jx-reveal" data-reveal-delay="160">
                        Pilih jasanya, ceritakan kebutuhannya, lalu pantau progres pengerjaan sampai
                        file hasilnya siap diunduh. Kamu tidak perlu menebak-nebak status pekerjaanmu.
                    </p>

                    <div class="jx-hero-actions jx-reveal" data-reveal-delay="240">
                        <a href="{{ route('services.index') }}" class="btn-custom btn-custom-primary btn-custom-lg">
                            <i class="bi bi-grid"></i> Lihat Katalog
                        </a>

                        @auth
                            <a href="{{ route(auth()->user()->role->homeRoute()) }}" class="btn-custom btn-custom-light btn-custom-lg">
                                <i class="bi bi-speedometer2"></i> Buka Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn-custom btn-custom-light btn-custom-lg">
                                <i class="bi bi-person-plus"></i> Daftar Gratis
                            </a>
                        @endauth
                    </div>

                    <span class="jx-sticker jx-reveal" data-reveal-delay="300">
                        <i class="bi bi-lightning-charge-fill"></i> Harga jujur · Pengerjaan cepat
                    </span>

                    <div class="jx-hero-trust jx-reveal" data-reveal-delay="320">
                        <span class="jx-hero-trust-item">
                            <i class="bi bi-eye"></i> Progres terpantau
                        </span>
                        <span class="jx-hero-trust-item">
                            <i class="bi bi-shield-check"></i> Bayar lewat Tripay
                        </span>
                        <span class="jx-hero-trust-item">
                            <i class="bi bi-arrow-repeat"></i> Revisi sesuai kesepakatan
                        </span>
                    </div>
                </div>

                <div class="jx-hero-panel jx-reveal jx-reveal-rotate" data-reveal-delay="200">
                    <div class="jx-hero-panel-head">
                        <h2 class="jx-hero-panel-title">Cara Pesan di Jovexa</h2>
                        <span class="badge-table success">Mulai hari ini</span>
                    </div>

                    <ol class="jx-hero-steps">
                        <li class="jx-hero-step">
                            <span class="jx-hero-step-num">1</span>
                            <span>
                                <span class="jx-hero-step-title d-block">Pilih jasa</span>
                                <span class="jx-hero-step-text">Cek harga dan estimasi hari pengerjaannya.</span>
                            </span>
                        </li>
                        <li class="jx-hero-step">
                            <span class="jx-hero-step-num">2</span>
                            <span>
                                <span class="jx-hero-step-title d-block">Ceritakan kebutuhan</span>
                                <span class="jx-hero-step-text">Tulis detail dan lampirkan contoh berkas.</span>
                            </span>
                        </li>
                        <li class="jx-hero-step">
                            <span class="jx-hero-step-num">3</span>
                            <span>
                                <span class="jx-hero-step-title d-block">Bayar &amp; pantau</span>
                                <span class="jx-hero-step-text">Status pesanan dan hasil akhir ada di dashboard.</span>
                            </span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>

        <div class="jx-hero-wave" aria-hidden="true">
            <svg viewBox="0 0 1440 90" preserveAspectRatio="none" focusable="false">
                <path d="M0,52 C240,92 480,12 720,34 C960,56 1200,96 1440,58 L1440,90 L0,90 Z"></path>
            </svg>
        </div>
    </section>

    <section class="jx-section jx-section-stats">
        <div class="jx-container">
            <div class="jx-stat-strip">
                <div class="jx-stat-tile jx-reveal" data-reveal-delay="0">
                    <div class="jx-stat-tile-value" data-counter="{{ $serviceCount }}">0</div>
                    <div class="jx-stat-tile-label">Jasa tayang saat ini</div>
                </div>
                <div class="jx-stat-tile jx-reveal" data-reveal-delay="120">
                    <div class="jx-stat-tile-value">Real-time</div>
                    <div class="jx-stat-tile-label">Status pesanan di dashboard</div>
                </div>
                <div class="jx-stat-tile jx-reveal" data-reveal-delay="240">
                    <div class="jx-stat-tile-value">Tripay</div>
                    <div class="jx-stat-tile-label">Payment gateway terverifikasi</div>
                </div>
            </div>
        </div>
    </section>

    <section class="jx-section jx-section-alt jx-band-lilac">
        <div class="jx-container">
            <div class="jx-section-head centered">
                <span class="jx-section-eyebrow">Kenapa Jovexa</span>
                <h2 class="jx-section-title">Tenang, semuanya kelihatan</h2>
                <p class="jx-section-text">
                    Tidak ada lagi "sudah dikerjakkan tapi belum kabar". Setiap status punya arti
                    dan bisa kamu pantau sendiri.
                </p>
            </div>

            <div class="row g-4 jx-zigzag">
                <div class="col-md-6 col-lg-3">
                    <div class="jx-feature-card jx-reveal" data-reveal-delay="0">
                        <span class="jx-feature-icon jx-icon-mint"><i class="bi bi-eye"></i></span>
                        <h3 class="jx-feature-title">Progres transparan</h3>
                        <p class="jx-feature-text">
                            Status pesanan berubah nyata: menunggu pembayaran, diverifikasi, dikerjakan,
                            sampai dikirim.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="jx-feature-card jx-reveal" data-reveal-delay="120">
                        <span class="jx-feature-icon jx-icon-sky"><i class="bi bi-shield-check"></i></span>
                        <h3 class="jx-feature-title">Bayar aman</h3>
                        <p class="jx-feature-text">
                            Pembayaran lewat Tripay dan baru dikonfirmasi lewat callback dari penyedia,
                            bukan dari klik tombol.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="jx-feature-card jx-reveal" data-reveal-delay="240">
                        <span class="jx-feature-icon jx-icon-coral"><i class="bi bi-arrow-repeat"></i></span>
                        <h3 class="jx-feature-title">Ada revisi</h3>
                        <p class="jx-feature-text">
                            Hasil belum sesuai? Kirim catatan revisi dan pengerjaan diulang di halaman
                            pesanan yang sama.
                        </p>
                    </div>
                </div>

                <div class="col-md-6 col-lg-3">
                    <div class="jx-feature-card jx-reveal" data-reveal-delay="360">
                        <span class="jx-feature-icon jx-icon-mango"><i class="bi bi-clock-history"></i></span>
                        <h3 class="jx-feature-title">Estimasi jelas</h3>
                        <p class="jx-feature-text">
                            Tiap jasa menyebut estimasi hari pengerjaan sejak awal, jadi kamu tahu
                            kapan harus menanti.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if ($featuredServices->isNotEmpty())
        <section class="jx-section jx-section-popular">
            <div class="jx-container">
                <div class="d-flex flex-wrap justify-content-between align-items-end gap-3 mb-4">
                    <div class="jx-section-head mb-0">
                        <span class="jx-section-eyebrow">Jasa populer</span>
                        <h2 class="jx-section-title mb-0">Mulai dari yang paling sering dipesan</h2>
                    </div>

                    <a href="{{ route('services.index') }}" class="btn-custom btn-custom-light">
                        Lihat semua jasa <i class="bi bi-arrow-right"></i>
                    </a>
                </div>

                <div class="row g-4 jx-zigzag">
                    @foreach ($featuredServices as $service)
                        <div class="col-md-6 col-lg-4 jx-reveal" data-reveal-delay="{{ $loop->index * 120 }}">
                            <x-service-card :service="$service" />
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="jx-section jx-section-alt jx-band-sky" id="cara-pesan">
        <div class="jx-container">
            <div class="jx-section-head">
                <span class="jx-section-eyebrow">Cara pesan</span>
                <h2 class="jx-section-title">Tiga langkah, beres</h2>
                <p class="jx-section-text">
                    Tidak ada proses yang bertele-tele. Kalau ada yang kurang jelas, tim kami
                    menghubungi kamu balik.
                </p>
            </div>

            <div class="jx-steps">
                <div class="jx-step jx-reveal" data-reveal-delay="0">
                    <span class="jx-step-num">1</span>
                    <h3 class="jx-step-title">Pilih jasa</h3>
                    <p class="jx-step-text">
                        Buka katalog, bandingkan harga dan estimasi pengerjaan, lalu masuk ke halaman detail.
                    </p>
                </div>

                <div class="jx-step jx-reveal" data-reveal-delay="140">
                    <span class="jx-step-num">2</span>
                    <h3 class="jx-step-title">Isi kebutuhan</h3>
                    <p class="jx-step-text">
                        Tulis sedetail mungkin: topik, jumlah halaman, format, dan tenggat. Lampirkan
                        contoh berkas bila ada.
                    </p>
                </div>

                <div class="jx-step jx-reveal" data-reveal-delay="280">
                    <span class="jx-step-num">3</span>
                    <h3 class="jx-step-title">Bayar &amp; pantau</h3>
                    <p class="jx-step-text">
                        Pilih kanal pembayaran, selesaikan transaksi, lalu pantau progres sampai
                        file hasil diunduh.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="jx-section jx-section-cta">
        <div class="jx-container">
            <div class="jx-cta jx-reveal">
                <span class="jx-blob jx-blob-lilac" style="width: 260px; height: 260px; top: -120px; right: -40px;"></span>
                <span class="jx-blob jx-blob-mango" style="width: 160px; height: 160px; bottom: -80px; left: 12%;"></span>
                <span class="jx-confetti jx-confetti-cta" aria-hidden="true"></span>

                <div class="jx-cta-inner">
                    <div>
                        <h2 class="jx-cta-title">Sudah siap kerjain?</h2>
                        <p class="jx-cta-text">
                            Buat akun gratis, lalu pesan jasanya dan pantau progresnya dari mana saja.
                        </p>
                    </div>

                    <div class="d-flex flex-wrap gap-2">
                        @auth
                            <a href="{{ route(auth()->user()->role->homeRoute()) }}" class="btn-custom btn-custom-primary btn-custom-lg">
                                Buka Dashboard
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="btn-custom btn-custom-primary btn-custom-lg">
                                <i class="bi bi-person-plus"></i> Daftar Gratis
                            </a>
                        @endauth

                        <a href="{{ route('services.index') }}" class="btn-custom btn-custom-light btn-custom-lg">
                            Lihat Katalog
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="jx-section jx-section-alt jx-band-mint" id="faq">
        <div class="jx-container">
            <div class="row g-5">
                <div class="col-lg-5">
                    <div class="jx-section-head mb-0">
                        <span class="jx-section-eyebrow">Pertanyaan umum</span>
                        <h2 class="jx-section-title">Masih ragu? Ini jawaban singkatnya</h2>
                        <p class="jx-section-text">
                            Kalau pertanyaannya belum terjawab, hubungi kami lewat WhatsApp.
                        </p>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="jx-faq" data-accordion>
                        <details open>
                            <summary>
                                Seberapa sering progres pesanan diperbarui?
                                <i class="bi bi-chevron-down"></i>
                            </summary>
                            <div class="jx-faq-answer">
                                Setiap kali status berubah, halaman detail pesananmu ikut berubah.
                                Kamu juga bisa melihat riwayat status lengkap beserta waktunya.
                            </div>
                        </details>

                        <details>
                            <summary>
                                Bisa minta revisi kalau hasilnya kurang sesuai?
                                <i class="bi bi-chevron-down"></i>
                            </summary>
                            <div class="jx-faq-answer">
                                Bisa. Kirim catatan revisi dari halaman detail pesanan, dan tim kami
                                akan mengerjakan ulang bagian yang perlu diperbaiki.
                            </div>
                        </details>

                        <details>
                            <summary>
                                Bagaimana cara pembayarannya?
                                <i class="bi bi-chevron-down"></i>
                            </summary>
                            <div class="jx-faq-answer">
                                Pembayaran memakai payment gateway Tripay. Pesanan baru diteruskan ke
                                pengerjaan setelah callback pembayaran diterima.
                            </div>
                        </details>

                        <details>
                            <summary>
                                Bagaimana cara mengunduh file hasil?
                                <i class="bi bi-chevron-down"></i>
                            </summary>
                            <div class="jx-faq-answer">
                                Setelah status pesanan menjadi "Selesai" atau "Sudah Dikirim", file hasil
                                muncul di halaman detail pesanan dan bisa diunduh.
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
