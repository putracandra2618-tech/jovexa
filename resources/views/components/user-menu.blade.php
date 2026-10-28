@props(['variant' => 'desktop', 'drawerId' => null])

@auth
    @if ($variant === 'drawer')
        <div class="jx-drawer-user">
            <span class="jx-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
            <span class="jx-drawer-user-meta">
                <strong>{{ auth()->user()->name }}</strong>
                <small>{{ auth()->user()->email }}</small>
            </span>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn-custom btn-custom-light w-100"
                    data-bs-dismiss="offcanvas" @if ($drawerId) data-bs-target="#{{ $drawerId }}" @endif>
                <i class="bi bi-box-arrow-right me-2"></i> Keluar
            </button>
        </form>
    @else
        <div class="dropdown">
            <button class="jx-user-btn dropdown-toggle" type="button" data-bs-toggle="dropdown"
                    aria-expanded="false">
                <span class="jx-user-avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                <span class="jx-user-name">{{ auth()->user()->name }}</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li>
                    <span class="dropdown-item-text">
                        <strong>{{ auth()->user()->name }}</strong>
                        <small class="d-block text-muted">{{ auth()->user()->email }}</small>
                    </span>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="bi bi-box-arrow-right me-2"></i> Keluar
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    @endif
@endauth
