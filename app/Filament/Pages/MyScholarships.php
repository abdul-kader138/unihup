<?php

namespace App\Filament\Pages;

use App\Models\RegionalScholarship;
use App\Models\ScholarshipTracker;
use App\Models\University;
use App\Support\ItalianRegions;
use App\Support\NationalScholarships;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

/**
 * Turns the passive scholarship info into something actionable: match
 * regional (DSU) bodies to the student's shortlist regions plus the
 * nationwide options, and let them track each one's application status and
 * personal deadline. Open to every panel user; no HasPageShield.
 */
class MyScholarships extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?string $navigationLabel = 'Scholarships';

    protected static ?string $title = 'Scholarships';

    protected static ?string $slug = 'my-scholarships';

    protected static ?string $navigationGroup = 'My Journey';

    protected static ?int $navigationSort = 28;

    protected static string $view = 'filament.pages.my-scholarships';

    /**
     * Regional bodies whose region matches a university on the student's
     * shortlist, plus the nationwide options — each flagged as already
     * tracked or not.
     *
     * @return array{regional: Collection<int, array<string, mixed>>, national: Collection<int, array<string, mixed>>}
     */
    public function getMatched(): array
    {
        $user = auth()->user();

        $programs = $user->shortlistItems()
            ->with('degreeProgram:id,university_id,degree_level')
            ->get()
            ->pluck('degreeProgram')
            ->filter();

        $hasMaster = $programs->contains('degree_level', 'master');

        $regionKeys = University::whereIn('id', $programs->pluck('university_id')->filter()->unique())
            ->pluck('region')
            ->map(fn ($r) => ItalianRegions::canonicalize($r))
            ->filter()
            ->unique();

        $trackedRefs = ScholarshipTracker::query()
            ->where('user_id', $user->id)
            ->get()
            ->keyBy(fn ($t) => $t->kind.':'.$t->ref);

        $regional = $regionKeys->isEmpty()
            ? collect()
            : RegionalScholarship::all()
                ->filter(fn ($s) => $regionKeys->contains(ItalianRegions::canonicalize($s->region)))
                ->map(fn ($s) => [
                    'kind' => ScholarshipTracker::KIND_REGIONAL,
                    'ref' => (string) $s->id,
                    'label' => $s->body_name,
                    'region' => $s->region,
                    'summary' => $s->description,
                    'url' => $s->website_url,
                    'amount' => $this->amountText($s),
                    'tracked' => $trackedRefs->has(ScholarshipTracker::KIND_REGIONAL.':'.$s->id),
                ])
                ->values();

        $national = collect(NationalScholarships::forStudent($hasMaster))
            ->map(fn ($s) => [
                'kind' => ScholarshipTracker::KIND_NATIONAL,
                'ref' => $s['key'],
                'label' => $s['name'],
                'region' => null,
                'summary' => $s['summary'],
                'url' => $s['url'],
                'amount' => null,
                'tracked' => $trackedRefs->has(ScholarshipTracker::KIND_NATIONAL.':'.$s['key']),
            ]);

        return ['regional' => $regional, 'national' => $national];
    }

    public function hasShortlist(): bool
    {
        return auth()->user()->shortlistItems()->exists();
    }

    public function track(string $kind, string $ref, string $label): void
    {
        if (! in_array($kind, [ScholarshipTracker::KIND_REGIONAL, ScholarshipTracker::KIND_NATIONAL], true)) {
            return;
        }

        ScholarshipTracker::firstOrCreate(
            ['user_id' => auth()->id(), 'kind' => $kind, 'ref' => $ref],
            ['label' => $label, 'status' => 'interested'],
        );

        Notification::make()->success()->title('Added to your scholarship tracker')->send();
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ScholarshipTracker::query()->where('user_id', auth()->id()))
            ->defaultSort('deadline_at')
            ->columns([
                TextColumn::make('label')->label('Scholarship')->wrap()->weight('medium'),
                TextColumn::make('kind')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (string $state) => ucfirst($state)),
                SelectColumn::make('status')
                    ->options(ScholarshipTracker::STATUSES)
                    ->selectablePlaceholder(false)
                    ->rules(['required']),
                TextColumn::make('deadline_at')
                    ->label('Deadline')
                    ->date('d M Y')
                    ->placeholder('—')
                    ->color(fn (?ScholarshipTracker $record) => $record?->deadline_at
                        && $record->deadline_at->isBefore(now()->addDays(30)) ? 'danger' : null),
                TextColumn::make('notes')->limit(60)->placeholder('—')->color('gray'),
            ])
            ->actions([
                EditAction::make()
                    ->form([
                        Select::make('status')->options(ScholarshipTracker::STATUSES)->required()->native(false),
                        DatePicker::make('deadline_at')->label('Application deadline')->native(false),
                        Textarea::make('notes')->rows(3),
                    ]),
                Action::make('open')
                    ->label('Official site')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn (ScholarshipTracker $record) => $this->urlFor($record))
                    ->openUrlInNewTab()
                    ->visible(fn (ScholarshipTracker $record) => filled($this->urlFor($record))),
                DeleteAction::make()->label('Remove'),
            ])
            ->emptyStateHeading('Nothing tracked yet')
            ->emptyStateDescription('Add a scholarship from the matched list above to track its deadline and status.')
            ->emptyStateIcon('heroicon-o-gift');
    }

    private function urlFor(ScholarshipTracker $record): ?string
    {
        if ($record->kind === ScholarshipTracker::KIND_NATIONAL) {
            return NationalScholarships::LIST[$record->ref]['url'] ?? null;
        }

        return RegionalScholarship::find($record->ref)?->website_url;
    }

    private function amountText(RegionalScholarship $s): ?string
    {
        if ($s->amount_min === null && $s->amount_max === null) {
            return null;
        }

        $min = number_format((float) ($s->amount_min ?? $s->amount_max));
        $max = number_format((float) ($s->amount_max ?? $s->amount_min));

        return "€{$min}–{$max}/yr";
    }
}
