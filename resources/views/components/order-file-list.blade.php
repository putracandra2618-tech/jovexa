@props([
    'title' => 'File',
    'files',
    'order',
    'label' => null,
    'route',
    'emptyMessage' => 'Belum ada file yang dilampirkan.',
    'variant' => 'customer',
    'deletable' => false,
    'showLabel' => true,
    'class' => 'mt-4',
])

@php
    $isCustomerVariant = $variant === 'customer';
@endphp

<x-card :title="$title" :class="$class">
    @if ($files->isEmpty())
        <p class="page-subtitle mb-0">{{ $emptyMessage }}</p>
    @else
        <ul class="{{ $isCustomerVariant ? 'jx-file-list' : 'list-unstyled mb-0' }}">
            @foreach ($files as $file)
                @php
                    $showDownload = ! $isCustomerVariant || ! $file->isDeliverable() || $order->canDownloadResults();
                @endphp

                <li class="{{ $isCustomerVariant ? 'jx-file-item' : 'd-flex align-items-center justify-content-between py-2 border-bottom' }}">
                    <span class="{{ $isCustomerVariant ? 'jx-file-icon' : 'd-flex align-items-center gap-2' }}">
                        <i class="bi {{ $file->icon() }} {{ $isCustomerVariant ? '' : 'fs-5 text-muted' }}"></i>

                        @unless ($isCustomerVariant)
                            <div>
                                <div class="fw-semibold">{{ $file->file_name }}</div>
                                <small class="text-muted">
                                    @if ($showLabel && $label){{ $label }} &middot; @endif{{ $file->readableSize() }}
                                </small>
                            </div>
                        @endunless
                    </span>

                    @if ($isCustomerVariant)
                        <div class="jx-file-info">
                            <div class="jx-file-name">{{ $file->file_name }}</div>
                            <small class="text-muted">
                                @if ($showLabel && $label){{ $label }} &middot; @endif{{ $file->readableSize() }}
                            </small>
                        </div>
                    @endif

                    <div class="d-flex gap-2">
                        @if ($showDownload)
                            <a href="{{ route($route, [$order, $file]) }}"
                               class="btn-custom btn-custom-light btn-custom-sm">
                                <i class="bi bi-download {{ $isCustomerVariant ? '' : 'me-1' }}"></i> Unduh
                            </a>
                        @endif

                        @if ($deletable && $file->isDeliverable() && $order->status === \App\Enums\OrderStatus::Completed)
                            <form method="POST" action="{{ route('worker.orders.file.destroy', [$order, $file]) }}"
                                  data-confirm="Yakin ingin menghapus file ini? File yang dihapus tidak dapat dikembalikan.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-custom btn-custom-danger btn-custom-sm">
                                    <i class="bi bi-trash me-1"></i> Hapus
                                </button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-card>
