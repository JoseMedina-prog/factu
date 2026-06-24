@props(['hover' => false])

@php
    $classes = 'card';
    if ($hover) $classes .= ' card-hover';
@endphp

<div {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</div>
