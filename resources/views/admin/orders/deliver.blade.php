@extends('layouts.admin')

@section('title', 'Kirim Hasil')
@section('page-title', 'Kirim Hasil Pekerjaan')
@section('page-subtitle', 'Pesanan '.$order->order_number.' · '.$order->service->name)

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <x-card title="Unggah File Hasil">
                <form method="POST" action="{{ route('admin.orders.result.store', $order) }}"
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

                    <a href="{{ route('admin.orders.show', $order) }}" class="btn-custom btn-custom-light">
                        Batal
                    </a>
                </form>
            </x-card>

            <x-order-file-list title="File Hasil Saat Ini"
                               :files="$order->deliverables"
                               :order="$order"
                               route="admin.orders.file"
                               variant="staff"
                               :show-label="false"
                               empty-message="Belum ada file hasil saat ini." />
        </div>
    </div>
@endsection
