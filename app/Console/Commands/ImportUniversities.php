<?php

namespace App\Console\Commands;

use App\Contracts\UniversityDataImporter;
use App\Services\Universities\SeedDataImporter;
use App\Services\Universities\UniversitalyImporter;
use Illuminate\Console\Command;

class ImportUniversities extends Command
{
    protected $signature = 'universities:import {--source=seed : "seed" (bundled curated dataset) or "universitaly" (not yet implemented)}';

    protected $description = 'Import or refresh university/subject/degree-program data';

    public function handle(): int
    {
        $importer = $this->resolveImporter((string) $this->option('source'));

        if (! $importer) {
            $this->error('Unknown --source. Use "seed" or "universitaly".');

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

    private function resolveImporter(string $source): ?UniversityDataImporter
    {
        return match ($source) {
            'seed' => new SeedDataImporter,
            'universitaly' => new UniversitalyImporter,
            default => null,
        };
    }
}
