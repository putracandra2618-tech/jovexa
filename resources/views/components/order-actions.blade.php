@props(['actions' => []])

@foreach ($actions as $action)
    @if ($action['type'] === 'link')
        <a href="{{ $action['route'] }}" @if ($action['key'] === 'upload-result') id="unggah-hasil" @endif
           @if (! empty($action['target'])) target="{{ $action['target'] }}" @endif
           @if (! empty($action['rel'])) rel="{{ $action['rel'] }}" @endif
           class="btn-custom btn-custom-{{ $action['variant'] }} w-100 mt-2">
            @if (! empty($action['icon'])) <i class="bi {{ $action['icon'] }} me-1"></i> @endif
            {{ $action['label'] }}
        </a>
    @else
        <form method="POST" action="{{ $action['route'] }}" class="mt-2"
              @if (isset($action['confirm'])) data-confirm="{{ $action['confirm'] }}" @endif
              @if (isset($action['file'])) enctype="multipart/form-data" @endif>
            @csrf
            @if ($action['method'] !== 'POST') @method($action['method']) @endif

            @foreach ($action['hidden'] ?? [] as $hidden)
                <input type="hidden" name="{{ $hidden['name'] }}" value="{{ $hidden['value'] }}">
            @endforeach

            @if (isset($action['file']))
                <div class="mb-3">
                    <label for="file-{{ $action['key'] }}" class="form-label-custom">{{ $action['file']['label'] }}</label>
                    <input id="file-{{ $action['key'] }}" type="file" name="{{ $action['file']['name'] }}"
                           class="form-control" required {!! $action['file']['attributes'] ?? '' !!}>
                    <small class="form-hint">{{ $action['file']['hint'] }}</small>
                    @error($action['file']['name'])
                        <div class="form-feedback-custom text-danger">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            @if (isset($action['note']))
                <div class="mb-3">
                    <label for="note-{{ $action['key'] }}" class="form-label-custom">{{ $action['note']['label'] }}</label>
                    <textarea id="note-{{ $action['key'] }}" name="{{ $action['note']['name'] }}" rows="3"
                              class="form-control-custom w-100" maxlength="1000"
                              @if ($action['note']['required']) required @endif
                              placeholder="{{ $action['note']['placeholder'] ?? '' }}"
                              data-char-counter="1000">{{ old($action['note']['name']) }}</textarea>
                    <div class="jx-char-counter" data-char-counter-output>
                        <span data-char-current>0</span>/1000 karakter
                    </div>
                    @error($action['note']['name'])
                        <div class="form-feedback-custom text-danger">{{ $message }}</div>
                    @enderror
                </div>
            @endif

            <button type="submit" class="btn-custom btn-custom-{{ $action['variant'] }} w-100">
                @if (! empty($action['icon'])) <i class="bi {{ $action['icon'] }} me-1"></i> @endif
                {{ $action['label'] }}
            </button>
        </form>
    @endif
@endforeach
