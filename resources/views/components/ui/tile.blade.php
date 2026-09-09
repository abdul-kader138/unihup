@props([
    'title',
    'description' => null,
    'icon' => 'heroicon-o-arrow-right',
    'href' => '#',
])

<a href="{{ $href }}" class="ui-card ui-card--pad ui-card--hover group flex items-start gap-3">
    <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-500/10 text-primary-600 transition group-hover:bg-primary-500/15 dark:text-primary-400">
        <x-dynamic-component :component="$icon" class="h-5 w-5" />
    </span>
    <span class="min-w-0">
        <span class="block text-sm font-semibold">{{ $title }}</span>
        @if ($description)
            <span class="mt-0.5 block text-xs text-gray-500 dark:text-gray-400">{{ $description }}</span>
        @endif
    </span>
</a>
