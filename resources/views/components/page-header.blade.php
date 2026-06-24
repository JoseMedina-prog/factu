@props([
    'title' => null,
    'subtitle' => null,
    'back' => null,
    'icon' => null,
])

<div class="page-header">
    @if($back)
        <a href="{{ $back }}" class="back-btn mb-3" aria-label="Volver">
            <x-icon name="arrow-left" class="w-5 h-5" />
        </a>
    @endif

    <div class="page-header-actions">
        <div class="flex items-center gap-4">
            @if($icon)
                <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 flex items-center justify-center shadow-lg shadow-primary-600/20 flex-shrink-0">
                    <x-icon :name="$icon" class="w-6 h-6 text-white" />
                </div>
            @endif
            <div>
                @if($title)
                    <h1 class="page-title">{{ $title }}</h1>
                @endif
                @if($subtitle)
                    <p class="page-subtitle">{{ $subtitle }}</p>
                @endif
            </div>
        </div>
        @isset($actions)
            <div class="flex items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>
