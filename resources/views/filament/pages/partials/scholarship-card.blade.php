<div class="ui-card ui-card--pad flex flex-col">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-sm font-semibold">{{ $s['label'] }}</div>
            @if ($s['region'])
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $s['region'] }}</div>
            @endif
        </div>
        @if ($s['amount'])
            <span class="shrink-0 rounded-full bg-success-50 px-2 py-0.5 text-xs font-medium text-success-700 dark:bg-success-400/10 dark:text-success-400">{{ $s['amount'] }}</span>
        @endif
    </div>

    @if ($s['summary'])
        <p class="mt-1.5 text-xs leading-snug text-gray-600 dark:text-gray-400">{{ \Illuminate\Support\Str::limit($s['summary'], 180) }}</p>
    @endif

    <div class="mt-3 flex items-center gap-3">
        @if ($s['tracked'])
            <span class="inline-flex items-center gap-1 text-xs font-semibold text-success-600 dark:text-success-400">
                <x-heroicon-s-check class="h-3.5 w-3.5" /> Tracking
            </span>
        @else
            <button
                type="button"
                wire:click="track('{{ $s['kind'] }}', @js($s['ref']), @js($s['label']))"
                class="inline-flex items-center gap-1 rounded-lg bg-primary-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-primary-500"
            >
                <x-heroicon-o-plus class="h-3.5 w-3.5" /> Track
            </button>
        @endif

        @if ($s['url'])
            <a href="{{ $s['url'] }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Official site &rarr;</a>
        @endif
    </div>
</div>
