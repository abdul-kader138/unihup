@php
    $university = $program->university;
@endphp

<div class="space-y-4 text-sm">
    <div>
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">University</div>
        <div class="text-base font-medium">{{ $university->name }} &middot; {{ $university->city }}</div>
        @if ($university->website_url)
            <a href="{{ $university->website_url }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline dark:text-primary-400">
                {{ $university->website_url }}
            </a>
        @endif
    </div>

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Level</div>
            <div>{{ \App\Models\DegreeProgram::DEGREE_LEVELS[$program->degree_level] ?? $program->degree_level }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Language</div>
            <div>{{ $program->language }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Duration</div>
            <div>{{ $program->duration_years }} {{ \Illuminate\Support\Str::plural('year', $program->duration_years) }}</div>
        </div>
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Admission</div>
            <div>{{ \App\Models\DegreeProgram::ADMISSION_TYPES[$program->admission_type] ?? $program->admission_type }}</div>
        </div>
    </div>

    @if ($program->admission_notes)
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Admission process</div>
            <p class="whitespace-pre-line">{{ $program->admission_notes }}</p>
        </div>
    @endif

    @if ($program->application_window_note)
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Application window</div>
            <p class="whitespace-pre-line">{{ $program->application_window_note }}</p>
        </div>
    @endif

    @if ($program->tuition_note)
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Tuition</div>
            <p class="whitespace-pre-line">{{ $program->tuition_note }}</p>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Verify before you apply</div>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
            This is general guidance, not a guarantee of current deadlines or fees. Always confirm on the official source.
        </p>
        <div class="mt-2 flex flex-wrap gap-3">
            @if ($program->official_admission_url)
                <a href="{{ $program->official_admission_url }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline dark:text-primary-400">
                    Official admission page &rarr;
                </a>
            @endif
            @if ($program->source_url)
                <a href="{{ $program->source_url }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline dark:text-primary-400">
                    {{ parse_url($program->source_url, PHP_URL_HOST) }} &rarr;
                </a>
            @endif
        </div>
        @if ($program->last_verified_at)
            <p class="mt-2 text-xs text-gray-400">Last verified {{ $program->last_verified_at->format('d M Y') }}</p>
        @endif
    </div>
</div>
