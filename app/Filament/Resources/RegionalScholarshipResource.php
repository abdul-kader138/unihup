<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RegionalScholarshipResource\Pages;
use App\Models\RegionalScholarship;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
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

class RegionalScholarshipResource extends Resource
{
    protected static ?string $model = RegionalScholarship::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Regional Scholarships';

    protected static ?int $navigationSort = 40;

    public static function getNavigationGroup(): ?string
    {
        return 'Universities';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Right-to-study body (diritto allo studio)')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('region')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Matched against University.region — see App\Support\ItalianRegions for how English/Italian spellings are reconciled.'),

                        TextInput::make('body_name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Several regions run more than one body (by province/university) — add one row per body.'),
                    ]),

                    Textarea::make('description')
                        ->rows(4)
                        ->columnSpanFull(),

                    Grid::make(2)->schema([
                        TextInput::make('website_url')->url()->maxLength(255),
                        TextInput::make('source_url')->url()->maxLength(255),
                    ]),

                    DateTimePicker::make('last_verified_at'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('region')->searchable()->sortable(),
                TextColumn::make('body_name')->label('Body')->searchable(),
                TextColumn::make('website_url')->label('Website')->url(fn ($state) => $state, true)->limit(40),
                TextColumn::make('last_verified_at')->dateTime('d M Y')->sortable(),
            ])
            ->filters([
                SelectFilter::make('region')
                    ->options(fn () => RegionalScholarship::query()->distinct()->pluck('region', 'region'))
                    ->searchable(),
            ])
            ->defaultSort('region')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRegionalScholarships::route('/'),
            'create' => Pages\CreateRegionalScholarship::route('/create'),
            'edit' => Pages\EditRegionalScholarship::route('/{record}/edit'),
        ];
    }
}
