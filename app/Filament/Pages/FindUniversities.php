<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\ScoutTableSearch;
use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use App\Support\EligibilityEngine;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;

/**
 * The panel's home page for every registered user (not just staff) — see
 * App\Providers\Filament\AdminPanelProvider::homeUrl() and
 * App\Filament\Auth\{LoginResponse,RegistrationResponse}. No HasPageShield
 * trait/canAccess() override, unlike App\Filament\Pages\SystemSettings —
 * that's what leaves this page ungated for anyone who can access the panel
 * at all (see User::canAccessPanel()), matching how the previous app's
 * FlightSearch page worked.
 */
class FindUniversities extends Page implements HasTable
{
    use InteractsWithTable, ScoutTableSearch {
        ScoutTableSearch::applyGlobalSearchToTableQuery insteadof InteractsWithTable;
    }

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass';

    protected static ?string $navigationLabel = 'Find Universities';

    protected static ?string $title = 'Find Universities';

    // Not 'universities' — that URL/route-name pair already belongs to the
    // admin-only App\Filament\Resources\UniversityResource, and Filament
    // doesn't error on the collision, it just silently drops one of the two
    // routes (confirmed via `artisan route:list`), so this needs to be
    // distinct even though the resource is permission-gated and this page
    // isn't.
    protected static ?string $slug = 'find-universities';

    protected static ?int $navigationSort = -1;

    protected static string $view = 'filament.pages.find-universities';

    public function table(Table $table): Table
    {
        return $table
            // latest_ranking_* on universities covers the card; the details
            // modal load()s the full rankings rows only when it's opened.
            ->query(DegreeProgram::query()->with(['university', 'subject']))
            ->searchPlaceholder('Search by university, program, or subject...')
            // Render the page shell immediately; the table body (a
            // university+subject join with a per-row eligibility read) loads
            // in a follow-up request so first paint isn't blocked on it.
            ->deferLoading()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('university_id')
                    ->label('University')
                    ->options(fn () => $this->decorateWithCounts(
                        Cache::remember('find-universities:university-options', 60, fn () => University::query()
                            ->orderByRaw('COALESCE(canonical_name, name)')
                            ->get(['id', 'name', 'canonical_name'])
                            ->mapWithKeys(fn ($university) => [$university->id => $university->display_name])
                            ->all()),
                        'university_id',
                    ))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn () => $this->decorateWithCounts(
                        Cache::remember('find-universities:subject-options', 60, fn () => Subject::query()
                            ->orderByRaw('COALESCE(canonical_name, name)')
                            ->get(['id', 'name', 'canonical_name'])
                            ->mapWithKeys(fn ($subject) => [$subject->id => $subject->display_name])
                            ->all()),
                        'subject_id',
                    ))
                    ->searchable()
                    ->preload()
                    ->default(fn () => auth()->user()->preferred_subject_id),

                SelectFilter::make('degree_level')
                    ->label('Degree level')
                    ->options(fn () => $this->decorateWithCounts(DegreeProgram::DEGREE_LEVELS, 'degree_level'))
                    ->default(fn () => auth()->user()->preferred_degree_level),

                SelectFilter::make('admission_type')
                    ->label('Admission')
                    ->options(fn () => $this->decorateWithCounts(DegreeProgram::ADMISSION_TYPES, 'admission_type')),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(4)
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Stack::make([
                    Split::make([
                        ImageColumn::make('university.display_logo_url')
                            ->label('')
                            ->circular()
                            ->size(40)
                            ->grow(false),

                        Stack::make([
                            TextColumn::make('university.name')
                                ->label('University')
                                ->weight('bold')
                                ->searchable(['name', 'canonical_name'])
                                ->sortable()
                                ->formatStateUsing(fn ($state, DegreeProgram $record) => $record->university->display_name)
                                ->url(fn (DegreeProgram $record) => UniversityProfile::getUrl(['id' => $record->university_id]))
                                ->wrap(),

                            TextColumn::make('degree_level')
                                ->badge()
                                ->grow(false)
                                ->wrap()
                                ->formatStateUsing(fn (string $state) => DegreeProgram::DEGREE_LEVELS[$state] ?? $state),

                            TextColumn::make('university.city')
                                ->label('City')
                                ->icon('heroicon-o-map-pin')
                                ->color('gray')
                                ->size('xs'),

                            TextColumn::make('university.latest_ranking_position')
                                ->label('')
                                ->sortable()
                                ->getStateUsing(fn (?DegreeProgram $record) => $record?->university?->latest_ranking_position
                                    ? "CENSIS #{$record->university->latest_ranking_position}"
                                    : null)
                                ->tooltip(fn (?DegreeProgram $record) => $record?->university?->rankingSummary())
                                ->badge()
                                ->color('warning')
                                ->icon('heroicon-o-trophy')
                                ->size('xs')
                                ->visible(fn (?DegreeProgram $record) => $record?->university?->hasRanking()),
                        ])->space(1),
                    ]),

                    TextColumn::make('name')
                        ->label('Program')
                        ->searchable()
                        ->size('lg')
                        ->weight('semibold')
                        ->wrap(),

                    TextColumn::make('subject.name')
                        ->label('Subject')
                        ->searchable(['name', 'canonical_name'])
                        ->badge()
                        ->color('gray')
                        ->limit(28)
                        ->formatStateUsing(fn ($state, DegreeProgram $record) => $record->subject->display_name)
                        ->tooltip(fn (DegreeProgram $record) => $record->subject->display_name),

                    TextColumn::make('admission_type')
                        ->label('Admission')
                        ->badge()
                        ->color(fn (string $state) => $state === 'restricted' ? 'warning' : 'success')
                        ->formatStateUsing(fn (string $state) => $state === 'restricted' ? 'Restricted' : 'Open access')
                        ->tooltip(fn (string $state) => DegreeProgram::ADMISSION_TYPES[$state] ?? $state),

                    TextColumn::make('language')
                        ->badge()
                        ->color('gray'),

                    TextColumn::make('eligibility')
                        ->label('')
                        ->badge()
                        ->icon('heroicon-o-sparkles')
                        ->getStateUsing(fn (DegreeProgram $record) => EligibilityEngine::LABELS[$this->eligibilityFor($record)['verdict']])
                        ->color(fn (DegreeProgram $record) => EligibilityEngine::COLORS[$this->eligibilityFor($record)['verdict']])
                        ->tooltip(fn (DegreeProgram $record) => implode(' ', $this->eligibilityFor($record)['reasons']))
                        ->visible(fn () => auth()->user()->hasCompletedStudyProfile()),

                    TextColumn::make('last_verified_at')
                        ->label('')
                        ->icon('heroicon-o-clock')
                        ->size('xs')
                        ->color(fn (DegreeProgram $record) => $record->isStale() ? 'warning' : 'gray')
                        ->getStateUsing(fn (DegreeProgram $record) => $record->verificationLabel())
                        ->tooltip('When an editor last confirmed this program\'s admission details against the official source. Always double-check on the university page.'),
                ])->space(2),
            ])
            ->recordAction('view')
            ->actions([
                TableAction::make('view')
                    ->label('View admission info')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->link()
                    ->modalHeading(fn (DegreeProgram $record) => $record->name)
                    ->modalContent(fn (DegreeProgram $record) => view('filament.pages.degree-program-details', [
                        'program' => $record->load('university.rankings'),
                    ]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                TableAction::make('shortlist')
                    ->label(fn (DegreeProgram $record) => $this->isShortlisted($record) ? 'On my list' : 'Save to my list')
                    ->icon(fn (DegreeProgram $record) => $this->isShortlisted($record) ? 'heroicon-s-bookmark' : 'heroicon-o-bookmark')
                    ->color(fn (DegreeProgram $record) => $this->isShortlisted($record) ? 'primary' : 'gray')
                    ->link()
                    ->action(fn (DegreeProgram $record) => $this->toggleShortlist($record)),
            ])
            ->defaultSort('university.name')
            ->emptyStateHeading('No matching programs yet')
            ->emptyStateDescription('Try clearing a filter above, or ask an admin to add more universities.')
            ->emptyStateIcon('heroicon-o-building-library');
    }

    protected function scoutModel(): string
    {
        return DegreeProgram::class;
    }

    /** @var array<string, array<string, int>>|null field => [facet value => match count] */
    protected ?array $facetCache = null;

    /** The filter fields that carry a live match count next to each option. */
    private const FACETED_FILTERS = ['university_id', 'subject_id', 'degree_level', 'admission_type'];

    /**
     * One Meilisearch facet query per faceted filter, each scoped to the
     * current search term plus every *other* active filter — so the number
     * beside an option is what picking it would actually narrow the results
     * to. Runs once per Livewire request (memoised) and costs one hits-less
     * engine call per field.
     *
     * Returns an empty array — meaning "don't decorate" — when Scout has no
     * real engine (the LIKE-search fallback in ScoutTableSearch can't
     * produce facets) or when the engine call fails; a search hiccup must
     * not take the page down over a cosmetic count.
     *
     * @return array<string, array<string, int>>
     */
    protected function facetCounts(): array
    {
        if ($this->facetCache !== null) {
            return $this->facetCache;
        }

        if (in_array(config('scout.driver'), [null, '', 'null'], true)) {
            return $this->facetCache = [];
        }

        $term = trim((string) $this->getTableSearch());
        $active = $this->tableFilters ?? [];

        try {
            $counts = [];

            foreach (self::FACETED_FILTERS as $field) {
                $filter = [];

                foreach (self::FACETED_FILTERS as $other) {
                    if ($other === $field) {
                        continue;
                    }

                    $value = $active[$other]['value'] ?? null;

                    if ($value === null || $value === '') {
                        continue;
                    }

                    $filter[] = is_numeric($value)
                        ? $other.' = '.$value
                        : $other.' = "'.addcslashes((string) $value, '"\\').'"';
                }

                $raw = DegreeProgram::search($term)
                    ->options([
                        'facets' => [$field],
                        'filter' => $filter,
                        'limit' => 0,
                    ])
                    ->raw();

                $counts[$field] = array_map('intval', $raw['facetDistribution'][$field] ?? []);
            }

            return $this->facetCache = $counts;
        } catch (\Throwable $e) {
            report($e);

            return $this->facetCache = [];
        }
    }

    /**
     * Append " (n)" to each option label from the facet counts for $field.
     * Options with no matches keep their bare label so the dropdown still
     * reads cleanly before a search term narrows anything.
     *
     * @param  array<int|string, string>  $labels
     * @return array<int|string, string>
     */
    protected function decorateWithCounts(array $labels, string $field): array
    {
        $counts = $this->facetCounts()[$field] ?? [];

        if ($counts === []) {
            return $labels;
        }

        $decorated = [];

        foreach ($labels as $value => $label) {
            $count = $counts[(string) $value] ?? 0;
            $decorated[$value] = $count > 0 ? $label.' ('.$count.')' : $label;
        }

        return $decorated;
    }

    /** @var array<int, true>|null program ids on the current user's list */
    protected ?array $shortlistedIds = null;

    /** @var array<int, array{verdict: string, reasons: array<int, string>}> per-request memo */
    protected array $eligibilityCache = [];

    /**
     * @return array{verdict: string, reasons: array<int, string>}
     */
    protected function eligibilityFor(DegreeProgram $program): array
    {
        return $this->eligibilityCache[$program->id] ??= EligibilityEngine::assess($program, auth()->user());
    }

    protected function isShortlisted(DegreeProgram $program): bool
    {
        if ($this->shortlistedIds === null) {
            $this->shortlistedIds = auth()->user()->shortlistItems()
                ->pluck('degree_program_id')
                ->flip()
                ->all();
        }

        return isset($this->shortlistedIds[$program->id]);
    }

    public function toggleShortlist(DegreeProgram $program): void
    {
        $existing = auth()->user()->shortlistItems()
            ->where('degree_program_id', $program->id)
            ->first();

        if ($existing) {
            $existing->delete();
            unset($this->shortlistedIds[$program->id]);
            Notification::make()->title('Removed from your list')->send();

            return;
        }

        $item = auth()->user()->shortlistItems()->make([
            'degree_program_id' => $program->id,
            'status' => 'researching',
            'sort_order' => (int) auth()->user()->shortlistItems()->max('sort_order') + 1,
        ]);
        $item->setRelation('degreeProgram', $program);
        $item->tier = $item->suggestedTier(auth()->user());
        $item->save();
        $this->shortlistedIds[$program->id] = true;

        Notification::make()
            ->success()
            ->title('Saved to your list')
            ->body('Track it in My Applications.')
            ->send();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('savePreference')
                ->label('Save as my default')
                ->icon('heroicon-o-bookmark')
                ->color('gray')
                ->action(function () {
                    $filters = $this->tableFilters ?? [];

                    auth()->user()->forceFill([
                        'preferred_subject_id' => $filters['subject_id']['value'] ?? null,
                        'preferred_degree_level' => $filters['degree_level']['value'] ?? null,
                    ])->save();

                    Notification::make()
                        ->success()
                        ->title('Saved — you\'ll see these results by default next time you sign in.')
                        ->send();
                }),
        ];
    }
}
