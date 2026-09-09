<x-filament-panels::page>
    <form wire:submit.prevent>
        {{ $this->form }}
    </form>

    @php($result = $this->getResult())

    @if (! $result)
        <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-white/5">
            Pick one of your saved programs above to see an estimate.
        </div>
    @else
        @php($e = $result['estimate'])
        @php($p = $result['program'])

        <div class="space-y-4">
            <div class="rounded-xl border border-primary-200 bg-primary-50 p-5 dark:border-primary-400/20 dark:bg-primary-400/5">
                <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-400">Estimated first-year cost</div>
                <div class="mt-1 text-3xl font-bold">
                    &euro;{{ number_format($e['net_min']) }} &ndash; &euro;{{ number_format($e['net_max']) }}
                </div>
                <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                    {{ $p->name }} &middot; {{ $p->university->display_name }}{{ $e['city'] ? ' &middot; '.$e['city'] : '' }}
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tuition / year</div>
                    <div class="mt-1 text-lg font-semibold">&euro;{{ number_format($e['tuition']['min']) }}&ndash;{{ number_format($e['tuition']['max']) }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @if ($e['tuition']['source'] === 'program')
                            From this program's published fees.
                        @else
                            Estimated from your ISEE band ({{ $e['tuition']['band_label'] }}).
                        @endif
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Living costs / year</div>
                    <div class="mt-1 text-lg font-semibold">&euro;{{ number_format($e['living_annual']) }}</div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        Rent &euro;{{ number_format($e['rent_monthly']) }}/mo + &euro;{{ number_format(\App\Support\CostEstimator::MONTHLY_LIVING_EX_RENT) }}/mo other, &times; {{ \App\Support\CostEstimator::MONTHS_PER_YEAR }} months.
                    </div>
                </div>

                <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Possible scholarship</div>
                    <div class="mt-1 text-lg font-semibold">
                        @if ($e['scholarship']['max'] > 0)
                            &minus;&euro;{{ number_format($e['scholarship']['min']) }}&ndash;{{ number_format($e['scholarship']['max']) }}
                        @else
                            &euro;0
                        @endif
                    </div>
                    <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                        @if ($e['scholarship']['bodies'])
                            {{ implode(', ', $e['scholarship']['bodies']) }}
                        @else
                            No regional body with a published amount matched this university's region.
                        @endif
                    </div>
                </div>
            </div>

            @if ($e['notes'])
                <ul class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                    @foreach ($e['notes'] as $note)
                        <li>• {{ $note }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-400">
                {{ $this->getIseeBandsNote() }}
                Health insurance, the visa fee, travel and one-off setup costs are <strong>not</strong> included.
                Always budget against the university's own fee page and a live cost-of-living index.
            </div>
        </div>
    @endif
</x-filament-panels::page>
