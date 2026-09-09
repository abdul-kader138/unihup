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

    @if ($data['scholarships']->isNotEmpty())
        <div>
            <div class="ui-eyebrow mb-2">Scholarship deadlines (from your tracker)</div>
            <div class="space-y-2">
                @foreach ($data['scholarships'] as $s)
                    @php($days = (int) round(now()->startOfDay()->diffInDays($s->deadline_at, false)))
                    <x-ui.card class="flex flex-wrap items-center gap-3 {{ $days < 0 ? 'opacity-70' : '' }}">
                        <div class="flex w-16 shrink-0 flex-col items-center rounded-lg bg-gray-100 py-1.5 text-center dark:bg-white/10">
                            <span class="text-[0.65rem] font-semibold uppercase text-gray-500 dark:text-gray-400">{{ $s->deadline_at->format('M') }}</span>
                            <span class="text-lg font-bold leading-none">{{ $s->deadline_at->format('j') }}</span>
                            <span class="text-[0.65rem] text-gray-500 dark:text-gray-400">{{ $s->deadline_at->format('Y') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="font-medium">{{ $s->label }}</span>
                                <x-filament::badge :color="\App\Models\ScholarshipTracker::STATUS_COLORS[$s->status] ?? 'gray'" size="sm">
                                    {{ \App\Models\ScholarshipTracker::STATUSES[$s->status] ?? $s->status }}
                                </x-filament::badge>
                            </div>
                            <div class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ ucfirst($s->kind) }} scholarship</div>
                        </div>
                        <div class="shrink-0 text-xs">
                            @if ($days < 0)
                                <span class="text-gray-400">{{ abs($days) }}d ago</span>
                            @elseif ($days <= 14)
                                <span class="rounded-full bg-danger-50 px-2 py-0.5 font-semibold text-danger-700 dark:bg-danger-400/10 dark:text-danger-400">in {{ $days }}d</span>
                            @else
                                <span class="text-gray-500 dark:text-gray-400">in {{ $days }}d</span>
                            @endif
                        </div>
                    </x-ui.card>
                @endforeach
            </div>
            <p class="mt-1.5 text-xs text-gray-400">
                Set these on the <a href="{{ \App\Filament\Pages\MyScholarships::getUrl() }}" class="text-primary-600 hover:underline dark:text-primary-400">Scholarships</a> page.
            </p>
        </div>
    @endif

    <p class="text-xs text-gray-400">
        Curated guidance — dates and rules change every cycle, so always confirm on each university's and consulate's own pages.
    </p>
</x-filament-panels::page>
