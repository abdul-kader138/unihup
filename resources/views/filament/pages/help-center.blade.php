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
    </style>

    <div class="flex flex-wrap items-center gap-3">
        <input
            type="search"
            wire:model.live.debounce.400ms="search"
            placeholder="Search the Help Center…"
            class="w-full max-w-md rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5"
        />
        <select
            wire:model.live="category"
            class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5"
        >
            <option value="">All topics</option>
            @foreach ($categories as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
        </select>
        @if ($search !== '' || $category)
            <button type="button" wire:click="clearFilters" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">Clear</button>
        @endif
    </div>

    @if ($grouped->isEmpty())
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-white/5">
            No answers match that. Try a different search, or
            <a href="{{ route('filament.admin.pages.support-chat') }}" class="text-primary-600 hover:underline dark:text-primary-400">ask our team</a>.
        </div>
    @else
        <div class="space-y-6">
            @foreach ($grouped as $categoryName => $entries)
                <div>
                    <h2 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $categoryName }}</h2>
                    <div class="space-y-2">
                        @foreach ($entries as $entry)
                            <details class="group rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
                                <summary class="flex cursor-pointer items-center justify-between gap-3 p-4 text-sm font-medium">
                                    <span>{{ $entry->question }}</span>
                                    <x-heroicon-o-chevron-down class="h-4 w-4 shrink-0 text-gray-400 transition group-open:rotate-180" />
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

    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
        Can't find it? <a href="{{ route('filament.admin.pages.support-chat') }}" class="text-primary-600 hover:underline dark:text-primary-400">Open a chat with our advisors</a>.
    </div>
</x-filament-panels::page>
