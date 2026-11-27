@php
    $user = auth()->user();
    $isWorker = $user?->isWorker() === true;

    $navigation = $isWorker
        ? [
            [
                'label' => 'Menu Worker',
                'items' => [
                    ['route' => 'worker.dashboard', 'pattern' => 'worker.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-fill'],
                    ['route' => 'worker.queue.index', 'pattern' => 'worker.queue.*', 'label' => 'Antrean Pesanan', 'icon' => 'bi-inbox'],
                    ['route' => 'worker.orders.index', 'pattern' => 'worker.orders.index', 'label' => 'Pesanan Saya', 'icon' => 'bi-briefcase'],
                    ['route' => 'worker.orders.history', 'pattern' => 'worker.orders.history', 'label' => 'Riwayat Saya', 'icon' => 'bi-clock-history'],
                ],
            ],
        ]
        : [
            [
                'label' => 'Menu',
                'items' => array_values(array_filter([
                    ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'bi-grid-fill'],
                    ['route' => 'admin.services.index', 'pattern' => 'admin.services.*', 'label' => 'Kelola Jasa', 'icon' => 'bi-box-seam'],
                    [
                        'route' => 'admin.orders.index',
                        'label' => 'Pesanan',
                        'icon' => 'bi-briefcase',
                        'children' => array_values(array_filter([
                            ['route' => 'admin.orders.index', 'pattern' => 'admin.orders.index', 'label' => 'Daftar Pesanan', 'icon' => 'bi-list-ul'],
                            ['route' => 'admin.orders.revision', 'pattern' => 'admin.orders.revision', 'label' => 'Pesanan Revisi', 'icon' => 'bi-arrow-repeat'],
                            ['route' => 'admin.orders.history', 'pattern' => 'admin.orders.history', 'label' => 'Riwayat Pesanan', 'icon' => 'bi-clock-history'],
                        ])),
                    ],
                    $user?->isSuperAdmin()
                        ? ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'label' => 'Kelola Pengguna', 'icon' => 'bi-people']
                        : null,
                    [
                        'route' => 'admin.trash.services',
                        'label' => 'Soft Delete',
                        'icon' => 'bi-trash3',
                        'children' => array_values(array_filter([
                            $user?->isSuperAdmin()
                                ? ['route' => 'admin.trash.users', 'pattern' => 'admin.trash.users', 'label' => 'Pengguna', 'icon' => 'bi-people']
                                : null,
                            ['route' => 'admin.trash.services', 'pattern' => 'admin.trash.services', 'label' => 'Jasa', 'icon' => 'bi-box-seam'],
                        ])),
                    ],
                ])),
            ],
        ];

    $navigation = array_values(array_filter(array_map(function (array $group): array {
        $group['items'] = array_values(array_filter(
            $group['items'],
            fn (array $item): bool => Route::has($item['route'])
        ));

        return $group;
    }, $navigation), fn (array $group): bool => $group['items'] !== []));
@endphp

<div class="sidebar-wrapper" id="sidebar">
    <a href="{{ route('home') }}" class="sidebar-brand" data-turbo="false">
        <i class="bi bi-asterisk"></i>
        <span>{{ config('app.name') }}</span>
    </a>

    <div class="flex-grow-1 overflow-y-auto">
        @foreach ($navigation as $group)
            <div class="sidebar-menu-section">
                <div class="sidebar-menu-title">{{ $group['label'] }}</div>
                <ul class="sidebar-menu-list">
                    @foreach ($group['items'] as $item)
                        @if (isset($item['children']) && $item['children'] !== [])
                            @php
                                $submenuId = 'submenu-' . str_replace('.', '-', $item['route']);
                                $hasActiveChild = collect($item['children'])
                                    ->contains(fn (array $child): bool => request()->routeIs($child['pattern']));
                            @endphp
                            <li class="sidebar-menu-item">
                                <a href="#"
                                   class="sidebar-menu-link {{ $hasActiveChild ? 'active' : '' }}"
                                   data-bs-toggle="collapse"
                                   data-bs-target="#{{ $submenuId }}"
                                   aria-expanded="{{ $hasActiveChild ? 'true' : 'false' }}">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                    <i class="bi bi-chevron-down dropdown-caret"></i>
                                </a>
                                <ul class="sidebar-submenu collapse {{ $hasActiveChild ? 'show' : '' }}" id="{{ $submenuId }}">
                                    @foreach ($item['children'] as $child)
                                        <li>
                                            <a href="{{ route($child['route']) }}"
                                               class="sidebar-menu-link {{ request()->routeIs($child['pattern']) ? 'active' : '' }}"
                                               title="{{ $child['label'] }}">
                                                <i class="bi {{ $child['icon'] }}"></i>
                                                <span>{{ $child['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </li>
                        @else
                            <li class="sidebar-menu-item">
                                <a href="{{ route($item['route']) }}"
                                   class="sidebar-menu-link {{ request()->routeIs($item['pattern']) ? 'active' : '' }}"
                                   title="{{ $item['label'] }}">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    @auth
        <div class="sidebar-profile">
            <img src="{{ asset('vendor/spark/images/avatar.png') }}" alt="{{ auth()->user()->name }}"
                 class="sidebar-profile-img">
            <div class="sidebar-profile-info">
                <div class="sidebar-profile-name">{{ auth()->user()->name }}</div>
                <div class="sidebar-profile-email">
                    {{ auth()->user()->role?->label() }}
                    @if (auth()->user()->isWorker())
                        &middot; {{ auth()->user()->is_online ? 'Online' : 'Offline' }}
                    @endif
                </div>
            </div>
        </div>
    @endauth
</div>
