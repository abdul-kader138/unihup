<?php

namespace App\Support;

/**
 * Curated guidance on two funding-related things international applicants
 * routinely miss — verified against CAF (tax-assistance center) guidance
 * pages, university international-office pages, and MAECI's own bando
 * pages while building this feature. National in scope (unlike
 * App\Models\RegionalScholarship, which is per-region), so shown
 * unconditionally rather than matched against the university's region.
 */
final class FinancialSupportCopy
{
    public const ISEE_PARIFICATO_NOTE = "If you're not resident in Italy (or your family isn't), a regular Italian ISEE can't be calculated for you — you need the \"ISEE Parificato\" instead, a foreign-income equivalent used to place you in the correct tuition bracket and determine scholarship eligibility. It's calculated by a CAF (tax-assistance center) affiliated with your university, from income/asset documents issued in your home country and officially translated into Italian. Miss the deadline (commonly around 30 September, but confirm with your university) and you're automatically placed in the maximum fee bracket for the year — there's usually no way to appeal that after the fact.";

    public const MAECI_SCHOLARSHIP_NOTE = "Separate from regional (DSU) scholarships: the Italian Ministry of Foreign Affairs (MAECI) runs its own annual scholarship programme for non-EU students and Italians resident abroad, covering university courses, PhD programmes, research projects, and Italian language/culture courses. It's competitive and country-quota-based (recent editions covered 144 countries), and applied for through your local Italian embassy/consulate or cultural institute, not through Universitaly or your university directly. Deadlines vary by year (recent editions closed in May for the following academic year) — check early, since the application is separate from your university admission and runs on its own timeline.";

    public const OFFICIAL_LINKS = [
        'MAECI scholarship calls (bandi)' => 'https://studyinitaly.esteri.it/ListaBandi',
        'CAF CISL — ISEE Parificato explainer' => 'https://www.cafcisl.it/it-schede-433-isee_universita',
    ];

    public const SOURCE_URL = 'https://studyinitaly.esteri.it/ListaBandi';
}
