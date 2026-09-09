<x-filament-panels::page>
    @php($data = $this->getDeadlineData())

    @unless ($data['has_shortlist'])
        <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
            Save programs in <a href="{{ \App\Filament\Pages\FindUniversities::getUrl() }}" class="text-primary-600 hover:underline dark:text-primary-400">Find Universities</a>
            to see the deadlines for those universities and their region's scholarships here. Nationwide deadlines are shown regardless.
        </div>
    @endunless

    @if ($data['overdue']->isNotEmpty())
        <div>
            <h2 class="mb-2 text-xs font-semibold uppercase tracking-wider text-danger-600 dark:text-danger-400">Passed</h2>
            <div class="space-y-2">
                @foreach ($data['overdue'] as $deadline)
                    @include('filament.pages.partials.deadline-row', ['deadline' => $deadline, 'past' => true])
                @endforeach
            </div>
        </div>
    @endif

    <div>
        <h2 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Upcoming</h2>

        @if ($data['upcoming']->isEmpty())
            <div class="rounded-xl border border-gray-200 bg-white p-6 text-center text-sm text-gray-500 dark:border-white/10 dark:bg-white/5">
                No upcoming deadlines on file for your shortlist yet.
            </div>
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
