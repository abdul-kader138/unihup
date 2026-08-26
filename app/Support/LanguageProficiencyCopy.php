<?php

namespace App\Support;

/**
 * Generic language-proficiency guidance shown alongside a degree program's
 * `language` field. No dataset records the exact test/score a given program
 * requires (MUR's open data doesn't carry it, and it varies by program and
 * even changes between admission cycles), so this is deliberately the same
 * "what's typically true" framing as App\Support\AdmissionCopy — not a
 * per-program fact, and labelled as such wherever it's shown.
 */
final class LanguageProficiencyCopy
{
    private const ENGLISH_NOTE = 'English-taught programs typically require proof of English proficiency: an IELTS score of 6.0-6.5, TOEFL iBT 80-90, or a Cambridge C1 certificate are commonly accepted (exact thresholds vary by university and program). Native speakers and applicants with a prior degree taught in English are often exempted — check the specific program page.';

    private const ITALIAN_NOTE = "Italian-taught programs typically require Italian proficiency around CEFR B1-B2, shown via a CILS, CELI, or PLIDA certificate, or the university's own placement test — many universities also run pre-enrollment Italian courses for international applicants who don't yet meet this. EU/international applicants should confirm the exact requirement with the university, as it's often waived or reduced for shorter exchange-style programs.";

    public static function forLanguage(string $language): string
    {
        return match ($language) {
            'English' => self::ENGLISH_NOTE,
            'Italian' => self::ITALIAN_NOTE,
            default => "Check the program's official page for its specific language-proficiency requirement.",
        };
    }
}
