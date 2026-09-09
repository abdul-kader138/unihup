<x-filament-panels::page>
    @php($summary = $this->getSummary())
    @php($checklist = $this->getChecklist())
    @php($progress = $this->getChecklistProgress())
    @php($guides = $this->getGuidesProgress())
    @php($deadlines = $this->getUpcomingDeadlines())
    @php($user = auth()->user())

    {{-- Hero --}}
    <div class="ui-hero">
        <h2 class="text-lg font-semibold tracking-tight">Welcome back, {{ $user->first_name ?: 'there' }}</h2>
        <p class="mt-0.5 max-w-2xl text-[0.8125rem] leading-relaxed text-gray-600 dark:text-gray-300">
            Your whole Italian university application in one place — follow the checklist, find and compare
            programs, keep your documents together, and stay ahead of every deadline.
        </p>
        @unless ($summary['profile_complete'])
            <a href="{{ \App\Filament\Auth\EditProfile::getUrl() }}"
               class="mt-2.5 inline-flex items-center gap-1.5 rounded-full bg-white/70 px-3 py-1 text-xs font-semibold text-primary-700 ring-1 ring-primary-500/20 backdrop-blur transition hover:bg-white dark:bg-white/10 dark:text-primary-300 dark:hover:bg-white/15">
                <x-heroicon-o-sparkles class="h-3.5 w-3.5" />
                Complete your study profile to personalise this page
            </a>
        @endunless
    </div>

    {{-- Stats --}}
    <div class="ui-grid ui-grid--4">
        <x-ui.stat
            label="Saved programs"
            :value="$summary['shortlist_total']"
            :href="\App\Filament\Pages\MyApplications::getUrl()"
            link-label="My Applications"
        >
            @if ($summary['status_counts'])
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($summary['status_counts'] as $label => $count)
                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">{{ $label }} · {{ $count }}</span>
                    @endforeach
                </div>
            @endif
        </x-ui.stat>

        <x-ui.stat
            label="Checklist"
            :value="$progress['percent'] . '%'"
            :href="null"
        >
            <x-ui.progress :value="$progress['percent']" :caption="$progress['done'] . ' / ' . $progress['total'] . ' done'" class="mt-2" />
        </x-ui.stat>

        <x-ui.stat
            label="Document vault"
            :value="$summary['documents_done'] . ' / ' . $summary['documents_total']"
            :href="\App\Filament\Pages\MyDocuments::getUrl()"
            link-label="My Documents"
        >
            <x-ui.progress :value="$summary['documents_percent']" class="mt-2" />
        </x-ui.stat>

        <x-ui.stat
            label="Guides read"
            :value="$guides['done'] . ' / ' . $guides['total']"
            :href="route('filament.admin.pages.admission-tests')"
            link-label="Open the guides"
        >
            <x-ui.progress :value="$guides['percent']" class="mt-2" />
        </x-ui.stat>
    </div>

    {{-- Next deadlines --}}
    @if ($deadlines->isNotEmpty())
        <x-ui.card>
            <x-ui.eyebrow>
                Next deadlines
                <x-slot:action>
                    <a href="{{ \App\Filament\Pages\MyDeadlines::getUrl() }}" class="font-semibold text-primary-600 hover:underline dark:text-primary-400">See all &rarr;</a>
                </x-slot:action>
            </x-ui.eyebrow>
            <ul class="mt-3 divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($deadlines as $deadline)
                    @php($days = $deadline->daysUntil())
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span class="min-w-0">
                            <span class="font-medium">{{ $deadline->title }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $deadline->due_at->format('j M Y') }} · {{ $deadline->scopeName() }}</span>
                        </span>
                        <span @class([
                            'shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold',
                            'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400' => $days <= 7,
                            'bg-warning-50 text-warning-700 dark:bg-warning-400/10 dark:text-warning-400' => $days > 7 && $days <= 30,
                            'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300' => $days > 30,
                        ])>{{ $days === 0 ? 'today' : "in {$days}d" }}</span>
                    </li>
                @endforeach
            </ul>
        </x-ui.card>
    @endif

    {{-- Checklist --}}
    <x-ui.card>
        <x-ui.eyebrow>
            Your admission checklist
            <x-slot:action>
                <span class="text-gray-500 dark:text-gray-400">{{ $progress['done'] }} / {{ $progress['total'] }} done</span>
            </x-slot:action>
        </x-ui.eyebrow>
        <x-ui.progress :value="$progress['percent']" class="mt-2.5" />

        <div class="mt-4 space-y-4">
            @foreach ($checklist as $phase)
                <div>
                    <h3 class="mb-1.5 text-[0.65rem] font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $phase['label'] }}</h3>
                    <ul class="space-y-1.5">
                        @foreach ($phase['steps'] as $step)
                            <li @class([
                                'flex items-start gap-2.5 rounded-lg border px-3 py-2 transition',
                                'border-gray-200 bg-white dark:border-white/10 dark:bg-white/5' => ! $step['done'],
                                'border-success-200 bg-success-50/50 dark:border-success-400/20 dark:bg-success-400/5' => $step['done'],
                            ])>
                                <button
                                    type="button"
                                    wire:click="toggleStep('{{ $step['key'] }}')"
                                    aria-label="Toggle step: {{ $step['title'] }}"
                                    @class([
                                        'mt-0.5 flex h-[1.15rem] w-[1.15rem] shrink-0 items-center justify-center rounded-full border transition',
                                        'border-gray-300 hover:border-primary-400 dark:border-white/20' => ! $step['done'],
                                        'border-success-500 bg-success-500 text-white' => $step['done'],
                                    ])
                                >
                                    @if ($step['done'])<x-heroicon-s-check class="h-3 w-3" />@endif
                                </button>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <x-dynamic-component :component="$step['icon']" class="h-3.5 w-3.5 shrink-0 text-gray-400" />
                                        <span @class(['text-[0.8125rem] font-medium', 'line-through opacity-60' => $step['done']])>{{ $step['title'] }}</span>
                                    </div>
                                    <p class="mt-0.5 text-xs leading-snug text-gray-500 dark:text-gray-400">{{ $step['body'] }}</p>
                                    @if ($step['help_url'])
                                        <a href="{{ $step['help_url'] }}" class="mt-0.5 inline-block text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">Open guide &rarr;</a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <p class="mt-3 text-xs text-gray-400">
            A general roadmap for studying in Italy — not a substitute for each university's and consulate's own instructions.
        </p>
    </x-ui.card>

    {{-- Explore --}}
    <div>
        <div class="ui-eyebrow mb-2">Explore</div>
        <div class="ui-grid ui-grid--3">
            @foreach ($this->getQuickLinks() as $link)
                <x-ui.tile
                    :title="$link['title']"
                    :description="$link['description']"
                    :icon="$link['icon']"
                    :href="$link['url']"
                />
            @endforeach
        </div>
    </div>
</x-filament-panels::page>
