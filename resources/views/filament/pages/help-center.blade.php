<x-filament-panels::page>
    @php($grouped = $this->getGroupedEntries())
    @php($categories = $this->getCategories())

    {{-- Self-contained answer styling: the Filament theme build doesn't
         guarantee the Tailwind Typography (`prose`) classes, so scope the
         essentials here instead. --}}
    <style>
        .faq-answer { font-size: 0.875rem; line-height: 1.6; }
        .faq-answer > :first-child { margin-top: 0; }
        .faq-answer > :last-child { margin-bottom: 0; }
        .faq-answer p { margin: 0.5rem 0; }
        .faq-answer ul, .faq-answer ol { margin: 0.5rem 0; padding-left: 1.25rem; }
        .faq-answer ul { list-style: disc; }
        .faq-answer ol { list-style: decimal; }
        .faq-answer li { margin: 0.25rem 0; }
        .faq-answer a { color: rgb(var(--primary-600, 37 99 235)); text-decoration: underline; }
        .faq-answer code { font-size: 0.85em; padding: 0.1em 0.3em; border-radius: 0.25rem; background: rgb(0 0 0 / 0.06); }
        .dark .faq-answer code { background: rgb(255 255 255 / 0.1); }
        details.faq[open] .faq-chevron { transform: rotate(180deg); }
    </style>

    <x-ui.card>
        <div class="flex flex-wrap items-center gap-3">
            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                placeholder="Search the Help Center…"
                aria-label="Search the Help Center"
                class="w-full max-w-md rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5"
            />
            <select
                wire:model.live="category"
                aria-label="Filter answers by topic"
                class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5"
            >
                <option value="">All topics</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat }}">{{ $cat }}</option>
                @endforeach
            </select>
            @if ($search !== '' || $category)
                <button type="button" wire:click="clearFilters" class="rounded text-xs font-semibold text-primary-600 hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:text-primary-400">Clear</button>
            @endif
        </div>
    </x-ui.card>

    @if ($grouped->isEmpty() && empty($this->getGlossary()))
        <x-ui.empty-state icon="heroicon-o-magnifying-glass" heading="No matching answers">
            <a href="{{ route('filament.admin.pages.support-chat') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">
                <x-heroicon-o-chat-bubble-left-right class="h-4 w-4" /> Ask our team
            </a>
        </x-ui.empty-state>
    @elseif ($grouped->isNotEmpty())
        <div class="space-y-6">
            @foreach ($grouped as $categoryName => $entries)
                <div>
                    <div class="ui-eyebrow mb-2">{{ $categoryName }}</div>
                    <div class="space-y-2">
                        @foreach ($entries as $entry)
                            <details class="faq ui-card" style="padding:0">
                                <summary class="flex cursor-pointer list-none items-center justify-between gap-3 rounded-lg p-4 text-sm font-medium focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary-500">
                                    <span>{{ $entry->question }}</span>
                                    <x-heroicon-o-chevron-down aria-hidden="true" class="faq-chevron h-4 w-4 shrink-0 text-gray-400 transition" />
                                </summary>
                                <div class="faq-answer border-t border-gray-100 p-4 text-gray-600 dark:border-white/5 dark:text-gray-300">
                                    {!! $entry->answerHtml() !!}
                                </div>
                            </details>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @php($glossary = $this->getGlossary())
    @if (! empty($glossary))
        <div>
            <div class="ui-eyebrow mb-2">Glossary — Italian study &amp; visa terms</div>
            <div class="ui-grid ui-grid--2">
                @foreach ($glossary as $g)
                    <x-ui.card class="text-sm">
                        <div class="font-semibold">{{ $g['term'] }}</div>
                        <p class="mt-1 text-xs leading-snug text-gray-600 dark:text-gray-400">{{ $g['definition'] }}</p>
                    </x-ui.card>
                @endforeach
            </div>
        </div>
    @endif

    <x-ui.card class="text-xs text-gray-500 dark:text-gray-400">
        Can't find it? <a href="{{ route('filament.admin.pages.support-chat') }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Open a chat with our advisors</a>.
    </x-ui.card>
</x-filament-panels::page>
