@props([
    'label',
    'value',
    'sub' => null,
    'href' => null,
    'linkLabel' => null,
])

<x-ui.card :hover="(bool) $href" class="flex flex-col">
    <span class="ui-eyebrow">{{ $label }}</span>
    <span class="ui-stat-value mt-0.5">{{ $value }}</span>

    @if ($sub)
        <div class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">{{ $sub }}</div>
    @endif

    {{ $slot }}

    @if ($href)
        <a href="{{ $href }}" class="mt-auto pt-2 inline-block text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
            {{ $linkLabel ?? 'Open' }} &rarr;
        </a>
    @endif
</x-ui.card>
