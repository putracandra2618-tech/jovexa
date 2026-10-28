@if (session()->has('impersonator_id'))
    <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-3 mb-0 rounded-0">
        <div>
            <i class="bi bi-person-badge me-2"></i>
            Anda sedang masuk sebagai <strong>{{ auth()->user()->name }}</strong>
            ({{ auth()->user()->role?->label() }}).
        </div>

        <form method="POST" action="{{ route('impersonation.stop') }}">
            @csrf
            <button type="submit" class="btn-custom btn-custom-light btn-custom-sm">
                <i class="bi bi-arrow-return-left me-1"></i> Kembali ke Super Admin
            </button>
        </form>
    </div>
@endif
