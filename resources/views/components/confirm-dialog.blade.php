@props([
    'id' => 'confirm-dialog-' . uniqid(),
    'title' => '¿Estás seguro?',
    'message' => 'Esta acción no se puede deshacer.',
    'confirmText' => 'Confirmar',
    'cancelText' => 'Cancelar',
    'variant' => 'danger',
    'formAction' => null,
    'formMethod' => 'POST',
])

<div
    x-data="{ open: false }"
    @open-confirm.window="open = true"
    x-cloak
>
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-sm"
        @keydown.escape.window="open = false"
        style="display: none;"
    >
        <div
            x-show="open"
            x-transition
            @click.outside="open = false"
            class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6"
            role="alertdialog"
            aria-modal="true"
            :aria-labelledby="'{{ $id }}-title'"
            :aria-describedby="'{{ $id }}-message'"
        >
            <div class="flex items-start gap-4 mb-4">
                <div class="flex-shrink-0 w-10 h-10 rounded-full bg-{{ $variant === 'danger' ? 'danger' : 'warning' }}-50 flex items-center justify-center">
                    <x-icon name="x" class="w-5 h-5 text-{{ $variant === 'danger' ? 'danger' : 'warning' }}-600" />
                </div>
                <div class="flex-1">
                    <h3 :id="'{{ $id }}-title'" class="text-lg font-semibold text-slate-900">
                        {{ $title }}
                    </h3>
                    <p :id="'{{ $id }}-message'" class="text-sm text-slate-500 mt-1">
                        {{ $message }}
                    </p>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button type="button" @click="open = false" class="btn btn-outline">
                    {{ $cancelText }}
                </button>
                <form
                    x-ref="form"
                    @if($formAction) action="{{ $formAction }}" @endif
                    method="POST"
                    class="inline"
                >
                    @csrf
                    @if($formMethod !== 'POST')
                        @method($formMethod)
                    @endif
                    <button type="submit" class="btn btn-{{ $variant }}">
                        {{ $confirmText }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
