@props([
    'variant' => 'pending',
])

<span {{ $attributes->class(['badge-table', $variant]) }}>{{ $slot }}</span>
