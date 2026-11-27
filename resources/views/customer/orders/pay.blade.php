@extends('layouts.customer')

@section('title', 'Pembayaran '.$order->order_number)
@section('page-title', 'Pembayaran')
@section('page-subtitle', 'Pesanan '.$order->order_number.' · '.$order->service->name)

@section('content')
    <div class="row g-4 justify-content-center">
        <div class="col-lg-7">
            <x-card title="Metode Pembayaran">
                @if (! $gatewayReady)
                    <x-alert variant="warning">
                        Kredensial Tripay belum dikonfigurasi di file <code>.env</code>, jadi tombol pembayaran
                        dinonaktifkan sampai <code>TRIPAY_API_KEY</code>, <code>TRIPAY_PRIVATE_KEY</code>, dan
                        <code>TRIPAY_MERCHANT_CODE</code> diisi.
                    </x-alert>
                @elseif ($channels === [])
                    <x-alert variant="warning">
                        Belum ada kanal pembayaran yang aktif di akun merchant Tripay. Aktifkan kanal di
                        Tripay Sandbox pada halaman <strong>Channel Pembayaran</strong> sebelum menerima pesanan.
                    </x-alert>
                @elseif ($channelsAreGuess)
                    <x-alert variant="info">
                        Gateway pembayaran sedang tidak terjangkau, jadi daftar metode di bawah berasal dari
                        konfigurasi cadangan dan mungkin belum semuanya aktif di akun Tripay. Kalau pembayaran
                        ditolak, coba lagi sebentar.
                    </x-alert>
                @endif

                @if ($order->latestPayment?->isStillOpen() && filled($order->latestPayment->checkout_url))
                    <x-alert variant="info">
                        Kamu sudah punya transaksi yang belum dibayar. Melanjutkan akan memakai transaksi
                        yang sama, jadi tidak ada transaksi ganda yang dibuat.
                    </x-alert>
                @endif

                <form method="POST" action="{{ route('customer.orders.pay.store', $order) }}" data-loading>
                    @csrf

                    @if ($channels === [])
                        <p class="page-subtitle mb-0">
                            Tidak ada metode pembayaran yang bisa dipilih saat ini.
                        </p>
                    @else
                        <span class="form-label-custom d-block mb-2">Pilih Metode</span>

                        <div class="jx-pay-options">
                            @foreach ($channels as $channelValue => $channelLabel)
                                <label class="jx-pay-option">
                                    <input type="radio" name="payment_method" value="{{ $channelValue }}"
                                           @checked((string) old('payment_method') === (string) $channelValue)
                                           @required($loop->first)>
                                    <span class="jx-pay-option-name">{{ $channelLabel }}</span>
                                    <span class="jx-pay-option-code">{{ $channelValue }}</span>
                                </label>
                            @endforeach
                        </div>

                        @error('payment_method')
                            <div class="form-feedback-custom invalid-custom mt-2">
                                <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                            </div>
                        @enderror
                    @endif

                    <button type="submit" class="btn-custom btn-custom-primary btn-custom-lg w-100 mt-3"
                            @disabled(! $gatewayReady || $channels === [])>
                        <i class="bi bi-box-arrow-up-right"></i> Bayar Sekarang
                    </button>
                </form>

                <a href="{{ route('customer.orders.show', $order) }}" class="btn-custom btn-custom-light w-100 mt-2">
                    Kembali ke Detail Pesanan
                </a>
            </x-card>
        </div>

        <div class="col-lg-5">
            <div class="jx-order-card">
                <h2 class="card-title mb-3">Rincian Tagihan</h2>

                <dl class="jx-summary">
                    <dt>Jasa</dt>
                    <dd>{{ $order->service->name }}</dd>

                    <dt>Nomor Pesanan</dt>
                    <dd>{{ $order->order_number }}</dd>

                    <dt>Total Tagihan</dt>
                    <dd class="fw-bold">{{ 'Rp'.number_format($order->total_price, 0, ',', '.') }}</dd>
                </dl>

                <ul class="jx-check-list">
                    <li>
                        <i class="bi bi-shield-lock"></i>
                        <span>Pembayaran diproses lewat kanal Tripay pilihanmu.</span>
                    </li>
                    <li>
                        <i class="bi bi-hourglass-split"></i>
                        <span>Pesanan diproses setelah pembayaran terkonfirmasi.</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
@endsection
