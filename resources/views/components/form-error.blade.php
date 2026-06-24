@props([
    'name' => null,
    'message' => null,
])

@if($name)
    @error($name)
        <p {{ $attributes->merge(['class' => 'form-error']) }}>{{ $message ?? $message }}</p>
    @enderror
@else
    <p {{ $attributes->merge(['class' => 'form-error']) }}>{{ $message }}</p>
@endif
