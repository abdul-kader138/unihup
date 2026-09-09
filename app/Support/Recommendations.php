<?php

namespace App\Support;

use App\Filament\Pages\CityGuides;
use App\Filament\Pages\FindUniversities;
use App\Filament\Pages\MyApplications;
use App\Filament\Pages\MyDocuments;
use App\Filament\Pages\MyScholarships;
use App\Filament\Pages\Onboarding;
use App\Filament\Pages\UniversityProfile;
use App\Models\CityGuide;
use App\Models\DegreeProgram;
use App\Models\JourneyProgress;
use App\Models\ScholarshipTracker;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * "For you" — a short, prioritised list of the next useful things a given
 * student could do, derived from their shortlist, study profile and progress.
 * Pure read model (no DB writes), rendered on App\Filament\Pages\MyJourney.
 * Curated-logic style, same spirit as App\Support\JourneyTemplate: it nudges,
 * it never promises an outcome.
 */
final class Recommendations
{
    /** Hard cap on how many cards we ever show. */
    public const LIMIT = 6;

    /**
     * @return array<int, array{key: string, icon: string, title: string, description: string, url: string, cta: string}>
     */
    public static function for(User $user): array
    {
        $items = $user->shortlistItems()->with('degreeProgram')->get();
        $programs = $items->pluck('degreeProgram')->filter();

        $out = [];

        if (! $user->hasCompletedStudyProfile()) {
            $out[] = [
                'key' => 'complete-profile',
                'icon' => 'heroicon-o-sparkles',
                'title' => 'Finish your study profile',
                'description' => 'Three quick questions unlock your personalised checklist and the "can I apply?" hints on every program.',
                'url' => Onboarding::getUrl(),
                'cta' => 'Get started',
            ];
        }

        // Nothing saved yet — one clear starting move.
        if ($programs->isEmpty()) {
            $out[] = [
                'key' => 'start-shortlist',
                'icon' => 'heroicon-o-magnifying-glass',
                'title' => 'Start your shortlist',
                'description' => 'Search programs by subject, level and city, then save the ones you like to track them here.',
                'url' => FindUniversities::getUrl(),
                'cta' => 'Find universities',
            ];

            return $out;
        }

        foreach (self::eligibilityCautions($user, $programs) as $rec) {
            $out[] = $rec;
        }

        if ($user->studentDocuments()->doesntExist()) {
            $out[] = [
                'key' => 'setup-vault',
                'icon' => 'heroicon-o-folder-plus',
                'title' => 'Set up your document vault',
                'description' => 'Keep your passport, transcripts and certificates in one place, and track what each application still needs.',
                'url' => MyDocuments::getUrl(),
                'cta' => 'Open My Documents',
            ];
        }

        foreach (self::programSuggestions($user, $programs) as $rec) {
            $out[] = $rec;
        }

        foreach (self::cityGuides($programs) as $rec) {
            $out[] = $rec;
        }

        if ($user->scholarship_interest
            && ! ScholarshipTracker::query()->where('user_id', $user->id)->exists()) {
            $out[] = [
                'key' => 'match-scholarships',
                'icon' => 'heroicon-o-gift',
                'title' => 'Match scholarships to your shortlist',
                'description' => 'See the DSU regional bodies for your saved universities plus the nationwide schemes you qualify for.',
                'url' => MyScholarships::getUrl(),
                'cta' => 'Open Scholarships',
            ];
        }

        if ($guide = self::unreadGuide($user, $programs)) {
            $out[] = $guide;
        }

        return array_slice($out, 0, self::LIMIT);
    }

    /**
     * @param  Collection<int, DegreeProgram>  $programs
     * @return array<int, array<string, string>>
     */
    private static function eligibilityCautions(User $user, $programs): array
    {
        if (! $user->hasCompletedStudyProfile()) {
            return [];
        }

        foreach ($programs as $program) {
            $verdict = EligibilityEngine::assess($program, $user)['verdict'];

            if ($verdict === EligibilityEngine::INELIGIBLE) {
                return [[
                    'key' => 'eligibility-'.$program->id,
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'title' => 'Re-check '.Str::limit($program->name, 48),
                    'description' => 'Your recorded language level looks below the typical minimum for this program. Confirm the exact requirement on the university page before you apply.',
                    'url' => MyApplications::getUrl(),
                    'cta' => 'Review in My Applications',
                ]];
            }
        }

        return [];
    }

    /**
     * More programs in the subjects the student already saved, favouring
     * universities not yet on their list.
     *
     * @param  Collection<int, DegreeProgram>  $programs
     * @return array<int, array<string, string>>
     */
    private static function programSuggestions(User $user, $programs): array
    {
        $subjectIds = $programs->pluck('subject_id')->unique()->filter()->all();

        if ($subjectIds === []) {
            return [];
        }

        $languages = ['English'];
        if (ProficiencyLevels::rank($user->italian_level) >= ProficiencyLevels::rank('b1')) {
            $languages[] = 'Italian';
        }

        $knownUniversityIds = $programs->pluck('university_id')->unique();

        $suggestions = DegreeProgram::query()
            ->whereIn('subject_id', $subjectIds)
            ->whereNotIn('id', $programs->pluck('id'))
            ->whereIn('language', $languages)
            ->with(['university:id,name,canonical_name,city', 'subject:id,name'])
            ->get()
            ->sortBy(fn (DegreeProgram $p) => $knownUniversityIds->contains($p->university_id) ? 1 : 0)
            ->take(3);

        return $suggestions->map(fn (DegreeProgram $p) => [
            'key' => 'program-'.$p->id,
            'icon' => 'heroicon-o-academic-cap',
            'title' => Str::limit($p->name, 52),
            'description' => trim(($p->university?->display_name ?? 'A university').($p->university?->city ? ' · '.$p->university->city : '')
                .' — another '.strtolower($p->subject?->name ?? 'similar').' option in your subject.'),
            'url' => $p->university_id
                ? UniversityProfile::getUrl(['id' => $p->university_id])
                : FindUniversities::getUrl(),
            'cta' => 'View the university',
        ])->values()->all();
    }

    /**
     * Published city guides for the cities of shortlisted universities.
     *
     * @param  Collection<int, DegreeProgram>  $programs
     * @return array<int, array<string, string>>
     */
    private static function cityGuides($programs): array
    {
        $cities = $programs
            ->map(fn (DegreeProgram $p) => $p->university?->city)
            ->filter()
            ->unique()
            ->values();

        $out = [];

        foreach ($cities as $city) {
            $guide = CityGuide::forCity($city);

            if ($guide) {
                $out[] = [
                    'key' => 'city-'.$guide->slug,
                    'icon' => 'heroicon-o-building-office-2',
                    'title' => 'Living in '.$guide->city,
                    'description' => 'Housing, cost of living, transport and student life for a city on your shortlist.',
                    'url' => CityGuides::getUrl(['city' => $guide->slug]),
                    'cta' => 'Read the city guide',
                ];
            }

            if (count($out) === 2) {
                break;
            }
        }

        return $out;
    }

    /**
     * The single most relevant curated guide the student has not finished.
     *
     * @param  Collection<int, DegreeProgram>  $programs
     * @return array<string, string>|null
     */
    private static function unreadGuide(User $user, $programs): ?array
    {
        $readKeys = $user->journeyProgress()
            ->where('state', JourneyProgress::STATE_DONE)
            ->where('step_key', 'like', 'guide:%')
            ->pluck('step_key');

        $guides = [
            'visa-arrival' => [
                'total' => count(VisaArrivalCopy::SECTIONS),
                'route' => 'filament.admin.pages.visa-arrival',
                'title' => 'Read the Visa & Arrival guide',
                'description' => 'Type D visa, permesso di soggiorno and codice fiscale — the steps every non-EU student has to plan for.',
                'when' => ! $user->is_eu_citizen,
            ],
            'admission-tests' => [
                'total' => count(AdmissionTestCopy::SECTIONS),
                'route' => 'filament.admin.pages.admission-tests',
                'title' => 'Read the Admission Tests guide',
                'description' => 'TOLC / IMAT and the semestre filtro — how restricted-access programs select applicants.',
                'when' => $programs->contains('admission_type', 'restricted'),
            ],
            'doc-recognition' => [
                'total' => count(DocumentRecognitionCopy::SECTIONS),
                'route' => 'filament.admin.pages.doc-recognition',
                'title' => 'Read the Document Recognition guide',
                'description' => 'Dichiarazione di Valore and CIMEA statements — getting your qualifications accepted in Italy.',
                'when' => true,
            ],
        ];

        foreach ($guides as $key => $guide) {
            if (! $guide['when']) {
                continue;
            }

            $read = $readKeys->filter(fn (string $k) => Str::startsWith($k, "guide:{$key}:"))->count();

            if ($read < $guide['total']) {
                return [
                    'key' => 'guide-'.$key,
                    'icon' => 'heroicon-o-book-open',
                    'title' => $guide['title'],
                    'description' => $guide['description'],
                    'url' => route($guide['route']),
                    'cta' => 'Open the guide',
                ];
            }
        }

        return null;
    }
}
