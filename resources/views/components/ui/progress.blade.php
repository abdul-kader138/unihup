@props([
    'value' => 0,
    'label' => null,
    'caption' => null,
])

@php($pct = max(0, min(100, (int) $value)))

<div {{ $attributes->only('class') }}>
    @if ($label || $caption)
        <div class="mb-1.5 flex items-center justify-between text-sm">
            @if ($label)<span class="font-medium">{{ $label }}</span>@endif
            @if ($caption)<span class="text-gray-500 dark:text-gray-400">{{ $caption }}</span>@endif
        </div>
    @endif
    <div class="ui-progress">
        <div class="ui-progress__bar" style="width: {{ $pct }}%"></div>
    </div>
</div>
