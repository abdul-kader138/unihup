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

// Daily 14/3/1-day deadline reminders for each student's shortlist. Runs
// once a day at 07:00; the command is idempotent (deadline_reminder_log) so
// a double-run never double-sends. Mail + optional WhatsApp go through the
// queue worker, so this returns fast.
Schedule::command('unihup:send-deadline-reminders')
    ->dailyAt('07:00')
    ->name('deadline-reminders')
    ->withoutOverlapping();

// Monday-morning "your week" digest — deadlines, next checklist steps,
// stalled applications. Once per ISO week (guarded on the command).
Schedule::command('unihup:send-weekly-digest')
    ->weeklyOn(1, '08:00')
    ->name('weekly-digest')
    ->withoutOverlapping();

// Daily in-app bell digest of changes to each student's shortlisted
// programs + newly relevant deadlines. Diffs from a global cache marker,
// so a missed day is caught up on the next run.
Schedule::command('unihup:notify-shortlist-changes')
    ->dailyAt('07:30')
    ->name('shortlist-change-notifications')
    ->withoutOverlapping();

// Weekly reachability sweep of every external catalog link — results feed
// the admin data-freshness widget.
Schedule::command('unihup:check-links')
    ->weeklyOn(1, '04:30')
    ->name('link-check')
    ->withoutOverlapping(120);

// Keep the activity_log table bounded — Spatie deletes rows older than
// config('activitylog.clean_after_days'). LogsActivity is on User + Setting,
// so this table grows on every profile/setting write.
Schedule::command('activitylog:clean')
    ->weeklyOn(1, '03:30')
    ->name('activitylog-clean')
    ->withoutOverlapping();
