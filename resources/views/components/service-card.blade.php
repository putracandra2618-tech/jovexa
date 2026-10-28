@props([
    'service',
])

@php
    $accent = \App\Support\ServiceAccent::for($service);
@endphp

<article class="jx-service-card"
         data-service-name="{{ $service->name }}"
         data-service-description="{{ $service->description }}">
    <div class="jx-service-card-top">
        <span class="jx-service-icon jx-icon-{{ $accent['tone'] }}">
            <i class="bi {{ $accent['icon'] }}"></i>
        </span>

        <span class="jx-service-duration">
            <i class="bi bi-hourglass-split"></i>
            {{ $service->estimated_duration }} hari
        </span>
    </div>

    <div class="jx-service-body">
        <h3 class="jx-service-name">
            <a href="{{ route('services.show', $service) }}">{{ $service->name }}</a>
        </h3>

        <p class="jx-service-desc">{{ Str::limit($service->description, 110) }}</p>

        <div class="jx-service-foot">
            <span class="jx-service-price">
                {{ 'Rp'.number_format($service->price, 0, ',', '.') }}
                <span class="jx-service-price-note">sampai selesai</span>
            </span>

            <a href="{{ route('services.show', $service) }}" class="btn-custom btn-custom-primary btn-custom-sm">
                Lihat Detail
            </a>
        </div>
    </div>
</article>
