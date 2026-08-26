<?php

namespace App\Filament\Pages;

use App\Jobs\EnrichUniversitiesJob;
use App\Jobs\ImportUniversitiesJob;
use App\Jobs\SeedRegionalScholarshipsJob;
use App\Jobs\SeedUniversityRankingsJob;
use App\Models\DegreeProgram;
use App\Models\RegionalScholarship;
use App\Models\Subject;
use App\Models\University;
use App\Models\UniversityRanking;
use App\Services\Universities\Enrichers\EnricherRegistry;
use App\Services\Universities\ImporterRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Bus;
use Spatie\Activitylog\Models\Activity;

/**
 * Lets an admin trigger every data feeder (App\Services\Universities\*
 * importers/enrichers, plus the CENSIS/regional-scholarship seeders) from
 * the UI instead of SSH-ing in to run artisan commands. Every action here
 * runs on the queue (see App\Jobs\ImportUniversitiesJob's doc comment for
 * why) — requires an active worker, which is how this app already runs
 * everything else queued in production (deploy/unihup-queue-worker.service).
 * Locally, run `php artisan queue:work` in another terminal to process
 * what this page dispatches.
 *
 * The header actions are ordered to match the actual pipeline sequence —
 * import, then enrich, then the two reference-data seeders — since
 * enrichment reads website_url set by import (for logos) and the ranking
 * seeder matches against University rows that must already exist. "Run
 * full pipeline" runs all four as a Bus::chain(), which guarantees that
 * order even if more than one queue worker is ever running, rather than
 * just hoping FIFO dispatch order holds.
 */
class DataSync extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Data Sync';

    protected static ?int $navigationSort = 98;

    protected static string $view = 'filament.pages.data-sync';

    public static function getNavigationGroup(): ?string
    {
        return 'Administration';
    }

    // Deliberately super_admin only, unlike SystemSettings' page_SystemSettings
    // permission fallback — triggering a live import/enrichment run (external
    // HTTP fetches, ~5,000-row upserts) is not something to delegate to a
    // scoped role, so there's no permission-based path here at all.
    public static function canAccess(): bool
    {
        $superAdminName = (string) config('filament-shield.super_admin.name', 'super_admin');

        return (bool) auth()->user()?->hasRole($superAdminName);
    }

    public function getTitle(): string
    {
        return 'Data Sync';
    }

    /** @return array<string, int> */
    public function getCounts(): array
    {
        return [
            'universities' => University::count(),
            'universities_with_website' => University::whereNotNull('website_url')->count(),
            'universities_with_logo' => University::whereNotNull('logo')->count(),
            'subjects' => Subject::count(),
            'degreePrograms' => DegreeProgram::count(),
            'degreePrograms_english' => DegreeProgram::where('language', 'English')->count(),
            'regionalScholarships' => RegionalScholarship::count(),
            'universityRankings' => UniversityRanking::count(),
        ];
    }

    /** @return Collection<int, Activity> */
    public function getRecentRuns()
    {
        return Activity::where('log_name', 'data-sync')->latest()->limit(15)->get();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('runFullPipeline')
                ->label('Run full pipeline')
                ->icon('heroicon-o-play')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Run the full data pipeline?')
                ->modalDescription('Runs, in order: 1) sync from MUR/USTAT, 2) all enrichers, 3) regional scholarships, 4) CENSIS rankings. Each step waits for the one before it to finish.')
                ->modalSubmitActionLabel('Run all 4 steps')
                ->action(function () {
                    Bus::chain([
                        new ImportUniversitiesJob('mur'),
                        new EnrichUniversitiesJob(array_keys(EnricherRegistry::ENRICHERS)),
                        new SeedRegionalScholarshipsJob,
                        new SeedUniversityRankingsJob,
                    ])->dispatch();

                    Notification::make()
                        ->success()
                        ->title('Full pipeline queued')
                        ->body('4 steps queued to run in sequence — refresh this page in a few minutes to watch the run log below fill in.')
                        ->send();
                }),

            Action::make('import')
                ->label('1. Sync universities/programs')
                ->icon('heroicon-o-cloud-arrow-down')
                ->color('primary')
                ->form([
                    Select::make('source')
                        ->label('Source')
                        ->options(ImporterRegistry::SOURCES)
                        ->default('mur')
                        ->required(),
                ])
                ->action(function (array $data) {
                    ImportUniversitiesJob::dispatch($data['source']);

                    Notification::make()
                        ->success()
                        ->title('Import queued')
                        ->body('Runs in the background — refresh this page in a minute to see updated counts and the run log below. Requires the queue worker to be running.')
                        ->send();
                }),

            Action::make('enrich')
                ->label('2. Run enrichment')
                ->icon('heroicon-o-sparkles')
                ->color('gray')
                ->form([
                    CheckboxList::make('only')
                        ->label('Enrichers to run')
                        ->options([
                            'content' => 'Admission/tuition/deadline text',
                            'website' => 'Official university websites (verified live)',
                            'language' => 'English-taught program heuristic',
                            'logo' => "University logos (fetched from each site's own icon)",
                        ])
                        ->default(array_keys(EnricherRegistry::ENRICHERS))
                        ->required(),
                ])
                ->action(function (array $data) {
                    EnrichUniversitiesJob::dispatch($data['only']);

                    Notification::make()
                        ->success()
                        ->title('Enrichment queued')
                        ->body('Runs in the background — refresh this page in a minute to see the run log below. Requires the queue worker to be running.')
                        ->send();
                }),

            Action::make('seedScholarships')
                ->label('3. Seed regional scholarships')
                ->icon('heroicon-o-banknotes')
                ->color('gray')
                ->action(function () {
                    SeedRegionalScholarshipsJob::dispatch();

                    Notification::make()
                        ->success()
                        ->title('Regional scholarship seed queued')
                        ->body('Re-applies the curated DSU body reference data — see database/seeders/RegionalScholarshipSeeder.')
                        ->send();
                }),

            Action::make('seedRankings')
                ->label('4. Seed CENSIS rankings')
                ->icon('heroicon-o-trophy')
                ->color('gray')
                ->action(function () {
                    SeedUniversityRankingsJob::dispatch();

                    Notification::make()
                        ->success()
                        ->title('CENSIS ranking seed queued')
                        ->body('Re-applies the transcribed CENSIS ranking data — run step 1 first if any university it references is missing.')
                        ->send();
                }),
        ];
    }
}
