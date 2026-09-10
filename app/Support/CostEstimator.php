<?php

namespace App\Support;

use App\Models\DegreeProgram;
use App\Models\RegionalScholarship;
use Illuminate\Support\Collection;

/**
 * A rough first-year cost estimate for a program: tuition + living costs
 * (rent + everything else) minus a likely regional-scholarship offset, as a
 * min–max range. Everything here is indicative — real tuition is set per
 * university by ISEE band, rent moves quarter to quarter, and scholarship
 * amounts change every bando. The UI says so loudly.
 */
final class CostEstimator
{
    /** Non-rent living costs (food, transport, utilities, phone) per month, EUR. */
    public const MONTHLY_LIVING_EX_RENT = 450;

    /** Months per year budgeted (students often 10, we use 12 to be safe). */
    public const MONTHS_PER_YEAR = 12;

    public const HOUSING_OPTIONS = [
        'none' => 'No rent (living at home / already covered)',
        'shared' => 'A room in a shared flat',
        'studio' => 'My own studio / one-bed',
    ];

    /** Share of the city's average whole-flat asking rent each option costs. */
    private const HOUSING_RENT_FACTOR = [
        'none' => 0.0,
        'shared' => 0.5,
        'studio' => 1.0,
    ];

    /**
     * @param  Collection<int, RegionalScholarship>|null  $scholarships  regional bodies whose region matches the program's university; null = look them up
     * @return array{
     *     tuition: array{min: float, max: float, source: string, band_label: ?string},
     *     rent_monthly: float, rent_is_national_fallback: bool, city: ?string,
     *     living_annual: float,
     *     scholarship: array{min: float, max: float, bodies: array<int, string>},
     *     net_min: float, net_max: float,
     *     notes: array<int, string>,
     * }
     */
    public static function estimate(
        DegreeProgram $program,
        float $iseeAmount,
        string $housing,
        ?Collection $scholarships = null,
    ): array {
        $housing = array_key_exists($housing, self::HOUSING_RENT_FACTOR) ? $housing : 'shared';
        $notes = [];

        // ── Tuition ──────────────────────────────────────────────────────────
        if ($program->tuition_min !== null || $program->tuition_max !== null) {
            $tMin = (float) ($program->tuition_min ?? $program->tuition_max);
            $tMax = (float) ($program->tuition_max ?? $program->tuition_min);
            $tuition = ['min' => $tMin, 'max' => $tMax, 'source' => 'program', 'band_label' => null];
        } else {
            $band = FinancialSupportCopy::feeBandFor($iseeAmount);
            $tuition = ['min' => (float) $band['min'], 'max' => (float) $band['max'], 'source' => 'isee_band', 'band_label' => $band['label']];
            $notes[] = 'Tuition is estimated from your ISEE band — this program has no exact figure on file.';
        }

        // ── Rent + living ───────────────────────────────────────────────────
        $city = $program->university?->city;
        $cityRent = CostOfLivingCopy::forCity($city);
        $rentIsFallback = $cityRent === null;
        $baseRent = $cityRent['rent'] ?? CostOfLivingCopy::NATIONAL_AVERAGE_RENT;
        $rentMonthly = (float) round($baseRent * self::HOUSING_RENT_FACTOR[$housing]);

        if ($rentIsFallback && $housing !== 'none') {
            $notes[] = "No sourced rent figure for {$city} — using the national average (€".number_format(CostOfLivingCopy::NATIONAL_AVERAGE_RENT).'/mo for a whole flat).';
        }

        $livingAnnual = ($rentMonthly + self::MONTHLY_LIVING_EX_RENT) * self::MONTHS_PER_YEAR;

        // ── Scholarship offset ─────────────────────────────────────────────
        $scholarships ??= self::scholarshipsForProgram($program);

        $eligible = $scholarships->filter(
            fn (RegionalScholarship $s) => $s->isee_threshold === null || $iseeAmount <= (float) $s->isee_threshold
        );

        $schMax = (float) $eligible->max(fn (RegionalScholarship $s) => (float) ($s->amount_max ?? $s->amount_min ?? 0));
        $schMin = (float) $eligible->max(fn (RegionalScholarship $s) => (float) ($s->amount_min ?? 0));

        if ($eligible->isNotEmpty() && $schMax > 0) {
            $notes[] = 'Best-case scholarship offset assumes you win a regional (DSU) award — these are income-tested and competitive.';
        }

        $scholarship = [
            'min' => $schMin,
            'max' => $schMax,
            'bodies' => $eligible->map(fn (RegionalScholarship $s) => $s->body_name)->values()->all(),
        ];

        // ── Net range ──────────────────────────────────────────────────────
        // Optimistic: lowest tuition + living − best scholarship.
        // Pessimistic: highest tuition + living − no scholarship.
        $netMin = (float) max(0, round($tuition['min'] + $livingAnnual - $schMax));
        $netMax = (float) max(0, round($tuition['max'] + $livingAnnual));

        return [
            'tuition' => $tuition,
            'rent_monthly' => $rentMonthly,
            'rent_is_national_fallback' => $rentIsFallback,
            'city' => $city,
            'living_annual' => $livingAnnual,
            'scholarship' => $scholarship,
            'net_min' => $netMin,
            'net_max' => $netMax,
            'notes' => $notes,
        ];
    }

    /**
     * Net first-year range with cautious defaults, as integers.
     *
     * @return array{min: int, max: int}
     */
    public static function quickNet(DegreeProgram $program): array
    {
        $e = self::estimate($program, 20000, 'shared');

        return ['min' => (int) $e['net_min'], 'max' => (int) $e['net_max']];
    }

    /** Single comparable figure for the net range (its midpoint). */
    public static function quickMidpoint(DegreeProgram $program): int
    {
        $net = self::quickNet($program);

        return (int) round(($net['min'] + $net['max']) / 2);
    }

    /** Compact "€X–Y" net range using cautious defaults, for list badges. */
    public static function quickRange(DegreeProgram $program): string
    {
        $net = self::quickNet($program);

        return '€'.number_format($net['min']).'–'.number_format($net['max']);
    }

    /**
     * @return Collection<int, RegionalScholarship>
     */
    private static function scholarshipsForProgram(DegreeProgram $program): Collection
    {
        $regionKey = ItalianRegions::canonicalize($program->university?->region);

        if ($regionKey === null) {
            return collect();
        }

        return RegionalScholarship::all()
            ->filter(fn (RegionalScholarship $s) => ItalianRegions::canonicalize($s->region) === $regionKey)
            ->values();
    }
}
