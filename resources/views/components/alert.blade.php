@props([
    'value' => null,
    'type' => 'success',
    'dismissible' => false,
])

<div {{ $attributes->merge(['class' => 'alert alert-' . $type]) }} role="alert">
    <div class="flex-1">
        {{ $slot }}
    </div>
    @if($dismissible)
        <button type="button" @click="$el.parentElement.remove()" class="ml-auto -mr-1 p-1 rounded hover:bg-black/5" aria-label="Cerrar">
            <x-icon name="x" class="w-4 h-4" />
        </button>
    @endif
</div>
