<x-filament-panels::page>
    @php($summary = $this->getSummary())
    @php($checklist = $this->getChecklist())
    @php($progress = $this->getChecklistProgress())
    @php($deadlines = $this->getUpcomingDeadlines())
    @php($user = auth()->user())

    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
        <h2 class="text-lg font-semibold">Welcome back, {{ $user->first_name ?: 'there' }}</h2>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Everything for your Italian university application in one place — follow the checklist below,
            find and compare programs, and keep your documents together.
        </p>
    </div>

    @unless ($summary['profile_complete'])
        <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-warning-300 bg-warning-50 p-4 text-sm dark:border-warning-400/30 dark:bg-warning-400/10">
            <span class="text-warning-800 dark:text-warning-200">
                Complete your <strong>Study profile</strong> so this checklist matches your situation (EU status, language level, scholarship interest).
            </span>
            <a href="{{ \App\Filament\Auth\EditProfile::getUrl() }}" class="inline-flex shrink-0 items-center gap-1 rounded-lg bg-warning-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-warning-500">
                Update profile
            </a>
        </div>
    @endunless

    @if ($deadlines->isNotEmpty())
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="flex items-center justify-between">
                <span class="text-sm font-semibold">Next deadlines</span>
                <a href="{{ \App\Filament\Pages\MyDeadlines::getUrl() }}" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">See all &rarr;</a>
            </div>
            <ul class="mt-2 divide-y divide-gray-100 dark:divide-white/5">
                @foreach ($deadlines as $deadline)
                    @php($days = $deadline->daysUntil())
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <span class="min-w-0">
                            <span class="font-medium">{{ $deadline->title }}</span>
                            <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $deadline->due_at->format('j M Y') }} · {{ $deadline->scopeName() }}</span>
                        </span>
                        <span @class([
                            'shrink-0 text-xs font-semibold',
                            'text-danger-600 dark:text-danger-400' => $days <= 7,
                            'text-warning-600 dark:text-warning-400' => $days > 7 && $days <= 30,
                            'text-gray-500 dark:text-gray-400' => $days > 30,
                        ])>{{ $days === 0 ? 'today' : "in {$days}d" }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Checklist --}}
    <div class="rounded-xl border border-gray-200 bg-white p-5 dark:border-white/10 dark:bg-white/5">
        <div class="flex items-center justify-between text-sm">
            <span class="font-semibold">Your admission checklist</span>
            <span class="text-gray-500 dark:text-gray-400">{{ $progress['done'] }} / {{ $progress['total'] }} done</span>
        </div>
        <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
            <div class="h-full rounded-full bg-primary-500 transition-all" style="width: {{ $progress['percent'] }}%"></div>
        </div>

        <div class="mt-5 space-y-6">
            @foreach ($checklist as $phase)
                <div>
                    <h3 class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">{{ $phase['label'] }}</h3>
                    <ul class="space-y-2">
                        @foreach ($phase['steps'] as $step)
                            <li @class([
                                'flex items-start gap-3 rounded-lg border p-3 transition',
                                'border-gray-200 bg-white dark:border-white/10 dark:bg-white/5' => ! $step['done'],
                                'border-success-200 bg-success-50/50 dark:border-success-400/20 dark:bg-success-400/5' => $step['done'],
                            ])>
                                <button
                                    type="button"
                                    wire:click="toggleStep('{{ $step['key'] }}')"
                                    aria-label="Toggle step: {{ $step['title'] }}"
                                    @class([
                                        'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition',
                                        'border-gray-300 hover:border-primary-400 dark:border-white/20' => ! $step['done'],
                                        'border-success-500 bg-success-500 text-white' => $step['done'],
                                    ])
                                >
                                    @if ($step['done'])
                                        <x-heroicon-s-check class="h-3.5 w-3.5" />
                                    @endif
                                </button>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <x-dynamic-component :component="$step['icon']" class="h-4 w-4 shrink-0 text-gray-400" />
                                        <span @class(['text-sm font-medium', 'line-through opacity-60' => $step['done']])>{{ $step['title'] }}</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $step['body'] }}</p>
                                    @if ($step['help_url'])
                                        <a href="{{ $step['help_url'] }}" class="mt-1 inline-block text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                                            Open guide &rarr;
                                        </a>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <p class="mt-4 text-xs text-gray-400">
            A general roadmap for studying in Italy — not a substitute for each university's and consulate's own instructions.
        </p>
    </div>

    {{-- Stat cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Saved programs</div>
            <div class="mt-1 text-2xl font-semibold">{{ $summary['shortlist_total'] }}</div>
            @if ($summary['status_counts'])
                <div class="mt-2 flex flex-wrap gap-1.5">
                    @foreach ($summary['status_counts'] as $label => $count)
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-white/10 dark:text-gray-300">
                            {{ $label }} · {{ $count }}
                        </span>
                    @endforeach
                </div>
            @endif
            <a href="{{ \App\Filament\Pages\MyApplications::getUrl() }}" class="mt-3 inline-block text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                Open My Applications &rarr;
            </a>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Document vault</div>
            <div class="mt-1 text-2xl font-semibold">{{ $summary['documents_done'] }} / {{ $summary['documents_total'] }}</div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                <div class="h-full rounded-full bg-primary-500 transition-all" style="width: {{ $summary['documents_percent'] }}%"></div>
            </div>
            <a href="{{ \App\Filament\Pages\MyDocuments::getUrl() }}" class="mt-3 inline-block text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                Open My Documents &rarr;
            </a>
        </div>

        @php($guides = $this->getGuidesProgress())
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Guides read</div>
            <div class="mt-1 text-2xl font-semibold">{{ $guides['done'] }} / {{ $guides['total'] }}</div>
            <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-gray-100 dark:bg-white/10">
                <div class="h-full rounded-full bg-primary-500 transition-all" style="width: {{ $guides['percent'] }}%"></div>
            </div>
            <a href="{{ route('filament.admin.pages.admission-tests') }}" class="mt-3 inline-block text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                Open the guides &rarr;
            </a>
        </div>
    </div>

    {{-- Section links --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        @foreach ($this->getQuickLinks() as $link)
            <a href="{{ $link['url'] }}" class="group flex items-start gap-3 rounded-xl border border-gray-200 bg-white p-4 transition hover:border-primary-300 hover:bg-primary-50/40 dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-400/40 dark:hover:bg-primary-400/5">
                <x-dynamic-component :component="$link['icon']" class="mt-0.5 h-5 w-5 shrink-0 text-primary-500" />
                <span>
                    <span class="block font-medium">{{ $link['title'] }}</span>
                    <span class="block text-xs text-gray-500 dark:text-gray-400">{{ $link['description'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
