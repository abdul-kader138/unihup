<x-filament-panels::page>
    @php($data = $this->getDeadlineData())

    @unless ($data['has_shortlist'])
        <x-ui.page-intro icon="heroicon-o-information-circle">
            Save programs in <a href="{{ \App\Filament\Pages\FindUniversities::getUrl() }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">Find Universities</a>
            to see the deadlines for those universities and their region's scholarships here. Nationwide deadlines are shown regardless.
        </x-ui.page-intro>
    @endunless

    @if ($data['overdue']->isNotEmpty())
        <div>
            <div class="ui-eyebrow mb-2" style="color: rgb(var(--danger-600))">Passed</div>
            <div class="space-y-2">
                @foreach ($data['overdue'] as $deadline)
                    @include('filament.pages.partials.deadline-row', ['deadline' => $deadline, 'past' => true])
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <div class="ui-eyebrow mb-2">Upcoming</div>

        @if ($data['upcoming']->isEmpty())
            <x-ui.empty-state
                icon="heroicon-o-calendar-days"
                heading="No upcoming deadlines"
                description="Nothing on file for your shortlist yet. Nationwide dates will appear here as staff add them."
            />
        @else
            <div class="space-y-2">
                @foreach ($data['upcoming'] as $deadline)
                    @include('filament.pages.partials.deadline-row', ['deadline' => $deadline, 'past' => false])
                @endforeach
            </div>
        @endif
    </div>

    <p class="text-xs text-gray-400">
        Curated guidance — dates and rules change every cycle, so always confirm on each university's and consulate's own pages.
    </p>
</x-filament-panels::page>
