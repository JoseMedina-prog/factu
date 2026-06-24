@props([
    'name' => null,
    'label' => null,
    'type' => 'text',
    'required' => false,
    'value' => null,
    'placeholder' => null,
    'hint' => null,
    'autocomplete' => null,
    'icon' => null,
])

@php
    $inputName = $name ?? $attributes->get('name');
    $inputId = $attributes->get('id') ?? ($inputName ? 'field-' . $inputName : null);
    $hasError = $inputName && $errors->has($inputName);
    $inputValue = $value ?? old($inputName);
    $inputClasses = 'input' . ($hasError ? ' input-error' : '');
    $isPassword = $type === 'password';
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $inputId }}" class="label {{ $required ? 'label-required' : '' }}">
            {{ $label }}
        </label>
    @endif

    @if($icon || $isPassword)
        <div class="input-group">
            @if($icon)
                <span class="input-group-icon">
                    <x-icon :name="$icon" class="w-[1.05rem] h-[1.05rem]" />
                </span>
            @endif

            <input
                type="{{ $isPassword ? 'password' : $type }}"
                id="{{ $inputId }}"
                name="{{ $inputName }}"
                value="{{ $inputValue }}"
                placeholder="{{ $placeholder }}"
                @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
                @if($required) required @endif
                @if($isPassword) data-password-toggle {{ $attributes->except(['name', 'id'])->merge(['class' => $inputClasses]) }} @else {{ $attributes->except(['name', 'id'])->merge(['class' => $inputClasses]) }} @endif
            >

            @if($isPassword)
                <button
                    type="button"
                    class="input-group-action"
                    data-password-toggle-btn
                    aria-label="Mostrar u ocultar contraseña"
                    tabindex="-1"
                >
                    <x-icon name="eye" class="w-[1.05rem] h-[1.05rem]" data-password-toggle-show />
                    <x-icon name="eye-off" class="w-[1.05rem] h-[1.05rem] hidden" data-password-toggle-hide />
                </button>
            @endif
        </div>
    @else
        <input
            type="{{ $type }}"
            id="{{ $inputId }}"
            name="{{ $inputName }}"
            value="{{ $inputValue }}"
            placeholder="{{ $placeholder }}"
            @if($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if($required) required @endif
            {{ $attributes->except(['name', 'id'])->merge(['class' => $inputClasses]) }}
        >
    @endif

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
