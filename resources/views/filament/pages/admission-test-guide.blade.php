<x-filament-panels::page>
    @php($states = $this->guideSectionStates())
    @php($progress = $this->guideProgress())

    <div class="space-y-6">
        <x-ui.page-intro icon="heroicon-o-pencil-square">
            Programs flagged <span class="font-medium">Restricted</span> admission generally require passing a standardized
            entrance test before you can enrol — which one depends on the subject area, not the university. This page
            explains the three tracks that cover almost every case.
            (Once you're admitted, non-EU applicants also need a separate visa process — see the
            <a href="{{ route('filament.admin.pages.visa-arrival') }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Visa &amp; Arrival guide</a>.)
        </x-ui.page-intro>

        <x-guide-progress :progress="$progress" />

        @foreach ($this->getSections() as $section)
            @php($sk = $section['key'])
            @php($done = $states[$sk] ?? false)
            <div @class([
                'ui-card ui-card--pad',
                'border-success-200 bg-success-50/40 dark:border-success-400/20 dark:bg-success-400/5' => $done,
            ])>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 class="text-base font-semibold">{{ $section['heading'] }}</h2>
                    @if (! empty($section['critical']))
                        <span class="inline-flex items-center gap-1 rounded-full bg-danger-50 px-2 py-0.5 text-xs font-medium text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">
                            <x-heroicon-o-exclamation-triangle class="h-3 w-3" />
                            Deadline-critical
                        </span>
                    @endif
                    <x-guide-section-toggle :done="$done" :section-key="$sk" />
                </div>

                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ $section['body'] }}</p>

                @if (! empty($section['checklist']))
                    <ul class="mt-3 space-y-1.5">
                        @foreach ($section['checklist'] as $item)
                            <li class="flex items-start gap-2 text-sm text-gray-600 dark:text-gray-400">
                                <x-heroicon-o-check-circle class="mt-0.5 h-4 w-4 shrink-0 text-primary-500" />
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (! empty($section['note']))
                    <p @class([
                        'mt-3 rounded-lg p-2 text-xs',
                        'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400' => ! empty($section['critical']),
                        'bg-gray-50 text-gray-500 dark:bg-white/5 dark:text-gray-400' => empty($section['critical']),
                    ])>
                        {{ $section['note'] }}
                    </p>
                @endif
            </div>
        @endforeach

        <x-ui.card>
            <h2 class="text-base font-semibold">Official sources</h2>
            <ul class="mt-2 space-y-1">
                @foreach ($this->getOfficialLinks() as $label => $url)
                    <li>
                        <a href="{{ $url }}" target="_blank" rel="noopener" class="text-sm text-primary-600 hover:underline dark:text-primary-400">
                            {{ $label }} &rarr;
                        </a>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>

        <x-ui.card class="text-xs text-gray-500 dark:text-gray-400">
            General guidance, not a substitute for the specific admission notice (bando) of the program you're
            applying to — exact test dates, registration windows and pass thresholds are set annually by CISIA,
            the Ministry, or the individual university. Confirm current details on the official sources above.
        </x-ui.card>
    </div>
</x-filament-panels::page>
