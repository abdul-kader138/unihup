<x-filament-panels::page>
    <div class="space-y-8">
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
            Getting admitted to a program is only half the process for non-EU applicants — entering and legally
            staying in Italy is a separate track with its own steps and deadlines, grouped by phase below.
            (Getting your prior qualification recognized is a related but different process —
            see the <a href="{{ route('filament.admin.pages.doc-recognition') }}" class="text-primary-600 hover:underline dark:text-primary-400">Document Recognition guide</a>.)
        </div>

        @foreach ($this->getPhasedSections() as $phase)
            <div>
                <h2 class="mb-6 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $phase['label'] }}</h2>

                <div class="space-y-0">
                    @foreach ($phase['sections'] as $section)
                        <div class="relative flex gap-4 pb-8 last:pb-0">
                            {{-- Timeline rail: icon node + connecting line to the next step --}}
                            <div class="flex flex-col items-center">
                                <div @class([
                                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-full ring-4',
                                    'bg-danger-50 text-danger-600 ring-danger-50/50 dark:bg-danger-400/10 dark:text-danger-400 dark:ring-danger-400/10' => $section['critical'],
                                    'bg-primary-50 text-primary-600 ring-primary-50/50 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/10' => ! $section['critical'],
                                ])>
                                    <x-dynamic-component :component="$section['icon']" class="h-5 w-5" />
                                </div>
                                @if (! $loop->last)
                                    <div class="mt-1 w-px flex-1 bg-gray-200 dark:bg-white/10"></div>
                                @endif
                            </div>

                            <div class="min-w-0 flex-1 rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-xs font-semibold text-gray-400 dark:text-gray-500">Step {{ $section['step'] }}</span>
                                    <h3 class="text-base font-semibold">{{ $section['heading'] }}</h3>
                                    @if ($section['critical'])
                                        <span class="inline-flex items-center gap-1 rounded-full bg-danger-50 px-2 py-0.5 text-xs font-medium text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">
                                            <x-heroicon-o-exclamation-triangle class="h-3 w-3" />
                                            Deadline-critical
                                        </span>
                                    @endif
                                </div>

                                @if (! empty($section['who']))
                                    <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                        <x-heroicon-o-user class="h-3 w-3" />
                                        {{ $section['who'] }}
                                    </div>
                                @endif

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
                                        'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400' => $section['critical'],
                                        'bg-gray-50 text-gray-500 dark:bg-white/5 dark:text-gray-400' => ! $section['critical'],
                                    ])>
                                        {{ $section['note'] }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
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
        </div>

        <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-500 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
            General guidance, not a substitute for your specific consulate's or university's instructions —
            financial-means thresholds, SSN contribution amounts, and deadlines are set by decree and change.
            Confirm current figures and deadlines on the official sources above.
        </div>
    </div>
</x-filament-panels::page>
