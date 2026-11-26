@props(['variant' => 'admin'])

@auth
    @php
        $notifications = auth()->user()->inAppNotifications()->with('order')->latestFirst()->take(5)->get();
        $unreadCount = auth()->user()->inAppNotifications()->unread()->count();
    @endphp

    <div class="dropdown"
         data-notification-bell
         data-notification-mark-all-url="{{ route('notifications.read-all') }}"
         data-notification-csrf="{{ csrf_token() }}">
        <button class="{{ $variant === 'admin' ? 'navbar-action-btn' : 'jx-user-btn px-2' }} position-relative"
                type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Notifikasi">
            <i class="bi bi-bell"></i>
            @if ($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                      data-notification-badge>
                    {{ min($unreadCount, 99) }}
                </span>
            @endif
        </button>

        <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width: 22rem;">
            <li class="dropdown-header d-flex justify-content-between align-items-center">
                <span>Notifikasi</span>
                @if ($unreadCount > 0)
                    <span class="small" data-notification-unread-label>{{ $unreadCount }} belum dibaca</span>
                @endif
            </li>

            @forelse ($notifications as $notification)
                <li>
                    <div class="dropdown-item-text py-2">
                        <div class="d-flex justify-content-between gap-2">
                            <strong class="small">{{ $notification->title }}</strong>
                            <small class="text-muted text-nowrap">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                        <div class="small text-muted">{{ $notification->body }}</div>

                        @if ($notification->order)
                            @php
                                $orderRoute = auth()->user()->isCustomer()
                                    ? route('customer.orders.show', $notification->order)
                                    : (auth()->user()->isWorker()
                                        ? route('worker.orders.show', $notification->order)
                                        : route('admin.orders.show', $notification->order));
                            @endphp
                            <a href="{{ $orderRoute }}" class="small">Lihat pesanan</a>
                        @endif

                        @if ($notification->read_at === null)
                            <form method="POST" action="{{ route('notifications.read', $notification) }}"
                                  data-notification-read-form>
                                @csrf
                                <button type="submit" class="btn btn-link btn-sm p-0">Tandai dibaca</button>
                            </form>
                        @endif
                    </div>
                </li>
                @if (! $loop->last)
                    <li><hr class="dropdown-divider my-0"></li>
                @endif
            @empty
                <li class="dropdown-item-text text-muted small py-3">Belum ada notifikasi.</li>
            @endforelse

            <li><hr class="dropdown-divider my-0"></li>
            <li>
                <a href="{{ route('notifications.index') }}" class="dropdown-item text-center small">
                    Lihat semua notifikasi
                </a>
            </li>
        </ul>
    </div>
@endauth
