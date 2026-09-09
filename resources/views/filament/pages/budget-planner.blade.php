<x-filament-panels::page>
    <x-ui.card>
        <form wire:submit.prevent>
            {{ $this->form }}
        </form>
    </x-ui.card>

    @php($result = $this->getResult())

    @if (! $result)
        <x-ui.empty-state
            icon="heroicon-o-banknotes"
            heading="Pick a saved program"
            description="Choose one of your shortlisted programs above for a full one-off + yearly budget."
        />
    @else
        @php($plan = $result['plan'])
        @php($p = $result['program'])
        @php($cur = $result['currency'])
        @php($fmt = fn ($eur) => '&euro;' . number_format((float) $eur))
        @php($fmtCur = fn ($eur) => $cur ? number_format((float) $eur * $cur['rate']) . ' ' . $cur['code'] : null)

        <div class="ui-grid ui-grid--2">
            <div class="ui-hero">
                <span class="ui-eyebrow" style="color: rgb(var(--primary-700))">First-year total</span>
                <div class="mt-1 text-3xl font-bold tracking-tight">
                    {!! $fmt($plan['first_year']['min']) !!} &ndash; {!! $fmt($plan['first_year']['max']) !!}
                </div>
                @if ($cur)
                    <div class="mt-0.5 text-sm font-medium text-gray-600 dark:text-gray-300">
                        ≈ {{ $fmtCur($plan['first_year']['min']) }} &ndash; {{ $fmtCur($plan['first_year']['max']) }}
                    </div>
                @endif
                <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    One-off relocation + first year of study &amp; living.
                </div>
            </div>

            <div class="ui-hero">
                <span class="ui-eyebrow" style="color: rgb(var(--primary-700))">Whole course ({{ $plan['duration_years'] }} yr{{ $plan['duration_years'] > 1 ? 's' : '' }})</span>
                <div class="mt-1 text-3xl font-bold tracking-tight">
                    {!! $fmt($plan['full_course']['min']) !!} &ndash; {!! $fmt($plan['full_course']['max']) !!}
                </div>
                @if ($cur)
                    <div class="mt-0.5 text-sm font-medium text-gray-600 dark:text-gray-300">
                        ≈ {{ $fmtCur($plan['full_course']['min']) }} &ndash; {{ $fmtCur($plan['full_course']['max']) }}
                    </div>
                @endif
                <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    {{ $p->name }} &middot; {{ $p->university->display_name }}
                </div>
            </div>
        </div>

        {{-- One-off --}}
        <x-ui.card>
            <x-ui.eyebrow>
                One-off &mdash; before you leave &amp; first month
                <x-slot:action>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{!! $fmt($plan['one_off_total']['min']) !!}&ndash;{!! $fmt($plan['one_off_total']['max']) !!}</span>
                </x-slot:action>
            </x-ui.eyebrow>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($plan['one_off'] as $row)
                            <tr>
                                <td class="py-2 pr-3 text-gray-700 dark:text-gray-200">{{ $row['label'] }}</td>
                                <td class="py-2 pl-3 text-right whitespace-nowrap tabular-nums">
                                    {!! $row['min'] == $row['max'] ? $fmt($row['min']) : $fmt($row['min']) . '&ndash;' . $fmt($row['max']) !!}
                                    @if ($cur)
                                        <span class="block text-xs text-gray-400">{{ $row['min'] == $row['max'] ? $fmtCur($row['min']) : $fmtCur($row['min']) . '–' . $fmtCur($row['max']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        {{-- Yearly --}}
        <x-ui.card>
            <x-ui.eyebrow>
                Every year you study
                <x-slot:action>
                    <span class="font-semibold text-gray-700 dark:text-gray-200">{!! $fmt($plan['yearly_net']['min']) !!}&ndash;{!! $fmt($plan['yearly_net']['max']) !!} net</span>
                </x-slot:action>
            </x-ui.eyebrow>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($plan['yearly'] as $row)
                            <tr>
                                <td class="py-2 pr-3 text-gray-700 dark:text-gray-200">{{ $row['label'] }}</td>
                                <td class="py-2 pl-3 text-right whitespace-nowrap tabular-nums">
                                    {!! $row['min'] == $row['max'] ? $fmt($row['min']) : $fmt($row['min']) . '&ndash;' . $fmt($row['max']) !!}
                                    @if ($cur)
                                        <span class="block text-xs text-gray-400">{{ $row['min'] == $row['max'] ? $fmtCur($row['min']) : $fmtCur($row['min']) . '–' . $fmtCur($row['max']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        @if ($plan['scholarship_max'] > 0)
                            <tr class="text-success-700 dark:text-success-400">
                                <td class="py-2 pr-3">Possible regional (DSU) scholarship</td>
                                <td class="py-2 pl-3 text-right whitespace-nowrap tabular-nums">
                                    &minus;{!! $fmt($plan['scholarship_max']) !!}
                                    @if ($cur)
                                        <span class="block text-xs opacity-70">−{{ $fmtCur($plan['scholarship_max']) }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        @if ($plan['notes'])
            <ul class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                @foreach ($plan['notes'] as $note)
                    <li>• {{ $note }}</li>
                @endforeach
            </ul>
        @endif

        <x-ui.card class="text-xs text-gray-600 dark:text-gray-400">
            Every figure is an indicative range, not a quote. One-off costs vary with your country and consulate;
            tuition is set per university by ISEE band; rent moves quarter to quarter; scholarships are income-tested
            and competitive. Home-currency amounts use the rate you entered. Budget against each university's own fee
            page, your consulate's visa page and a live cost-of-living index before committing.
            @if ($cur)
                <a href="{{ \App\Filament\Pages\MyScholarships::getUrl() }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Match &amp; track scholarships &rarr;</a>
            @endif
        </x-ui.card>
    @endif
</x-filament-panels::page>
