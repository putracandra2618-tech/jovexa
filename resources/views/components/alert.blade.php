@props([
    'variant' => 'info',
    'title' => null,
    'icon' => null,
    'dismissible' => true,
])

@php
    $variantIcons = [
        'success' => 'bi-check-circle',
        'danger' => 'bi-exclamation-triangle',
        'warning' => 'bi-exclamation-circle',
        'info' => 'bi-info-circle',
    ];

    $resolvedIcon = $icon ?? $variantIcons[$variant] ?? 'bi-info-circle';
@endphp

<div {{ $attributes->class(['alert', "alert-custom-{$variant}", 'alert-custom-dismissible' => $dismissible]) }}
     role="alert">
    <i class="bi {{ $resolvedIcon }} alert-custom-icon"></i>

    <div class="alert-custom-content">
        @if ($title)
            <strong>{{ $title }}</strong>
        @endif

        {{ $slot }}
    </div>

    @if ($dismissible)
        <button type="button" class="alert-custom-close" data-bs-dismiss="alert" aria-label="Tutup">
            <i class="bi bi-x-lg"></i>
        </button>
    @endif
</div>
