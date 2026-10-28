@extends('layouts.admin')

@section('title', 'Kirim Hasil')
@section('page-title', 'Kirim Hasil Pekerjaan')
@section('page-subtitle', 'Pesanan '.$order->order_number.' · '.$order->service->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <x-card title="Unggah File Hasil">
                <form method="POST" action="{{ route('worker.orders.result.store', $order) }}"
                      enctype="multipart/form-data">
                    @csrf

                    <x-form.input
                        type="file"
                        name="result"
                        label="File Hasil"
                        required
                        accept=".pdf,.doc,.docx,.zip,.png,.jpg,.jpeg"
                        help="Ukuran maksimal 20 MB. File disimpan di storage privat."
                    />

                    <button type="submit" class="btn-custom btn-custom-primary">
                        <i class="bi bi-cloud-arrow-up me-1"></i> Unggah
                    </button>

                    <a href="{{ route('worker.orders.show', $order) }}" class="btn-custom btn-custom-light">
                        Batal
                    </a>
                </form>
            </x-card>

            @if ($order->results->isNotEmpty())
                <x-card title="File Hasil Saat Ini" class="mt-4">
                    <ul class="list-unstyled mb-0">
                        @foreach ($order->results as $file)
                            <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi {{ $file->icon() }} fs-5 text-muted"></i>
                                    <div>
                                        <div class="fw-semibold">{{ $file->file_name }}</div>
                                        <small class="text-muted">{{ $file->readableSize() }}</small>
                                    </div>
                                </div>

                                <a href="{{ route('worker.orders.file', [$order, $file]) }}"
                                   class="btn-custom btn-custom-light btn-custom-sm">
                                    <i class="bi bi-download me-1"></i> Unduh
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </x-card>
            @endif
        </div>
    </div>
@endsection
