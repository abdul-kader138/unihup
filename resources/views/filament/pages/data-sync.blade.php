<x-filament-panels::page>
    @php
        $counts = $this->getCounts();
        $runs = $this->getRecentRuns();
    @endphp

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Universities</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format($counts['universities']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($counts['universities_with_website']) }} website · {{ number_format($counts['universities_with_logo']) }} logo</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subjects</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format($counts['subjects']) }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Degree programs</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format($counts['degreePrograms']) }}</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($counts['degreePrograms_english']) }} English-taught</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Regional scholarships</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format($counts['regionalScholarships']) }}</div>
        </div>

        <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">CENSIS rankings</div>
            <div class="mt-1 text-2xl font-bold">{{ number_format($counts['universityRankings']) }}</div>
        </div>
    </div>

    <div class="rounded-xl border border-gray-200 bg-white p-4 text-sm text-gray-600 dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
        <strong>Runs in the background, in order.</strong> Every action above queues a job rather than running inline,
        since a full sync can take a minute or more. The numbered actions (1-4) are the pipeline's actual sequence —
        <strong>Run full pipeline</strong> chains all four so each step only starts once the one before it finishes.
        Locally, make sure a worker is running
        (<code class="rounded bg-gray-100 px-1 py-0.5 dark:bg-white/10">php artisan queue:work</code>); in
        production this is the always-on <code class="rounded bg-gray-100 px-1 py-0.5 dark:bg-white/10">unihup-queue-worker</code>
        service. Refresh this page to see the run log below update.
    </div>

    <div class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
        <div class="border-b border-gray-200 p-4 text-sm font-semibold dark:border-white/10">Recent runs</div>

        @if ($runs->isEmpty())
            <div class="p-4 text-sm text-gray-500 dark:text-gray-400">No sync has been run yet.</div>
        @else
            <ul class="divide-y divide-gray-200 dark:divide-white/10">
                @foreach ($runs as $run)
                    @php $status = $run->getProperty('status'); @endphp
                    <li class="flex items-start justify-between gap-4 p-4 text-sm">
                        <div>
                            <div class="font-medium">{{ $run->description }}</div>
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $run->created_at->diffForHumans() }}</div>
                        </div>
                        @if ($status)
                            <span @class([
                                'shrink-0 rounded-full px-2 py-0.5 text-xs font-medium',
                                'bg-success-50 text-success-700 dark:bg-success-400/10 dark:text-success-400' => $status === 'success',
                                'bg-danger-50 text-danger-700 dark:bg-danger-400/10 dark:text-danger-400' => $status === 'failed',
                            ])>
                                {{ ucfirst($status) }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</x-filament-panels::page>
