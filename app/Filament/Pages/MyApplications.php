<?php

namespace App\Filament\Pages;

use App\Models\ApplicationProgress;
use App\Models\ShortlistItem;
use App\Models\StudentDocument;
use App\Support\ApplicationSteps;
use App\Support\CostEstimator as CostEstimatorSupport;
use App\Support\EligibilityEngine;
use Filament\Actions\Action as HeaderAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * The student's saved programs and where they stand with each one. Open to
 * every panel user (no HasPageShield), same as FindUniversities. Rows are
 * always scoped to the signed-in user's own shortlist.
 */
class MyApplications extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';

    protected static ?string $navigationLabel = 'My Applications';

    protected static ?string $title = 'My Applications';

    protected static ?string $slug = 'my-applications';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 10;

    protected static string $view = 'filament.pages.my-applications';

    /** @var array<int, array{verdict: string, reasons: array<int, string>}> per-request memo, keyed by program id */
    protected array $eligibilityCache = [];

    /**
     * @return array{verdict: string, reasons: array<int, string>}
     */
    protected function eligibilityFor(ShortlistItem $item): array
    {
        $program = $item->degreeProgram;

        return $this->eligibilityCache[$program->id] ??= EligibilityEngine::assess($program, auth()->user());
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ShortlistItem::query()
                    ->where('user_id', auth()->id())
                    ->with(['degreeProgram.university', 'degreeProgram.subject', 'applicationProgress', 'documents'])
            )
            // Each row does an eligibility read, a checklist tally and a cost
            // estimate on top of the four eager-loaded relations — let the
            // page shell paint first and pull the rows in a second request.
            ->deferLoading()
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->columns([
                Stack::make([
                    Split::make([
                        ImageColumn::make('degreeProgram.university.display_logo_url')
                            ->label('')
                            ->circular()
                            ->size(40)
                            ->grow(false),

                        Stack::make([
                            TextColumn::make('degreeProgram.university.name')
                                ->label('University')
                                ->weight('bold')
                                ->formatStateUsing(fn (ShortlistItem $record) => $record->degreeProgram->university->display_name)
                                ->url(fn (ShortlistItem $record) => UniversityProfile::getUrl(['id' => $record->degreeProgram->university_id]))
                                ->wrap(),
                            TextColumn::make('degreeProgram.name')
                                ->label('Program')
                                ->color('gray')
                                ->wrap(),
                            TextColumn::make('degreeProgram.university.city')
                                ->label('City')
                                ->icon('heroicon-o-map-pin')
                                ->color('gray')
                                ->size('xs'),
                        ])->space(1),
                    ]),

                    SelectColumn::make('status')
                        ->label('Status')
                        ->options(ShortlistItem::STATUSES)
                        ->selectablePlaceholder(false)
                        ->rules(['required']),

                    SelectColumn::make('tier')
                        ->label('Tier')
                        ->options(ShortlistItem::TIERS)
                        ->placeholder('Set reach / target / safety'),

                    TextColumn::make('degreeProgram.admission_type')
                        ->label('Admission')
                        ->badge()
                        ->color(fn (string $state) => $state === 'restricted' ? 'warning' : 'success')
                        ->formatStateUsing(fn (string $state) => $state === 'restricted' ? 'Restricted' : 'Open access'),

                    TextColumn::make('eligibility')
                        ->label('')
                        ->badge()
                        ->icon('heroicon-o-sparkles')
                        ->getStateUsing(fn (ShortlistItem $record) => EligibilityEngine::LABELS[$this->eligibilityFor($record)['verdict']])
                        ->color(fn (ShortlistItem $record) => EligibilityEngine::COLORS[$this->eligibilityFor($record)['verdict']])
                        ->tooltip(fn (ShortlistItem $record) => implode(' ', $this->eligibilityFor($record)['reasons']))
                        ->visible(fn () => auth()->user()->hasCompletedStudyProfile()),

                    TextColumn::make('application_checklist')
                        ->label('')
                        ->badge()
                        ->icon('heroicon-o-clipboard-document-check')
                        ->getStateUsing(function (ShortlistItem $record) {
                            $c = $record->applicationChecklist((bool) auth()->user()->is_eu_citizen);

                            return "Application {$c['done']}/{$c['total']}";
                        })
                        ->color(function (ShortlistItem $record) {
                            $c = $record->applicationChecklist((bool) auth()->user()->is_eu_citizen);

                            return match (true) {
                                $c['percent'] === 100 => 'success',
                                $c['percent'] > 0 => 'info',
                                default => 'gray',
                            };
                        }),

                    TextColumn::make('docs_link')
                        ->label('')
                        ->badge()
                        ->color('gray')
                        ->icon('heroicon-o-paper-clip')
                        ->getStateUsing(function (ShortlistItem $record) {
                            $attached = $record->documents->count();

                            if ($attached === 0) {
                                return null;
                            }

                            $submitted = $record->documents->where('pivot.status', 'submitted')->count();

                            return "Docs {$submitted}/{$attached} submitted";
                        }),

                    TextColumn::make('estimated_cost')
                        ->label('')
                        ->badge()
                        ->color('gray')
                        ->icon('heroicon-o-calculator')
                        ->getStateUsing(fn (ShortlistItem $record) => CostEstimatorSupport::quickRange($record->degreeProgram).' / yr est.')
                        ->tooltip('Rough first-year total (tuition + living − a possible scholarship), assuming ISEE €20,000 and a shared flat. Open the Cost Estimator to tune it.')
                        ->url(fn (ShortlistItem $record) => CostEstimator::getUrl(['program' => $record->degree_program_id])),

                    TextColumn::make('data_freshness')
                        ->label('')
                        ->size('xs')
                        ->icon('heroicon-o-clock')
                        ->color(fn (ShortlistItem $record) => $record->degreeProgram->isStale() ? 'warning' : 'gray')
                        ->getStateUsing(fn (ShortlistItem $record) => $record->degreeProgram->verificationLabel())
                        ->tooltip('When an editor last confirmed this program\'s admission details. Re-check the official page before you rely on dates or fees.'),

                    TextColumn::make('notes')
                        ->label('My notes')
                        ->placeholder('—')
                        ->color('gray')
                        ->wrap()
                        ->lineClamp(3),
                ])->space(3),
            ])
            ->filters([
                SelectFilter::make('status')->options(ShortlistItem::STATUSES),
                SelectFilter::make('tier')->options(ShortlistItem::TIERS),
            ])
            ->contentGrid(['default' => 1, 'md' => 2, 'xl' => 3])
            ->actions([
                Action::make('view')
                    ->label('Admission info')
                    ->icon('heroicon-o-information-circle')
                    ->link()
                    ->modalHeading(fn (ShortlistItem $record) => $record->degreeProgram->name)
                    ->modalContent(fn (ShortlistItem $record) => view(
                        'filament.pages.degree-program-details',
                        ['program' => $record->degreeProgram],
                    ))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),

                Action::make('trackApplication')
                    ->label('Application steps')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->link()
                    ->modalHeading(fn (ShortlistItem $record) => 'Application checklist — '.$record->degreeProgram->name)
                    ->modalSubmitActionLabel('Save')
                    ->fillForm(fn (ShortlistItem $record) => [
                        'steps' => $record->applicationProgress->where('done', true)->pluck('step_key')->all(),
                    ])
                    ->form(function (ShortlistItem $record) {
                        $steps = ApplicationSteps::forItem($record, (bool) auth()->user()->is_eu_citizen);

                        return [
                            CheckboxList::make('steps')
                                ->label('Tick what you have done for this program')
                                ->options(collect($steps)->pluck('label', 'key'))
                                ->descriptions(collect($steps)->pluck('hint', 'key'))
                                ->bulkToggleable()
                                ->columns(1),
                        ];
                    })
                    ->action(function (ShortlistItem $record, array $data) {
                        $checked = collect($data['steps'] ?? []);
                        $now = now();

                        foreach (ApplicationSteps::keys() as $key) {
                            $done = $checked->contains($key);

                            ApplicationProgress::updateOrCreate(
                                ['shortlist_item_id' => $record->id, 'step_key' => $key],
                                ['done' => $done, 'completed_at' => $done ? $now : null],
                            );
                        }

                        $record->load('applicationProgress');
                    }),

                Action::make('linkDocuments')
                    ->label('Documents')
                    ->icon('heroicon-o-paper-clip')
                    ->link()
                    ->modalHeading(fn (ShortlistItem $record) => 'Documents for '.$record->degreeProgram->name)
                    ->modalSubmitActionLabel('Save')
                    ->visible(fn () => auth()->user()->studentDocuments()->exists())
                    ->fillForm(fn (ShortlistItem $record) => [
                        'attached' => $record->documents->pluck('id')->all(),
                        'submitted' => $record->documents->where('pivot.status', 'submitted')->pluck('id')->all(),
                    ])
                    ->form(function () {
                        $options = auth()->user()->studentDocuments()
                            ->get()
                            ->mapWithKeys(fn (StudentDocument $d) => [$d->id => trim($d->typeLabel().($d->label ? " — {$d->label}" : ''))]);

                        return [
                            CheckboxList::make('attached')
                                ->label('Documents this application needs')
                                ->helperText('Link items from your vault. Manage the vault in My Documents.')
                                ->options($options)
                                ->columns(1)
                                ->bulkToggleable(),
                            CheckboxList::make('submitted')
                                ->label('...and already uploaded to the university portal')
                                ->options($options)
                                ->columns(1),
                        ];
                    })
                    ->action(function (ShortlistItem $record, array $data) {
                        $attached = collect($data['attached'] ?? [])->map(fn ($v) => (int) $v);
                        $submitted = collect($data['submitted'] ?? [])->map(fn ($v) => (int) $v);
                        $ownIds = auth()->user()->studentDocuments()->pluck('id');

                        $sync = $attached->intersect($ownIds)
                            ->mapWithKeys(fn ($id) => [
                                (int) $id => ['status' => $submitted->contains($id) ? 'submitted' : 'attached'],
                            ])
                            ->all();

                        $record->documents()->sync($sync);
                        $record->load('documents');
                    }),

                Action::make('editNotes')
                    ->label('Notes')
                    ->icon('heroicon-o-pencil-square')
                    ->link()
                    ->fillForm(fn (ShortlistItem $record) => ['notes' => $record->notes])
                    ->form([
                        Textarea::make('notes')
                            ->label('Private notes for this program')
                            ->rows(4),
                    ])
                    ->action(fn (ShortlistItem $record, array $data) => $record->update(['notes' => $data['notes']])),

                DeleteAction::make()
                    ->label('Remove')
                    ->modalHeading('Remove from my list')
                    ->successNotificationTitle('Removed from your list'),
            ])
            ->emptyStateHeading('Your list is empty')
            ->emptyStateDescription('Save programs from Find Universities to track them here.')
            ->emptyStateIcon('heroicon-o-bookmark')
            ->emptyStateActions([
                Action::make('find')
                    ->label('Find universities')
                    ->url(FindUniversities::getUrl())
                    ->icon('heroicon-o-magnifying-glass'),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            HeaderAction::make('board')
                ->label('Board view')
                ->icon('heroicon-o-view-columns')
                ->color('gray')
                ->url(MyApplicationsBoard::getUrl()),

            HeaderAction::make('compare')
                ->label('Compare my list')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(CompareShortlist::getUrl())
                ->visible(fn () => auth()->user()->shortlistItems()->count() >= 2),
        ];
    }
}
