<x-filament-panels::page>
    @php($comparison = $this->getComparison())
    @php($total = $this->getTotalShortlisted())

    @if (count($comparison['programs']) === 0)
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
            Save at least two programs from
            <a href="{{ \App\Filament\Pages\FindUniversities::getUrl() }}" class="text-primary-600 hover:underline dark:text-primary-400">Find Universities</a>
            to compare them here.
        </div>
    @else
        @if ($total > count($comparison['programs']))
            <div class="mb-4 rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                Showing the first {{ count($comparison['programs']) }} of {{ $total }} saved programs. Remove some from
                <a href="{{ \App\Filament\Pages\MyApplications::getUrl() }}" class="text-primary-600 hover:underline dark:text-primary-400">My Applications</a>
                to compare a different set.
            </div>
        @endif

        <div class="overflow-x-auto rounded-xl border border-gray-200 dark:border-white/10">
            <table class="w-full min-w-[640px] border-collapse text-sm">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="w-40 p-3 text-left align-bottom text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Field</th>
                        @foreach ($comparison['programs'] as $program)
                            <th class="p-3 text-left align-bottom">
                                <div class="flex items-center gap-2">
                                    <img src="{{ $program['logo'] }}" alt="" class="h-8 w-8 shrink-0 rounded-md object-contain ring-1 ring-gray-200 dark:ring-white/10">
                                    <span class="font-semibold">{{ $program['university'] }}</span>
                                </div>
                                <div class="mt-1 text-xs font-normal text-gray-500 dark:text-gray-400">{{ $program['name'] }}</div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($comparison['rows'] as $row)
                        <tr class="border-t border-gray-100 dark:border-white/5">
                            <td class="p-3 align-top text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ $row['label'] }}</td>
                            @foreach ($row['values'] as $value)
                                <td class="whitespace-pre-line p-3 align-top">
                                    @if ($row['label'] === 'Official page' && $value !== '—')
                                        <a href="{{ $value }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline dark:text-primary-400">Admission page &rarr;</a>
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

        <p class="mt-3 text-xs text-gray-500 dark:text-gray-400">
            * Rough first-year total (tuition + living − a possible regional scholarship), assuming ISEE €20,000 and a room in a shared flat.
            Tune the inputs on the <a href="{{ \App\Filament\Pages\CostEstimator::getUrl() }}" class="text-primary-600 hover:underline dark:text-primary-400">Cost Estimator</a>.
            General guidance only — always confirm fees and deadlines on each university's official page before applying.
        </p>
    @endif
</x-filament-panels::page>
