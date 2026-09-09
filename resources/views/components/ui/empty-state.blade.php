@props([
    'icon' => 'heroicon-o-inbox',
    'heading' => null,
    'description' => null,
])

<x-ui.card class="ui-empty">
    <div class="ui-empty__icon">
        <x-dynamic-component :component="$icon" class="h-6 w-6" />
    </div>
    @if ($heading)
        <div class="text-sm font-semibold">{{ $heading }}</div>
    @endif
    @if ($description)
        <p class="mx-auto mt-1 max-w-sm text-sm text-gray-500 dark:text-gray-400">{{ $description }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-4 flex flex-wrap justify-center gap-2">{{ $slot }}</div>
    @endif
</x-ui.card>
