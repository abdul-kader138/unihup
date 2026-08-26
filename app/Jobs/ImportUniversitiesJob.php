<?php

namespace App\Jobs;

use App\Services\Universities\ImporterRegistry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Background counterpart to `php artisan universities:import`, for the
 * Filament "Data Sync" page — an admin clicking "Sync now" in a web request
 * can't block on a live HTTP fetch + ~5,000-row upsert (MurUstatImporter
 * routinely takes 60-90s), so this runs on the queue instead. Requires an
 * active worker — see deploy/unihup-queue-worker.service, which is already
 * how this app runs everything else queued in production.
 */
class ImportUniversitiesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;

    public int $tries = 2;

    public function __construct(
        public readonly string $source,
        public readonly ?int $year = null,
    ) {}

    public function handle(): void
    {
        $importer = ImporterRegistry::resolve($this->source, $this->year);

        if (! $importer) {
            activity('data-sync')
                ->withProperties(['source' => $this->source, 'status' => 'failed'])
                ->log("Unknown import source \"{$this->source}\".");

            return;
        }

        try {
            $result = $importer->import();
        } catch (\Throwable $e) {
            activity('data-sync')
                ->withProperties(['source' => $this->source, 'status' => 'failed', 'error' => $e->getMessage()])
                ->log("Import from \"{$this->source}\" failed: {$e->getMessage()}");

            throw $e;
        }

        activity('data-sync')
            ->withProperties([
                'source' => $this->source,
                'status' => 'success',
                'universities' => $result->universities,
                'subjects' => $result->subjects,
                'degreePrograms' => $result->degreePrograms,
            ])
            ->log($result->summary);
    }
}
