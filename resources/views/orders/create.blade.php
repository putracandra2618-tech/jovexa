@extends('layouts.customer')

@section('title', 'Buat Pesanan')
@section('page-title', 'Pesan '.$service->name)
@section('page-subtitle', 'Jelaskan kebutuhan kamu agar tim Jovexa bisa langsung bekerja.')

@section('content')
    @php
        $accent = \App\Support\ServiceAccent::for($service);
    @endphp

    <form method="POST" action="{{ route('orders.store', $service) }}" enctype="multipart/form-data" data-loading>
        @csrf

        <div class="jx-detail-hero mb-4">
            <span class="jx-service-icon jx-icon-{{ $accent['tone'] }}">
                <i class="bi {{ $accent['icon'] }}"></i>
            </span>

            <div>
                <h1 class="mb-2">{{ $service->name }}</h1>
                <div class="jx-detail-chips">
                    <span class="jx-chip jx-chip-mint">
                        <i class="bi bi-tag"></i>
                        {{ 'Rp'.number_format($service->price, 0, ',', '.') }}
                    </span>
                    <span class="jx-chip">
                        <i class="bi bi-clock"></i>
                        {{ $service->estimated_duration }} hari kerja
                    </span>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <x-card title="Detail Kebutuhan">
                    <x-form.textarea
                        name="description"
                        label="Apa yang perlu dikerjakan?"
                        rows="8"
                        required
                        :counter="5000"
                        :value="old('description')"
                        placeholder="Contoh: Saya butuh makalah 20 halaman dengan 15 referensi jurnal, tenggat 5 hari."
                    />

                    <div class="mb-3">
                        <label for="requirements" class="form-label-custom">File Pendukung (opsional)</label>

                        <div class="jx-dropzone" data-dropzone data-dropzone-preview="#requirements-preview">
                            <input id="requirements" name="requirements[]" type="file" multiple
                                   accept=".pdf,.doc,.docx,.txt,.zip,.png,.jpg,.jpeg"
                                   class="jx-dropzone-input" data-dropzone-input>

                            <div class="jx-dropzone-inner">
                                <span class="jx-dropzone-icon"><i class="bi bi-cloud-arrow-up"></i></span>
                                <strong>Tarik &amp; lepas berkas di sini</strong>
                                <span class="jx-dropzone-hint">
                                    atau <span class="jx-dropzone-browse">pilih berkas</span> &middot;
                                    maks 5 file, 10 MB per file
                                </span>
                            </div>
                        </div>

                        <ul class="jx-file-preview" id="requirements-preview" data-file-preview></ul>

                        <div class="form-feedback-custom invalid-custom d-none" data-dropzone-message></div>

                        @error('requirements')
                            <div class="form-feedback-custom invalid-custom">
                                <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                            </div>
                        @enderror

                        @error('requirements.*')
                            <div class="form-feedback-custom invalid-custom">
                                <i class="bi bi-exclamation-circle-fill"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    @if ($service->requirements)
                        <div class="jx-hint-card">
                            <span class="jx-hint-icon"><i class="bi bi-info-lg"></i></span>
                            <div>
                                <strong>Siapkan dulu hal ini</strong>
                                <p class="mb-0">{{ $service->requirements }}</p>
                            </div>
                        </div>
                    @endif
                </x-card>
            </div>

            <div class="col-lg-5">
                <div class="jx-order-card">
                    <h2 class="card-title mb-3">Ringkasan Pesanan</h2>

                    <dl class="jx-summary">
                        <dt>Jasa</dt>
                        <dd>{{ $service->name }}</dd>

                        <dt>Estimasi Pengerjaan</dt>
                        <dd>{{ $service->estimated_duration }} hari</dd>

                        <dt>Total</dt>
                        <dd class="fw-bold">{{ 'Rp'.number_format($service->price, 0, ',', '.') }}</dd>
                    </dl>

                    <button type="submit" class="btn-custom btn-custom-primary btn-custom-lg w-100">
                        <i class="bi bi-check2-circle"></i> Buat Pesanan
                    </button>

                    <a href="{{ route('services.show', $service) }}" class="btn-custom btn-custom-light w-100 mt-2">
                        Batal
                    </a>

                    <p class="form-hint mt-3 mb-0">
                        Kamu akan diarahkan ke halaman pembayaran setelah pesanan dibuat.
                    </p>
                </div>
            </div>
        </div>
    </form>
@endsection
