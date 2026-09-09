@props(['done' => false, 'sectionKey'])

<button
    type="button"
    wire:click="toggleGuideSection('{{ $sectionKey }}')"
    wire:loading.attr="disabled"
    @class([
        'ml-auto inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium transition',
        'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-white/10 dark:text-gray-300 dark:hover:bg-white/15' => ! $done,
        'bg-success-100 text-success-700 dark:bg-success-400/15 dark:text-success-300' => $done,
    ])
>
    @if ($done)
        <x-heroicon-s-check class="h-3.5 w-3.5" />
        Read
    @else
        <x-heroicon-o-check class="h-3.5 w-3.5" />
        Mark as read
    @endif
</button>
