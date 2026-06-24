@props([
    'name' => null,
    'label' => null,
    'required' => false,
    'value' => null,
    'rows' => 3,
    'placeholder' => null,
    'hint' => null,
])

@php
    $inputName = $name ?? $attributes->get('name');
    $inputId = $attributes->get('id') ?? ($inputName ? 'field-' . $inputName : null);
    $hasError = $inputName && $errors->has($inputName);
    $inputValue = $value ?? old($inputName);
    $inputClasses = 'input resize-y min-h-[100px]' . ($hasError ? ' input-error' : '');
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $inputId }}" class="label {{ $required ? 'label-required' : '' }}">
            {{ $label }}
        </label>
    @endif

    <textarea
        id="{{ $inputId }}"
        name="{{ $inputName }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if($required) required @endif
        {{ $attributes->except(['name', 'id'])->merge(['class' => $inputClasses]) }}
    >{{ $inputValue }}</textarea>

    @if($hint)
        <p class="form-hint">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ $hint }}
        </p>
    @endif

    @if($hasError)
        <p class="form-error">{{ $errors->first($inputName) }}</p>
    @endif
</div>
