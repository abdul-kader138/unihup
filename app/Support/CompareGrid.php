<?php

namespace App\Support;

use App\Models\DegreeProgram;
use Illuminate\Support\Collection;

/**
 * Turns a set of shortlisted programs into the grouped comparison grid the
 * Compare page and its PDF export both render — field rows split into
 * sections, with the best value in each comparable row flagged and rows
 * where every column agrees marked so the UI can fold them away.
 */
final class CompareGrid
{
    /** Section order for the grouped grid. */
    public const GROUPS = ['Basics', 'Admission', 'Money', 'Standing'];

    /**
     * @param  Collection<int, DegreeProgram>  $programs
     * @param  bool  $onlyDifferences  drop rows where every column is equal
     * @return array{
     *     programs: array<int, array{id: int, name: string, university: string, logo: string}>,
     *     groups: array<int, array{label: string, rows: array<int, array{
     *         label: string, all_equal: bool,
     *         values: array<int, array{display: string, best: bool}>,
     *     }>}>,
     *     hidden_rows: int,
     * }
     */
    public static function build(Collection $programs, bool $onlyDifferences = false): array
    {
        $fields = self::fields();
        $groups = [];
        $hidden = 0;

        foreach (self::GROUPS as $groupLabel) {
            $rows = [];

            foreach ($fields as $field) {
                if ($field['group'] !== $groupLabel) {
                    continue;
                }

                $displays = $programs->map($field['display'])->map(fn ($v) => (string) $v)->all();
                $allEqual = count(array_unique($displays)) <= 1 && count($displays) > 1;

                if ($onlyDifferences && $allEqual) {
                    $hidden++;

                    continue;
                }

                $bestIndexes = isset($field['metric'])
                    ? self::bestIndexes($programs->map($field['metric'])->all(), $field['best'] ?? 'low')
                    : [];

                $rows[] = [
                    'label' => $field['label'],
                    'all_equal' => $allEqual,
                    'values' => array_map(fn ($display, $i) => [
                        'display' => $display,
                        'best' => in_array($i, $bestIndexes, true),
                    ], $displays, array_keys($displays)),
                ];
            }

            if ($rows !== []) {
                $groups[] = ['label' => $groupLabel, 'rows' => $rows];
            }
        }

        return [
            'programs' => $programs->map(fn (DegreeProgram $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'university' => $p->university->display_name,
                'logo' => $p->university->display_logo_url,
            ])->all(),
            'groups' => $groups,
            'hidden_rows' => $hidden,
        ];
    }

    /**
     * Which column indexes hold the winning value — only when at least two
     * columns carry a number and they are not all identical.
     *
     * @param  array<int, int|float|null>  $metrics
     * @return array<int, int>
     */
    private static function bestIndexes(array $metrics, string $direction): array
    {
        $present = array_filter($metrics, fn ($m) => $m !== null && $m !== '');

        if (count($present) < 2 || count(array_unique($present)) <= 1) {
            return [];
        }

        $target = $direction === 'high' ? max($present) : min($present);

        return array_keys(array_filter($metrics, fn ($m) => $m !== null && $m !== '' && $m == $target));
    }

    /**
     * @return array<int, array{
     *     label: string, group: string,
     *     display: callable(DegreeProgram): string,
     *     metric?: callable(DegreeProgram): (int|float|null),
     *     best?: 'low'|'high',
     * }>
     */
    private static function fields(): array
    {
        return [
            ['label' => 'City', 'group' => 'Basics',
                'display' => fn (DegreeProgram $p) => $p->university->city],
            ['label' => 'Subject', 'group' => 'Basics',
                'display' => fn (DegreeProgram $p) => $p->subject->display_name],
            ['label' => 'Level', 'group' => 'Basics',
                'display' => fn (DegreeProgram $p) => DegreeProgram::DEGREE_LEVELS[$p->degree_level] ?? $p->degree_level],
            ['label' => 'Language', 'group' => 'Basics',
                'display' => fn (DegreeProgram $p) => $p->language],
            ['label' => 'Duration', 'group' => 'Basics', 'best' => 'low',
                'display' => fn (DegreeProgram $p) => $p->duration_years.' '.str('year')->plural($p->duration_years),
                'metric' => fn (DegreeProgram $p) => $p->duration_years],

            ['label' => 'Admission', 'group' => 'Admission',
                'display' => fn (DegreeProgram $p) => DegreeProgram::ADMISSION_TYPES[$p->admission_type] ?? $p->admission_type],
            ['label' => 'Application window', 'group' => 'Admission',
                'display' => fn (DegreeProgram $p) => $p->application_window_note ?: '—'],
            ['label' => 'Official page', 'group' => 'Admission',
                'display' => fn (DegreeProgram $p) => $p->official_admission_url ?: '—'],

            ['label' => 'Tuition', 'group' => 'Money', 'best' => 'low',
                'display' => fn (DegreeProgram $p) => $p->tuition_note ?: '—',
                'metric' => fn (DegreeProgram $p) => $p->tuition_min ?? $p->tuition_max],
            ['label' => 'Est. yearly cost*', 'group' => 'Money', 'best' => 'low',
                'display' => fn (DegreeProgram $p) => CostEstimator::quickRange($p),
                'metric' => fn (DegreeProgram $p) => CostEstimator::quickMidpoint($p)],

            ['label' => 'CENSIS ranking', 'group' => 'Standing', 'best' => 'low',
                'display' => fn (DegreeProgram $p) => $p->university->rankingSummary() ?? '—',
                'metric' => fn (DegreeProgram $p) => $p->university->latest_ranking_position],
        ];
    }
}
