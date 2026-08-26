<?php

namespace App\Jobs;

use App\Models\RegionalScholarship;
use Database\Seeders\RegionalScholarshipSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Background counterpart to `php artisan db:seed --class=RegionalScholarshipSeeder`
 * — exposed in the Data Sync UI alongside the other feeders so an admin
 * doesn't need shell access to refresh it after hand-editing the seeder
 * (see that class's doc comment for why this is curated content, not an
 * automated import). Fast — runs inline-safe, but queued anyway so it
 * shows up in the same "recent runs" log as everything else.
 */
class SeedRegionalScholarshipsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 60;

    public int $tries = 2;

    public function handle(): void
    {
        (new RegionalScholarshipSeeder)->run();

        activity('data-sync')
            ->withProperties(['status' => 'success', 'count' => RegionalScholarship::count()])
            ->log('Refreshed regional right-to-study (DSU) body reference data.');
    }
}
