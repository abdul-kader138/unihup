<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DeadlineResource\Pages;
use App\Models\Deadline;
use App\Models\DegreeProgram;
use App\Models\RegionalScholarship;
use App\Models\University;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DeadlineResource extends Resource
{
    protected static ?string $model = Deadline::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Deadlines';

    protected static ?int $navigationSort = 45;

    public static function getNavigationGroup(): ?string
    {
        return 'Universities';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('What & when')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        Select::make('category')
                            ->options(Deadline::CATEGORIES)
                            ->required()
                            ->native(false),
                        TextInput::make('cycle_label')
                            ->label('Intake / cycle')
                            ->placeholder('2026/2027')
                            ->maxLength(20),
                    ]),

                    Grid::make(2)->schema([
                        DateTimePicker::make('due_at')
                            ->label('Due date')
                            ->required()
                            ->native(false)
                            ->seconds(false),
                        Select::make('due_precision')
                            ->options(Deadline::PRECISIONS)
                            ->default('day')
                            ->required()
                            ->native(false)
                            ->helperText('Reminders only fire for "Exact day".'),
                    ]),

                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),

            Section::make('Who it applies to')
                ->schema([
                    Select::make('scope_type')
                        ->label('Applies to')
                        ->options(Deadline::SCOPE_TYPES)
                        ->default(Deadline::SCOPE_GLOBAL)
                        ->required()
                        ->native(false)
                        ->live(),

                    Select::make('scope_id')
                        ->label(fn (Get $get) => match ($get('scope_type')) {
                            Deadline::SCOPE_UNIVERSITY => 'University',
                            Deadline::SCOPE_PROGRAM => 'Degree program',
                            Deadline::SCOPE_SCHOLARSHIP => 'Scholarship body',
                            default => 'Target',
                        })
                        ->options(function (Get $get) {
                            return match ($get('scope_type')) {
                                Deadline::SCOPE_UNIVERSITY => University::orderBy('name')->pluck('name', 'id'),
                                Deadline::SCOPE_PROGRAM => DegreeProgram::with('university')->get()
                                    ->mapWithKeys(fn ($p) => [$p->id => "{$p->name} — {$p->university?->name}"]),
                                Deadline::SCOPE_SCHOLARSHIP => RegionalScholarship::orderBy('body_name')->pluck('body_name', 'id'),
                                default => [],
                            };
                        })
                        ->searchable()
                        ->required(fn (Get $get) => in_array($get('scope_type'), [
                            Deadline::SCOPE_UNIVERSITY, Deadline::SCOPE_PROGRAM, Deadline::SCOPE_SCHOLARSHIP,
                        ], true))
                        ->visible(fn (Get $get) => in_array($get('scope_type'), [
                            Deadline::SCOPE_UNIVERSITY, Deadline::SCOPE_PROGRAM, Deadline::SCOPE_SCHOLARSHIP,
                        ], true)),
                ]),

            Section::make('Provenance')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('url')->label('Official page')->url()->maxLength(255),
                        TextInput::make('source_url')->label('Source (verify here)')->url()->maxLength(255),
                    ]),
                    Grid::make(2)->schema([
                        DateTimePicker::make('last_verified_at')->native(false)->seconds(false),
                        Toggle::make('is_active')->default(true)->helperText('Hide from students without deleting.'),
                    ]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('due_at')->label('Due')->date('d M Y')->sortable(),
                TextColumn::make('title')->searchable()->wrap(),
                TextColumn::make('category')
                    ->badge()
                    ->color(fn (string $state) => Deadline::CATEGORY_COLORS[$state] ?? 'gray')
                    ->formatStateUsing(fn (string $state) => Deadline::CATEGORIES[$state] ?? $state),
                TextColumn::make('scope_type')
                    ->label('Applies to')
                    ->formatStateUsing(fn (Deadline $record) => $record->scopeName()),
                TextColumn::make('cycle_label')->label('Cycle')->toggleable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                SelectFilter::make('category')->options(Deadline::CATEGORIES),
                SelectFilter::make('scope_type')->options(Deadline::SCOPE_TYPES),
            ])
            ->defaultSort('due_at')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeadlines::route('/'),
            'create' => Pages\CreateDeadline::route('/create'),
            'edit' => Pages\EditDeadline::route('/{record}/edit'),
        ];
    }
}
