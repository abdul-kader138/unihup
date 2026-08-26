<?php

namespace App\Jobs;

use App\Models\UniversityRanking;
use Database\Seeders\UniversityRankingSeeder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Background counterpart to `php artisan db:seed --class=UniversityRankingSeeder`
 * — exposed in the Data Sync UI so an admin can re-apply the CENSIS
 * ranking data (e.g. after transcribing next year's edition into that
 * seeder's constants — see its doc comment for why this can't be
 * automated) without shell access. Should run after universities:import,
 * since rows are matched to existing University records by slug.
 */
class SeedUniversityRankingsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public int $tries = 2;

    public function handle(): void
    {
        (new UniversityRankingSeeder)->run();

        activity('data-sync')
            ->withProperties(['status' => 'success', 'count' => UniversityRanking::count()])
            ->log('Refreshed CENSIS university ranking data.');
    }
}
