@props([
    'label' => null,
    'name',
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'rows' => 4,
    'required' => false,
    'counter' => null,
])

@php
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->class(['mb-3']) }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label-custom">{{ $label }}</label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($required) required @endif
        @if ($counter) data-char-counter="{{ $counter }}" @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        class="form-control-custom {{ $hasError ? 'is-invalid-custom' : '' }}"
    >{{ old($name, $value) }}</textarea>

    @if ($counter)
        <div class="jx-char-counter" data-char-counter-output>
            <span data-char-current>0</span>/{{ $counter }} karakter
        </div>
    @endif

    @if ($hasError)
        <div class="form-feedback-custom invalid-custom" id="{{ $name }}-error">
            <i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first($name) }}
        </div>
    @elseif ($hint)
        <span class="text-muted">{{ $hint }}</span>
    @endif
</div>
