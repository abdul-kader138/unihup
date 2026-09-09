<?php

namespace App\Support;

/**
 * Nationwide funding options that aren't in App\Models\RegionalScholarship
 * (which is per-region DSU). Curated, indicative — see App\Support\FinancialSupportCopy
 * for the full text these summarise.
 *
 * `applies_when`: always | master
 */
final class NationalScholarships
{
    /**
     * @var array<string, array{name: string, summary: string, url: string, applies_when: string}>
     */
    public const LIST = [
        'maeci' => [
            'name' => 'MAECI government scholarships',
            'summary' => 'Italian Ministry of Foreign Affairs scholarships for non-EU students and Italians resident abroad — a monthly stipend plus a tuition-fee exemption. Applied for on the Study in Italy portal, on its own timeline (recent editions closed around March).',
            'url' => 'https://studyinitaly.esteri.it/ListaBandi',
            'applies_when' => 'always',
        ],
        'iyt' => [
            'name' => 'Invest Your Talent in Italy',
            'summary' => 'MAECI-funded programme for Master\'s courses in Engineering, Advanced Technologies, Architecture, Design, Economics or Management, with a compulsory internship at an Italian company. Country-restricted; you apply after receiving your admission letter.',
            'url' => 'https://investyourtalent.esteri.it/SitoIYT/EN/how-does-it-work',
            'applies_when' => 'master',
        ],
        'isee_parificato' => [
            'name' => 'ISEE Parificato (tuition reduction)',
            'summary' => 'Not a scholarship you win, but the foreign-income equivalent of the ISEE — file it through a CAF affiliated with your university to be placed in a lower tuition-fee bracket. Miss the deadline (often ~30 September) and you pay the top rate.',
            'url' => 'https://www.cafcisl.it/it-schede-433-isee_universita',
            'applies_when' => 'always',
        ],
    ];

    /**
     * @return array<int, array{key: string, name: string, summary: string, url: string}>
     */
    public static function forStudent(bool $hasMasterProgram): array
    {
        $out = [];

        foreach (self::LIST as $key => $s) {
            if ($s['applies_when'] === 'master' && ! $hasMasterProgram) {
                continue;
            }

            $out[] = ['key' => $key] + $s;
        }

        return $out;
    }

    public static function name(string $key): string
    {
        return self::LIST[$key]['name'] ?? $key;
    }
}
