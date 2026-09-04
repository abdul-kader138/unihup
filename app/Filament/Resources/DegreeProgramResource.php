<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DegreeProgramResource\Pages;
use App\Models\DegreeProgram;
use App\Models\Subject;
use App\Models\University;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DegreeProgramResource extends Resource
{
    protected static ?string $model = DegreeProgram::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?int $navigationSort = 30;

    public static function getNavigationGroup(): ?string
    {
        return 'Universities';
    }

    public static function getNavigationLabel(): string
    {
        return 'Degree Programs';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Program')
                ->schema([
                    Grid::make(2)->schema([
                        Select::make('university_id')
                            ->label('University')
                            ->relationship('university', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),

                        Select::make('subject_id')
                            ->label('Subject')
                            ->relationship('subject', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ]),

                    TextInput::make('name')
                        ->label('Official course title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Grid::make(4)->schema([
                        Select::make('degree_level')
                            ->options(DegreeProgram::DEGREE_LEVELS)
                            ->native(false)
                            ->required(),

                        TextInput::make('language')
                            ->required()
                            ->maxLength(100)
                            ->default('Italian'),

                        TextInput::make('duration_years')
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(6)
                            ->required()
                            ->default(3),

                        Select::make('admission_type')
                            ->options(DegreeProgram::ADMISSION_TYPES)
                            ->native(false)
                            ->required()
                            ->default('open'),
                    ]),
                ]),

            Section::make('Admission info')
                ->description('General guidance shown to students — not a guarantee of current deadlines or fees. Always link back to an official source.')
                ->schema([
                    Textarea::make('admission_notes')
                        ->label('Admission process')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('tuition_note')
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('application_window_note')
                        ->label('Application window')
                        ->rows(2)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('official_admission_url')
                            ->label('Official admission page')
                            ->url()
                            ->maxLength(255),

                        TextInput::make('source_url')
                            ->label('Source (verify here)')
                            ->url()
                            ->maxLength(255)
                            ->default('https://www.universitaly.it'),
                    ]),

                    DateTimePicker::make('last_verified_at')
                        ->native(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('university.name')
                    ->formatStateUsing(fn ($state, DegreeProgram $record) => $record->university->display_name)
                    ->searchable(['name', 'canonical_name'])
                    ->sortable(),
                TextColumn::make('name')->label('Program')->searchable(),
                TextColumn::make('subject.name')
                    ->label('Subject')
                    ->formatStateUsing(fn ($state, DegreeProgram $record) => $record->subject->display_name)
                    ->searchable(['name', 'canonical_name'])
                    ->sortable(),
                TextColumn::make('degree_level')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => DegreeProgram::DEGREE_LEVELS[$state] ?? $state),
                TextColumn::make('admission_type')
                    ->badge()
                    ->color(fn (string $state) => $state === 'restricted' ? 'warning' : 'success')
                    ->formatStateUsing(fn (string $state) => DegreeProgram::ADMISSION_TYPES[$state] ?? $state),
                TextColumn::make('last_verified_at')->dateTime('d M Y')->placeholder('Never')->toggleable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('subject_id')->label('Subject')->relationship('subject', 'name')->preload(),
                SelectFilter::make('university_id')->label('University')->relationship('university', 'name')->preload(),
                SelectFilter::make('degree_level')->options(DegreeProgram::DEGREE_LEVELS),
                SelectFilter::make('admission_type')->options(DegreeProgram::ADMISSION_TYPES),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDegreePrograms::route('/'),
            'create' => Pages\CreateDegreeProgram::route('/create'),
            'edit' => Pages\EditDegreeProgram::route('/{record}/edit'),
        ];
    }
}
