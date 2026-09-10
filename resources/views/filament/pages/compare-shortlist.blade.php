<x-filament-panels::page>
    @php($tray = $this->getTray())
    @php($comparison = $this->getComparison())
    @php($total = $this->getTotalShortlisted())

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
                        @disabled($locked)
                        @if ($locked) title="Remove one first — the table holds {{ \App\Filament\Pages\CompareShortlist::MAX_COLUMNS }}" @endif
                        class="inline-flex max-w-[15rem] items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium transition
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

        @if (count($comparison['programs']) === 0)
            <x-ui.empty-state
                icon="heroicon-o-cursor-arrow-rays"
                heading="Pick a couple to line up"
                description="Select two or more programs above to see them side by side."
            />
        @else
            <div class="ui-card overflow-x-auto" style="padding:0">
                <table class="w-full min-w-[640px] border-collapse text-sm">
                    <thead>
                        <tr class="bg-gray-50 dark:bg-white/5">
                            <th class="sticky left-0 z-10 w-40 bg-gray-50 p-3 text-left align-bottom dark:bg-[#181f33]">
                                <span class="ui-eyebrow">Field</span>
                            </th>
                            @foreach ($comparison['programs'] as $program)
                                <th class="p-3 text-left align-bottom">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <img src="{{ $program['logo'] }}" alt="" class="h-8 w-8 shrink-0 rounded-md object-contain ring-1 ring-gray-200 dark:ring-white/10">
                                            <span class="font-semibold">{{ $program['university'] }}</span>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="remove({{ $program['id'] }})"
                                            title="Remove from comparison"
                                            class="shrink-0 rounded p-0.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-200"
                                        >
                                            <x-heroicon-o-x-mark class="h-4 w-4" />
                                        </button>
                                    </div>
                                    <div class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400">{{ $program['name'] }}</div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($comparison['rows'] as $row)
                            <tr class="border-t border-gray-100 dark:border-white/5">
                                <td class="sticky left-0 z-10 bg-white p-3 align-top dark:bg-[#131c31]">
                                    <span class="ui-eyebrow">{{ $row['label'] }}</span>
                                </td>
                                @foreach ($row['values'] as $value)
                                    <td class="whitespace-pre-line p-3 align-top">
                                        @if ($row['label'] === 'Official page' && $value !== '—')
                                            <a href="{{ $value }}" target="_blank" rel="noopener" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Admission page &rarr;</a>
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <p class="text-xs text-gray-500 dark:text-gray-400">
                * Rough first-year total (tuition + living − a possible regional scholarship), assuming ISEE €20,000 and a room in a shared flat.
                Tune the inputs on the <a href="{{ \App\Filament\Pages\CostEstimator::getUrl() }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Cost Estimator</a>.
                General guidance only — always confirm fees and deadlines on each university's official page before applying.
            </p>
        @endif
    @endif
</x-filament-panels::page>
