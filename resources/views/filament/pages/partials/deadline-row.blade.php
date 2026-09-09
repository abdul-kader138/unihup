@php($days = $deadline->daysUntil())
@php($color = \App\Models\Deadline::CATEGORY_COLORS[$deadline->category] ?? 'gray')

<div @class([
    'ui-card ui-card--pad flex flex-wrap items-start gap-3',
    'opacity-70' => $past,
])>
    <div class="flex w-16 shrink-0 flex-col items-center rounded-lg bg-gray-100 py-1.5 text-center dark:bg-white/10">
        <span class="text-[0.65rem] font-semibold uppercase text-gray-500 dark:text-gray-400">{{ $deadline->due_at->format('M') }}</span>
        <span class="text-lg font-bold leading-none">{{ $deadline->due_at->format('j') }}</span>
        <span class="text-[0.65rem] text-gray-500 dark:text-gray-400">{{ $deadline->due_at->format('Y') }}</span>
    </div>

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-2">
            <span class="font-medium">{{ $deadline->title }}</span>
            <x-filament::badge :color="$color" size="sm">{{ $deadline->categoryLabel() }}</x-filament::badge>
            @if ($deadline->due_precision !== 'day')
                <span class="text-xs text-gray-400">({{ \Illuminate\Support\Str::lower(\App\Models\Deadline::PRECISIONS[$deadline->due_precision] ?? $deadline->due_precision) }})</span>
            @endif
        </div>
        <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
            {{ $deadline->scopeName() }}@if ($deadline->cycle_label) · {{ $deadline->cycle_label }}@endif
        </div>
        @if ($deadline->description)
            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $deadline->description }}</p>
        @endif
        @if ($deadline->url)
            <a href="{{ $deadline->url }}" target="_blank" rel="noopener" class="mt-1 inline-block text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">Official page &rarr;</a>
        @endif
    </div>

    <div class="shrink-0 text-right text-xs">
        @if ($past)
            <span class="text-gray-400">{{ abs($days) }}d ago</span>
        @elseif ($days === 0)
            <span class="rounded-full bg-danger-50 px-2 py-0.5 font-semibold text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">Today</span>
        @elseif ($days <= 7)
            <span class="rounded-full bg-danger-50 px-2 py-0.5 font-semibold text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">in {{ $days }}d</span>
        @elseif ($days <= 30)
            <span class="rounded-full bg-warning-50 px-2 py-0.5 font-medium text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">in {{ $days }}d</span>
        @else
            <span class="text-gray-500 dark:text-gray-400">in {{ $days }}d</span>
        @endif
    </div>
</div>
