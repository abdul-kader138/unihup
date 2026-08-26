<?php

namespace App\Jobs;

use App\Services\Universities\Enrichers\EnricherRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Background counterpart to `php artisan universities:enrich` — see
 * ImportUniversitiesJob's doc comment for why this runs on the queue rather
 * than inline in the web request (UniversityWebsiteEnricher alone makes a
 * live HTTP request per university).
 */
class EnrichUniversitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    /** @param  list<string>  $only */
    public function __construct(public readonly array $only) {}

    public function handle(): void
    {
        foreach (EnricherRegistry::resolve($this->only) as $key => $enricher) {
            try {
                $result = $enricher->enrich();
            } catch (\Throwable $e) {
                activity('data-sync')
                    ->withProperties(['enricher' => $key, 'status' => 'failed', 'error' => $e->getMessage()])
                    ->log("Enrichment \"{$key}\" failed: {$e->getMessage()}");

                continue;
            }

            activity('data-sync')
                ->withProperties([
                    'enricher' => $key,
                    'status' => 'success',
                    'updated' => $result->updated,
                    'skipped' => $result->skipped,
                ])
                ->log("[{$key}] {$result->summary}");
        }
    }
}
