<?php

namespace App\Filament\Pages;

use App\Models\DegreeProgram;
use App\Models\Subject;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

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
    use InteractsWithTable;

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
            ->query(DegreeProgram::query()->with(['university', 'subject']))
            ->filters([
                SelectFilter::make('subject_id')
                    ->label('Subject')
                    ->options(fn () => Subject::orderBy('name')->pluck('name', 'id'))
                    ->default(fn () => auth()->user()->preferred_subject_id),

                SelectFilter::make('degree_level')
                    ->label('Degree level')
                    ->options(DegreeProgram::DEGREE_LEVELS)
                    ->default(fn () => auth()->user()->preferred_degree_level),
            ], layout: \Filament\Tables\Enums\FiltersLayout::AboveContent)
            ->filtersFormColumns(2)
            ->columns([
                TextColumn::make('university.name')
                    ->label('University')
                    ->searchable()
                    ->sortable()
                    ->description(fn (DegreeProgram $record) => $record->university->city),

                TextColumn::make('name')
                    ->label('Program')
                    ->searchable()
                    ->wrap(),

                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->sortable(),

                TextColumn::make('degree_level')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => DegreeProgram::DEGREE_LEVELS[$state] ?? $state),

                TextColumn::make('admission_type')
                    ->label('Admission')
                    ->badge()
                    ->color(fn (string $state) => $state === 'restricted' ? 'warning' : 'success')
                    ->formatStateUsing(fn (string $state) => DegreeProgram::ADMISSION_TYPES[$state] ?? $state),

                TextColumn::make('language')
                    ->toggleable(),
            ])
            ->actions([
                TableAction::make('view')
                    ->label('Admission info')
                    ->icon('heroicon-o-information-circle')
                    ->modalHeading(fn (DegreeProgram $record) => $record->name)
                    ->modalContent(fn (DegreeProgram $record) => view('filament.pages.degree-program-details', ['program' => $record]))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->defaultSort('university.name')
            ->emptyStateHeading('No matching programs yet')
            ->emptyStateDescription('Pick a subject and degree level above, or ask an admin to add more universities.')
            ->emptyStateIcon('heroicon-o-building-library');
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
