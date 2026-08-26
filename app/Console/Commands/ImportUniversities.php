<?php

namespace App\Console\Commands;

use App\Services\Universities\ImporterRegistry;
use Illuminate\Console\Command;

class ImportUniversities extends Command
{
    protected $signature = 'universities:import
        {--source=seed : "seed" (bundled curated dataset), "mur" (live MUR/USTAT open data), or "universitaly" (not yet implemented)}
        {--year= : Academic year to import for --source=mur (defaults to the most recent year in the dataset)}';

    protected $description = 'Import or refresh university/subject/degree-program data';

    public function handle(): int
    {
        $source = (string) $this->option('source');
        $year = $this->option('year') !== null ? (int) $this->option('year') : null;
        $importer = ImporterRegistry::resolve($source, $year);

        if (! $importer) {
            $this->error('Unknown --source. Use one of: '.implode(', ', array_keys(ImporterRegistry::SOURCES)));

            return self::INVALID;
        }

        try {
            $result = $importer->import();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info($result->summary);
        $this->line("Universities: {$result->universities} · Subjects: {$result->subjects} · Degree programs: {$result->degreePrograms}");

        return self::SUCCESS;
    }
}
