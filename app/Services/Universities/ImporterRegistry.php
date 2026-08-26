<?php

namespace App\Services\Universities;

use App\Contracts\UniversityDataImporter;

/**
 * Single place mapping a --source string to its importer, shared by
 * App\Console\Commands\ImportUniversities and App\Jobs\ImportUniversitiesJob
 * so the CLI and the Filament "sync" UI can't drift out of sync with each
 * other on which sources exist.
 */
class ImporterRegistry
{
    public const SOURCES = [
        'seed' => 'Bundled curated dataset',
        'mur' => 'MUR/USTAT open data (live)',
        'universitaly' => 'Universitaly (not yet implemented)',
    ];

    public static function resolve(string $source, ?int $year = null): ?UniversityDataImporter
    {
        return match ($source) {
            'seed' => new SeedDataImporter,
            'mur' => new MurUstatImporter($year),
            'universitaly' => new UniversitalyImporter,
            default => null,
        };
    }
}
