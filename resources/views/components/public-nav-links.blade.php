@props(['variant' => 'desktop'])

@php
    $isDrawer = $variant === 'drawer';
    $linkClass = $isDrawer ? 'jx-drawer-link' : 'jx-nav-link';
    $listClass = $isDrawer ? 'jx-drawer-nav' : 'jx-nav-links';
@endphp

@auth
    @if (auth()->user()->isCustomer())
        <x-customer-nav-links :variant="$variant" />
    @else
        <ul class="{{ $listClass }}">
            <li><a href="{{ route('home') }}#cara-pesan" class="{{ $linkClass }}">Cara Pesan</a></li>
            <li><a href="{{ route('home') }}#faq" class="{{ $linkClass }}">Pertanyaan</a></li>
        </ul>
    @endif
@else
    <ul class="{{ $listClass }}">
        <li><a href="{{ route('home') }}#cara-pesan" class="{{ $linkClass }}">Cara Pesan</a></li>
        <li><a href="{{ route('home') }}#faq" class="{{ $linkClass }}">Pertanyaan</a></li>
    </ul>
@endauth
