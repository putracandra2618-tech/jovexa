<header class="navbar-custom">
    <div class="navbar-left">
        <button class="btn-desktop-toggle d-none d-xl-flex align-items-center justify-content-center me-3"
                id="desktop-sidebar-toggle" aria-label="Perkecil sidebar">
            <i class="bi bi-chevron-bar-left"></i>
        </button>

        <button class="sidebar-toggle-btn me-2" id="sidebar-toggle" aria-label="Buka navigasi">
            <i class="bi bi-list"></i>
        </button>
    </div>

@php
    $searchTarget = \App\Support\AdminSearchContext::for(request());
    $search = trim((string) request()->query('q', ''));
@endphp

<div class="navbar-search-wrapper">
    @if ($searchTarget)
        <form method="GET" action="{{ route($searchTarget->action) }}" data-admin-search>
            {{-- Keep the secondary filter active when the keyword narrows the list. --}}
            @if ($searchTarget->filter && request()->filled($searchTarget->filter))
                <input type="hidden" name="{{ $searchTarget->filter }}" value="{{ request($searchTarget->filter) }}">
            @endif

            <label for="main-search" class="visually-hidden">{{ $searchTarget->placeholder }}</label>
            <input type="search" id="main-search" name="q" value="{{ $search }}" class="navbar-search-input"
                   placeholder="{{ $searchTarget->placeholder }}" autocomplete="off" data-admin-search-input>

            <button type="submit" class="navbar-search-btn" aria-label="Cari">
                <i class="bi bi-search"></i>
            </button>
        </form>
    @endif
</div>

    <div class="navbar-actions">
        <button class="navbar-action-btn me-1" aria-label="Mode layar penuh" id="btn-fullscreen">
            <i class="bi bi-arrows-fullscreen"></i>
        </button>

        @auth
            <div class="dropdown ms-2">
                <button class="navbar-profile-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                        aria-expanded="false" id="profile-dropdown">
                    <img src="{{ asset('vendor/spark/images/avatar.png') }}" alt="Foto profil"
                         class="navbar-profile-img">
                    <span class="navbar-profile-name d-none d-md-inline">{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down navbar-profile-caret"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end dropdown-menu-profile" aria-labelledby="profile-dropdown">
                    <li class="dropdown-header">{{ auth()->user()->email }}</li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}" data-turbo="false">
                            @csrf
                            <button type="submit" class="dropdown-item text-danger">
                                <i class="bi bi-box-arrow-right"></i> Keluar
                            </button>
                        </form>
                    </li>
                </ul>
            </div>
        @endauth
    </div>
</header>
