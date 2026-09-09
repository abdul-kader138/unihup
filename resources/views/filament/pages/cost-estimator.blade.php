<x-filament-panels::page>
    <x-ui.card>
        <form wire:submit.prevent>
            {{ $this->form }}
        </form>
    </x-ui.card>

    @php($result = $this->getResult())

    @if (! $result)
        <x-ui.empty-state
            icon="heroicon-o-calculator"
            heading="Pick a saved program"
            description="Choose one of your shortlisted programs above and we'll estimate a first-year cost range."
        />
    @else
        @php($e = $result['estimate'])
        @php($p = $result['program'])

        <div class="ui-hero">
            <span class="ui-eyebrow" style="color: rgb(var(--primary-700))">Estimated first-year cost</span>
            <div class="mt-1 text-3xl font-bold tracking-tight">
                &euro;{{ number_format($e['net_min']) }} &ndash; &euro;{{ number_format($e['net_max']) }}
            </div>
            <div class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                {{ $p->name }} &middot; {{ $p->university->display_name }}{{ $e['city'] ? ' · '.$e['city'] : '' }}
            </div>
        </div>

        <div class="ui-grid ui-grid--3">
            <x-ui.card class="flex flex-col">
                <span class="ui-eyebrow">Tuition / year</span>
                <span class="ui-stat-value mt-1">&euro;{{ number_format($e['tuition']['min']) }}&ndash;{{ number_format($e['tuition']['max']) }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @if ($e['tuition']['source'] === 'program')
                        From this program's published fees.
                    @else
                        Estimated from your ISEE band ({{ $e['tuition']['band_label'] }}).
                    @endif
                </span>
            </x-ui.card>

            <x-ui.card class="flex flex-col">
                <span class="ui-eyebrow">Living costs / year</span>
                <span class="ui-stat-value mt-1">&euro;{{ number_format($e['living_annual']) }}</span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Rent &euro;{{ number_format($e['rent_monthly']) }}/mo + &euro;{{ number_format(\App\Support\CostEstimator::MONTHLY_LIVING_EX_RENT) }}/mo other, &times; {{ \App\Support\CostEstimator::MONTHS_PER_YEAR }} months.
                </span>
            </x-ui.card>

            <x-ui.card class="flex flex-col">
                <span class="ui-eyebrow">Possible scholarship</span>
                <span class="ui-stat-value mt-1">
                    @if ($e['scholarship']['max'] > 0)
                        &minus;&euro;{{ number_format($e['scholarship']['min']) }}&ndash;{{ number_format($e['scholarship']['max']) }}
                    @else
                        &euro;0
                    @endif
                </span>
                <span class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    @if ($e['scholarship']['bodies'])
                        {{ implode(', ', $e['scholarship']['bodies']) }}
                    @else
                        No regional body with a published amount matched this university's region.
                    @endif
                </span>
                <a href="{{ \App\Filament\Pages\MyScholarships::getUrl() }}" class="mt-auto pt-2 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">Match &amp; track scholarships &rarr;</a>
            </x-ui.card>
        </div>

        @if ($e['notes'])
            <ul class="space-y-1 text-xs text-gray-500 dark:text-gray-400">
                @foreach ($e['notes'] as $note)
                    <li>• {{ $note }}</li>
                @endforeach
            </ul>
        @endif

        <x-ui.card class="text-xs text-gray-600 dark:text-gray-400">
            {{ $this->getIseeBandsNote() }}
            Health insurance, the visa fee, travel and one-off setup costs are <strong>not</strong> included here &mdash;
            the <a href="{{ \App\Filament\Pages\BudgetPlanner::getUrl() }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Budget Planner</a>
            adds those and rolls up a whole-course total.
            Always budget against the university's own fee page and a live cost-of-living index.
        </x-ui.card>
    @endif
</x-filament-panels::page>
