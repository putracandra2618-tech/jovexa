@php
    $flashes = [
        'success' => 'success',
        'error' => 'danger',
        'warning' => 'warning',
        'info' => 'info',
    ];
@endphp

@foreach ($flashes as $key => $variant)
    @if (session()->has($key))
        <x-alert :variant="$variant">{{ session($key) }}</x-alert>
    @endif
@endforeach

@if ($errors->any())
    <x-alert variant="danger" title="Periksa kembali isian formulir.">
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </x-alert>
@endif
