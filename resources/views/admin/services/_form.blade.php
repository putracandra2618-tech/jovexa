@php
    $statusOptions = collect($statuses)
        ->mapWithKeys(fn (App\Enums\ServiceStatus $status) => [$status->value => $status->label()])
        ->all();
@endphp

<x-form.input
    label="Nama Jasa"
    name="name"
    :value="$service->name"
    placeholder="Contoh: Pembuatan Skripsi Word"
    required
/>

<div class="row">
    <div class="col-md-8">
        <x-form.input
            label="Slug"
            name="slug"
            :value="$service->slug"
            placeholder="Dibuat otomatis dari nama bila dikosongkan"
            hint="Dipakai pada URL halaman jasa."
        />
    </div>

    <div class="col-md-4">
        <x-form.select
            label="Status"
            name="status"
            :options="$statusOptions"
            :value="$service->status?->value"
            required
        />
    </div>
</div>

<x-form.textarea
    label="Deskripsi"
    name="description"
    :value="$service->description"
    placeholder="Jelaskan apa saja yang dikerjakan, apa yang kamu dapatkan, dan batasan jasanya."
    :rows="5"
    required
/>

<div class="row">
    <div class="col-md-6">
        <x-form.input
            label="Harga (Rp)"
            name="price"
            type="number"
            min="1000"
            step="1000"
            :value="$service->price"
            placeholder="150000"
            required
        />
    </div>

    <div class="col-md-6">
        <x-form.input
            label="Estimasi Pengerjaan (hari)"
            name="estimated_duration"
            type="number"
            min="1"
            max="365"
            :value="$service->estimated_duration"
            placeholder="3"
            required
        />
    </div>
</div>

<x-form.textarea
    label="Persyaratan"
    name="requirements"
    :value="$service->requirements"
    placeholder="Contoh: Siapkan outline, data yang harus dianalisis, dan referensi format."
    :rows="3"
/>
