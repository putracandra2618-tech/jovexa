@props([
    'total' => 0,
    'search' => '',
    'route',
    'filtered' => false,
])

<div class="d-flex flex-wrap align-items-center gap-3">
    <span class="text-muted small">
        {{ number_format($total, 0, ',', '.') }} {{ $search === '' ? 'data' : 'hasil untuk' }}

        @if ($search !== '')
            <strong class="text-dark">{{ $search }}</strong>
        @endif
    </span>

    @if ($filtered)
        <a href="{{ route($route) }}" class="btn-custom btn-custom-light btn-custom-sm">
            <i class="bi bi-x-circle me-1"></i> Reset
        </a>
    @endif
</div>