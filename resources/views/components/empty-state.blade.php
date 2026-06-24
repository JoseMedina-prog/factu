@props([
    'title' => null,
    'description' => null,
    'icon' => null,
    'action' => null,
    'actionHref' => null,
    'actionLabel' => null,
])

<div {{ $attributes->merge(['class' => 'text-center py-12']) }}>
    <div class="inline-flex items-center justify-center w-12 h-12 bg-slate-100 rounded-full mb-3">
        @if($icon)
            <x-icon :name="$icon" class="w-6 h-6 text-slate-400" />
        @else
            <x-icon name="inbox" class="w-6 h-6 text-slate-400" />
        @endif
    </div>

    @if($title)
        <p class="text-slate-700 font-medium">{{ $title }}</p>
    @endif

    @if($description)
        <p class="text-slate-500 text-sm mt-1">{{ $description }}</p>
    @endif

    @if($actionHref && $actionLabel)
        <a href="{{ $actionHref }}" class="inline-block mt-3 text-sm font-medium text-primary-600 hover:underline">
            {{ $actionLabel }}
        </a>
    @elseif($action)
        <div class="mt-3">{{ $action }}</div>
    @endif

    {{ $slot }}
</div>
