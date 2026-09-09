<?php

namespace App\Filament\Pages;

use App\Models\ShortlistItem;
use App\Support\CostEstimator as CostEstimatorSupport;
use App\Support\EligibilityEngine;
use Filament\Actions\Action as HeaderAction;
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
                    ->with(['degreeProgram.university', 'degreeProgram.subject'])
            )
            ->defaultSort('updated_at', 'desc')
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

                    TextColumn::make('estimated_cost')
                        ->label('')
                        ->badge()
                        ->color('gray')
                        ->icon('heroicon-o-calculator')
                        ->getStateUsing(fn (ShortlistItem $record) => CostEstimatorSupport::quickRange($record->degreeProgram).' / yr est.')
                        ->tooltip('Rough first-year total (tuition + living − a possible scholarship), assuming ISEE €20,000 and a shared flat. Open the Cost Estimator to tune it.')
                        ->url(fn (ShortlistItem $record) => CostEstimator::getUrl(['program' => $record->degree_program_id])),

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
            HeaderAction::make('compare')
                ->label('Compare my list')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->url(CompareShortlist::getUrl())
                ->visible(fn () => auth()->user()->shortlistItems()->count() >= 2),
        ];
    }
}
