@props([
    'type' => 'button',
    'variant' => 'primary',
    'size' => null,
    'block' => false,
    'href' => null,
    'icon' => null,
    'iconRight' => null,
])

@php
    $classes = 'btn';
    if ($variant && $variant !== 'white') $classes .= ' btn-' . $variant;
    if ($variant === 'white') $classes .= ' btn-white';
    if ($size) $classes .= ' btn-' . $size;
    if ($block) $classes .= ' btn-block';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
        {{ $slot }}
        @if($iconRight)
            <x-icon :name="$iconRight" class="w-4 h-4" />
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
        {{ $slot }}
        @if($iconRight)
            <x-icon :name="$iconRight" class="w-4 h-4" />
        @endif
    </button>
@endif
