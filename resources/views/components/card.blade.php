@props([
    'title' => null,
    'subtitle' => null,
    'footer' => false,
])

<div {{ $attributes->class(['card']) }}>
    @if ($title || isset($actions))
        <div class="card-header">
            <div>
                @if ($title)
                    <h5 class="card-title">{{ $title }}</h5>
                @endif

                @if ($subtitle)
                    <p class="page-subtitle mt-1">{{ $subtitle }}</p>
                @endif
            </div>

            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    @endif

    {{ $slot }}

    @if ($footer)
        <div class="sparkline-card-footer">
            {{ $footer }}
        </div>
    @endif
</div>
