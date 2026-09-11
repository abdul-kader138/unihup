<?php

namespace App\Console\Commands;

use App\Models\University;
use Illuminate\Console\Command;

/**
 * Drift-correction safety net for universities.shortlist_items_count —
 * normally kept live by the increment/decrement in
 * UniversityProfile::toggleShortlist(), this resync catches any drift (e.g.
 * bulk data changes, degree programs deleted with shortlist items attached).
 * Scheduled daily in routes/console.php.
 */
class SyncShortlistCounts extends Command
{
    protected $signature = 'unihup:sync-shortlist-counts';

    protected $description = 'Resync the denormalised shortlist_items_count column on every university';

    public function handle(): int
    {
        University::syncAllShortlistCounts();

        $this->info('Shortlist counts resynced.');

        return self::SUCCESS;
    }
}
