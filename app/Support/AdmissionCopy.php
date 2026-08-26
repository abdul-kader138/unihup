<?php

namespace App\Support;

/**
 * Curated, general-purpose explanatory text for Italy's admission system.
 * Deliberately generic — exact deadlines/fees change yearly and vary by
 * applicant nationality and income, so every consumer also carries an
 * official_admission_url/source_url a student should check before relying
 * on this. Shared by Database\Seeders\DegreeProgramSeeder (hand-curated
 * rows) and App\Services\Universities\Enrichers\AdmissionContentEnricher
 * (bulk MUR-imported rows), so the two data paths don't drift apart.
 */
final class AdmissionCopy
{
    public const OPEN_NOTE = "Open access (libero accesso): apply directly through the university's online enrollment portal. No entrance exam is required to be admitted, though some programs ask you to sit a non-selective self-assessment test (TOLC) first.";

    public const RESTRICTED_NOTE = 'Restricted access (numero programmato): admission is capped and decided by a national or university-run entrance exam. Seats go by rank order of exam score, for EU and non-EU applicants alike.';

    public const MASTER_NOTE = "Apply with a relevant bachelor's degree via the university's online portal. Programs typically assess your transcript and require proof of language proficiency (Italian or English, depending on the program); some also ask for a CV or short motivation letter.";

    public const TUITION_NOTE = 'Public Italian universities set tuition on a sliding scale tied to family income (ISEE) for most applicants, commonly from a few hundred to around €4,000/year; private universities and English-taught international tracks charge more. Confirm the exact figure for your situation on the official page.';

    public const WINDOW_OPEN = 'Applications typically open in July and close around September, for enrollment starting late September/October — check Universitaly and the university\'s own portal for exact dates each year.';

    public const WINDOW_RESTRICTED = "Entrance exam and application dates are published annually, usually between July and September for a fall start — check Universitaly and the university's page for the exact date, which changes every year.";

    public static function admissionNotes(string $degreeLevel, string $admissionType): string
    {
        if ($degreeLevel === 'master') {
            return self::MASTER_NOTE;
        }

        return $admissionType === 'restricted' ? self::RESTRICTED_NOTE : self::OPEN_NOTE;
    }

    public static function applicationWindowNote(string $admissionType): string
    {
        return $admissionType === 'restricted' ? self::WINDOW_RESTRICTED : self::WINDOW_OPEN;
    }
}
