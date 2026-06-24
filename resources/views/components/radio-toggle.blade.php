@props([
    'name' => null,
    'label' => null,
    'required' => false,
    'value' => null,
    'checked' => null,
    'trueLabel' => 'Sí',
    'falseLabel' => 'No',
    'icon' => 'check',
])

@php
    $inputName = $name ?? $attributes->get('name');
    $hasError = $inputName && $errors->has($inputName);
    $oldValue = old($inputName);
    $currentValue = $oldValue !== null ? (bool) $oldValue : ($checked !== null ? (bool) $checked : (bool) $value);
@endphp

<div class="form-group">
    @if($label)
        <label class="label {{ $required ? 'label-required' : '' }}">{{ $label }}</label>
    @endif

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <label class="relative flex items-center gap-3 cursor-pointer p-4 rounded-xl border-[1.5px] border-slate-200 hover:border-success-400 has-[:checked]:border-success-500 has-[:checked]:bg-success-50/70 transition-all duration-200">
            <input type="radio" name="{{ $inputName }}" value="1" @checked($currentValue) class="sr-only peer">
            <span class="w-10 h-10 rounded-lg bg-success-50 flex items-center justify-center peer-checked:bg-success-500 transition-all duration-200 flex-shrink-0">
                <x-icon :name="$icon" class="w-[1.15rem] h-[1.15rem] text-success-600 peer-checked:text-white transition-colors" />
            </span>
            <div class="flex-1">
                <span class="block text-sm font-semibold text-slate-700 peer-checked:text-success-700">{{ $trueLabel }}</span>
                <span class="block text-xs text-slate-500 mt-0.5">Opción activa</span>
            </div>
            <span class="absolute top-2.5 right-2.5 w-4 h-4 rounded-full bg-success-500 flex items-center justify-center opacity-0 peer-checked:opacity-100 scale-50 peer-checked:scale-100 transition-all duration-200">
                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </span>
        </label>

        <label class="relative flex items-center gap-3 cursor-pointer p-4 rounded-xl border-[1.5px] border-slate-200 hover:border-slate-400 has-[:checked]:border-slate-500 has-[:checked]:bg-slate-100 transition-all duration-200">
            <input type="radio" name="{{ $inputName }}" value="0" @checked(!$currentValue) class="sr-only peer">
            <span class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center peer-checked:bg-slate-500 transition-all duration-200 flex-shrink-0">
                <x-icon name="x" class="w-[1.15rem] h-[1.15rem] text-slate-500 peer-checked:text-white transition-colors" />
            </span>
            <div class="flex-1">
                <span class="block text-sm font-semibold text-slate-700 peer-checked:text-slate-800">{{ $falseLabel }}</span>
                <span class="block text-xs text-slate-500 mt-0.5">Opción inactiva</span>
            </div>
            <span class="absolute top-2.5 right-2.5 w-4 h-4 rounded-full bg-slate-500 flex items-center justify-center opacity-0 peer-checked:opacity-100 scale-50 peer-checked:scale-100 transition-all duration-200">
                <svg class="w-2.5 h-2.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                </svg>
            </span>
        </label>
    </div>

    @if($hasError)
        <p class="form-error">{{ $errors->first($inputName) }}</p>
    @endif
</div>
