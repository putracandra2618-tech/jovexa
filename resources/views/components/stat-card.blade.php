@props([
    'label',
    'value',
    'icon' => null,
    'trend' => null,
    'trendDirection' => 'up',
    'href' => null,
])

<div {{ $attributes->class(['card', 'card-stat', 'd-flex', 'flex-column', 'justify-content-between']) }}>
    <div>
        <div class="card-header">
            <span class="stat-label">
                @if ($icon)
                    <i class="bi {{ $icon }} me-2"></i>
                @endif
                {{ $label }}
            </span>
        </div>

        <div class="stat-value">{{ $value }}</div>

        @if ($trend)
            <div class="trend-badge trend-{{ $trendDirection === 'up' ? 'up' : 'down' }}">
                <i class="bi bi-arrow-{{ $trendDirection === 'up' ? 'up-right' : 'down-right' }}"></i>
                <span>{{ $trend }}</span>
            </div>
        @endif
    </div>

    @if (isset($footer))
        <div class="sparkline-card-footer">
            {{ $footer }}
        </div>
    @elseif ($href)
        <a href="{{ $href }}" class="alert-green-link mt-3">
            <span>Selengkapnya</span>
            <i class="bi bi-arrow-right"></i>
        </a>
    @endif
</div>
