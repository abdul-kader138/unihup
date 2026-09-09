<?php

namespace App\Filament\Pages;

use App\Models\StudentDocument;
use App\Support\DocumentChecklist;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\CreateAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * A student's private document vault: track which documents are needed /
 * ready / submitted, and optionally store a copy of the file. Files go on
 * the private 'local' disk and are only retrievable via the owner-checked
 * route 'student-documents.download'. Open to every panel user (no
 * HasPageShield); every query is scoped to the signed-in user.
 */
class MyDocuments extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'My Documents';

    protected static ?string $title = 'My Documents';

    protected static ?string $slug = 'my-documents';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.my-documents';

    /**
     * Progress meter data for the view: how many tracked documents are done.
     *
     * @return array{done: int, total: int, percent: int}
     */
    public function getProgress(): array
    {
        $documents = auth()->user()->studentDocuments()->get();
        $total = $documents->count();
        $done = $documents->filter->isDone()->count();

        return [
            'done' => $done,
            'total' => $total,
            'percent' => $total > 0 ? (int) round($done / $total * 100) : 0,
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(StudentDocument::query()->where('user_id', auth()->id()))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label('Document')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => DocumentChecklist::label($state))
                    ->description(fn (StudentDocument $record) => $record->label),
                SelectColumn::make('status')
                    ->options(StudentDocument::STATUSES)
                    ->selectablePlaceholder(false)
                    ->rules(['required']),
                IconColumn::make('has_file')
                    ->label('File')
                    ->boolean(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->color(fn (?StudentDocument $record) => $record?->expires_at
                        && $record->expires_at->isBefore(now()->addDays(60)) ? 'danger' : null),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('type')->options(DocumentChecklist::options()),
                SelectFilter::make('status')->options(StudentDocument::STATUSES),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Add document')
                    ->icon('heroicon-o-plus')
                    ->model(StudentDocument::class)
                    ->form($this->formSchema())
                    ->after(fn (StudentDocument $record) => $record->syncFileMeta()),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->formSchema())
                    ->after(fn (StudentDocument $record) => $record->syncFileMeta()),
                Action::make('download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->url(fn (StudentDocument $record) => route('student-documents.download', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (StudentDocument $record) => $record->has_file),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('No documents yet')
            ->emptyStateDescription('Add the documents you need for your application and keep track of them here.')
            ->emptyStateIcon('heroicon-o-folder-open');
    }

    /**
     * @return array<Component>
     */
    protected function formSchema(): array
    {
        return [
            Select::make('type')
                ->label('Document type')
                ->options(DocumentChecklist::groupedOptions())
                ->required()
                ->native(false)
                ->live()
                ->helperText(fn ($state) => $state ? (DocumentChecklist::TYPES[$state]['description'] ?? null) : null),
            TextInput::make('label')
                ->label('Custom label')
                ->maxLength(255)
                ->helperText('Optional — useful for "Other", or when you have more than one of the same type.'),
            Select::make('status')
                ->options(StudentDocument::STATUSES)
                ->default('needed')
                ->required()
                ->native(false),
            FileUpload::make('file_path')
                ->label('File (optional)')
                ->disk(StudentDocument::DISK)
                ->directory(fn () => 'student-documents/'.auth()->id())
                ->visibility('private')
                ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
                ->maxSize(10240)
                ->downloadable()
                ->openable()
                ->storeFileNamesIn('original_filename'),
            DatePicker::make('expires_at')
                ->label('Expiry date (if any)')
                ->native(false),
            Textarea::make('notes')
                ->rows(3),
        ];
    }
}
