<x-filament-panels::page>
    @php($guides = $this->getGuides())
    @php($open = $this->openSlug())

    @if ($guides->isEmpty())
        <x-ui.empty-state icon="heroicon-o-building-office-2" heading="No city guides yet" description="Published city guides will appear here." />
    @else
        <div class="space-y-3">
            @foreach ($guides as $guide)
                <details class="ui-card group" style="padding:0" @if ($open === $guide->slug) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-lg p-4 text-sm font-semibold focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
                        <span>{{ $guide->city }}@if ($guide->region)<span class="ml-2 text-xs font-normal text-gray-400">{{ $guide->region }}</span>@endif</span>
                        <x-heroicon-o-chevron-down aria-hidden="true" class="h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-180" />
                    </summary>

                    <div class="space-y-4 border-t border-gray-100 p-4 dark:border-white/5">
                        @if ($guide->intro)
                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $guide->intro }}</p>
                        @endif

                        @foreach ($guide->filledSections() as $section)
                            <div>
                                <div class="ui-eyebrow">{{ $section['label'] }}</div>
                                <p class="mt-1 whitespace-pre-line text-sm text-gray-600 dark:text-gray-400">{{ $section['body'] }}</p>
                            </div>
                        @endforeach

                        @if (! empty($guide->useful_links))
                            <div class="flex flex-wrap gap-3">
                                @foreach ($guide->useful_links as $link)
                                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">{{ $link['label'] }} &rarr;</a>
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
