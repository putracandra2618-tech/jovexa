@props([
    'icon' => 'bi-inbox',
    'title',
    'description' => null,
])

<div {{ $attributes->class(['empty-state', 'text-center', 'py-5']) }}>
    <i class="bi {{ $icon }} empty-state-icon d-block mb-3"></i>

    <h5 class="card-title">{{ $title }}</h5>

    @if ($description)
        <p class="page-subtitle mt-2 mb-0">{{ $description }}</p>
    @endif

    @isset($actions)
        <div class="mt-4 d-flex justify-content-center gap-2">
            {{ $actions }}
        </div>
    @endisset
</div>
