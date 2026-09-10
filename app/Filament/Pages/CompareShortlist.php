<?php

namespace App\Filament\Pages;

use App\Models\DegreeProgram;
use App\Models\UniversityRanking;
use App\Support\CostEstimator;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Livewire\Attributes\Url;

/**
 * Side-by-side comparison of the programs on the student's list — tuition,
 * language, duration, admission type, application window and CENSIS
 * standing in one view. Open to every panel user (no HasPageShield).
 *
 * The student picks which saved programs go into the table from a tray of
 * chips; the choice rides in the URL (?programs=3,7,9) so it survives a
 * reload and can be linked. Capped at MAX_COLUMNS so the table stays
 * readable — going over the cap swaps, it never silently hides a program.
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

    public const MAX_COLUMNS = 5;

    /**
     * Degree-program IDs currently in the comparison, in column order.
     *
     * @var array<int, int>
     */
    #[Url(as: 'programs')]
    public array $selected = [];

    /** @var Collection<int, DegreeProgram>|null per-request memo of every saved program */
    protected ?Collection $savedCache = null;

    public function mount(): void
    {
        $this->selected = $this->sanitise($this->selected);

        if ($this->selected === []) {
            $this->selected = $this->savedPrograms()
                ->take(self::MAX_COLUMNS)
                ->pluck('id')
                ->all();
        }
    }

    /**
     * Every program the student has saved, best-ranked first, then A–Z.
     * Feeds both the chip tray and (filtered) the comparison table.
     *
     * @return Collection<int, DegreeProgram>
     */
    public function savedPrograms(): Collection
    {
        return $this->savedCache ??= auth()->user()
            ->shortlistedPrograms()
            ->with(['university.rankings', 'subject'])
            ->get()
            ->sortBy(fn (DegreeProgram $p) => [
                $this->rankingSortKey($p),
                $p->university->display_name,
            ])
            ->values();
    }

    public function getTotalShortlisted(): int
    {
        return $this->savedPrograms()->count();
    }

    /** True once the student has picked the most the table will hold. */
    public function isAtCapacity(): bool
    {
        return count($this->selected) >= self::MAX_COLUMNS;
    }

    /**
     * Chip tray: every saved program with its current on/off state.
     *
     * @return array<int, array{id: int, university: string, name: string, logo: string, selected: bool}>
     */
    public function getTray(): array
    {
        return $this->savedPrograms()
            ->map(fn (DegreeProgram $p) => [
                'id' => $p->id,
                'university' => $p->university->display_name,
                'name' => $p->name,
                'logo' => $p->university->display_logo_url,
                'selected' => in_array($p->id, $this->selected, true),
            ])
            ->all();
    }

    public function toggle(int $programId): void
    {
        if (in_array($programId, $this->selected, true)) {
            $this->remove($programId);

            return;
        }

        if ($this->isAtCapacity()) {
            return;
        }

        $this->selected[] = $programId;
    }

    public function remove(int $programId): void
    {
        $this->selected = array_values(
            array_filter($this->selected, fn (int $id) => $id !== $programId)
        );
    }

    /**
     * Drop everything that is not a program this user still has saved, kill
     * duplicates and hold the column cap. Keeps the student's chosen order.
     *
     * @param  array<int, mixed>  $ids
     * @return array<int, int>
     */
    protected function sanitise(array $ids): array
    {
        $valid = $this->savedPrograms()->pluck('id');

        return collect($ids)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $valid->contains($id))
            ->unique()
            ->take(self::MAX_COLUMNS)
            ->values()
            ->all();
    }

    /**
     * The chosen programs, in the order their chips were added.
     *
     * @return Collection<int, DegreeProgram>
     */
    public function getPrograms(): Collection
    {
        $order = array_flip($this->selected);

        return $this->savedPrograms()
            ->filter(fn (DegreeProgram $p) => isset($order[$p->id]))
            ->sortBy(fn (DegreeProgram $p) => $order[$p->id])
            ->values();
    }

    /**
     * Pre-built comparison grid so the Blade view needs no logic.
     *
     * @return array{
     *     programs: array<int, array{id: int, name: string, university: string, logo: string}>,
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
                'id' => $p->id,
                'name' => $p->name,
                'university' => $p->university->display_name,
                'logo' => $p->university->display_logo_url,
            ])->all(),
            'rows' => $rows,
        ];
    }

    /** Sort weight: better CENSIS position first, unranked universities last. */
    private function rankingSortKey(DegreeProgram $program): int
    {
        return $program->university->latestRanking()?->position ?? PHP_INT_MAX;
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
