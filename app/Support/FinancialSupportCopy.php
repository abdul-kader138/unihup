<?php

namespace App\Support;

/**
 * Curated guidance on funding-related things international applicants
 * routinely miss — verified against CAF (tax-assistance center) guidance
 * pages, university international-office pages (e.g. unipi.it's own MAECI
 * scholarship page, which quotes the bando's exact figures), and MAECI/IYT's
 * own official pages (esteri.it, investyourtalent.esteri.it) while building
 * this feature. National in scope (unlike App\Models\RegionalScholarship,
 * which is per-region), so shown unconditionally rather than matched
 * against the university's region — IYT_SCHOLARSHIP_NOTE is the one
 * exception, shown only for master's-level programs since it doesn't fund
 * bachelor's study.
 */
final class FinancialSupportCopy
{
    public const ISEE_PARIFICATO_NOTE = "If you're not resident in Italy (or your family isn't), a regular Italian ISEE can't be calculated for you — you need the \"ISEE Parificato\" instead, a foreign-income equivalent used to place you in the correct tuition bracket and determine scholarship eligibility. It's calculated by a CAF (tax-assistance center) affiliated with your university, from income/asset documents issued in your home country and officially translated into Italian. Miss the deadline (commonly around 30 September, but confirm with your university) and you're automatically placed in the maximum fee bracket for the year — there's usually no way to appeal that after the fact.";

    public const MAECI_SCHOLARSHIP_NOTE = "Separate from regional (DSU) scholarships: the Italian Ministry of Foreign Affairs (MAECI) runs its own annual scholarship programme for non-EU students and Italians resident abroad, covering master's degrees, AFAM (arts/music/dance) programmes, PhD programmes, research projects, and Italian language/culture courses. A recent edition paid €10,800 over 9 months (paid quarterly) for degree/PhD/research awards, or a shorter 3-month award for language/culture courses, plus a university tuition-fee exemption (you still owe the regional tax and admin fee, typically well under €200) — it does not cover health insurance or accommodation. It's competitive and country-quota-based, and applied for through the Study in Italy portal (studyinitaly.esteri.it), not through Universitaly or your university directly. Deadlines vary by year (recent editions closed around March for the following academic year) — check early, since the application is separate from your university admission and runs on its own timeline.";

    public const IYT_SCHOLARSHIP_NOTE = "\"Invest Your Talent in Italy\" (IYT) is a distinct MAECI-funded programme, only for master's/postgraduate courses in Engineering, Advanced Technologies, Architecture, Design, Economics or Management taught at participating Italian universities, and only open to applicants from a fixed list of eligible countries that changes by edition (recent editions included India, Brazil, China, Egypt, Mexico, Türkiye, Vietnam and others — confirm your country is still listed before applying). Alongside study funding, it requires a compulsory 3-4 month (about 480-hour), full-time internship at an Italian company after your coursework — arranged through your university's career service, not something you organize yourself. You apply after receiving your university admission letter, directly on the IYT portal, separately from both your university application and the general MAECI scholarship above.";

    public const IYT_LINK = 'https://investyourtalent.esteri.it/SitoIYT/EN/how-does-it-work';

    /**
     * Indicative annual tuition at a public Italian university by household
     * ISEE / ISEE Parificato band, used by App\Support\CostEstimator ONLY as
     * a fallback when a program has no structured tuition_min/max on file.
     * Deliberately wide ranges: the regional tax + stamp duty (~€140–360) is
     * always owed even in the "no-tax area", and the ceiling varies a lot by
     * university and faculty. Private universities are not modelled here.
     *
     * @var array<int, array{upto: ?int, label: string, min: int, max: int}>
     */
    public const ISEE_FEE_BANDS = [
        ['upto' => 24000, 'label' => 'No-tax area (fascia di esonero)', 'min' => 140, 'max' => 360],
        ['upto' => 40000, 'label' => 'Lower band', 'min' => 400, 'max' => 1200],
        ['upto' => 60000, 'label' => 'Middle band', 'min' => 1000, 'max' => 2400],
        ['upto' => 90000, 'label' => 'Upper band', 'min' => 2000, 'max' => 3600],
        ['upto' => null, 'label' => 'Top band / no ISEE filed', 'min' => 2800, 'max' => 4200],
    ];

    public const ISEE_FEE_BANDS_NOTE = 'Public-university tuition in Italy scales with your household ISEE (or ISEE Parificato for foreign income). These are indicative national ranges — the exact figures are set per university and faculty, and not filing an ISEE by the deadline places you in the top band automatically.';

    /**
     * @return array{upto: ?int, label: string, min: int, max: int}
     */
    public static function feeBandFor(float $iseeAmount): array
    {
        foreach (self::ISEE_FEE_BANDS as $band) {
            if ($band['upto'] === null || $iseeAmount <= $band['upto']) {
                return $band;
            }
        }

        return self::ISEE_FEE_BANDS[array_key_last(self::ISEE_FEE_BANDS)];
    }

    public const OFFICIAL_LINKS = [
        'MAECI scholarship calls (bandi)' => 'https://studyinitaly.esteri.it/ListaBandi',
        'CAF CISL — ISEE Parificato explainer' => 'https://www.cafcisl.it/it-schede-433-isee_universita',
    ];

    public const SOURCE_URL = 'https://studyinitaly.esteri.it/ListaBandi';
}
