@props(['icon' => null])

<x-ui.card class="flex items-start gap-3 text-sm text-gray-600 dark:text-gray-300">
    @if ($icon)
        <x-dynamic-component :component="$icon" class="mt-0.5 h-5 w-5 shrink-0 text-primary-500" />
    @endif
    <div>{{ $slot }}</div>
</x-ui.card>
