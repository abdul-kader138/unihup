<?php

namespace App\Services\Universities\Enrichers;

use App\Contracts\DataEnricher;

/**
 * Single place mapping an --only key to its enricher, shared by
 * App\Console\Commands\EnrichUniversities and App\Jobs\EnrichUniversitiesJob
 * so the CLI and the Filament "sync" UI can't drift out of sync with each
 * other on which enrichers exist.
 */
class EnricherRegistry
{
    /** @var array<string, class-string<DataEnricher>> */
    public const ENRICHERS = [
        'content' => AdmissionContentEnricher::class,
        'website' => UniversityWebsiteEnricher::class,
        'language' => LanguageHeuristicEnricher::class,
        'logo' => UniversityLogoEnricher::class,
    ];

    /** @return array<string, DataEnricher> */
    public static function resolve(array $keys): array
    {
        return collect($keys)
            ->filter(fn (string $key) => isset(self::ENRICHERS[$key]))
            ->mapWithKeys(fn (string $key) => [$key => app(self::ENRICHERS[$key])])
            ->all();
    }
}
