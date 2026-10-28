@php
    $variant = $variant ?? 'desktop';

    $customerNavigation = [
        ['route' => 'customer.dashboard', 'pattern' => 'customer.dashboard', 'label' => 'Dashboard'],
        ['route' => 'customer.orders.index', 'pattern' => 'customer.orders.*', 'label' => 'Pesanan Saya'],
        ['route' => 'services.index', 'pattern' => 'services.*', 'label' => 'Katalog Jasa'],
    ];

    $customerNavigation = array_values(array_filter(
        $customerNavigation,
        fn (array $item): bool => Route::has($item['route'])
    ));

    $isDrawer = $variant === 'drawer';
@endphp

<ul class="{{ $isDrawer ? 'jx-drawer-nav' : 'jx-nav-links' }}">
    @foreach ($customerNavigation as $item)
        <li>
            <a href="{{ route($item['route']) }}"
               class="{{ $isDrawer ? 'jx-drawer-link' : 'jx-nav-link' }} {{ request()->routeIs($item['pattern']) ? 'active' : '' }}">
                {{ $item['label'] }}
            </a>
        </li>
    @endforeach
</ul>
