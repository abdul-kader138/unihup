@php
    $university = $program->university;

    $regionKey = \App\Support\ItalianRegions::canonicalize($university->region);
    $scholarships = $regionKey
        ? \App\Models\RegionalScholarship::get()->filter(
            fn ($s) => \App\Support\ItalianRegions::canonicalize($s->region) === $regionKey
        )
        : collect();

    $languageNote = \App\Support\LanguageProficiencyCopy::forLanguage($program->language);
    $ranking = $university->latestRanking();
@endphp

<div class="space-y-4 text-sm">
    <div class="flex items-center gap-3">
        <img src="{{ $university->display_logo_url }}" alt="" class="h-12 w-12 shrink-0 rounded-lg object-contain ring-1 ring-gray-200 dark:ring-white/10">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">University</div>
            <div class="text-base font-medium">{{ $university->name }} &middot; {{ $university->city }}</div>
            @if ($university->website_url)
                <a href="{{ $university->website_url }}" target="_blank" rel="noopener" class="text-primary-600 hover:underline dark:text-primary-400">
                    {{ $university->website_url }}
                </a>
            @endif
            @if ($ranking)
                <div class="mt-1 inline-flex items-center gap-1 rounded-full bg-warning-50 px-2 py-0.5 text-xs font-medium text-warning-700 dark:bg-warning-400/10 dark:text-warning-400">
                    <x-heroicon-o-trophy class="h-3.5 w-3.5" />
                    CENSIS {{ $ranking->edition }}: #{{ $ranking->position }} among {{ \App\Models\UniversityRanking::CATEGORIES[$ranking->category] }} (score {{ $ranking->overall_score }})
                </div>
            @endif
        </div>
    </div>

    @if ($university->description)
        <p class="text-xs text-gray-600 dark:text-gray-400">{{ $university->description }}</p>
    @endif

    <div class="grid grid-cols-2 gap-4 sm:grid-cols-5">
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Subject</div>
            <div>{{ $program->subject->name }}</div>
        </div>
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

    <div>
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Financial support for international students</div>
        <div class="mt-2 space-y-2">
            <div class="rounded-lg border border-gray-200 p-2 dark:border-white/10">
                <div class="font-medium">ISEE Parificato (for your tuition bracket)</div>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ \App\Support\FinancialSupportCopy::ISEE_PARIFICATO_NOTE }}</p>
            </div>
            <div class="rounded-lg border border-gray-200 p-2 dark:border-white/10">
                <div class="font-medium">MAECI government scholarships</div>
                <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ \App\Support\FinancialSupportCopy::MAECI_SCHOLARSHIP_NOTE }}</p>
            </div>
        </div>
        <div class="mt-2 flex flex-wrap gap-4">
            @foreach (\App\Support\FinancialSupportCopy::OFFICIAL_LINKS as $label => $url)
                <a href="{{ $url }}" target="_blank" rel="noopener" class="text-xs text-primary-600 hover:underline dark:text-primary-400">
                    {{ $label }} &rarr;
                </a>
            @endforeach
        </div>
    </div>

    <div>
        <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Language proficiency</div>
        <p class="whitespace-pre-line">{{ $languageNote }}</p>
        <p class="mt-1 text-xs text-gray-400">General guidance for {{ $program->language }}-taught programs — not this specific program's exact requirement.</p>
    </div>

    @if ($scholarships->isNotEmpty())
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                Regional scholarships ({{ $university->region }})
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Italy's right-to-study (diritto allo studio) benefits — income-tested scholarships, subsidized housing,
                meal plans — are administered per region, not by MUR nationally.
            </p>
            <ul class="mt-2 space-y-2">
                @foreach ($scholarships as $scholarship)
                    <li class="rounded-lg border border-gray-200 p-2 dark:border-white/10">
                        <div class="flex items-center justify-between gap-2">
                            <span class="font-medium">{{ $scholarship->body_name }}</span>
                            @if ($scholarship->website_url)
                                <a href="{{ $scholarship->website_url }}" target="_blank" rel="noopener" class="shrink-0 text-xs text-primary-600 hover:underline dark:text-primary-400">
                                    Official site &rarr;
                                </a>
                            @endif
                        </div>
                        @if ($scholarship->description)
                            <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ $scholarship->description }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($ranking)
        <div>
            <div class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                CENSIS score breakdown ({{ $ranking->edition }})
            </div>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Each score is relative to other {{ \App\Models\UniversityRanking::CATEGORIES[$ranking->category] }} in the same edition — not a percentage.
            </p>
            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3">
                @foreach ([
                    'Services' => $ranking->score_services,
                    'Scholarships' => $ranking->score_scholarships,
                    'Facilities' => $ranking->score_facilities,
                    'Communication/digital' => $ranking->score_communication_digital,
                    'Internationalization' => $ranking->score_internationalization,
                    'Employability' => $ranking->score_employability,
                ] as $label => $value)
                    @if ($value !== null)
                        <div class="rounded-lg border border-gray-200 px-2 py-1.5 dark:border-white/10">
                            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $label }}</div>
                            <div class="font-semibold">{{ $value }}</div>
                        </div>
                    @endif
                @endforeach
            </div>
            @if ($ranking->source_url)
                <a href="{{ $ranking->source_url }}" target="_blank" rel="noopener" class="mt-2 inline-block text-xs text-primary-600 hover:underline dark:text-primary-400">
                    CENSIS full ranking &rarr;
                </a>
            @endif
        </div>
    @endif

    <div class="rounded-lg border border-primary-200 bg-primary-50 p-3 dark:border-primary-400/20 dark:bg-primary-400/5">
        <div class="text-xs font-semibold uppercase tracking-wide text-primary-700 dark:text-primary-400">Applying from outside Italy?</div>
        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">{{ \App\Support\DocumentRecognitionCopy::MODAL_NOTE }}</p>
        <p class="mt-2 text-xs text-gray-600 dark:text-gray-400">{{ \App\Support\VisaArrivalCopy::MODAL_NOTE }}</p>
        <div class="mt-2 flex flex-wrap gap-4">
            <a href="{{ route('filament.admin.pages.doc-recognition') }}" target="_blank" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                Document recognition (DOV/CIMEA) guide &rarr;
            </a>
            <a href="{{ route('filament.admin.pages.visa-arrival') }}" target="_blank" class="text-xs font-medium text-primary-600 hover:underline dark:text-primary-400">
                Visa &amp; arrival guide &rarr;
            </a>
        </div>
    </div>

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
