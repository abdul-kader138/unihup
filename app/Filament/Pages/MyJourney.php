<?php

namespace App\Filament\Pages;

use App\Models\Deadline;
use App\Models\JourneyProgress;
use App\Models\ShortlistItem;
use App\Support\AdmissionTestCopy;
use App\Support\DocumentRecognitionCopy;
use App\Support\JourneyTemplate;
use App\Support\Recommendations;
use App\Support\VisaArrivalCopy;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * The student's home base — a personalised admission-journey checklist plus
 * a snapshot of saved programs and document-vault progress, with jumping-off
 * points to every other part of the app. This is the post-login landing page
 * for non-staff (see App\Providers\Filament\AdminPanelProvider and the
 * Login/Registration/Google responses). Open to every panel user; no
 * HasPageShield.
 *
 * Phase 3 will add a deadline strip above the checklist.
 */
class MyJourney extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'My Journey';

    protected static ?string $title = 'My Journey';

    protected static ?string $slug = 'my-journey';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = -10;

    protected static string $view = 'filament.pages.my-journey';

    /**
     * Toggle a checklist step done / pending for the current user.
     */
    public function toggleStep(string $stepKey): void
    {
        $valid = collect(JourneyTemplate::STEPS)->pluck('key')->contains($stepKey);

        if (! $valid) {
            return;
        }

        $progress = JourneyProgress::firstOrNew([
            'user_id' => auth()->id(),
            'step_key' => $stepKey,
        ]);

        if ($progress->state === JourneyProgress::STATE_DONE) {
            $progress->state = JourneyProgress::STATE_PENDING;
            $progress->completed_at = null;
        } else {
            $progress->state = JourneyProgress::STATE_DONE;
            $progress->completed_at = now();
        }

        $progress->save();
    }

    /**
     * How many curated-guide sections the student has ticked off, across all
     * three guides.
     *
     * @return array{done: int, total: int, percent: int}
     */
    public function getGuidesProgress(): array
    {
        $total = count(AdmissionTestCopy::SECTIONS)
            + count(DocumentRecognitionCopy::SECTIONS)
            + count(VisaArrivalCopy::SECTIONS);

        $done = auth()->user()->journeyProgress()
            ->where('state', JourneyProgress::STATE_DONE)
            ->where('step_key', 'like', 'guide:%')
            ->count();

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round(min($done, $total) / $total * 100) : 0,
        ];
    }

    /**
     * "For you" — a short, prioritised list of next useful moves.
     *
     * @return array<int, array{key: string, icon: string, title: string, description: string, url: string, cta: string}>
     */
    public function getRecommendations(): array
    {
        return Recommendations::for(auth()->user());
    }

    /**
     * The next few relevant deadlines, for the strip above the checklist.
     *
     * @return Collection<int, Deadline>
     */
    public function getUpcomingDeadlines(): Collection
    {
        return Deadline::relevantTo(auth()->user(), upcomingOnly: true)->take(3);
    }

    /**
     * Quick-link cards shown on the journey home.
     *
     * @return array<int, array{title: string, description: string, icon: string, url: string}>
     */
    public function getQuickLinks(): array
    {
        return [
            ['title' => 'Find Universities', 'description' => 'Search programs by subject, level and city', 'icon' => 'heroicon-o-magnifying-glass', 'url' => FindUniversities::getUrl()],
            ['title' => 'My Applications', 'description' => 'Track status and notes per program', 'icon' => 'heroicon-o-bookmark', 'url' => MyApplications::getUrl()],
            ['title' => 'My Documents', 'description' => 'Your private document vault', 'icon' => 'heroicon-o-folder', 'url' => MyDocuments::getUrl()],
            ['title' => 'Compare', 'description' => 'Saved programs side by side', 'icon' => 'heroicon-o-table-cells', 'url' => CompareShortlist::getUrl()],
            ['title' => 'My Deadlines', 'description' => 'Every date on your shortlist, with calendar export', 'icon' => 'heroicon-o-calendar-days', 'url' => MyDeadlines::getUrl()],
            ['title' => 'Cost Estimator', 'description' => 'Rough yearly cost of a saved program', 'icon' => 'heroicon-o-calculator', 'url' => CostEstimator::getUrl()],
            ['title' => 'Budget Planner', 'description' => 'One-off + yearly budget for the whole course, in your currency', 'icon' => 'heroicon-o-banknotes', 'url' => BudgetPlanner::getUrl()],
            ['title' => 'Scholarships', 'description' => 'Match DSU / national funding and track deadlines', 'icon' => 'heroicon-o-gift', 'url' => MyScholarships::getUrl()],
            ['title' => 'Admission Tests', 'description' => 'TOLC / IMAT and the semestre filtro', 'icon' => 'heroicon-o-pencil-square', 'url' => route('filament.admin.pages.admission-tests')],
            ['title' => 'Doc Recognition', 'description' => 'Dichiarazione di Valore / CIMEA', 'icon' => 'heroicon-o-document-check', 'url' => route('filament.admin.pages.doc-recognition')],
            ['title' => 'Visa & Arrival', 'description' => 'Type D visa, permesso di soggiorno, codice fiscale', 'icon' => 'heroicon-o-identification', 'url' => route('filament.admin.pages.visa-arrival')],
            ['title' => 'City Guides', 'description' => 'Housing, costs and student life by city', 'icon' => 'heroicon-o-building-office-2', 'url' => CityGuides::getUrl()],
            ['title' => 'Help Center', 'description' => 'Answers to common questions', 'icon' => 'heroicon-o-lifebuoy', 'url' => route('filament.admin.pages.help-center')],
            ['title' => 'Support Chat', 'description' => 'Ask our team a question', 'icon' => 'heroicon-o-chat-bubble-left-right', 'url' => route('filament.admin.pages.support-chat')],
        ];
    }

    /**
     * The personalised checklist, grouped by phase.
     *
     * @return array<int, array{
     *     key: string, label: string,
     *     steps: array<int, array{key: string, title: string, body: string, icon: string, help_url: ?string, done: bool}>
     * }>
     */
    public function getChecklist(): array
    {
        $user = auth()->user();
        $programs = $user->shortlistItems()
            ->with('degreeProgram:id,admission_type,language')
            ->get()
            ->pluck('degreeProgram')
            ->filter();

        $tokens = JourneyTemplate::activeTokens(
            isEuCitizen: (bool) $user->is_eu_citizen,
            wantsScholarship: (bool) $user->scholarship_interest,
            hasRestrictedProgram: $programs->contains('admission_type', 'restricted'),
            hasItalianTaughtProgram: $programs->contains('language', 'Italian'),
        );

        $steps = JourneyTemplate::stepsForTokens($tokens);
        $done = $user->journeyProgress()
            ->where('state', JourneyProgress::STATE_DONE)
            ->pluck('step_key')
            ->flip();

        $grouped = [];
        foreach (JourneyTemplate::PHASES as $phaseKey => $phaseLabel) {
            $phaseSteps = [];

            foreach ($steps as $step) {
                if ($step['phase'] !== $phaseKey) {
                    continue;
                }

                $phaseSteps[] = [
                    'key' => $step['key'],
                    'title' => $step['title'],
                    'body' => $step['body'],
                    'icon' => $step['icon'],
                    'help_url' => $step['help_route'] ? route($step['help_route']) : null,
                    'done' => isset($done[$step['key']]),
                ];
            }

            if ($phaseSteps !== []) {
                $grouped[] = [
                    'key' => $phaseKey,
                    'label' => $phaseLabel,
                    'steps' => $phaseSteps,
                ];
            }
        }

        return $grouped;
    }

    /**
     * @return array{done: int, total: int, percent: int}
     */
    public function getChecklistProgress(): array
    {
        $checklist = $this->getChecklist();
        $total = 0;
        $done = 0;

        foreach ($checklist as $phase) {
            foreach ($phase['steps'] as $step) {
                $total++;
                $done += $step['done'] ? 1 : 0;
            }
        }

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    /**
     * @return array{
     *     shortlist_total: int,
     *     status_counts: array<string, int>,
     *     documents_total: int,
     *     documents_done: int,
     *     documents_percent: int,
     *     profile_complete: bool,
     * }
     */
    public function getSummary(): array
    {
        $user = auth()->user();

        $items = $user->shortlistItems()->get();
        $documents = $user->studentDocuments()->get();

        $statusCounts = [];
        foreach (ShortlistItem::STATUSES as $key => $label) {
            $count = $items->where('status', $key)->count();
            if ($count > 0) {
                $statusCounts[$label] = $count;
            }
        }

        $documentsDone = $documents->filter->isDone()->count();

        return [
            'shortlist_total' => $items->count(),
            'status_counts' => $statusCounts,
            'documents_total' => $documents->count(),
            'documents_done' => $documentsDone,
            'documents_percent' => $documents->count() > 0
                ? (int) round($documentsDone / $documents->count() * 100)
                : 0,
            'profile_complete' => $user->hasCompletedStudyProfile(),
        ];
    }
}
