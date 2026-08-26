<?php

namespace App\Support;

/**
 * Curated monthly rent reference points by city, so applicants can budget
 * beyond tuition — tuition/scholarship info already covers the fee side,
 * this covers what a room/flat typically costs. Sourced from Idealista's
 * own Italian residential rental market report (idealista.it/news), which
 * tracks average asking rent per city monthly — figures below are from the
 * June 2026 edition (published mid-August 2026); re-verify before assuming
 * still current, since rent moves quarter to quarter and this is a curated
 * snapshot, not a live feed. Deliberately does not cover every city in the
 * database — only cities that edition actually reported a rent figure for;
 * forCity() returns null for anything else so the UI can fall back to a
 * general pointer instead of guessing.
 *
 * This is about rent, not full cost of living (food, transport, utilities
 * vary too) — the note text says so explicitly rather than implying the
 * rent figure is a complete budget.
 */
final class CostOfLivingCopy
{
    public const SOURCE_URL = 'https://www.idealista.it/news/immobiliare/residenziale/2026/08/19/431804-affitti-residenziali-giugno-2026-il-sud-batte-il-nord-sui-rendimenti';

    public const AS_OF = 'June 2026';

    public const NATIONAL_AVERAGE_RENT = 880;

    /**
     * City name (as it might appear in our data, English or Italian) =>
     * canonical key. Covers the English/Italian spelling pairs that show up
     * in App\Models\University::city.
     */
    private const CITY_ALIASES = [
        'milan' => 'milano',
        'milano' => 'milano',
        'rome' => 'roma',
        'roma' => 'roma',
        'naples' => 'napoli',
        'napoli' => 'napoli',
        'turin' => 'torino',
        'torino' => 'torino',
        'florence' => 'firenze',
        'firenze' => 'firenze',
        'genoa' => 'genova',
        'genova' => 'genova',
        'venice' => 'venezia',
        'venezia' => 'venezia',
        'padua' => 'padova',
        'padova' => 'padova',
        'trieste' => 'trieste',
        'catania' => 'catania',
        'messina' => 'messina',
    ];

    /**
     * Canonical city key => average monthly rent in euros, per the June
     * 2026 Idealista report. Padova appears in CITY_ALIASES (it's a
     * university city in our data) but that edition only reported its
     * rental yield, not a rent figure — deliberately omitted here so
     * forCity() falls back to the general note rather than fabricating one.
     */
    private const RENT_BY_CITY = [
        'milano' => 1338,
        'firenze' => 1283,
        'roma' => 1120,
        'venezia' => 980,
        'napoli' => 899,
        'torino' => 771,
        'trieste' => 737,
        'genova' => 643,
        'catania' => 580,
        'messina' => 513,
    ];

    public const GENERAL_NOTE = "Rent varies a lot by city — Milan and Florence run well above the national average, while several southern and smaller cities run well below it. Where we have a sourced figure for a city, it's shown above; for others, check a live index like Numbeo or Idealista for that specific city before budgeting.";

    public const OFFICIAL_LINKS = [
        'Idealista — Italian rental market report' => 'https://www.idealista.it/news/immobiliare/residenziale/2026/08/19/431804-affitti-residenziali-giugno-2026-il-sud-batte-il-nord-sui-rendimenti',
        'Numbeo — cost of living by city' => 'https://www.numbeo.com/cost-of-living/country_result.jsp?country=Italy',
    ];

    /**
     * @return array{rent: int, tier: string}|null
     */
    public static function forCity(?string $city): ?array
    {
        if (! $city) {
            return null;
        }

        $key = self::CITY_ALIASES[mb_strtolower(trim($city))] ?? null;

        if (! $key || ! isset(self::RENT_BY_CITY[$key])) {
            return null;
        }

        $rent = self::RENT_BY_CITY[$key];

        return [
            'rent' => $rent,
            'tier' => self::tierFor($rent),
        ];
    }

    private static function tierFor(int $rent): string
    {
        return match (true) {
            $rent >= self::NATIONAL_AVERAGE_RENT * 1.3 => 'Well above national average',
            $rent >= self::NATIONAL_AVERAGE_RENT * 1.05 => 'Above national average',
            $rent >= self::NATIONAL_AVERAGE_RENT * 0.85 => 'Near national average',
            default => 'Below national average',
        };
    }
}
