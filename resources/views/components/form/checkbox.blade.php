@props([
    'label',
    'name',
    'value' => null,
    'hint' => null,
])

@php
    $hasError = $errors->has($name);
@endphp

<div {{ $attributes->class(['form-check-custom', 'mb-3']) }}>
    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="checkbox"
        value="1"
        @checked(old($name, $value))
        @if ($hasError) aria-invalid="true" @endif
        class="form-check-input-custom"
    >
    <label class="form-check-label" for="{{ $name }}">{{ $label }}</label>

    @if ($hasError)
        <div class="form-feedback-custom invalid-custom">
            <i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first($name) }}
        </div>
    @elseif ($hint)
        <span class="text-muted">{{ $hint }}</span>
    @endif
</div>
