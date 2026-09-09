@props(['action' => null])

<div class="flex items-center justify-between gap-3">
    <span {{ $attributes->class(['ui-eyebrow']) }}>{{ $slot }}</span>
    @if ($action)
        <span class="text-xs">{{ $action }}</span>
    @endif
</div>
