@props([
    'name' => null,
    'label' => null,
    'required' => false,
    'value' => null,
    'placeholder' => null,
    'options' => [],
    'selected' => null,
])

@php
    $inputName = $name ?? $attributes->get('name');
    $inputId = $attributes->get('id') ?? ($inputName ? 'field-' . $inputName : null);
    $hasError = $inputName && $errors->has($inputName);
    $inputValue = $value ?? old($inputName, $selected);
    $inputClasses = 'input' . ($hasError ? ' input-error' : '');
    $hasSlot = trim($slot) !== '';
@endphp

<div class="form-group">
    @if($label)
        <label for="{{ $inputId }}" class="label {{ $required ? 'label-required' : '' }}">
            {{ $label }}
        </label>
    @endif

    <div class="relative">
        <select
            id="{{ $inputId }}"
            name="{{ $inputName }}"
            {{ $attributes->except(['name', 'id'])->merge(['class' => $inputClasses . ' appearance-none pr-10 cursor-pointer']) }}
        >
            @if($hasSlot)
                {{ $slot }}
            @else
                @if($placeholder)
                    <option value="">{{ $placeholder }}</option>
                @endif
                @foreach($options as $key => $option)
                    <option value="{{ $key }}" @selected((string) $inputValue === (string) $key)>{{ $option }}</option>
                @endforeach
            @endif
        </select>
        <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 flex items-center">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </span>
    </div>

    @if($hasError)
        <p class="form-error">{{ $errors->first($inputName) }}</p>
    @endif
</div>
