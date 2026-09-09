<x-filament-panels::page>
    @php($guides = $this->getGuides())
    @php($open = $this->openSlug())

    @if ($guides->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-white/5">
            No city guides published yet.
        </div>
    @else
        <div class="space-y-3">
            @foreach ($guides as $guide)
                <details class="group rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5" @if ($open === $guide->slug) open @endif>
                    <summary class="flex cursor-pointer items-center justify-between gap-3 p-4 text-sm font-semibold">
                        <span>{{ $guide->city }}@if ($guide->region)<span class="ml-2 text-xs font-normal text-gray-400">{{ $guide->region }}</span>@endif</span>
                        <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-180" />
                    </summary>

                    <div class="space-y-4 border-t border-gray-100 p-4 dark:border-white/5">
                        @if ($guide->intro)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $guide->intro }}</p>
                        @endif

                        @foreach ($guide->filledSections() as $section)
                            <div>
                                <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $section['label'] }}</h3>
                                <p class="mt-1 whitespace-pre-line text-sm text-gray-600 dark:text-gray-400">{{ $section['body'] }}</p>
                            </div>
                        @endforeach

                        @if (! empty($guide->useful_links))
                            <div class="flex flex-wrap gap-3">
                                @foreach ($guide->useful_links as $link)
                                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $link['label'] }} &rarr;
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($guide->last_verified_at)
                            <p class="text-xs text-gray-400">Last verified {{ $guide->last_verified_at->format('d M Y') }}</p>
                        @endif
                    </div>
                </details>
            @endforeach
        </div>

        <p class="text-xs text-gray-400">
            Curated overviews to help you budget and settle in — rents and prices move, so check a live index before committing.
        </p>
    @endif
</x-filament-panels::page>
