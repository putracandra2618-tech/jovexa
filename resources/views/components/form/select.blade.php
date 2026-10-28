@props([
    'label' => null,
    'name',
    'options' => [],
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'required' => false,
])

@php
    $hasError = $errors->has($name);
    $selected = old($name, $value);
@endphp

<div {{ $attributes->class(['mb-3']) }}>
    @if ($label)
        <label for="{{ $name }}" class="form-label-custom">{{ $label }}</label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($required) required @endif
        @if ($hasError) aria-invalid="true" aria-describedby="{{ $name }}-error" @endif
        class="form-select-custom {{ $hasError ? 'is-invalid-custom' : '' }}"
    >
        @if ($placeholder)
            <option value="" @selected($selected === null) disabled>{{ $placeholder }}</option>
        @endif

        @foreach ($options as $optionValue => $optionLabel)
            <option value="{{ $optionValue }}" @selected((string) $selected === (string) $optionValue)>
                {{ $optionLabel }}
            </option>
        @endforeach
    </select>

    @if ($hasError)
        <div class="form-feedback-custom invalid-custom" id="{{ $name }}-error">
            <i class="bi bi-exclamation-circle-fill"></i> {{ $errors->first($name) }}
        </div>
    @elseif ($hint)
        <span class="text-muted">{{ $hint }}</span>
    @endif
</div>
