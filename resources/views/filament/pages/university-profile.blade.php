<x-filament-panels::page>
    @php($u = $this->university)
    @php($ranking = $u->latestRanking())
    @php($programsByLevel = $this->getProgramsByLevel())
    @php($saved = $this->getShortlistedProgramIds())
    @php($cityGuide = $this->getCityGuide())
    @php($col = $this->getCostOfLiving())
    @php($scholarships = $this->getRegionalScholarships())
    @php($deadlines = $this->getUpcomingDeadlines())

    {{-- Header --}}
    <div class="ui-hero">
        <div class="flex items-start gap-4">
            <img src="{{ $u->display_logo_url }}" alt="" class="h-14 w-14 shrink-0 rounded-xl object-contain bg-white ring-1 ring-gray-200 dark:ring-white/10">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold tracking-tight">{{ $u->display_name }}</h2>
                <p class="mt-0.5 text-[0.8125rem] text-gray-600 dark:text-gray-300">
                    {{ $u->city }}@if ($u->region) · {{ $u->region }}@endif
                </p>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                    @if ($ranking)
                        <span class="inline-flex items-center gap-1 rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">
                            <x-heroicon-o-trophy class="h-3.5 w-3.5" />
                            CENSIS {{ $ranking->edition }}: #{{ $ranking->position }} ({{ \App\Models\UniversityRanking::CATEGORIES[$ranking->category] }})
                        </span>
                    @endif
                    @if ($u->website_url)
                        <a href="{{ $u->website_url }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                            Official website &rarr;
                        </a>
                    @endif
                </div>
            </div>
        </div>
        @if ($u->description)
            <p class="mt-3 max-w-3xl text-xs leading-relaxed text-gray-600 dark:text-gray-400">{{ $u->description }}</p>
        @endif
    </div>

    {{-- Programs --}}
    <x-ui.card>
        <x-ui.eyebrow>Programs at this university</x-ui.eyebrow>

        @if ($programsByLevel->isEmpty())
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">No programs on file yet.</p>
        @else
            <div class="mt-3 space-y-4">
                @foreach (\App\Models\DegreeProgram::DEGREE_LEVELS as $levelKey => $levelLabel)
                    @php($rows = $programsByLevel->get($levelKey))
                    @if ($rows)
                        <div>
                            <h3 class="mb-1.5 text-[0.65rem] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $levelLabel }}</h3>
                            <ul class="space-y-1.5">
                                @foreach ($rows as $program)
                                    <li class="flex items-start justify-between gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-white/5">
                                        <div class="min-w-0">
                                            <div class="text-[0.8125rem] font-medium">{{ $program->name }}</div>
                                            <div class="mt-0.5 flex flex-wrap items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                                                <span>{{ $program->subject?->display_name }}</span>
                                                <span>·</span>
                                                <span>{{ $program->language }}</span>
                                                <span>·</span>
                                                <span @class([
                                                    'rounded-full px-1.5 py-0.5 font-medium',
                                                    'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-400' => $program->admission_type === 'restricted',
                                                    'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400' => $program->admission_type !== 'restricted',
                                                ])>{{ \App\Models\DegreeProgram::ADMISSION_TYPES[$program->admission_type] ?? $program->admission_type }}</span>
                                            </div>
                                        </div>
                                        <button
                                            type="button"
                                            wire:click="toggleShortlist({{ $program->id }})"
                                            @class([
                                                'shrink-0 inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-semibold transition',
                                                'bg-primary-50 text-primary-700 dark:bg-primary-400/10 dark:text-primary-300' => in_array($program->id, $saved, true),
                                                'text-gray-500 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-white/10' => ! in_array($program->id, $saved, true),
                                            ])
                                        >
                                            @if (in_array($program->id, $saved, true))
                                                <x-heroicon-s-bookmark class="h-3.5 w-3.5" /> On my list
                                            @else
                                                <x-heroicon-o-bookmark class="h-3.5 w-3.5" /> Save
                                            @endif
                                        </button>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endforeach
            </div>
        @endif
    </x-ui.card>

    {{-- Deadlines --}}
    @if ($deadlines->isNotEmpty())
        <x-ui.card>
            <x-ui.eyebrow>Deadlines for this university</x-ui.eyebrow>
            <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($deadlines as $deadline)
                    @php($days = $deadline->daysUntil())
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span class="min-w-0">
                            <span class="font-medium">{{ $deadline->title }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $deadline->due_at->format('j M Y') }} · {{ $deadline->scopeName() }}</span>
                        </span>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ $days === 0 ? 'today' : "in {$days}d" }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    {{-- Living + money --}}
    <div class="ui-grid ui-grid--2">
        <x-ui.card class="flex flex-col">
            <x-ui.eyebrow>Living in {{ $u->city }}</x-ui.eyebrow>
            @if ($col)
                <p class="mt-2 text-sm">
                    Average asking rent <span class="font-semibold">&euro;{{ number_format($col['rent']) }}/month</span>
                    <span class="text-gray-500 dark:text-gray-400">({{ $col['tier'] }})</span>
                </p>
            @else
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No sourced rent figure for this city — check a live index.</p>
            @endif
            @if ($cityGuide)
                <a href="{{ \App\Filament\Pages\CityGuides::getUrl(['city' => $u->city]) }}" class="mt-auto pt-3 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                    Read the {{ $cityGuide->city }} city guide &rarr;
                </a>
            @endif
        </x-ui.card>

        <x-ui.card class="flex flex-col">
            <x-ui.eyebrow>{{ $u->region ? "Regional scholarships ({$u->region})" : 'Regional scholarships' }}</x-ui.eyebrow>
            @if ($scholarships->isNotEmpty())
                <ul class="mt-2 space-y-1 text-sm">
                    @foreach ($scholarships as $s)
                        <li class="flex items-center justify-between gap-2">
                            <span>{{ $s->body_name }}</span>
                            @if ($s->website_url)
                                <a href="{{ $s->website_url }}" target="_blank" rel="noopener" class="shrink-0 text-xs text-primary-600 hover:underline dark:text-primary-400">Site &rarr;</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            @else
                <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No regional (DSU) body matched this region on file.</p>
            @endif
            <a href="{{ \App\Filament\Pages\MyScholarships::getUrl() }}" class="mt-auto pt-3 text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                Match &amp; track scholarships &rarr;
            </a>
        </x-ui.card>
    </div>

    <div>
        <a href="{{ \App\Filament\Pages\FindUniversities::getUrl() }}" class="text-xs font-medium text-gray-500 hover:text-gray-700 hover:underline dark:text-gray-400 dark:hover:text-gray-200">
            &larr; Back to Find Universities
        </a>
    </div>
</x-filament-panels::page>
