<?php

namespace App\Support;

use App\Models\DegreeProgram;

/**
 * A whole-journey money picture for one saved program: the one-off cost of
 * getting to Italy and settling in, plus the recurring yearly cost of
 * studying and living there, rolled up to a first-year total and a
 * full-course total. The yearly side reuses App\Support\CostEstimator
 * (tuition + living − scholarship); this class adds the relocation and
 * per-year extras it doesn't cover. Every figure is an indicative range —
 * the page says so loudly, same framing as CostEstimator.
 */
final class BudgetPlanner
{
    /**
     * One-off costs before / just after arrival, EUR ranges. `when`:
     * `always` or `non_eu`. A null min/max is computed (see plan()).
     *
     * @var array<string, array{label: string, min: ?int, max: ?int, when: string}>
     */
    public const ONE_OFF = [
        'recognition' => ['label' => 'Qualification recognition (CIMEA statement or Dichiarazione di Valore)', 'min' => 50, 'max' => 350, 'when' => 'non_eu'],
        'translation' => ['label' => 'Certified translation & legalisation of documents', 'min' => 60, 'max' => 250, 'when' => 'non_eu'],
        'visa' => ['label' => 'Type D student visa fee', 'min' => 116, 'max' => 116, 'when' => 'non_eu'],
        'permit' => ['label' => 'Permesso di soggiorno (permit kit, stamp, postal fee)', 'min' => 100, 'max' => 160, 'when' => 'non_eu'],
        'travel' => ['label' => 'Flights / travel to Italy', 'min' => 250, 'max' => 700, 'when' => 'always'],
        'deposit' => ['label' => 'Rent deposit + first month up front', 'min' => null, 'max' => null, 'when' => 'always'],
        'settling' => ['label' => 'Settling in (bedding, kitchen kit, SIM, transport pass)', 'min' => 200, 'max' => 500, 'when' => 'always'],
    ];

    /**
     * Recurring yearly costs on top of CostEstimator's tuition + living.
     *
     * @var array<string, array{label: string, min: int, max: int, when: string}>
     */
    public const YEARLY_EXTRA = [
        'regional_tax' => ['label' => 'Regional student tax (tassa regionale)', 'min' => 140, 'max' => 160, 'when' => 'always'],
        'health_cover' => ['label' => 'Health insurance / voluntary SSN registration', 'min' => 150, 'max' => 700, 'when' => 'non_eu'],
        'books' => ['label' => 'Books & study materials', 'min' => 150, 'max' => 400, 'when' => 'always'],
    ];

    /**
     * @param  array{isee?: float, housing?: string, is_eu_citizen?: bool, travel_estimate?: ?float}  $opts
     * @return array{
     *     one_off: array<int, array{key: string, label: string, min: float, max: float}>,
     *     one_off_total: array{min: float, max: float},
     *     yearly: array<int, array{key: string, label: string, min: float, max: float}>,
     *     yearly_net: array{min: float, max: float},
     *     scholarship_max: float,
     *     first_year: array{min: float, max: float},
     *     full_course: array{min: float, max: float},
     *     duration_years: int,
     *     notes: array<int, string>,
     * }
     */
    public static function plan(DegreeProgram $program, array $opts = []): array
    {
        $isee = (float) ($opts['isee'] ?? 20000);
        $housing = (string) ($opts['housing'] ?? 'shared');
        $isEu = (bool) ($opts['is_eu_citizen'] ?? false);
        $travelOverride = $opts['travel_estimate'] ?? null;

        $estimate = CostEstimator::estimate($program, $isee, $housing);
        $notes = $estimate['notes'];

        $applies = fn (string $when) => $when === 'always' || ($when === 'non_eu' && ! $isEu);

        // ── One-off ────────────────────────────────────────────────────────
        $rentMonthly = (float) $estimate['rent_monthly'];
        $oneOff = [];

        foreach (self::ONE_OFF as $key => $row) {
            if (! $applies($row['when'])) {
                continue;
            }

            if ($key === 'deposit') {
                if ($rentMonthly <= 0) {
                    continue;
                }
                $min = $max = round($rentMonthly * 2);
            } elseif ($key === 'travel' && $travelOverride !== null && $travelOverride > 0) {
                $min = $max = round((float) $travelOverride);
            } else {
                $min = (float) $row['min'];
                $max = (float) $row['max'];
            }

            $oneOff[] = ['key' => $key, 'label' => $row['label'], 'min' => $min, 'max' => $max];
        }

        $oneOffTotal = [
            'min' => (float) array_sum(array_column($oneOff, 'min')),
            'max' => (float) array_sum(array_column($oneOff, 'max')),
        ];

        // ── Yearly ─────────────────────────────────────────────────────────
        $yearly = [
            ['key' => 'tuition', 'label' => 'Tuition', 'min' => (float) $estimate['tuition']['min'], 'max' => (float) $estimate['tuition']['max']],
            ['key' => 'living', 'label' => 'Rent + living costs', 'min' => (float) $estimate['living_annual'], 'max' => (float) $estimate['living_annual']],
        ];

        foreach (self::YEARLY_EXTRA as $key => $row) {
            if (! $applies($row['when'])) {
                continue;
            }
            $yearly[] = ['key' => $key, 'label' => $row['label'], 'min' => (float) $row['min'], 'max' => (float) $row['max']];
        }

        $yearlyGrossMin = (float) array_sum(array_column($yearly, 'min'));
        $yearlyGrossMax = (float) array_sum(array_column($yearly, 'max'));
        $scholarshipMax = (float) $estimate['scholarship']['max'];

        $yearlyNet = [
            'min' => (float) max(0, round($yearlyGrossMin - $scholarshipMax)),
            'max' => (float) round($yearlyGrossMax),
        ];

        $duration = max(1, (int) ($program->duration_years ?? 2));

        return [
            'one_off' => $oneOff,
            'one_off_total' => $oneOffTotal,
            'yearly' => $yearly,
            'yearly_net' => $yearlyNet,
            'scholarship_max' => $scholarshipMax,
            'first_year' => [
                'min' => $oneOffTotal['min'] + $yearlyNet['min'],
                'max' => $oneOffTotal['max'] + $yearlyNet['max'],
            ],
            'full_course' => [
                'min' => $oneOffTotal['min'] + $yearlyNet['min'] * $duration,
                'max' => $oneOffTotal['max'] + $yearlyNet['max'] * $duration,
            ],
            'duration_years' => $duration,
            'notes' => $notes,
        ];
    }
}
