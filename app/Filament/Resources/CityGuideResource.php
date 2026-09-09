<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CityGuideResource\Pages;
use App\Models\CityGuide;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CityGuideResource extends Resource
{
    protected static ?string $model = CityGuide::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'City Guides';

    protected static ?int $navigationSort = 50;

    public static function getNavigationGroup(): ?string
    {
        return 'Universities';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()
                ->schema([
                    Grid::make(3)->schema([
                        TextInput::make('city')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Use the same spelling as University.city — the slug is derived from it.'),
                        TextInput::make('region')->maxLength(255),
                        Toggle::make('is_published')->default(true),
                    ]),
                    Textarea::make('intro')->rows(2)->columnSpanFull(),
                ]),

            Section::make('Sections')
                ->description('Leave a field blank to hide that section for this city.')
                ->schema([
                    Textarea::make('housing')->rows(3),
                    Textarea::make('cost_of_living')->rows(3),
                    Textarea::make('transport')->rows(3),
                    Textarea::make('student_life')->rows(3),
                    Textarea::make('safety')->rows(3),
                ]),

            Section::make('Links & provenance')
                ->schema([
                    Repeater::make('useful_links')
                        ->schema([
                            Grid::make(2)->schema([
                                TextInput::make('label')->required(),
                                TextInput::make('url')->url()->required(),
                            ]),
                        ])
                        ->itemLabel(fn (array $state) => $state['label'] ?? null)
                        ->addActionLabel('Add link')
                        ->reorderable(false)
                        ->default([]),
                    DateTimePicker::make('last_verified_at')->native(false)->seconds(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('city')->searchable()->sortable(),
                TextColumn::make('region')->toggleable(),
                IconColumn::make('is_published')->boolean(),
                TextColumn::make('last_verified_at')->dateTime('d M Y')->placeholder('—'),
            ])
            ->defaultSort('city')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCityGuides::route('/'),
            'create' => Pages\CreateCityGuide::route('/create'),
            'edit' => Pages\EditCityGuide::route('/{record}/edit'),
        ];
    }
}
