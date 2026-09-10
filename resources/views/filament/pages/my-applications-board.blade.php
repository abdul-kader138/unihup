<x-filament-panels::page>
    @php($board = $this->getBoard())
    @php($cardTotal = collect($board)->sum(fn ($c) => count($c['cards'])))

    @if ($cardTotal === 0)
        <x-ui.empty-state
            icon="heroicon-o-view-columns"
            heading="No saved programs yet"
            description="Save programs from Find Universities and they'll appear here, grouped by where you are with each one."
        >
            <a href="{{ \App\Filament\Pages\FindUniversities::getUrl() }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">
                <x-heroicon-o-magnifying-glass class="h-4 w-4" /> Find universities
            </a>
        </x-ui.empty-state>
    @else
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Drag a card between columns to change its status, or use the <span class="font-medium">Move to</span> menu on a card. Order within a column is saved.
        </p>

        <div
            x-data="{ dragging: null }"
            class="flex gap-3 overflow-x-auto pb-3"
            role="list"
        >
            @foreach ($board as $column)
                <section
                    role="listitem"
                    aria-label="{{ $column['label'] }}"
                    class="flex w-64 shrink-0 flex-col rounded-xl border border-gray-200 bg-gray-50/70 dark:border-white/10 dark:bg-white/[0.03]"
                    x-on:dragover.prevent
                    x-on:drop.prevent="if (dragging) { $wire.moveCard(dragging, '{{ $column['status'] }}', null); dragging = null }"
                >
                    <header class="flex items-center justify-between gap-2 border-b border-gray-200 px-3 py-2 dark:border-white/10">
                        <span class="ui-eyebrow">{{ $column['label'] }}</span>
                        <x-filament::badge :color="$column['color']" size="xs">{{ count($column['cards']) }}</x-filament::badge>
                    </header>

                    <div class="flex min-h-[3rem] flex-1 flex-col gap-2 p-2" wire:loading.class="opacity-60">
                        @foreach ($column['cards'] as $card)
                            <article
                                draggable="true"
                                x-on:dragstart="dragging = {{ $card['id'] }}"
                                x-on:dragend="dragging = null"
                                x-on:dragover.prevent
                                x-on:drop.stop.prevent="if (dragging && dragging !== {{ $card['id'] }}) { $wire.moveCard(dragging, '{{ $column['status'] }}', {{ $card['id'] }}); dragging = null }"
                                class="cursor-grab rounded-lg border border-gray-200 bg-white p-2.5 shadow-sm active:cursor-grabbing dark:border-white/10 dark:bg-white/5"
                            >
                                <div class="flex items-start gap-2">
                                    <img src="{{ $card['logo'] }}" alt="" class="h-7 w-7 shrink-0 rounded object-contain ring-1 ring-gray-200 dark:ring-white/10">
                                    <div class="min-w-0">
                                        <div class="truncate text-xs font-semibold">{{ $card['university'] }}</div>
                                        <div class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $card['program'] }}</div>
                                    </div>
                                </div>

                                <div class="mt-2 flex items-center justify-between gap-2">
                                    @if ($card['tier'])
                                        <x-filament::badge :color="$card['tier_color']" size="xs">{{ $card['tier'] }}</x-filament::badge>
                                    @else
                                        <span></span>
                                    @endif

                                    <label class="text-[10px] text-gray-500 dark:text-gray-400">
                                        <span class="sr-only">Move {{ $card['university'] }} to status</span>
                                        <select
                                            class="rounded border-gray-300 bg-transparent py-0.5 pl-1 pr-5 text-[10px] focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-white/15"
                                            x-on:change="$wire.moveCard({{ $card['id'] }}, $event.target.value, null)"
                                        >
                                            @foreach (\App\Models\ShortlistItem::STATUSES as $value => $label)
                                                <option value="{{ $value }}" @selected($value === $column['status'])>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @endif
</x-filament-panels::page>
