<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UniversityResource\Pages;
use App\Models\University;
use Filament\Forms\Components\FileUpload;
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
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class UniversityResource extends Resource
{
    protected static ?string $model = University::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 20;

    public static function getNavigationGroup(): ?string
    {
        return 'Universities';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('University')
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get, string $operation) => $operation === 'create'
                                ? $set('slug', Str::slug($state))
                                : null),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(University::class, 'slug', ignoreRecord: true),
                    ]),

                    Grid::make(2)->schema([
                        TextInput::make('city')->required()->maxLength(255),
                        TextInput::make('region')->maxLength(255),
                    ]),

                    TextInput::make('website_url')
                        ->label('Website')
                        ->url()
                        ->maxLength(255),

                    Textarea::make('description')
                        ->rows(3)
                        ->columnSpanFull(),

                    FileUpload::make('logo')
                        ->image()
                        ->disk('public')
                        ->directory('universities')
                        ->visibility('public'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('logo')
                    ->disk('public')
                    ->circular()
                    ->defaultImageUrl(fn ($record) => 'https://ui-avatars.com/api/?name='.urlencode($record->name)),

                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('city')->searchable()->sortable(),
                TextColumn::make('region')->searchable()->toggleable(),
                TextColumn::make('degree_programs_count')
                    ->counts('degreePrograms')
                    ->label('Programs'),
            ])
            ->defaultSort('name')
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUniversities::route('/'),
            'create' => Pages\CreateUniversity::route('/create'),
            'edit' => Pages\EditUniversity::route('/{record}/edit'),
        ];
    }
}
