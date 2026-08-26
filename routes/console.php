<?php

use App\Jobs\EnrichUniversitiesJob;
use App\Jobs\ImportUniversitiesJob;
use App\Services\Universities\Enrichers\EnricherRegistry;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Keep the catalog fresh from MUR/USTAT without anyone clicking the Data Sync
// page — 8 times a day (every 3 hours) lands in the "5-10x/day" the data
// actually changes (MUR republishes its open-data CSVs infrequently, so this
// is about catching those updates promptly, not high-frequency polling).
// Both dispatch to the queue rather than running inline here — see
// App\Jobs\ImportUniversitiesJob's doc comment for why — so this closure
// returns in milliseconds; the real work happens on the queue worker
// (deploy/unihup-queue-worker.service in production, `queue:work` locally).
Schedule::job(new ImportUniversitiesJob('mur'))
    ->cron('0 */3 * * *')
    ->name('universities-import-mur')
    ->withoutOverlapping();

// Offset 15 minutes after import, not simultaneous — both land on the same
// queue and a single worker processes it FIFO, but the buffer keeps this
// resilient even if that assumption ever changes (e.g. a second worker).
Schedule::job(new EnrichUniversitiesJob(array_keys(EnricherRegistry::ENRICHERS)))
    ->cron('15 */3 * * *')
    ->name('universities-enrich')
    ->withoutOverlapping();
