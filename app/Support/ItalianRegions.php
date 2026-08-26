<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Normalizes an Italian region name to a stable comparison key, so
 * RegionalScholarship rows (seeded with Italian names) can be matched
 * against University.region — which holds a mix of Italian and English
 * names depending on data source (MurUstatImporter uses Italian; the
 * hand-curated UniversitySeeder uses English, e.g. "Lombardy"/"Piedmont"),
 * and further splits Trentino-Alto Adige into its two provinces
 * ("Provincia Autonoma Di Bolzano"/"...Di Trento"). Confirmed the actual
 * spread of values via `University::distinct()->pluck('region')` rather
 * than assuming a single naming convention.
 */
final class ItalianRegions
{
    private const ENGLISH_SYNONYMS = [
        'piedmont' => 'piemonte',
        'lombardy' => 'lombardia',
        'tuscany' => 'toscana',
        'apulia' => 'puglia',
        'sicily' => 'sicilia',
        'sardinia' => 'sardegna',
        'aosta valley' => 'valle daosta',
    ];

    public static function canonicalize(?string $region): ?string
    {
        if (blank($region)) {
            return null;
        }

        $key = Str::of($region)->lower()->replace("'", '')->squish()->toString();

        // Trentino-Alto Adige is split into two autonomous provinces across
        // data sources — treat any of those spellings as the same region.
        if (Str::contains($key, ['bolzano', 'trento', 'alto adige', 'trentino', 'south tyrol'])) {
            return 'trentino-alto-adige';
        }

        $key = self::ENGLISH_SYNONYMS[$key] ?? $key;

        return Str::slug($key);
    }
}
