<x-filament-panels::page>
    @php($tray = $this->getTray())
    @php($comparison = $this->getComparison())
    @php($total = $this->getTotalShortlisted())
    @php($colCount = count($comparison['programs']))

    @if ($total === 0)
        <x-ui.empty-state
            icon="heroicon-o-table-cells"
            heading="Nothing to compare yet"
            description="Save at least two programs from Find Universities and they'll line up side by side here."
        >
            <a href="{{ \App\Filament\Pages\FindUniversities::getUrl() }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-500">
                <x-heroicon-o-magnifying-glass class="h-4 w-4" /> Find universities
            </a>
        </x-ui.empty-state>
    @else
        {{-- Chip tray: pick which saved programs sit in the table --}}
        <x-ui.card>
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <span class="ui-eyebrow">Choose which to compare</span>
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    {{ count($this->selected) }} of {{ $total }} selected · up to {{ \App\Filament\Pages\CompareShortlist::MAX_COLUMNS }}
                </span>
            </div>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($tray as $chip)
                    @php($locked = ! $chip['selected'] && $this->isAtCapacity())
                    <button
                        type="button"
                        wire:click="toggle({{ $chip['id'] }})"
                        aria-pressed="{{ $chip['selected'] ? 'true' : 'false' }}"
                        @disabled($locked)
                        @if ($locked) title="Remove one first — the table holds {{ \App\Filament\Pages\CompareShortlist::MAX_COLUMNS }}" @endif
                        class="inline-flex max-w-[15rem] items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 focus-visible:ring-offset-1
                            @if ($chip['selected'])
                                border-primary-500 bg-primary-50 text-primary-700 hover:bg-primary-100 dark:border-primary-400/40 dark:bg-primary-400/10 dark:text-primary-300
                            @elseif ($locked)
                                cursor-not-allowed border-gray-200 text-gray-400 dark:border-white/10 dark:text-gray-600
                            @else
                                border-gray-300 text-gray-700 hover:bg-gray-50 dark:border-white/15 dark:text-gray-300 dark:hover:bg-white/5
                            @endif"
                    >
                        <img src="{{ $chip['logo'] }}" alt="" class="h-4 w-4 shrink-0 rounded object-contain">
                        <span class="truncate">{{ $chip['university'] }}</span>
                        @if ($chip['selected'])
                            <x-heroicon-s-check class="h-3.5 w-3.5 shrink-0" />
                        @endif
                    </button>
                @endforeach
            </div>
        </x-ui.card>

        @if ($colCount === 0)
            <x-ui.empty-state
                icon="heroicon-o-cursor-arrow-rays"
                heading="Pick a couple to line up"
                description="Select two or more programs above to see them side by side."
            />
        @else
            <div class="flex flex-wrap items-center justify-between gap-2">
                <button
                    type="button"
                    wire:click="toggleOnlyDifferences"
                    aria-pressed="{{ $this->onlyDifferences ? 'true' : 'false' }}"
                    class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500
                        {{ $this->onlyDifferences
                            ? 'border-primary-500 bg-primary-50 text-primary-700 dark:border-primary-400/40 dark:bg-primary-400/10 dark:text-primary-300'
                            : 'border-gray-300 text-gray-600 hover:bg-gray-50 dark:border-white/15 dark:text-gray-300 dark:hover:bg-white/5' }}"
                >
                    <x-heroicon-o-funnel class="h-4 w-4" />
                    {{ $this->onlyDifferences ? 'Showing differences only' : 'Only show differences' }}
                    @if ($this->onlyDifferences && $comparison['hidden_rows'] > 0)
                        <span class="rounded-full bg-primary-600 px-1.5 text-[10px] text-white">{{ $comparison['hidden_rows'] }} hidden</span>
                    @endif
                </button>

                <a href="{{ $this->getPdfUrl() }}" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:border-white/15 dark:text-gray-300 dark:hover:bg-white/5">
                    <x-heroicon-o-arrow-down-tray class="h-4 w-4" /> Download PDF
                </a>
            </div>

            <div class="ui-card overflow-x-auto" style="padding:0">
                <table class="w-full min-w-[640px] border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th scope="col" class="sticky left-0 z-10 w-40 bg-gray-50 p-3 text-left align-bottom dark:bg-[#181f33]">
                                <span class="ui-eyebrow">Field</span>
                            </th>
                            @foreach ($comparison['programs'] as $program)
                                <th scope="col" class="p-3 text-left align-bottom">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <img src="{{ $program['logo'] }}" alt="" class="h-8 w-8 shrink-0 rounded-md object-contain ring-1 ring-gray-200 dark:ring-white/10">
                                            <span class="font-semibold">{{ $program['university'] }}</span>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="remove({{ $program['id'] }})"
                                            aria-label="Remove {{ $program['university'] }} from comparison"
                                            title="Remove from comparison"
                                            class="shrink-0 rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary-500 dark:hover:bg-white/10 dark:hover:text-gray-200"
                                        >
                                            <x-heroicon-o-x-mark class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400">{{ $program['name'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    @foreach ($comparison['groups'] as $group)
                        <tbody>
                            <tr class="bg-gray-50/60 dark:bg-white/[0.03]">
                                <th scope="colgroup" colspan="{{ $colCount + 1 }}"
                                    class="sticky left-0 px-3 py-1.5 text-left">
                                    <span class="ui-eyebrow text-primary-600 dark:text-primary-400">{{ $group['label'] }}</span>
                                </th>
                            </tr>
                            @foreach ($group['rows'] as $row)
                                <tr class="border-t border-gray-100 dark:border-white/5">
                                    <th scope="row" class="sticky left-0 z-10 bg-white p-3 text-left align-top font-normal dark:bg-[#131c31]">
                                        <span class="ui-eyebrow">{{ $row['label'] }}</span>
                                    </th>
                                    @foreach ($row['values'] as $value)
                                        <td @class([
                                            'whitespace-pre-line p-3 align-top',
                                            'bg-success-50 dark:bg-success-500/10' => $value['best'],
                                        ])>
                                            @if ($row['label'] === 'Official page' && $value['display'] !== '—')
                                                <a href="{{ $value['display'] }}" target="_blank" rel="noopener" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Admission page &rarr;</a>
                                            @else
                                                {{ $value['display'] }}
                                            @endif
                                            @if ($value['best'])
                                                <span class="ml-1 inline-flex items-center rounded bg-success-600 px-1 text-[10px] font-semibold uppercase tracking-wide text-white">Best</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    @endforeach
                </table>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                <strong>Best</strong> marks the lowest cost / tuition / duration and the strongest CENSIS position among the programs shown — a shortcut, not advice.
                * Rough first-year total (tuition + living − a possible regional scholarship), assuming ISEE €20,000 and a room in a shared flat.
                Tune the inputs on the <a href="{{ \App\Filament\Pages\CostEstimator::getUrl() }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Cost Estimator</a>.
                Always confirm fees and deadlines on each university's official page before applying.
            </p>
        @endif
    @endif
</x-filament-panels::page>
