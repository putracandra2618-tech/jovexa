@props([
    'label' => null,
    'name',
    'type' => 'text',
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'help' => null,
    'autocomplete' => null,
    'accept' => null,
    'required' => false,
    'multiple' => false,
])

@php
    $hasError = $errors->has($name);
    $isFile = $type === 'file';
@endphp

<div {{ $attributes->class(['mb-3']) }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label-custom">{{ $label }}</label>
    @endif

    <input
        id="{{ $name }}"
        name="{{ $name }}"
        type="{{ $type }}"
        @if (! $isFile) value="{{ old($name, $value) }}" @endif
        @if ($accept) accept="{{ $accept }}" @endif
        @if ($multiple) multiple @endif
        @if ($placeholder) placeholder="{{ $placeholder }}" @endif
        @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        class="{{ $isFile ? 'form-control-file' : 'form-control-custom' }} {{ $hasError ? 'is-invalid-custom' : '' }}"
    >

    @if ($hasError)
        <div class="form-feedback-custom invalid-custom" id="{{ $name }}-error">
            <i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first($name) }}
        </div>
    @elseif ($help)
        <span class="text-muted">{{ $help }}</span>
    @elseif ($hint)
        <span class="text-muted">{{ $hint }}</span>
    @endif
</div>
