@extends($layout)

@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')
@section('page-subtitle', 'Pembaruan revisi dan pengiriman hasil pesananmu.')

@section('content')
    <x-card>
        <div class="d-flex justify-content-end mb-3">
            <form method="POST" action="{{ route('notifications.read-all') }}">
                @csrf
                <button type="submit" class="btn-custom btn-custom-light btn-custom-sm">
                    <i class="bi bi-check2-all me-1"></i> Tandai Semua Dibaca
                </button>
            </form>
        </div>

        @if ($notifications->isEmpty())
            <x-empty-state
                icon="bi-bell"
                title="Belum ada notifikasi"
                description="Notifikasi revisi dan pengiriman hasil akan muncul di sini."
            />
        @else
            <ul class="list-unstyled mb-0">
                @foreach ($notifications as $notification)
                    <li class="py-3 border-bottom {{ $notification->read_at === null ? 'fw-semibold' : '' }}">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <div>
                                <div>
                                    <i class="bi bi-{{ $notification->read_at === null ? 'bell-fill' : 'bell' }} me-1"></i>
                                    {{ $notification->title }}
                                </div>
                                <div class="small mt-1">{{ $notification->body }}</div>
                            </div>
                            <div class="d-flex flex-column align-items-end gap-1">
                                <small class="text-muted">{{ $notification->created_at->format('d M Y H:i') }}</small>
                                @if ($notification->read_at === null)
                                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                                        @csrf
                                        <button type="submit" class="btn-custom btn-custom-light btn-custom-sm">
                                            Tandai dibaca
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>

            @if ($notifications->hasPages())
                <div class="mt-4 d-flex justify-content-center">{{ $notifications->links() }}</div>
            @endif
        @endif
    </x-card>
@endsection
