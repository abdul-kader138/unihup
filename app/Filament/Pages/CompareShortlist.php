<?php

namespace App\Filament\Pages;

use App\Models\DegreeProgram;
use App\Models\UniversityRanking;
use App\Support\CostEstimator;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

/**
 * Side-by-side comparison of the programs on the student's list — tuition,
 * language, duration, admission type, application window and CENSIS
 * standing in one view. Open to every panel user (no HasPageShield).
 * Capped at 4 columns so the table stays readable.
 */
class CompareShortlist extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Compare';

    protected static ?string $title = 'Compare My List';

    protected static ?string $slug = 'compare';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 20;

    protected static string $view = 'filament.pages.compare-shortlist';

    public const MAX_COLUMNS = 4;

    /**
     * @return Collection<int, DegreeProgram>
     */
    public function getPrograms(): Collection
    {
        return auth()->user()
            ->shortlistedPrograms()
            ->with(['university.rankings', 'subject'])
            ->get()
            ->sortBy(fn (DegreeProgram $program) => $program->university->display_name)
            ->take(self::MAX_COLUMNS)
            ->values();
    }

    public function getTotalShortlisted(): int
    {
        return auth()->user()->shortlistItems()->count();
    }

    /**
     * Pre-built comparison grid so the Blade view needs no logic.
     *
     * @return array{
     *     programs: array<int, array{name: string, university: string, logo: string}>,
     *     rows: array<int, array{label: string, values: array<int, string>}>,
     * }
     */
    public function getComparison(): array
    {
        $programs = $this->getPrograms();

        $fields = [
            'City' => fn (DegreeProgram $p) => $p->university->city,
            'Subject' => fn (DegreeProgram $p) => $p->subject->display_name,
            'Level' => fn (DegreeProgram $p) => DegreeProgram::DEGREE_LEVELS[$p->degree_level] ?? $p->degree_level,
            'Language' => fn (DegreeProgram $p) => $p->language,
            'Duration' => fn (DegreeProgram $p) => $p->duration_years.' '.str('year')->plural($p->duration_years),
            'Admission' => fn (DegreeProgram $p) => DegreeProgram::ADMISSION_TYPES[$p->admission_type] ?? $p->admission_type,
            'Application window' => fn (DegreeProgram $p) => $p->application_window_note ?: '—',
            'Tuition' => fn (DegreeProgram $p) => $p->tuition_note ?: '—',
            'CENSIS ranking' => fn (DegreeProgram $p) => $this->rankingText($p),
            'Est. yearly cost*' => fn (DegreeProgram $p) => CostEstimator::quickRange($p),
            'Official page' => fn (DegreeProgram $p) => $p->official_admission_url ?: '—',
        ];

        $rows = [];
        foreach ($fields as $label => $accessor) {
            $rows[] = [
                'label' => $label,
                'values' => $programs->map($accessor)->all(),
            ];
        }

        return [
            'programs' => $programs->map(fn (DegreeProgram $p) => [
                'name' => $p->name,
                'university' => $p->university->display_name,
                'logo' => $p->university->display_logo_url,
            ])->all(),
            'rows' => $rows,
        ];
    }

    private function rankingText(DegreeProgram $program): string
    {
        $ranking = $program->university->latestRanking();

        if ($ranking === null) {
            return '—';
        }

        $category = UniversityRanking::CATEGORIES[$ranking->category] ?? $ranking->category;

        return "#{$ranking->position} among {$category} (score {$ranking->overall_score}, {$ranking->edition})";
    }
}
