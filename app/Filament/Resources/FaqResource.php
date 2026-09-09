<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FaqResource\Pages;
use App\Models\FaqEntry;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class FaqResource extends Resource
{
    protected static ?string $model = FaqEntry::class;

    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';

    protected static ?string $navigationLabel = 'Help Center (FAQ)';

    protected static ?int $navigationSort = 55;

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make()->schema([
                TextInput::make('question')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Textarea::make('answer')
                    ->required()
                    ->rows(8)
                    ->helperText('Markdown supported.')
                    ->columnSpanFull(),
                Grid::make(3)->schema([
                    Select::make('category')
                        ->options(array_combine(FaqEntry::CATEGORIES, FaqEntry::CATEGORIES))
                        ->default('General')
                        ->required()
                        ->native(false),
                    TextInput::make('sort')->numeric()->default(0),
                    Toggle::make('is_published')->default(true),
                ]),
                TagsInput::make('tags')->placeholder('Add a tag'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('question')->searchable()->wrap()->limit(80),
                TextColumn::make('category')->badge()->color('gray'),
                TextColumn::make('sort')->sortable()->toggleable(),
                IconColumn::make('is_published')->boolean(),
            ])
            ->defaultSort('sort')
            ->reorderable('sort')
            ->filters([
                SelectFilter::make('category')->options(array_combine(FaqEntry::CATEGORIES, FaqEntry::CATEGORIES)),
            ])
            ->actions([EditAction::make(), DeleteAction::make()])
            ->bulkActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFaqs::route('/'),
            'create' => Pages\CreateFaq::route('/create'),
            'edit' => Pages\EditFaq::route('/{record}/edit'),
        ];
    }
}
